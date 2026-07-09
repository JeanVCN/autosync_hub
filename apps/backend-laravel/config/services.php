<?php

return [
    'integration_hub' => [
        'url' => env('INTEGRATION_HUB_URL', 'http://localhost:8080'),
        'timeout_seconds' => (int) env('INTEGRATION_HUB_TIMEOUT_SECONDS', 5),
        'token' => env('INTEGRATION_HUB_TOKEN'),
        'callback_url' => env('INTEGRATION_CALLBACK_URL', env('APP_URL', 'http://localhost:8000').'/api/integration-callbacks'),
        'contract_version' => env('INTEGRATION_CONTRACT_VERSION', '2026-07-09'),
    ],
];
