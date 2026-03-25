<?php

declare(strict_types=1);

namespace App\Support;

final class ApplicationMailer
{
    private MailTransport $transport;
    private string $appUrl;

    public function __construct(private array $mailConfig)
    {
        $this->transport = new MailTransport($mailConfig);
        $this->appUrl = rtrim((string) ($_ENV['APP_URL'] ?? getenv('APP_URL') ?: ''), '/');
    }

    public function sendNewApplicationNotification(
        string $recipientEmail,
        string $internshipTitle
    ): bool {
        return $this->sendPlatformAlert(
            $recipientEmail,
            'Nouvelle candidature Avenir Pro',
            'Une nouvelle candidature est disponible pour l\'offre : ' . $internshipTitle
        );
    }

    public function sendNewMessageNotification(
        string $recipientEmail,
        string $internshipTitle
    ): bool {
        return $this->sendPlatformAlert(
            $recipientEmail,
            'Nouveau message Avenir Pro',
            'Un nouveau message a ete poste dans la discussion liee a l\'offre : ' . $internshipTitle
        );
    }

    public function sendApplicationStatusNotification(
        string $recipientEmail,
        string $internshipTitle,
        string $statusLabel
    ): bool {
        return $this->sendPlatformAlert(
            $recipientEmail,
            'Mise a jour de candidature Avenir Pro',
            'Le statut de votre candidature pour l\'offre "' . $internshipTitle . '" est maintenant : ' . $statusLabel
        );
    }

    public function sendCompanyValidationNotification(
        string $recipientEmail,
        string $companyLabel,
        string $decisionLabel
    ): bool {
        return $this->sendPlatformAlert(
            $recipientEmail,
            'Decision sur votre entreprise Avenir Pro',
            'Le profil entreprise "' . $companyLabel . '" a ete traite par l\'administration : ' . $decisionLabel
        );
    }

    public function sendInternshipValidationNotification(
        string $recipientEmail,
        string $internshipTitle,
        string $decisionLabel
    ): bool {
        return $this->sendPlatformAlert(
            $recipientEmail,
            'Decision sur votre offre Avenir Pro',
            'Votre offre "' . $internshipTitle . '" a ete traitee par l\'administration : ' . $decisionLabel
        );
    }

    private function absoluteUrl(string $path): string
    {
        if ($this->appUrl === '') {
            return $path;
        }

        return $this->appUrl . '/' . ltrim($path, '/');
    }

    private function sendPlatformAlert(string $recipientEmail, string $subject, string $headline): bool
    {
        $body = implode("\r\n", [
            'Bonjour,',
            '',
            $headline,
            '',
            'Une nouveaute vous attend dans Avenir Pro.',
            'Pour proteger les donnees des mineurs, le detail reste accessible uniquement apres connexion dans la webapp.',
            '',
            'Ouvrir mes news : ' . $this->absoluteUrl('/news'),
        ]);

        return $this->transport->sendPlainText($recipientEmail, $subject, $body);
    }
}
