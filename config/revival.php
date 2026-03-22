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
    'start_month' => (int) (getenv('REVIVAL_START_MONTH') ?: '9'),
    'start_day' => (int) (getenv('REVIVAL_START_DAY') ?: '1'),
    'reminder_delay_days' => (int) (getenv('REVIVAL_REMINDER_DELAY_DAYS') ?: '3'),
    'max_emails' => (int) (getenv('REVIVAL_MAX_EMAILS') ?: '3'),
];
