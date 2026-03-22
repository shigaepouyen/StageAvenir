<?php

declare(strict_types=1);

use App\Support\Env;

$rootDir = dirname(__DIR__, 2);
$autoloadPath = $rootDir . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    fwrite(STDERR, "Dependances manquantes. Lancez 'composer install'.\n");
    exit(1);
}

require $autoloadPath;

Env::load($rootDir . '/.env.local');

/** @var \PDO $pdo */
$pdo = require $rootDir . '/config/database.php';
$config = require $rootDir . '/config/onisep.php';

try {
    $metadata = fetchJson(resolveDatasetApiUrl($config), $config);
    $resourceUrl = findJsonResourceUrl($metadata);

    if ($resourceUrl === null) {
        throw new RuntimeException("Aucune ressource JSON ONISEP n'a ete trouvee.");
    }

    $payload = fetchJson($resourceUrl, $config);
    $entries = normalizeJobEntries($payload);

    if ($entries === []) {
        throw new RuntimeException("Le fichier JSON ONISEP ne contient aucune entree exploitable.");
    }

    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        'INSERT INTO ref_jobs (id_onisep, libelle, domaine)
         VALUES (:id_onisep, :libelle, :domaine)
         ON DUPLICATE KEY UPDATE
            libelle = VALUES(libelle),
            domaine = VALUES(domaine)'
    );

    $importedCount = 0;

    foreach ($entries as $entry) {
        $job = extractJob($entry);

        if ($job === null) {
            continue;
        }

        $statement->execute([
            'id_onisep' => $job['id_onisep'],
            'libelle' => $job['libelle'],
            'domaine' => $job['domaine'],
        ]);

        $importedCount++;
    }

    $pdo->commit();

    if ($importedCount === 0) {
        throw new RuntimeException("Aucune ligne ONISEP n'a pu etre importee apres analyse du JSON.");
    }

    fwrite(STDOUT, sprintf("Import ONISEP termine : %d ligne(s) inseree(s) ou mise(s) a jour.\n", $importedCount));
    exit(0);
} catch (\Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Echec import ONISEP : " . $exception->getMessage() . "\n");
    exit(1);
}

function resolveDatasetApiUrl(array $config): string
{
    $url = trim((string) ($config['dataset_api_url'] ?? ''));

    if ($url === '') {
        throw new RuntimeException("La configuration ONISEP_DATASET_API_URL est vide.");
    }

    return $url;
}

function fetchJson(string $url, array $config): array
{
    $headers = [
        'Accept: application/json',
        'User-Agent: ' . $config['user_agent'],
    ];

    if (function_exists('curl_init')) {
        $body = fetchWithCurl($url, $headers, (int) $config['timeout_seconds']);
    } else {
        $body = fetchWithStream($url, $headers, (int) $config['timeout_seconds']);
    }

    $decoded = json_decode($body, true);

    if (!is_array($decoded)) {
        throw new RuntimeException("Reponse JSON invalide depuis " . $url);
    }

    return $decoded;
}

function fetchWithCurl(string $url, array $headers, int $timeoutSeconds): string
{
    $handle = curl_init($url);

    if ($handle === false) {
        throw new RuntimeException("Impossible d'initialiser la connexion HTTP.");
    }

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => max(1, $timeoutSeconds),
        CURLOPT_CONNECTTIMEOUT => max(1, $timeoutSeconds),
        CURLOPT_FAILONERROR => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
    ]);

    $response = curl_exec($handle);

    if ($response === false) {
        $message = curl_error($handle);
        curl_close($handle);
        throw new RuntimeException("Erreur reseau ONISEP : " . $message);
    }

    $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);

    if ($statusCode >= 400) {
        throw new RuntimeException("Le serveur ONISEP/Data.gouv a retourne HTTP " . $statusCode . '.');
    }

    return $response;
}

