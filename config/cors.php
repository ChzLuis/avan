<?php

return [
    // La extensión (content script en web.whatsapp.com) llama a la API del Copilot.
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://web.whatsapp.com',
    ],

    'allowed_origins_patterns' => [
        '#^chrome-extension://#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
