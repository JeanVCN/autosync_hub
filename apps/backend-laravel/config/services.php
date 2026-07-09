<?php

return [
    'integration_hub' => [
        'url' => getenv('INTEGRATION_HUB_URL') ?: env('INTEGRATION_HUB_URL', 'http://localhost:8080'),
        'timeout_seconds' => (int) (getenv('INTEGRATION_HUB_TIMEOUT_SECONDS') ?: env('INTEGRATION_HUB_TIMEOUT_SECONDS', 5)),
        'token' => getenv('INTEGRATION_HUB_TOKEN') ?: env('INTEGRATION_HUB_TOKEN'),
        'callback_url' => getenv('INTEGRATION_CALLBACK_URL') ?: env('INTEGRATION_CALLBACK_URL', env('APP_URL', 'http://localhost:8000').'/api/integration-callbacks'),
        'contract_version' => getenv('INTEGRATION_CONTRACT_VERSION') ?: env('INTEGRATION_CONTRACT_VERSION', '2026-07-09'),
    ],
];
