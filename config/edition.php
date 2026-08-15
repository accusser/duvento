<?php

return [
    'edition' => env('APP_EDITION', 'self-host'),

    'cloud_url' => env('CLOUD_URL'),

    'cloud_features' => [
        'white_label' => true,
        'public_api' => true,
        'webhooks' => true,
        'mcp' => true,
        'fine_grained_roles' => true,
        'telegram' => true,
        'slack' => true,
        'whois' => true,
        'billing' => true,
    ],
];
