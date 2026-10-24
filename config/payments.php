<?php

return [
    'currency'     => env('PAYMENT_CURRENCY', 'USD'),
    'frontend_url' => env('FRONTEND_URL', env('APP_URL')),

    // Comma separated: cod,stripe,paypal  (only enabled methods are accepted at checkout)
    'enabled' => array_values(array_filter(array_map('trim', explode(',', env('PAYMENT_METHODS', 'cod,stripe,paypal'))))),

    'stripe' => [
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
    'paypal' => [
        'base_url'   => env('PAYPAL_BASE_URL', 'https://api-m.sandbox.paypal.com'),
        'client_id'  => env('PAYPAL_CLIENT_ID'),
        'secret'     => env('PAYPAL_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],
];
