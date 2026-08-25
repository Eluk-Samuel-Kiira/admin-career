<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Countries
    |--------------------------------------------------------------------------
    | "id" is a normal auto-increment column. Uniqueness/identity of a
    | migrated row is legacy_id + country_code (already enforced by the
    | exists() check in MigrationService::migrate() and used everywhere
    | relations are resolved), so ids never need to be forced.
    |
    | This list only drives the country dropdown in the migration UI.
    | Adding a new country later = add one line here.
    */
    'countries' => [
        'AU' => ['name' => 'Australia', 'flag' => '🇦🇺'],
        'KE' => ['name' => 'Kenya', 'flag' => '🇰🇪'],
        'UG' => ['name' => 'Uganda', 'flag' => '🇺🇬'],
        'TZ' => ['name' => 'Tanzania', 'flag' => '🇹🇿'],
        'RW' => ['name' => 'Rwanda', 'flag' => '🇷🇼'],
        'ZM' => ['name' => 'Zambia', 'flag' => '🇿🇲'],
        'MW' => ['name' => 'Malawi', 'flag' => '🇲🇼'],
        'SG' => ['name' => 'Singapore', 'flag' => '🇸🇬'],
    ],

    'default_country' => env('MIGRATION_COUNTRY', 'AU'),

    'batch_size' => env('MIGRATION_BATCH_SIZE', 100),
];