<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function line(string $label, string $value): string
{
    return str_pad($label, 30, ' ', STR_PAD_RIGHT) . ': ' . $value;
}

function maskSecret(string $value): string
{
    if ($value === '') {
        return '(vide)';
    }

    $length = strlen($value);

    if ($length <= 4) {
        return str_repeat('*', $length);
    }

    return substr($value, 0, 2) . str_repeat('*', max(0, $length - 4)) . substr($value, -2);
}

function maskEmail(string $value): string
{
    if ($value === '') {
        return '(vide)';
    }

    if (!str_contains($value, '@')) {
        return maskSecret($value);
    }

    [$local, $domain] = explode('@', $value, 2);
    $localLength = strlen($local);

    if ($localLength <= 2) {
        $maskedLocal = str_repeat('*', $localLength);
    } else {
        $maskedLocal = substr($local, 0, 2) . str_repeat('*', max(0, $localLength - 2));
    }

    return $maskedLocal . '@' . $domain;
}

function inspectEnvFile(string $path): array
{
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        return [];
    }

    $entries = [];

    foreach ($lines as $index => $rawLine) {
        $trimmed = trim($rawLine);

        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $trimmed, 2);
        $entries[] = [
            'line' => $index + 1,
            'name' => trim($name),
            'value' => trim($value),
        ];
    }

    return $entries;
}

function stringifyEnvKey(string $value): string
{
    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return $encoded === false ? $value : $encoded;
}

function smtpEncryption(array $config): string
{
    $configured = strtolower(trim((string) ($config['smtp_encryption'] ?? '')));

    if (in_array($configured, ['ssl', 'tls'], true)) {
        return $configured;
    }

    $port = max(0, (int) ($config['smtp_port'] ?? 0));

    return $port === 465 ? 'ssl' : ($port === 587 ? 'tls' : '');
}

function smtpRemote(array $config): string
{
    $host = trim((string) ($config['smtp_host'] ?? ''));
    $port = max(0, (int) ($config['smtp_port'] ?? 0));
    $encryption = smtpEncryption($config);
    $transport = $encryption === 'ssl' ? 'ssl://' : 'tcp://';

    return $transport . $host . ':' . $port;
}

function readResponse($socket): array
{
    $lines = [];
    $code = null;

    while (($line = fgets($socket)) !== false) {
        $lines[] = rtrim($line, "\r\n");

        if (preg_match('/^(\d{3})([\s-])/', $line, $matches) === 1) {
            $code = (int) $matches[1];

            if ($matches[2] === ' ') {
                break;
            }
        }
    }

    if ($lines === []) {
        throw new RuntimeException('Aucune reponse recue du serveur SMTP.');
    }

    return [$code ?? 0, implode(' | ', $lines)];
}

function sendCommand($socket, string $command): void
{
    $payload = $command . "\r\n";
    $offset = 0;
    $length = strlen($payload);

    while ($offset < $length) {
        $written = fwrite($socket, substr($payload, $offset));

        if ($written === false || $written === 0) {
            throw new RuntimeException('Ecriture SMTP incomplete.');
        }

        $offset += $written;
    }
}

function sendRaw($socket, string $payload): void
{
    $offset = 0;
    $length = strlen($payload);

    while ($offset < $length) {
        $written = fwrite($socket, substr($payload, $offset));

        if ($written === false || $written === 0) {
            throw new RuntimeException('Ecriture SMTP incomplete.');
        }

        $offset += $written;
    }
}

function assertResponseCode($socket, array $expectedCodes): string
{
    [$code, $message] = readResponse($socket);

    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('SMTP a repondu ' . $code . ' : ' . $message);
    }

    return $message;
}

function helloHost(): string
{
    $host = gethostname();

    return is_string($host) && $host !== '' ? $host : 'localhost';
}

