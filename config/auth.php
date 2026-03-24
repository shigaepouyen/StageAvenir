<?php

declare(strict_types=1);

use App\Support\Env;

Env::load(__DIR__ . '/../.env.local');

return [
    'app_url' => rtrim(getenv('APP_URL') ?: 'http://127.0.0.1:8000', '/'),
    'mail' => [
        'from_email' => getenv('MAIL_FROM') ?: 'no-reply@example.test',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Avenir Pro',
        'smtp_host' => getenv('SMTP_HOST') ?: '',
        'smtp_username' => getenv('SMTP_USERNAME') ?: '',
        'smtp_password' => getenv('SMTP_PASSWORD') ?: '',
        'smtp_port' => (int) (getenv('SMTP_PORT') ?: '0'),
        'smtp_from_email' => getenv('SMTP_FROM_EMAIL') ?: (getenv('MAIL_FROM') ?: 'no-reply@example.test'),
        'smtp_from_name' => getenv('SMTP_FROM_NAME') ?: (getenv('MAIL_FROM_NAME') ?: 'Avenir Pro'),
        'smtp_timeout_seconds' => (int) (getenv('SMTP_TIMEOUT_SECONDS') ?: '15'),
        'smtp_encryption' => getenv('SMTP_ENCRYPTION') ?: '',
    ],
    'magic_link' => [
        'ttl_minutes' => (int) (getenv('MAGIC_LINK_TTL_MINUTES') ?: '20'),
        'auto_create_user' => (getenv('AUTH_AUTO_CREATE') ?: '1') === '1',
        'default_role' => getenv('AUTH_DEFAULT_ROLE') ?: 'student',
        'rate_limit_window_minutes' => (int) (getenv('MAGIC_LINK_RATE_LIMIT_WINDOW_MINUTES') ?: '15'),
        'rate_limit_max_per_email' => (int) (getenv('MAGIC_LINK_RATE_LIMIT_MAX_PER_EMAIL') ?: '3'),
        'rate_limit_max_per_ip' => (int) (getenv('MAGIC_LINK_RATE_LIMIT_MAX_PER_IP') ?: '10'),
        'rate_limit_log_retention_hours' => (int) (getenv('MAGIC_LINK_RATE_LIMIT_LOG_RETENTION_HOURS') ?: '48'),
    ],
    'session' => [
        'cookie_name' => 'avenir_pro_session',
        'lifetime_days' => (int) (getenv('SESSION_LIFETIME_DAYS') ?: '30'),
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
];
