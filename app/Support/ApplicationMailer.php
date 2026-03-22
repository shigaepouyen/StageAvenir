<?php

declare(strict_types=1);

namespace App\Support;

final class ApplicationMailer
{
    public function __construct(private array $mailConfig)
    {
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

        $fromName = str_replace(["\r", "\n"], '', $this->mailConfig['from_name']);
        $fromEmail = str_replace(["\r", "\n"], '', $this->mailConfig['from_email']);
        $safeReplyTo = str_replace(["\r", "\n"], '', $studentEmail);

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . sprintf('%s <%s>', $fromName, $fromEmail),
            'Reply-To: ' . $safeReplyTo,
        ];

        return mail($parentEmail, $subject, $body, implode("\r\n", $headers));
    }
}
