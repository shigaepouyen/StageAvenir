<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AuthTokenRepository;
use App\Repositories\UserRepository;
use App\Support\MagicLinkMailer;
use App\Support\SessionManager;
use PDO;

final class AuthController
{
    private UserRepository $users;
    private AuthTokenRepository $tokens;
    private MagicLinkMailer $mailer;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->users = new UserRepository($pdo);
        $this->tokens = new AuthTokenRepository($pdo);
        $this->mailer = new MagicLinkMailer(
            $config['mail'],
            $config['app_url'],
            (int) $config['magic_link']['ttl_minutes']
        );
        $this->config = $config;
    }

    public function showLoginForm(): void
    {
        $title = 'Connexion';
        $status = $_GET['status'] ?? null;
        $error = null;
        $success = null;

        if ($status === 'sent') {
            $success = "Si l'email est valide, un lien de connexion a ete envoye.";
        }

        require __DIR__ . '/../Views/login.php';
    }

    public function sendMagicLink(): void
    {
        $title = 'Connexion';
        $error = null;
        $success = null;
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $error = "Veuillez saisir une adresse email valide.";
            require __DIR__ . '/../Views/login.php';
            return;
        }

        $email = strtolower($email);

        $user = $this->users->findByEmail($email);

        if ($user === null) {
            if (!$this->config['magic_link']['auto_create_user']) {
                $success = "Si l'email est valide, un lien de connexion a ete envoye.";
                require __DIR__ . '/../Views/login.php';
                return;
            }

            $userId = $this->users->create($email, $this->config['magic_link']['default_role']);
            $user = $this->users->findById($userId);
        }

        if ($user === null) {
            http_response_code(500);
            $error = "Impossible de preparer la connexion.";
            require __DIR__ . '/../Views/login.php';
            return;
        }

        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $hashedValidator = hash('sha256', $validator);
        $expiresAt = (new \DateTimeImmutable())
            ->modify(sprintf('+%d minutes', $this->config['magic_link']['ttl_minutes']))
            ->format('Y-m-d H:i:s');

        $this->tokens->deleteExpired();
        $this->tokens->deleteByUserId((int) $user['id']);
        $tokenId = $this->tokens->create($selector, $hashedValidator, (int) $user['id'], $expiresAt);

        $sent = $this->mailer->sendMagicLink((string) $user['email'], $selector, $validator);

        if (!$sent) {
            $this->tokens->deleteById($tokenId);
            http_response_code(500);
            $error = "L'email n'a pas pu etre envoye. Reessayez plus tard.";
            require __DIR__ . '/../Views/login.php';
            return;
        }

        header('Location: ' . $this->config['app_url'] . '/login?status=sent', true, 302);
        exit;
    }

    public function consumeMagicLink(): void
    {
        $title = 'Connexion';
        $error = null;
        $success = null;
        $selector = trim((string) ($_GET['selector'] ?? ''));
        $validator = trim((string) ($_GET['token'] ?? ''));

        if ($selector === '' || $validator === '') {
            http_response_code(400);
            $error = 'Lien de connexion invalide.';
            require __DIR__ . '/../Views/login.php';
            return;
        }

        $tokenRow = $this->tokens->findActiveBySelector($selector);

        if ($tokenRow === null) {
            http_response_code(400);
            $error = 'Lien de connexion invalide ou expire.';
            require __DIR__ . '/../Views/login.php';
            return;
        }

        $isValid = hash_equals((string) $tokenRow['hashed_validator'], hash('sha256', $validator));

        if (!$isValid) {
            $this->tokens->deleteById((int) $tokenRow['id']);
            http_response_code(400);
            $error = 'Lien de connexion invalide.';
            require __DIR__ . '/../Views/login.php';
            return;
        }

        if (strtotime((string) $tokenRow['expires_at']) < time()) {
            $this->tokens->deleteById((int) $tokenRow['id']);
            http_response_code(400);
            $error = 'Lien de connexion expire.';
            require __DIR__ . '/../Views/login.php';
            return;
        }

        $this->tokens->deleteByUserId((int) $tokenRow['user_id']);

        SessionManager::login([
            'id' => (int) $tokenRow['user_id'],
            'email' => (string) $tokenRow['email'],
            'role' => (string) $tokenRow['role'],
        ]);

        $success = 'Connexion reussie. Votre session est active pour 30 jours.';
        require __DIR__ . '/../Views/login.php';
    }
}
