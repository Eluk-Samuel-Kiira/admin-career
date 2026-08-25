<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique();
            $table->date('date');
            $table->text('description')->nullable();
            
            // Relationships
            $table->foreignId('category_id')->constrained('expense_categories');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Vendor details
            $table->string('vendor_name')->nullable();
            $table->string('vendor_contact')->nullable();
            $table->string('vendor_email')->nullable();
            
            // Amounts (in cents/base units)
            $table->bigInteger('gross_amount')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('net_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            
            // Tax breakdown as JSON
            $table->json('tax_breakdown')->nullable();
            
            // Payment details
            $table->enum('payment_status', ['pending', 'approved', 'paid', 'cancelled', 'rejected'])->default('pending');
            $table->timestamp('paid_date')->nullable();
            
            // Recurring expense
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
            $table->date('next_recurring_date')->nullable();
            
            // Receipt
            $table->string('receipt_url')->nullable();
            $table->string('receipt_number')->nullable();
            
            // Approval
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('expense_number');
            $table->index('date');
            $table->index('category_id');
            $table->index('department_id');
            $table->index('payment_method_id');
            $table->index('payment_status');
            $table->index('employee_id');
            $table->index('created_by');
            $table->index('is_recurring');
            $table->index('next_recurring_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};