<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;
use App\Models\Currency;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        // First get the currencies
        $usd = Currency::where('code', 'USD')->first();
        $ugx = Currency::where('code', 'UGX')->first();
        
        if (!$usd || !$ugx) {
            $this->command->error('Currencies not found. Please run CurrencySeeder first.');
            return;
        }

        // MTN Mobile Money
        PaymentMethod::updateOrCreate(
            ['code' => 'MTN_UGX'],
            [
                'name' => 'Mobile Money - MTN Uganda',
                'type' => 'mobile_money',
                'description' => 'MTN Mobile Money collection account',
                'provider' => 'MTN Uganda',
                'phone_number' => '+256712345678',
                'account_name' => 'LAFAB Solutions',
                'wallet_id' => 'MTN_LAFAB_001',
                'currency_id' => $ugx->id,
                'current_balance' => 0,
                'available_balance' => 0,
                'pending_balance' => 0,
                'min_balance_limit' => 0,
                'allow_negative_balance' => false,
                'is_active' => true,
                'is_default' => false,
                'min_transaction_amount' => 1000,
                'max_transaction_amount' => 10000000,
                'daily_limit' => 20000000,
                'monthly_limit' => 200000000,
                'transaction_fee_percentage' => 50, // 0.5%
                'transaction_fee_fixed' => 100,
            ]
        );

        // Airtel Mobile Money
        PaymentMethod::updateOrCreate(
            ['code' => 'AIRTEL_UGX'],
            [
                'name' => 'Mobile Money - Airtel Uganda',
                'type' => 'mobile_money',
                'description' => 'Airtel Mobile Money collection account',
                'provider' => 'Airtel Uganda',
                'phone_number' => '+256756789012',
                'account_name' => 'LAFAB Solutions',
                'wallet_id' => 'AIRTEL_LAFAB_001',
                'currency_id' => $ugx->id,
                'current_balance' => 0,
                'available_balance' => 0,
                'pending_balance' => 0,
                'min_balance_limit' => 0,
                'allow_negative_balance' => false,
                'is_active' => true,
                'is_default' => false,
                'min_transaction_amount' => 1000,
                'max_transaction_amount' => 10000000,
                'daily_limit' => 20000000,
                'monthly_limit' => 200000000,
                'transaction_fee_percentage' => 50,
                'transaction_fee_fixed' => 100,
            ]
        );

        // Company Bank Account - Stanbic
        PaymentMethod::updateOrCreate(
            ['code' => 'STANBIC_UGX'],
            [
                'name' => 'Company Bank Account - Stanbic',
                'type' => 'bank',
                'description' => 'Main company operating account',
                'provider' => 'Stanbic Bank',
                'account_name' => 'LAFAB Solutions Ltd',
                'account_number' => '0140012345678',
                'iban' => 'UG12345678901234567890',
                'swift_bic' => 'SBICUGKX',
                'currency_id' => $ugx->id,
                'current_balance' => 0,
                'available_balance' => 0,
                'pending_balance' => 0,
                'min_balance_limit' => 1000000,
                'max_balance_limit' => null,
                'allow_negative_balance' => false,
                'is_active' => true,
                'is_default' => true,
                'min_transaction_amount' => 10000,
                'max_transaction_amount' => 50000000,
                'daily_limit' => 100000000,
                'monthly_limit' => 1000000000,
                'transaction_fee_percentage' => 0,
                'transaction_fee_fixed' => 0,
            ]
        );

        // PayPal Business
        PaymentMethod::updateOrCreate(
            ['code' => 'PAYPAL_USD'],
            [
                'name' => 'PayPal Business',
                'type' => 'e_wallet',
                'description' => 'PayPal business account for international payments',
                'provider' => 'PayPal',
                'wallet_email' => 'finance@lafab.com',
                'currency_id' => $usd->id,
                'current_balance' => 0,
                'available_balance' => 0,
                'pending_balance' => 0,
                'min_balance_limit' => 0,
                'allow_negative_balance' => false,
                'is_active' => true,
                'is_default' => false,
                'min_transaction_amount' => 1000,
                'max_transaction_amount' => 500000,
                'daily_limit' => 1000000,
                'monthly_limit' => 5000000,
                'transaction_fee_percentage' => 340, // 3.4%
                'transaction_fee_fixed' => 30,
            ]
        );

        // Cash - Head Office
        PaymentMethod::updateOrCreate(
            ['code' => 'CASH_HO'],
            [
                'name' => 'Cash - Head Office',
                'type' => 'cash',
                'description' => 'Physical cash at head office',
                'cash_location' => 'Kampala Head Office - Safe Room',
                'currency_id' => $ugx->id,
                'current_balance' => 0,
                'available_balance' => 0,
                'pending_balance' => 0,
                'min_balance_limit' => 500000,
                'allow_negative_balance' => false,
                'is_active' => true,
                'is_default' => false,
                'min_transaction_amount' => 1000,
                'max_transaction_amount' => 20000000,
                'daily_limit' => 20000000,
                'monthly_limit' => 100000000,
                'transaction_fee_percentage' => 0,
                'transaction_fee_fixed' => 0,
            ]
        );

        $this->command->info('Payment Methods seeded successfully!');
    }
}