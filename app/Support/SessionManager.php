<?php

declare(strict_types=1);

namespace App\Support;

final class SessionManager
{
    public static function start(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetime = max(1, (int) $config['lifetime_days']) * 86400;

        session_name((string) $config['cookie_name']);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'secure' => (bool) $config['secure'],
            'httponly' => (bool) $config['httponly'],
            'samesite' => (string) $config['samesite'],
        ]);

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $config['secure'] ? '1' : '0');
        ini_set('session.gc_maxlifetime', (string) $lifetime);

        session_start();
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'logged_in_at' => time(),
        ];
    }

    public static function currentUser(): ?array
    {
        $user = $_SESSION['user'] ?? null;

        return is_array($user) ? $user : null;
    }
}
