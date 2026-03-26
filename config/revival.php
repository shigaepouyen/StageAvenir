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
    'start_month' => (int) Env::get('REVIVAL_START_MONTH', '9'),
    'start_day' => (int) Env::get('REVIVAL_START_DAY', '1'),
    'reminder_delay_days' => (int) Env::get('REVIVAL_REMINDER_DELAY_DAYS', '3'),
    'max_emails' => (int) Env::get('REVIVAL_MAX_EMAILS', '3'),
];
