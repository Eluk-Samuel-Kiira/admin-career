<?php

return [

    'AU' => [
        'code'    => 'AU',
        'name'    => 'Australia',
        'web_url' => env('AUSTRALIA_WEB_URL'),
        'api_key' => env('AU_API_KEY'),

        // Payment not enabled for this country yet
        'payment' => null,
    ],

    'UG' => [
        'code'    => 'UG',
        'name'    => 'Uganda',
        'web_url' => env('UGANDA_WEB_URL'),
        'api_key' => env('UG_API_KEY'),

        'payment' => [
            'gateway'      => 'marzpay',
            'api_key'      => env('UG_MARZPAY_API_KEY'),
            'api_secret'   => env('UG_MARZPAY_API_SECRET'),
            'base_url'     => env('UG_MARZPAY_BASE_URL', 'https://wallet.wearemarz.com/api/v1'),
            'callback_url' => env('UG_MARZPAY_CALLBACK_URL', 'https://stardencareersug.com/api/payments/marzpay/webhook'),
            'currency'     => 'UGX',
            'min_amount'   => 500,
            'max_amount'   => 10000000,
            'supports'     => ['mobile_money', 'card'],
        ],
    ],

    'KE' => [
        'code'    => 'KE',
        'name'    => 'Kenya',
        'web_url' => env('KENYA_WEB_URL'),
        'api_key' => env('KENYA_API_KEY'),

        // When Kenya goes live with payments, this becomes a block
        // similar to UG above, with Kenya's gateway and credentials.
        'payment' => null,
    ],

    'TZ' => [
        'code'    => 'TZ',
        'name'    => 'Tanzania',
        'web_url' => env('TANZANIA_WEB_URL'),
        'api_key' => env('TANZANIA_API_KEY'),

        'payment' => null,
    ],

];