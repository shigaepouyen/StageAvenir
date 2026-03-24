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
    'start_month' => (int) (getenv('REVIVAL_START_MONTH') ?: '9'),
    'start_day' => (int) (getenv('REVIVAL_START_DAY') ?: '1'),
    'reminder_delay_days' => (int) (getenv('REVIVAL_REMINDER_DELAY_DAYS') ?: '3'),
    'max_emails' => (int) (getenv('REVIVAL_MAX_EMAILS') ?: '3'),
];