function fetchWithStream(string $url, array $headers, int $timeoutSeconds): string
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => max(1, $timeoutSeconds),
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        throw new RuntimeException("Erreur reseau ONISEP.");
    }

    $statusCode = 200;

    foreach ($http_response_header ?? [] as $headerLine) {
        if (preg_match('#HTTP/\S+\s+(\d{3})#', $headerLine, $matches)) {
            $statusCode = (int) $matches[1];
            break;
        }
    }

    if ($statusCode >= 400) {
        throw new RuntimeException("Le serveur ONISEP/Data.gouv a retourne HTTP " . $statusCode . '.');
    }

    return $response;
}

function findJsonResourceUrl(array $metadata): ?string
{
    $resources = $metadata['resources'] ?? null;

    if (!is_array($resources)) {
        return null;
    }

    foreach ($resources as $resource) {
        if (!is_array($resource)) {
            continue;
        }

        $format = strtolower(trim((string) ($resource['format'] ?? '')));

        if ($format !== 'json') {
            continue;
        }

        $url = trim((string) ($resource['url'] ?? ''));

        if ($url !== '') {
            return $url;
        }
    }

    return null;
}

function normalizeJobEntries(array $payload): array
{
    if (array_is_list($payload)) {
        return $payload;
    }

    foreach (['results', 'data', 'items', 'jobs', 'metiers'] as $key) {
        if (isset($payload[$key]) && is_array($payload[$key])) {
            return $payload[$key];
        }
    }

    return [];
}

function extractJob(array $entry): ?array
{
    $id = firstNonEmptyString($entry, [
        'id_onisep',
        'id',
        'onisep_id',
        'identifiant',
        'identifiant_metier',
    ]);

    if ($id === null) {
        $url = firstNonEmptyString($entry, [
            'url',
            'url_onisep',
            'lien',
            'lien_onisep',
            'url_fiche_metier',
            'url_redirection',
        ]);

        if ($url !== null) {
            $id = extractIdFromUrl($url);
        }
    }

    $libelle = firstNonEmptyString($entry, [
        'libelle',
        'libellé',
        'libelle_metier',
        'libellé métier',
        'metier',
        'nom',
        'nom_metier',
    ]);

    $domaine = firstNonEmptyString($entry, [
        'domaine',
        'domaines',
        'domaine_libelle',
        'libelle_domaine',
        'domaine_onisep',
    ]);

    if ($domaine === null) {
        $domaine = normalizeDomaine($entry['domaines'] ?? null);
    }

    if ($id === null || $libelle === null) {
        return null;
    }

    return [
        'id_onisep' => $id,
        'libelle' => $libelle,
        'domaine' => $domaine,
    ];
}

function firstNonEmptyString(array $entry, array $keys): ?string
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $entry)) {
            continue;
        }

        $value = $entry[$key];

        if (is_string($value)) {
            $value = trim($value);

            if ($value !== '') {
                return $value;
            }
        }
    }

    return null;
}

function extractIdFromUrl(string $url): ?string
{
    if (preg_match('#/metier/([^/?#]+)#', $url, $matches) === 1) {
        return trim($matches[1]);
    }

    if (preg_match('#/([0-9A-Za-z_-]+)$#', trim($url, '/'), $matches) === 1) {
        return trim($matches[1]);
    }

    return null;
}

function normalizeDomaine(mixed $value): ?string
{
    if (is_string($value)) {
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    if (!is_array($value)) {
        return null;
    }

    $labels = [];

    foreach ($value as $item) {
        if (is_string($item) && trim($item) !== '') {
            $labels[] = trim($item);
            continue;
        }

        if (!is_array($item)) {
            continue;
        }

        foreach (['libelle', 'label', 'domaine', 'nom'] as $key) {
            if (isset($item[$key]) && is_string($item[$key]) && trim($item[$key]) !== '') {
                $labels[] = trim($item[$key]);
                break;
            }
        }
    }

    $labels = array_values(array_unique($labels));

    return $labels === [] ? null : implode(' | ', $labels);
}
