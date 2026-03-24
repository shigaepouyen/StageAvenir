<?php

declare(strict_types=1);

namespace App\Support;

final class RevivalMailer
{
    private MailTransport $transport;

    public function __construct(
        private array $mailConfig,
        private string $appUrl
    ) {
        $this->transport = new MailTransport($mailConfig);
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

        return $this->transport->sendPlainText($parentEmail, $subject, $body);
    }
}
