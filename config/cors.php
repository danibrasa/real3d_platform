<?php

return [

    'paths' => ['api/v1/*'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    // SECURITY NOTE: Change '*' to your actual domain when HTTPS is configured
    // Example: 'allowed_origins' => ['https://tu-dominio.com'],
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Authorization', 'Content-Type', 'Accept', 'X-Requested-With'],

    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],

    'max_age' => 86400,

    'supports_credentials' => false,

];
