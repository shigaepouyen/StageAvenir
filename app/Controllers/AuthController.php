<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AuthTokenRepository;
use App\Repositories\MagicLinkRequestRepository;
use App\Repositories\UserRepository;
use App\Support\MagicLinkMailer;
use App\Support\SessionManager;
use PDO;

final class AuthController
{
    private const ACCOUNT_TYPE_STUDENT = 'student';
    private const ACCOUNT_TYPE_COMPANY = 'company';

    private UserRepository $users;
    private AuthTokenRepository $tokens;
    private MagicLinkRequestRepository $requestLog;
    private MagicLinkMailer $mailer;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->users = new UserRepository($pdo);
        $this->tokens = new AuthTokenRepository($pdo);
        $this->requestLog = new MagicLinkRequestRepository($pdo);
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
        $selectedAccountType = $this->normalizeAccountType($_GET['account_type'] ?? null);
        $returnTo = $this->resolveReturnTo($_GET['return_to'] ?? null, $selectedAccountType);
        $email = '';

        if ($status === 'sent') {
            $success = "Si l'email est valide, un lien de connexion a ete envoye.";
        }

        if ($status === 'logged_out') {
            $success = 'Vous avez bien ete deconnecte.';
        }

        require __DIR__ . '/../Views/login.php';
    }

    public function sendMagicLink(): void
    {
        $title = 'Connexion';
        $error = null;
        $success = null;
        $email = trim((string) ($_POST['email'] ?? ''));
        $selectedAccountType = $this->normalizeAccountType($_POST['account_type'] ?? null);
        $returnTo = $this->resolveReturnTo($_POST['return_to'] ?? null, $selectedAccountType);

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $error = "Veuillez saisir une adresse email valide.";
            require __DIR__ . '/../Views/login.php';
            return;
        }

        $email = strtolower($email);
        $magicLinkConfig = $this->config['magic_link'];
        $clientIp = $this->detectClientIp();

        $this->requestLog->deleteOlderThanHours((int) $magicLinkConfig['rate_limit_log_retention_hours']);

        if (
            $this->requestLog->countRecentByEmail($email, (int) $magicLinkConfig['rate_limit_window_minutes']) >= (int) $magicLinkConfig['rate_limit_max_per_email']
            || $this->requestLog->countRecentByIp($clientIp, (int) $magicLinkConfig['rate_limit_window_minutes']) >= (int) $magicLinkConfig['rate_limit_max_per_ip']
        ) {
            http_response_code(429);
            $error = "Trop de demandes de connexion ont ete envoyees. Reessayez dans quelques minutes.";
            require __DIR__ . '/../Views/login.php';
            return;
        }

        $user = $this->users->findByEmail($email);

        if ($user !== null && $selectedAccountType === self::ACCOUNT_TYPE_COMPANY && ($user['role'] ?? '') === self::ACCOUNT_TYPE_STUDENT) {
            http_response_code(409);
            $error = "Cette adresse email existe deja comme compte eleve. Utilisez une autre adresse pour l'entreprise ou demandez a l'administrateur de corriger le role.";
            require __DIR__ . '/../Views/login.php';
            return;
        }

        if ($user === null) {
            if (!$this->config['magic_link']['auto_create_user']) {
                $success = "Si l'email est valide, un lien de connexion a ete envoye.";
                require __DIR__ . '/../Views/login.php';
                return;
            }

            $userId = $this->users->create($email, $this->roleForAccountType($selectedAccountType));
            $user = $this->users->findById($userId);
        }

        if ($user === null) {
            http_response_code(500);
            $error = "Impossible de preparer la connexion.";
            require __DIR__ . '/../Views/login.php';
            return;
        }

        $this->requestLog->create($email, $clientIp);

        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $hashedValidator = hash('sha256', $validator);
        $expiresAt = (new \DateTimeImmutable())
            ->modify(sprintf('+%d minutes', $this->config['magic_link']['ttl_minutes']))
            ->format('Y-m-d H:i:s');

        $this->tokens->deleteExpired();
        $this->tokens->deleteByUserId((int) $user['id']);
        $tokenId = $this->tokens->create($selector, $hashedValidator, (int) $user['id'], $expiresAt);

        $sent = $this->mailer->sendMagicLink((string) $user['email'], $selector, $validator, $returnTo);

        if (!$sent) {
            $this->tokens->deleteById($tokenId);
            http_response_code(500);
            $error = "L'email n'a pas pu etre envoye. Reessayez plus tard.";
            require __DIR__ . '/../Views/login.php';
            return;
        }

        app_redirect('/login?' . http_build_query([
            'status' => 'sent',
            'account_type' => $selectedAccountType,
            'return_to' => $returnTo,
        ]));
    }

    public function consumeMagicLink(): void
    {
        $title = 'Connexion';
        $error = null;
        $success = null;
        $selector = trim((string) ($_GET['selector'] ?? ''));
        $validator = trim((string) ($_GET['token'] ?? ''));
        $returnTo = $this->resolveReturnTo($_GET['return_to'] ?? null, self::ACCOUNT_TYPE_STUDENT);

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

        app_redirect($returnTo);
    }

    public function logout(): void
    {
        SessionManager::logout();
        app_redirect('/login?status=logged_out');
    }

    private function detectClientIp(): string
    {
        $forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));

        if ($forwardedFor !== '') {
            $parts = explode(',', $forwardedFor);
            $candidate = trim($parts[0]);

            if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                return $candidate;
            }
        }

        $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

        if ($remoteAddr !== '' && filter_var($remoteAddr, FILTER_VALIDATE_IP) !== false) {
            return $remoteAddr;
        }

        return '0.0.0.0';
    }

    private function normalizeAccountType(mixed $value): string
    {
        $accountType = trim((string) $value);

        return $accountType === self::ACCOUNT_TYPE_COMPANY
            ? self::ACCOUNT_TYPE_COMPANY
            : self::ACCOUNT_TYPE_STUDENT;
    }

    private function roleForAccountType(string $accountType): string
    {
        return $accountType === self::ACCOUNT_TYPE_COMPANY
            ? self::ACCOUNT_TYPE_COMPANY
            : (string) $this->config['magic_link']['default_role'];
    }

    private function defaultReturnToFor(string $accountType): string
    {
        return $accountType === self::ACCOUNT_TYPE_COMPANY ? '/company-profile' : '/';
    }

    private function resolveReturnTo(mixed $value, string $accountType): string
    {
        $returnTo = trim((string) $value);

        if ($returnTo === '') {
            return $this->defaultReturnToFor($accountType);
        }

        if (str_contains($returnTo, "\n") || str_contains($returnTo, "\r")) {
            return $this->defaultReturnToFor($accountType);
        }

        if (preg_match('#^https?://#i', $returnTo) === 1 || str_starts_with($returnTo, '//')) {
            return $this->defaultReturnToFor($accountType);
        }

        if (!str_starts_with($returnTo, '/')) {
            return $this->defaultReturnToFor($accountType);
        }

        $basePath = app_base_path();

        if ($basePath !== '' && ($returnTo === $basePath || str_starts_with($returnTo, $basePath . '/'))) {
            $returnTo = substr($returnTo, strlen($basePath));
            $returnTo = $returnTo === '' ? '/' : $returnTo;
        }

        return str_starts_with($returnTo, '/') ? $returnTo : $this->defaultReturnToFor($accountType);
    }
}
