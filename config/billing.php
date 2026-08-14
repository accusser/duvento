<?php

return [
    'trial_days' => 14,

    'plans' => [
        'self-hosted' => [
            'clients' => null,
            'assets' => null,
            'price' => 0,
        ],
        'free-trial' => [
            'clients' => 25,
            'assets' => 100,
            'price' => 0,
        ],
        'starter' => [
            'clients' => 25,
            'assets' => 250,
            'price' => 19,
        ],
        'agency' => [
            'clients' => 100,
            'assets' => 2000,
            'price' => 49,
        ],
    ],
];
