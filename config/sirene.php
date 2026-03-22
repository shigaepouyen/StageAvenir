<?php

declare(strict_types=1);

use App\Support\Env;

Env::load(__DIR__ . '/../.env.local');

return [
    'base_url' => rtrim(getenv('SIRENE_API_BASE_URL') ?: 'https://recherche-entreprises.api.gouv.fr', '/'),
    'timeout_seconds' => (int) (getenv('SIRENE_API_TIMEOUT_SECONDS') ?: '10'),
    'user_agent' => getenv('SIRENE_API_USER_AGENT') ?: 'AvenirPro/1.0',
];
