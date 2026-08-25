<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->uuid('deposit_ref')->nullable()->unique();
            
            // Deposit details
            $table->foreignId('payment_method_id')->constrained('payment_methods');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->bigInteger('amount');
            $table->bigInteger('fee')->default(0);
            $table->bigInteger('net_amount');
            
            // Deposit method
            $table->enum('deposit_method', ['cash', 'bank_transfer', 'mobile_money', 'card', 'cheque', 'e_wallet', 'crypto']);
            $table->string('reference_number')->nullable();
            $table->string('cheque_number')->nullable();
            $table->string('card_last_four', 4)->nullable();
            
            // Department & Depositor (NEW)
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('depositor_id')->nullable()->constrained('users')->nullOnDelete();
            
            // SOURCE OF MONEY
            $table->foreignId('source_id')->nullable()->constrained('payment_sources');
            $table->string('source_name_manual')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('source_contact')->nullable();
            
            // Source Details
            $table->string('customer_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('po_number')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('project_code')->nullable();
            
            // Payment Purpose
            $table->foreignId('purpose_id')->nullable()->constrained('payment_purposes');
            $table->string('purpose_description')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            
            // Dates
            $table->timestamp('deposit_date');
            $table->timestamp('cleared_date')->nullable();
            
            // Depositor details (physical person making deposit)
            $table->string('depositor_name')->nullable();
            $table->string('depositor_phone')->nullable();
            $table->string('depositor_email')->nullable();
            
            // Description & Notes
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            
            // Attachments
            $table->string('receipt_image')->nullable();
            $table->json('attachments')->nullable();
            
            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            // Verification
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            
            // Audit
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index('deposit_ref');
            $table->index('payment_method_id');
            $table->index('department_id');
            $table->index('depositor_id');
            $table->index('source_id');
            $table->index('purpose_id');
            $table->index('status');
            $table->index('deposit_date');
            $table->index('reference_number');
            $table->index('customer_id');
            $table->index('invoice_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};