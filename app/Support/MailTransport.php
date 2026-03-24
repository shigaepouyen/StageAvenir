<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class MailTransport
{
    public function __construct(private array $config)
    {
    }

    public function sendPlainText(string $to, string $subject, string $body, ?string $replyTo = null): bool
    {
        $recipient = $this->sanitizeEmail($to);
        $replyTo = $replyTo === null ? null : $this->sanitizeEmail($replyTo);
        $fromEmail = $this->resolveFromEmail();
        $fromName = $this->resolveFromName();

        if ($recipient === null || $fromEmail === null) {
            error_log('[AvenirPro][Mail] Parametres email invalides.');
            return false;
        }

        [$encodedSubject, $headers, $encodedBody] = $this->buildMessageParts(
            $recipient,
            $subject,
            $body,
            $fromEmail,
            $fromName,
            $replyTo
        );

        try {
            if ($this->isSmtpConfigured()) {
                return $this->sendWithSmtp($recipient, $fromEmail, $headers, $encodedBody);
            }

            $mailHeaders = array_values(array_filter(
                $headers,
                static fn (string $header): bool => !str_starts_with($header, 'Subject: ')
            ));

            return mail($recipient, $encodedSubject, $encodedBody, implode("\r\n", $mailHeaders));
        } catch (\Throwable $exception) {
            error_log('[AvenirPro][Mail] ' . $exception->getMessage());
            return false;
        }
    }

    private function isSmtpConfigured(): bool
    {
        return $this->smtpHost() !== ''
            && $this->smtpPort() > 0
            && $this->smtpUsername() !== ''
            && $this->smtpPassword() !== '';
    }

    private function sendWithSmtp(string $to, string $fromEmail, array $headers, string $encodedBody): bool
    {
        $socket = $this->openSocket();

        try {
            $this->assertResponseCode($socket, [220]);
            $this->sendCommand($socket, 'EHLO ' . $this->helloHost());
            $this->assertResponseCode($socket, [250]);

            if ($this->smtpEncryption() === 'tls') {
                $this->sendCommand($socket, 'STARTTLS');
                $this->assertResponseCode($socket, [220]);

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Impossible d activer STARTTLS.');
                }

                $this->sendCommand($socket, 'EHLO ' . $this->helloHost());
                $this->assertResponseCode($socket, [250]);
            }

            $this->sendCommand($socket, 'AUTH LOGIN');
            $this->assertResponseCode($socket, [334]);

            $this->sendCommand($socket, base64_encode($this->smtpUsername()));
            $this->assertResponseCode($socket, [334]);

            $this->sendCommand($socket, base64_encode($this->smtpPassword()));
            $this->assertResponseCode($socket, [235]);

            $this->sendCommand($socket, 'MAIL FROM:<' . $fromEmail . '>');
            $this->assertResponseCode($socket, [250]);

            $this->sendCommand($socket, 'RCPT TO:<' . $to . '>');
            $this->assertResponseCode($socket, [250, 251]);

            $this->sendCommand($socket, 'DATA');
            $this->assertResponseCode($socket, [354]);

            $message = $this->dotStuff(implode("\r\n", $headers) . "\r\n\r\n" . $encodedBody);
            $this->sendRaw($socket, $message . "\r\n.\r\n");
            $this->assertResponseCode($socket, [250]);

            $this->sendCommand($socket, 'QUIT');

            return true;
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }

    private function openSocket()
    {
        $timeout = max(1, $this->smtpTimeoutSeconds());
        $host = $this->smtpHost();
        $port = $this->smtpPort();
        $transport = $this->smtpEncryption() === 'ssl' ? 'ssl://' : 'tcp://';
        $remote = $transport . $host . ':' . $port;

        $socket = @stream_socket_client($remote, $errorCode, $errorMessage, $timeout);

        if (!is_resource($socket)) {
            throw new RuntimeException('Connexion SMTP impossible : ' . $errorMessage . ' (' . $errorCode . ')');
        }

        stream_set_timeout($socket, $timeout);

        return $socket;
    }

    private function buildMessageParts(
        string $to,
        string $subject,
        string $body,
        string $fromEmail,
        string $fromName,
        ?string $replyTo
    ): array {
        $encodedSubject = $this->encodeHeader($this->sanitizeHeaderValue($subject));
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'Message-ID: ' . $this->messageId($fromEmail),
            'From: ' . $this->formatMailbox($fromEmail, $fromName),
            'To: <' . $to . '>',
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];

        if ($replyTo !== null) {
            $headers[] = 'Reply-To: <' . $replyTo . '>';
        }

        $encodedBody = rtrim(chunk_split(base64_encode($body), 76, "\r\n"));

        return [$encodedSubject, $headers, $encodedBody];
    }

    private function sendCommand($socket, string $command): void
    {
        $this->sendRaw($socket, $command . "\r\n");
    }

    private function sendRaw($socket, string $payload): void
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

    private function assertResponseCode($socket, array $expectedCodes): void
    {
        [$code, $message] = $this->readResponse($socket);

        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP a repondu ' . $code . ' : ' . $message);
        }
    }

    private function readResponse($socket): array
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

    private function dotStuff(string $message): string
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

    private function formatMailbox(string $email, string $name): string
    {
        return $name === ''
            ? '<' . $email . '>'
            : $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/^[\x20-\x7E]+$/', $value) === 1) {
            return $value;
        }

        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function messageId(string $fromEmail): string
    {
        $domain = substr(strrchr($fromEmail, '@') ?: '', 1);

        if ($domain === '') {
            $domain = 'localhost';
        }

        return '<' . bin2hex(random_bytes(12)) . '@' . $domain . '>';
    }

    private function helloHost(): string
    {
        $host = gethostname();

        return is_string($host) && $host !== '' ? $host : 'localhost';
    }

    private function resolveFromEmail(): ?string
    {
        $candidates = [
            (string) ($this->config['smtp_from_email'] ?? ''),
            (string) ($this->config['from_email'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            $email = $this->sanitizeEmail($candidate);

            if ($email !== null) {
                return $email;
            }
        }

        return null;
    }

    private function resolveFromName(): string
    {
        $candidate = (string) ($this->config['smtp_from_name'] ?? $this->config['from_name'] ?? '');

        return $this->sanitizeHeaderValue($candidate);
    }

    private function smtpHost(): string
    {
        return trim((string) ($this->config['smtp_host'] ?? ''));
    }

    private function smtpPort(): int
    {
        return max(0, (int) ($this->config['smtp_port'] ?? 0));
    }

    private function smtpUsername(): string
    {
        return trim((string) ($this->config['smtp_username'] ?? ''));
    }

    private function smtpPassword(): string
    {
        return (string) ($this->config['smtp_password'] ?? '');
    }

    private function smtpTimeoutSeconds(): int
    {
        return max(1, (int) ($this->config['smtp_timeout_seconds'] ?? 15));
    }

    private function smtpEncryption(): string
    {
        $configured = strtolower(trim((string) ($this->config['smtp_encryption'] ?? '')));

        if (in_array($configured, ['ssl', 'tls'], true)) {
            return $configured;
        }

        return $this->smtpPort() === 465 ? 'ssl' : ($this->smtpPort() === 587 ? 'tls' : '');
    }

    private function sanitizeEmail(string $value): ?string
    {
        $email = trim(str_replace(["\r", "\n"], '', $value));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $email;
    }

    private function sanitizeHeaderValue(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }
}
