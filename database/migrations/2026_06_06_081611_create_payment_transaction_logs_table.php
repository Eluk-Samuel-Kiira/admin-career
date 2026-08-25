<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_ref')->unique();
            
            // Payment Method
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('cascade');
            
            // Department & User Tracking
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('depositor_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Transaction Details - Updated to include transfer_in and transfer_out
            $table->enum('transaction_type', [
                'deposit', 
                'withdrawal', 
                'transfer', 
                'transfer_in', 
                'transfer_out', 
                'payment', 
                'refund', 
                'fee', 
                'adjustment'
            ])->default('deposit');
            
            $table->enum('transaction_category', [
                'revenue', 
                'expense', 
                'asset', 
                'liability', 
                'equity', 
                'bonus', 
                'transfer'
            ])->default('revenue');
            
            $table->string('reference_table')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            
            // Amounts (in cents/base units)
            $table->bigInteger('amount'); // In cents
            $table->bigInteger('transaction_fee')->default(0); // In cents
            $table->bigInteger('net_amount'); // In cents
            $table->bigInteger('balance_before'); // In cents
            $table->bigInteger('balance_after'); // In cents
            
            // Currency & Exchange
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->string('original_currency', 3)->nullable();
            $table->bigInteger('original_amount')->nullable(); // In cents of original currency
            
            // Status & Dates - Added 'reversed' status
            $table->enum('status', [
                'pending', 
                'processing', 
                'completed', 
                'failed', 
                'cancelled', 
                'refunded',
                'reversed'
            ])->default('pending');
            
            $table->timestamp('transaction_date');
            $table->timestamp('effective_date')->nullable();
            $table->timestamp('settlement_date')->nullable();
            
            // Descriptions & Metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            
            // External References
            $table->string('external_reference')->nullable();
            $table->string('bank_reference')->nullable();
            $table->string('receipt_number')->nullable();
            
            // Counterparty Details
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('counterparty_id')->nullable();
            $table->string('counterparty_name')->nullable();
            $table->string('counterparty_account')->nullable();
            
            // Transfer Specific Fields - NEW
            $table->foreignId('related_transaction_id')->nullable()->comment('Reference to the paired transfer transaction');
            $table->foreignId('from_payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('to_payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->bigInteger('converted_amount')->nullable()->comment('Amount in destination currency for transfers');
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes - Using shorter names to avoid MySQL 64 character limit
            $table->index('transaction_ref', 'idx_trans_ref');
            $table->index('payment_method_id', 'idx_payment_method');
            $table->index('department_id', 'idx_department');
            $table->index('depositor_id', 'idx_depositor');
            $table->index(['reference_table', 'reference_id'], 'idx_reference');
            $table->index('status', 'idx_status');
            $table->index('transaction_date', 'idx_trans_date');
            $table->index('transaction_type', 'idx_trans_type');
            $table->index('transaction_category', 'idx_trans_cat');
            $table->index('user_id', 'idx_user');
            $table->index('related_transaction_id', 'idx_related_trans');
            $table->index('from_payment_method_id', 'idx_from_pm');
            $table->index('to_payment_method_id', 'idx_to_pm');
            
            // Composite indexes with shorter names
            $table->index(['payment_method_id', 'transaction_type', 'status'], 'idx_pm_type_status');
            $table->index(['payment_method_id', 'transaction_date', 'transaction_type'], 'idx_pm_date_type');
            $table->index(['from_payment_method_id', 'to_payment_method_id'], 'idx_from_to_pm');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transaction_logs');
    }
};