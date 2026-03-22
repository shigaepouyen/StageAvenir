<?php

declare(strict_types=1);

use App\Support\Env;

Env::load(__DIR__ . '/../.env.local');

return [
    'app_url' => rtrim(getenv('APP_URL') ?: 'http://127.0.0.1:8000', '/'),
    'mail' => [
        'from_email' => getenv('MAIL_FROM') ?: 'no-reply@example.test',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Avenir Pro',
    ],
    'magic_link' => [
        'ttl_minutes' => (int) (getenv('MAGIC_LINK_TTL_MINUTES') ?: '20'),
        'auto_create_user' => (getenv('AUTH_AUTO_CREATE') ?: '1') === '1',
        'default_role' => getenv('AUTH_DEFAULT_ROLE') ?: 'student',
    ],
    'session' => [
        'cookie_name' => 'avenir_pro_session',
        'lifetime_days' => (int) (getenv('SESSION_LIFETIME_DAYS') ?: '30'),
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
];
