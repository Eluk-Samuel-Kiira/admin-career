<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('name');
            $table->enum('type', ['cash', 'bank', 'card', 'mobile_money', 'e_wallet', 'crypto', 'cheque']);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            
            // Provider/Institution Details
            $table->string('provider')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('swift_bic')->nullable();
            $table->string('routing_number')->nullable();
            
            // Card Details
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_type')->nullable();
            $table->date('card_expiry_date')->nullable();
            
            // Mobile Money / E-Wallet
            $table->string('wallet_id')->nullable();
            $table->string('wallet_email')->nullable();
            $table->string('phone_number')->nullable();
            
            // Transaction Limits & Fees (in cents/base units)
            $table->integer('transaction_fee_percentage')->default(0); // In basis points (100 = 1%)
            $table->bigInteger('transaction_fee_fixed')->default(0); // In cents
            $table->bigInteger('min_transaction_amount')->default(0); // In cents
            $table->bigInteger('max_transaction_amount')->nullable(); // In cents
            $table->bigInteger('daily_limit')->nullable(); // In cents
            $table->bigInteger('monthly_limit')->nullable(); // In cents
            
            // Balance Tracking (in cents/base units)
            $table->bigInteger('current_balance')->default(0); // In cents
            $table->bigInteger('available_balance')->default(0); // In cents
            $table->bigInteger('pending_balance')->default(0); // In cents
            $table->bigInteger('min_balance_limit')->default(0); // In cents
            $table->bigInteger('max_balance_limit')->nullable(); // In cents
            $table->boolean('allow_negative_balance')->default(false);
            
            // Status & Verification
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_online')->default(true);
            $table->boolean('requires_verification')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            
            // API & Webhook
            $table->string('token', 100)->nullable();
            $table->string('api_key', 100)->nullable();
            $table->string('secret_key', 255)->nullable();
            $table->string('webhook_url')->nullable();
            
            // Settings & Metadata
            $table->json('settings')->nullable();
            $table->json('supported_currencies')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            $table->json('extra_data')->nullable();
            
            // Cash Specific
            $table->foreignId('cash_handler_id')->nullable();
            $table->string('cash_location')->nullable();
            
            // Tracking
            $table->timestamp('last_reconciled_at')->nullable();
            $table->timestamp('last_transaction_at')->nullable();
            $table->bigInteger('last_transaction_amount')->nullable(); // In cents
            $table->string('last_transaction_type')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index('type');
            $table->index('code');
            $table->index('is_active');
            $table->index('provider');
            $table->index('account_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};