function buildPlainTextHeaders(array $mailConfig, string $recipient): array
{
    $fromEmail = trim((string) ($mailConfig['smtp_from_email'] ?? $mailConfig['from_email'] ?? ''));
    $fromName = trim((string) ($mailConfig['smtp_from_name'] ?? $mailConfig['from_name'] ?? ''));

    return [
        'Date: ' . date(DATE_RFC2822),
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (substr(strrchr($fromEmail, '@') ?: '', 1) ?: 'localhost') . '>',
        'From: ' . ($fromName === '' ? '<' . $fromEmail . '>' : '=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromEmail . '>'),
        'To: <' . $recipient . '>',
        'Subject: Test SMTP Avenir Pro',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ];
}

function dotStuff(string $message): string
{
    $message = str_replace(["\r\n", "\r"], "\n", $message);
    $lines = explode("\n", $message);

    foreach ($lines as &$line) {
        if (str_starts_with($line, '.')) {
            $line = '.' . $line;
        }
    }
    unset($line);

    return implode("\r\n", $lines);
}

$root = __DIR__;
$autoloadPath = $root . '/vendor/autoload.php';
$configPath = $root . '/config/auth.php';
$envPath = $root . '/.env.local';

$report = [];
$testMailResult = null;
$testMailRequested = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    && ($_POST['action'] ?? '') === 'send_test_mail';

$report[] = 'Avenir Pro - Diagnostic Mail SMTP';
$report[] = '========================================';
$report[] = line('Date', date('Y-m-d H:i:s'));
$report[] = line('PHP version', PHP_VERSION);
$report[] = line('PHP SAPI', PHP_SAPI);
$report[] = line('Script', __FILE__);
$report[] = line('Document root', (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
$report[] = line('autoload.php present', is_file($autoloadPath) ? 'OK' : 'ECHEC');
$report[] = line('.env.local present', is_file($envPath) ? 'OK' : 'ECHEC');
$report[] = line('.env.local lisible', is_readable($envPath) ? 'OK' : 'ECHEC');
$report[] = line('openssl extension', extension_loaded('openssl') ? 'OK' : 'ECHEC');
$report[] = line('stream_socket_client', function_exists('stream_socket_client') ? 'OK' : 'ECHEC');
$report[] = '';
$report[] = '[1] Chargement configuration';

if (!is_file($autoloadPath)) {
    $report[] = line('Conclusion', 'vendor/autoload.php manque.');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Mail diagnostic</title></head><body><pre>' . h(implode(PHP_EOL, $report)) . '</pre></body></html>';
    exit;
}

require $autoloadPath;

if (!is_file($configPath)) {
    $report[] = line('config/auth.php', 'ECHEC');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Mail diagnostic</title></head><body><pre>' . h(implode(PHP_EOL, $report)) . '</pre></body></html>';
    exit;
}

$authConfig = require $configPath;
$mailConfig = is_array($authConfig['mail'] ?? null) ? $authConfig['mail'] : [];
$smtpHost = trim((string) ($mailConfig['smtp_host'] ?? ''));
$smtpPort = max(0, (int) ($mailConfig['smtp_port'] ?? 0));
$smtpUser = trim((string) ($mailConfig['smtp_username'] ?? ''));
$smtpPassword = (string) ($mailConfig['smtp_password'] ?? '');
$smtpFromEmail = trim((string) ($mailConfig['smtp_from_email'] ?? $mailConfig['from_email'] ?? ''));
$smtpFromName = trim((string) ($mailConfig['smtp_from_name'] ?? $mailConfig['from_name'] ?? ''));
$smtpTimeout = max(1, (int) ($mailConfig['smtp_timeout_seconds'] ?? 15));
$smtpMode = smtpEncryption($mailConfig);
$envEntries = inspectEnvFile($envPath);
$smtpEnvEntries = array_values(array_filter(
    $envEntries,
    static fn (array $entry): bool => str_contains(strtoupper($entry['name']), 'SMTP')
));

$report[] = line('APP_URL', (string) ($authConfig['app_url'] ?? '(non defini)'));
$report[] = line('SMTP_HOST', $smtpHost !== '' ? $smtpHost : '(vide)');
$report[] = line('SMTP_PORT', (string) $smtpPort);
$report[] = line('SMTP_ENCRYPTION', $smtpMode !== '' ? $smtpMode : '(aucun)');
$report[] = line('SMTP_USERNAME', maskEmail($smtpUser));
$report[] = line('SMTP_PASSWORD', maskSecret($smtpPassword));
$report[] = line('SMTP_FROM_EMAIL', $smtpFromEmail !== '' ? $smtpFromEmail : '(vide)');
$report[] = line('SMTP_FROM_NAME', $smtpFromName !== '' ? $smtpFromName : '(vide)');
$report[] = line('SMTP_TIMEOUT_SECONDS', (string) $smtpTimeout);
$report[] = line(
    'SMTP configuration',
    $smtpHost !== '' && $smtpPort > 0 && $smtpUser !== '' && $smtpPassword !== '' ? 'OK' : 'ECHEC'
);
$report[] = '';
$report[] = '[2] Clés SMTP vues dans .env.local';

if ($smtpEnvEntries === []) {
    $report[] = line('Clés SMTP detectees', '(aucune)');
} else {
    foreach ($smtpEnvEntries as $entry) {
        $displayValue = $entry['name'] === 'SMTP_PASSWORD'
            ? maskSecret($entry['value'])
            : ($entry['name'] === 'SMTP_USERNAME' || $entry['name'] === 'SMTP_FROM_EMAIL'
                ? maskEmail($entry['value'])
                : ($entry['value'] !== '' ? $entry['value'] : '(vide)'));

        $report[] = line(
            'Ligne ' . (string) $entry['line'],
            stringifyEnvKey($entry['name']) . ' = ' . $displayValue
        );
    }
}

$report[] = '';
$report[] = '[3] Resolution DNS';

if ($smtpHost === '' || $smtpPort <= 0 || $smtpUser === '' || $smtpPassword === '') {
    $report[] = line('Conclusion', 'La configuration SMTP est incomplete.');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Mail diagnostic</title></head><body><pre>' . h(implode(PHP_EOL, $report)) . '</pre></body></html>';
    exit;
}

$dnsTargets = [];

if (function_exists('dns_get_record')) {
    $records = @dns_get_record($smtpHost, DNS_A + DNS_AAAA);

    if (is_array($records)) {
        foreach ($records as $record) {
            if (isset($record['ip']) && is_string($record['ip']) && $record['ip'] !== '') {
                $dnsTargets[] = $record['ip'];
            }

            if (isset($record['ipv6']) && is_string($record['ipv6']) && $record['ipv6'] !== '') {
                $dnsTargets[] = $record['ipv6'];
            }
        }
    }
}

if ($dnsTargets === []) {
    $resolved = gethostbyname($smtpHost);

    if ($resolved !== $smtpHost) {
        $dnsTargets[] = $resolved;
    }
}

$report[] = line('DNS lookup', $dnsTargets === [] ? 'ECHEC' : 'OK');
$report[] = line('IPs resolues', $dnsTargets === [] ? '(aucune)' : implode(', ', array_unique($dnsTargets)));
$report[] = '';
$report[] = '[4] Connexion et authentification SMTP';

$socket = null;
$smtpReady = false;

try {
    $socket = @stream_socket_client(smtpRemote($mailConfig), $errorCode, $errorMessage, $smtpTimeout);

    if (!is_resource($socket)) {
        throw new RuntimeException('Connexion SMTP impossible : ' . $errorMessage . ' (' . $errorCode . ')');
    }

    stream_set_timeout($socket, $smtpTimeout);

    $report[] = line('Socket', 'OK');
    $report[] = line('Remote', smtpRemote($mailConfig));
    $report[] = line('Banniere', assertResponseCode($socket, [220]));

    sendCommand($socket, 'EHLO ' . helloHost());
    $report[] = line('EHLO', assertResponseCode($socket, [250]));

    if ($smtpMode === 'tls') {
        sendCommand($socket, 'STARTTLS');
        $report[] = line('STARTTLS', assertResponseCode($socket, [220]));

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('Impossible d activer STARTTLS.');
        }

        sendCommand($socket, 'EHLO ' . helloHost());
        $report[] = line('EHLO apres TLS', assertResponseCode($socket, [250]));
    }

    sendCommand($socket, 'AUTH LOGIN');
    $report[] = line('AUTH LOGIN', assertResponseCode($socket, [334]));

    sendCommand($socket, base64_encode($smtpUser));
    $report[] = line('SMTP username', assertResponseCode($socket, [334]));

    sendCommand($socket, base64_encode($smtpPassword));
    $report[] = line('SMTP password', assertResponseCode($socket, [235]));

    $smtpReady = true;
    $report[] = line('Authentification', 'OK');

    if ($testMailRequested) {
        $recipient = filter_var($smtpFromEmail, FILTER_VALIDATE_EMAIL) !== false ? $smtpFromEmail : $smtpUser;

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Aucune adresse de test valide n est configuree.');
        }

        sendCommand($socket, 'MAIL FROM:<' . $smtpFromEmail . '>');
        $mailFromResponse = assertResponseCode($socket, [250]);

        sendCommand($socket, 'RCPT TO:<' . $recipient . '>');
        $rcptResponse = assertResponseCode($socket, [250, 251]);

        sendCommand($socket, 'DATA');
        $dataResponse = assertResponseCode($socket, [354]);

        $body = "Diagnostic SMTP Avenir Pro\r\n\r\nSi vous recevez ce message, l'envoi SMTP fonctionne depuis l'hebergement OVH.";
        $headers = buildPlainTextHeaders($mailConfig, $recipient);
        $message = dotStuff(implode("\r\n", $headers) . "\r\n\r\n" . rtrim(chunk_split(base64_encode($body), 76, "\r\n")));
        sendRaw($socket, $message . "\r\n.\r\n");
        $deliveryResponse = assertResponseCode($socket, [250]);

        $testMailResult = [
            'ok' => true,
            'recipient' => $recipient,
            'mail_from' => $mailFromResponse,
            'rcpt_to' => $rcptResponse,
            'data' => $dataResponse,
            'delivery' => $deliveryResponse,
        ];
    }

    sendCommand($socket, 'QUIT');
} catch (Throwable $exception) {
    $report[] = line('Diagnostic SMTP', 'ECHEC');
    $report[] = line('Exception', get_class($exception));
$report[] = line('Message', $exception->getMessage());
} finally {
    if (is_resource($socket)) {
        fclose($socket);
    }
}

$report[] = '';
$report[] = '[5] Conclusion rapide';
$report[] = line('Diagnostic', $smtpReady ? 'Le handshake SMTP et l authentification fonctionnent.' : 'Le probleme est sur le canal SMTP.');
$report[] = line('Conseil', 'Supprimez mail_diagnostic.php apres usage.');

if ($testMailResult !== null) {
    $report[] = '';
    $report[] = '[6] Envoi test';
    $report[] = line('Recipient', $testMailResult['recipient']);
    $report[] = line('MAIL FROM', $testMailResult['mail_from']);
    $report[] = line('RCPT TO', $testMailResult['rcpt_to']);
    $report[] = line('DATA', $testMailResult['data']);
    $report[] = line('Livraison', $testMailResult['delivery']);
}

$page = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Mail diagnostic</title><style>body{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,monospace;background:#f4f1ea;color:#1f2933;margin:0;padding:24px}main{max-width:1080px;margin:0 auto}pre{white-space:pre-wrap;word-break:break-word;background:#fff;border:1px solid #d7d0c3;border-radius:12px;padding:20px;line-height:1.45}form{margin-top:16px;background:#fff;border:1px solid #d7d0c3;border-radius:12px;padding:20px}button{appearance:none;border:0;background:#0b6bcb;color:#fff;padding:12px 16px;border-radius:10px;font:inherit;cursor:pointer}p{max-width:80ch;line-height:1.5}</style></head><body><main>';
$page .= '<pre>' . h(implode(PHP_EOL, $report)) . '</pre>';
$page .= '<p>Ce script teste le canal SMTP configure dans <code>.env.local</code>. Le bouton ci-dessous envoie un mail de test uniquement vers l adresse expeditrice configuree, pour eviter d ouvrir un relais public.</p>';
$page .= '<form method="post" action=""><input type="hidden" name="action" value="send_test_mail"><button type="submit">Envoyer un mail de test vers l adresse expeditrice</button></form>';
$page .= '</main></body></html>';

echo $page;
