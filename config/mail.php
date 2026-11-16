<?php

return [

    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [

        // ─── Default / fallback ───────────────────────────────
        'smtp' => [
            'transport'    => 'smtp',
            'host'         => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port'         => env('MAIL_PORT', 587),
            'encryption'   => env('MAIL_ENCRYPTION', 'tls'),
            'username'     => env('MAIL_USERNAME'),
            'password'     => env('MAIL_PASSWORD'),
            'timeout'      => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        // ─── Australia ────────────────────────────────────────
        'au' => [
            'transport'    => 'smtp',
            'host'         => env('MAIL_AU_HOST', env('MAIL_HOST')),
            'port'         => env('MAIL_AU_PORT', env('MAIL_PORT', 465)),
            'encryption'   => env('MAIL_AU_ENCRYPTION', env('MAIL_ENCRYPTION', 'ssl')),
            'username'     => env('MAIL_AU_USERNAME', env('MAIL_USERNAME')),
            'password'     => env('MAIL_AU_PASSWORD', env('MAIL_PASSWORD')),
            'timeout'      => null,
            'local_domain' => env('MAIL_AU_EHLO_DOMAIN', env('MAIL_EHLO_DOMAIN')),
        ],

        // ─── Uganda ───────────────────────────────────────────
        'ug' => [
            'transport'    => 'smtp',
            'host'         => env('MAIL_UG_HOST', env('MAIL_HOST')),
            'port'         => env('MAIL_UG_PORT', env('MAIL_PORT', 465)),
            'encryption'   => env('MAIL_UG_ENCRYPTION', env('MAIL_ENCRYPTION', 'ssl')),
            'username'     => env('MAIL_UG_USERNAME', env('MAIL_USERNAME')),
            'password'     => env('MAIL_UG_PASSWORD', env('MAIL_PASSWORD')),
            'timeout'      => null,
            'local_domain' => env('MAIL_UG_EHLO_DOMAIN', env('MAIL_EHLO_DOMAIN')),
            'from'         => [
                'address' => env('MAIL_UG_FROM_ADDRESS'),
                'name'    => env('MAIL_UG_FROM_NAME'),
            ],
        ],

        'log' => [
            'transport' => 'log',
            'channel'   => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport'  => 'failover',
            'mailers'    => ['smtp', 'log'],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport'  => 'roundrobin',
            'mailers'    => ['ses', 'postmark'],
            'retry_after' => 60,
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name'    => env('MAIL_FROM_NAME', 'Example'),
    ],

    'markdown' => [
        'theme' => 'default',
        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];