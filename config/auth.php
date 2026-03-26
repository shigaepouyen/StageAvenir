<?php

declare(strict_types=1);

use App\Support\Env;

Env::load(__DIR__ . '/../.env.local');

return [
    'app_url' => rtrim(Env::get('APP_URL', 'http://127.0.0.1:8000'), '/'),
    'mail' => [
        'from_email' => Env::get('MAIL_FROM', 'no-reply@example.test'),
        'from_name' => Env::get('MAIL_FROM_NAME', 'Avenir Pro'),
        'smtp_host' => Env::get('SMTP_HOST'),
        'smtp_username' => Env::get('SMTP_USERNAME'),
        'smtp_password' => Env::get('SMTP_PASSWORD'),
        'smtp_port' => (int) Env::get('SMTP_PORT', '0'),
        'smtp_from_email' => Env::get('SMTP_FROM_EMAIL', Env::get('MAIL_FROM', 'no-reply@example.test')),
        'smtp_from_name' => Env::get('SMTP_FROM_NAME', Env::get('MAIL_FROM_NAME', 'Avenir Pro')),
        'smtp_timeout_seconds' => (int) Env::get('SMTP_TIMEOUT_SECONDS', '15'),
        'smtp_encryption' => Env::get('SMTP_ENCRYPTION'),
    ],
    'magic_link' => [
        'ttl_minutes' => (int) Env::get('MAGIC_LINK_TTL_MINUTES', '20'),
        'auto_create_user' => Env::get('AUTH_AUTO_CREATE', '1') === '1',
        'default_role' => Env::get('AUTH_DEFAULT_ROLE', 'student'),
        'rate_limit_window_minutes' => (int) Env::get('MAGIC_LINK_RATE_LIMIT_WINDOW_MINUTES', '15'),
        'rate_limit_max_per_email' => (int) Env::get('MAGIC_LINK_RATE_LIMIT_MAX_PER_EMAIL', '3'),
        'rate_limit_max_per_ip' => (int) Env::get('MAGIC_LINK_RATE_LIMIT_MAX_PER_IP', '10'),
        'rate_limit_log_retention_hours' => (int) Env::get('MAGIC_LINK_RATE_LIMIT_LOG_RETENTION_HOURS', '48'),
    ],
    'session' => [
        'cookie_name' => 'avenir_pro_session',
        'lifetime_days' => (int) Env::get('SESSION_LIFETIME_DAYS', '30'),
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
];
