<?php

declare(strict_types=1);

namespace App\Support;

final class MagicLinkMailer
{
    private MailTransport $transport;

    public function __construct(
        private array $mailConfig,
        private string $appUrl,
        private int $ttlMinutes
    ) {
        $this->transport = new MailTransport($mailConfig);
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

        return $this->transport->sendPlainText($email, $subject, $message);
    }
}
