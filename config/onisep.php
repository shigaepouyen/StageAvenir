<?php

declare(strict_types=1);

use App\Support\Env;

Env::load(__DIR__ . '/../.env.local');

return [
    'dataset_api_url' => Env::get('ONISEP_DATASET_API_URL', 'https://www.data.gouv.fr/api/1/datasets/ideo-metiers-onisep/'),
    'timeout_seconds' => (int) Env::get('ONISEP_TIMEOUT_SECONDS', '20'),
    'user_agent' => Env::get('ONISEP_USER_AGENT', 'AvenirPro/1.0'),
];
