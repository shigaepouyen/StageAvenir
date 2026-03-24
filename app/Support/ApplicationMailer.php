<?php

declare(strict_types=1);

namespace App\Support;

final class ApplicationMailer
{
    private MailTransport $transport;

    public function __construct(private array $mailConfig)
    {
        $this->transport = new MailTransport($mailConfig);
    }

    public function sendToParent(
        string $parentEmail,
        string $studentEmail,
        string $internshipTitle,
        string $studentMessage,
        string $classe
    ): bool {
        $subject = 'Nouvelle candidature Avenir Pro';
        $body = implode("\r\n", [
            'Bonjour,',
            '',
            'Vous avez recu une nouvelle candidature pour l\'offre : ' . $internshipTitle,
            '',
            'Email eleve : ' . $studentEmail,
            'Classe : ' . $classe,
            '',
            'Message de motivation :',
            $studentMessage,
        ]);

        return $this->transport->sendPlainText($parentEmail, $subject, $body, $studentEmail);
    }
}
