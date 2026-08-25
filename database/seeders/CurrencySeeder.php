<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        // USD - 2 decimal places (cents)
        Currency::updateOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'decimal_places' => 2,
                'base_unit_multiplier' => 100,
                'exchange_rate_to_usd' => 1,
                'is_active' => true,
                'is_default' => true,
            ]
        );
        
        // UGX - 0 decimal places (no cents)
        Currency::updateOrCreate(
            ['code' => 'UGX'],
            [
                'name' => 'Ugandan Shilling',
                'symbol' => 'UGX',
                'decimal_places' => 0,
                'base_unit_multiplier' => 1,
                'exchange_rate_to_usd' => 3700,
                'is_active' => true,
                'is_default' => false,
            ]
        );
        
        // KES - 0 decimal places (no cents)
        Currency::updateOrCreate(
            ['code' => 'KES'],
            [
                'name' => 'Kenyan Shilling',
                'symbol' => 'KSh',
                'decimal_places' => 0,
                'base_unit_multiplier' => 1,
                'exchange_rate_to_usd' => 130,
                'is_active' => true,
                'is_default' => false,
            ]
        );
    }
}