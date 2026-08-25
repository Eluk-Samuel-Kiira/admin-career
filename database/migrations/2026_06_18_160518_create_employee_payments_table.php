<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->string('uuid')->unique()->nullable();
            
            // Relationships
            $table->foreignId('employee_salary_id')->nullable()->constrained('employee_salaries')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            
            // Payment Details
            $table->date('payment_date');
            $table->enum('payment_type', ['salary', 'bonus', 'commission', 'advance', 'reimbursement'])->default('salary');
            $table->text('description')->nullable();
            $table->string('short_description', 255)->nullable();
            
            // Amounts (in cents/base units)
            $table->bigInteger('gross_amount')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('net_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            
            // Currency
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 15, 6)->default(1);
            
            // Breakdowns (JSON)
            $table->json('deductions')->nullable();
            $table->json('allowances')->nullable();
            $table->json('breakdown')->nullable();
            $table->json('tax_breakdown')->nullable();
            
            // Payment Status
            $table->enum('payment_status', ['pending', 'approved', 'paid', 'cancelled', 'rejected', 'failed'])->default('pending');
            
            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Payment Processing
            $table->timestamp('paid_date')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Pay Period
            $table->date('pay_period_start')->nullable();
            $table->date('pay_period_end')->nullable();
            
            // Hourly / Commission Details
            $table->decimal('hours_worked', 10, 2)->nullable();
            $table->decimal('hourly_rate', 15, 2)->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();
            
            // Performance
            $table->decimal('performance_rating', 5, 2)->nullable();
            $table->decimal('performance_multiplier', 5, 2)->nullable();
            
            // References
            $table->string('reference_number')->nullable();
            $table->string('bank_reference')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('external_reference')->nullable();
            
            // Additional Data
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index('payment_number');
            $table->index('uuid');
            $table->index('user_id');
            $table->index('department_id');
            $table->index('payment_method_id');
            $table->index('currency_id');
            $table->index('payment_date');
            $table->index('payment_type');
            $table->index('payment_status');
            $table->index('approved_by');
            $table->index('paid_date');
            $table->index('reference_number');
            $table->index('receipt_number');
            $table->index('external_reference');
            
            // Composite indexes for common queries
            $table->index(['pay_period_start', 'pay_period_end']);
            $table->index(['user_id', 'payment_status']);
            $table->index(['department_id', 'payment_status']);
            $table->index(['payment_date', 'payment_status']);
            $table->index(['payment_type', 'payment_status']);
            $table->index(['user_id', 'payment_date']);
            $table->index(['payment_status', 'payment_date']);
            $table->index(['department_id', 'payment_date']);
            $table->index(['payment_method_id', 'payment_status']);
            $table->index(['user_id', 'payment_type']);
            $table->index(['created_by', 'payment_status']);
            $table->index(['approved_by', 'payment_status']);
            
            // Full text indexes for search
            $table->fullText(['description', 'short_description', 'notes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payments');
    }
};