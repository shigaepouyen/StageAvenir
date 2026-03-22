<?php

declare(strict_types=1);

use App\Support\Env;

Env::load(__DIR__ . '/../.env.local');

return [
    'month' => (int) (getenv('NETTOYAGE_MONTH') ?: '7'),
    'day' => (int) (getenv('NETTOYAGE_DAY') ?: '15'),
];
