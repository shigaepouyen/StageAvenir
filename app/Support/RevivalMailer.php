<?php

declare(strict_types=1);

namespace App\Support;

final class RevivalMailer
{
    public function __construct(
        private array $mailConfig,
        private string $appUrl
    ) {
    }

    public function sendRevivalEmail(
        string $parentEmail,
        string $internshipTitle,
        string $selector,
        string $validator
    ): bool {
        $link = $this->appUrl
            . '/internships/revival/confirm?selector=' . rawurlencode($selector)
            . '&token=' . rawurlencode($validator);

        $subject = 'Relancer cette offre de stage ?';
        $body = implode("\r\n", [
            'Bonjour,',
            '',
            'Voulez-vous relancer un stagiaire cette annee ?',
            'Offre concernee : ' . $internshipTitle,
            '',
            'Oui : ' . $link,
        ]);

        $fromName = str_replace(["\r", "\n"], '', $this->mailConfig['from_name']);
        $fromEmail = str_replace(["\r", "\n"], '', $this->mailConfig['from_email']);

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . sprintf('%s <%s>', $fromName, $fromEmail),
        ];

        return mail($parentEmail, $subject, $body, implode("\r\n", $headers));
    }
}
