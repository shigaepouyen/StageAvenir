<?php

declare(strict_types=1);

namespace App\Support;

final class MagicLinkMailer
{
    public function __construct(
        private array $mailConfig,
        private string $appUrl,
        private int $ttlMinutes
    ) {
    }

    public function sendMagicLink(string $email, string $selector, string $validator, string $returnTo = '/'): bool
    {
        $query = [
            'selector' => $selector,
            'token' => $validator,
        ];

        if ($returnTo !== '') {
            $query['return_to'] = $returnTo;
        }

        $link = $this->appUrl . '/magic-link?' . http_build_query($query);

        $subject = 'Votre lien de connexion Avenir Pro';
        $message = implode("\r\n", [
            'Bonjour,',
            '',
            'Cliquez sur le lien ci-dessous pour vous connecter :',
            $link,
            '',
            'Ce lien expire dans ' . $this->ttlMinutes . ' minutes.',
        ]);

        $fromName = str_replace(["\r", "\n"], '', $this->mailConfig['from_name']);
        $fromEmail = str_replace(["\r", "\n"], '', $this->mailConfig['from_email']);

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . sprintf('%s <%s>', $fromName, $fromEmail),
        ];

        return mail($email, $subject, $message, implode("\r\n", $headers));
    }
}
