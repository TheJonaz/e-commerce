<?php

return [
    'supported_locales' => ['sv', 'en'],
    'default_locale' => env('SHOP_DEFAULT_LOCALE', 'sv'),

    'supported_currencies' => ['SEK', 'EUR', 'USD', 'NOK', 'DKK'],
    'default_currency' => env('SHOP_DEFAULT_CURRENCY', 'SEK'),

    'vat' => [
        'rates' => [25.00, 12.00, 6.00, 0.00],
        'default_rate' => 25.00,
        'prices_include_vat' => true,
    ],
];
