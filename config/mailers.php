<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Country → Mailer map
    |--------------------------------------------------------------------------
    |
    | Any country code in this list resolves to the given mailer name
    | (a key in config/mail.php > mailers). Countries not listed fall back
    | to the default 'smtp' mailer.
    |
    */

    'map' => [
        'AU' => 'au',
        'UG' => 'ug',
    ],

    /*
    |--------------------------------------------------------------------------
    | Country → From address override
    |--------------------------------------------------------------------------
    |
    | If you want the "From" address on emails to be country-specific
    | regardless of the SMTP account, set it here. Leave null to use the
    | SMTP account's own from address.
    |
    */

    'from' => [
        'AU' => [
            'address' => env('MAIL_AU_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
            'name'    => env('MAIL_AU_FROM_NAME', env('MAIL_FROM_NAME', config('app.name'))),
        ],
        'UG' => [
            'address' => env('MAIL_UG_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
            'name'    => env('MAIL_UG_FROM_NAME', env('MAIL_FROM_NAME', config('app.name'))),
        ],
    ],
];