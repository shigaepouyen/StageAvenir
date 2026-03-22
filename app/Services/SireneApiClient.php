<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class SireneApiClient
{
    public function __construct(private array $config)
    {
    }

    public function search(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $payload = $this->request('/search', [
            'q' => $query,
            'per_page' => '10',
        ]);

        $results = $payload['results'] ?? [];

        if (!is_array($results)) {
            throw new RuntimeException("Reponse Sirene invalide.");
        }

        return $this->mapResults($results, $query);
    }

    public function findBySiret(string $siret): ?array
    {
        $matches = $this->search($siret);

        foreach ($matches as $match) {
            if (($match['siret'] ?? '') === $siret) {
                return $match;
            }
        }

        return null;
    }

    private function request(string $path, array $query): array
    {
        $url = $this->config['base_url'] . $path . '?' . http_build_query($query);
        $headers = [
            'Accept: application/json',
            'User-Agent: ' . $this->config['user_agent'],
        ];

        if (function_exists('curl_init')) {
            $response = $this->requestWithCurl($url, $headers);
        } else {
            $response = $this->requestWithStream($url, $headers);
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            throw new RuntimeException("Impossible de decoder la reponse Sirene.");
        }

        if (isset($decoded['erreur']) && is_string($decoded['erreur'])) {
            throw new RuntimeException($decoded['erreur']);
        }

        return $decoded;
    }

    private function requestWithCurl(string $url, array $headers): string
    {
        $handle = curl_init($url);

        if ($handle === false) {
            throw new RuntimeException("Impossible d'initialiser l'appel Sirene.");
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => max(1, (int) $this->config['timeout_seconds']),
            CURLOPT_CONNECTTIMEOUT => max(1, (int) $this->config['timeout_seconds']),
            CURLOPT_FAILONERROR => false,
        ]);

        $response = curl_exec($handle);

        if ($response === false) {
            $message = curl_error($handle);
            curl_close($handle);
            throw new RuntimeException("Erreur d'appel Sirene : " . $message);
        }

        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if ($statusCode >= 400) {
            throw new RuntimeException("L'API Sirene a retourne le code HTTP " . $statusCode . '.');
        }

        return $response;
    }

    private function requestWithStream(string $url, array $headers): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => max(1, (int) $this->config['timeout_seconds']),
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new RuntimeException("Erreur d'appel Sirene.");
        }

        $statusCode = 200;

        foreach ($http_response_header ?? [] as $headerLine) {
            if (preg_match('#HTTP/\S+\s+(\d{3})#', $headerLine, $matches)) {
                $statusCode = (int) $matches[1];
                break;
            }
        }

        if ($statusCode >= 400) {
            throw new RuntimeException("L'API Sirene a retourne le code HTTP " . $statusCode . '.');
        }

        return $response;
    }

    private function mapResults(array $results, string $query): array
    {
        $isExactSiretSearch = preg_match('/^\d{14}$/', $query) == 1;
        $mapped = [];

        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            $establishments = $this->extractEstablishments($result, $isExactSiretSearch, $query);

            foreach ($establishments as $establishment) {
                $candidate = $this->mapCandidate($result, $establishment);

                if (($candidate['siret'] ?? '') === '') {
                    continue;
                }

                $mapped[$candidate['siret']] = $candidate;
            }
        }

        return array_values($mapped);
    }

    private function extractEstablishments(array $result, bool $isExactSiretSearch, string $query): array
    {
        $establishments = [];

        if ($isExactSiretSearch) {
            foreach ([$result['siege'] ?? null, ...($result['matching_etablissements'] ?? [])] as $establishment) {
                if (is_array($establishment) && (($establishment['siret'] ?? '') === $query)) {
                    $establishments[] = $establishment;
                }
            }

            return $establishments;
        }

        if (isset($result['siege']) && is_array($result['siege'])) {
            $establishments[] = $result['siege'];
        }

        if ($establishments === [] && isset($result['matching_etablissements']) && is_array($result['matching_etablissements'])) {
            foreach ($result['matching_etablissements'] as $establishment) {
                if (is_array($establishment)) {
                    $establishments[] = $establishment;
                }
            }
        }

        return $establishments;
    }

    private function mapCandidate(array $result, array $establishment): array
    {
        [$lat, $lng] = $this->extractCoordinates($establishment);

        return [
            'siret' => (string) ($establishment['siret'] ?? ''),
            'name' => (string) ($result['nom_raison_sociale'] ?? $result['nom_complet'] ?? $establishment['nom_commercial'] ?? ''),
            'naf_code' => (string) ($establishment['activite_principale'] ?? $result['activite_principale'] ?? ''),
            'address' => $this->buildAddress($establishment),
            'lat' => $lat,
            'lng' => $lng,
        ];
    }

    private function buildAddress(array $establishment): string
    {
        $line1 = trim(implode(' ', array_filter([
            trim((string) ($establishment['numero_voie'] ?? '')),
            trim((string) ($establishment['indice_repetition'] ?? '')),
            trim((string) ($establishment['type_voie'] ?? '')),
            trim((string) ($establishment['libelle_voie'] ?? '')),
        ])));

        $line2 = trim(implode(' ', array_filter([
            trim((string) ($establishment['code_postal'] ?? '')),
            trim((string) ($establishment['libelle_commune'] ?? '')),
        ])));

        return trim(implode(', ', array_filter([$line1, $line2])));
    }

    private function extractCoordinates(array $establishment): array
    {
        $lat = $this->normalizeDecimal($establishment['latitude'] ?? null);
        $lng = $this->normalizeDecimal($establishment['longitude'] ?? null);

        if ($lat !== null && $lng !== null) {
            return [$lat, $lng];
        }

        $coordinates = trim((string) ($establishment['coordonnees'] ?? ''));

        if ($coordinates !== '' && str_contains($coordinates, ',')) {
            [$rawLat, $rawLng] = array_map('trim', explode(',', $coordinates, 2));
            return [
                $this->normalizeDecimal($rawLat),
                $this->normalizeDecimal($rawLng),
            ];
        }

        return [null, null];
    }

    private function normalizeDecimal(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        if ($stringValue === '' || !is_numeric($stringValue)) {
            return null;
        }

        return $stringValue;
    }
}
