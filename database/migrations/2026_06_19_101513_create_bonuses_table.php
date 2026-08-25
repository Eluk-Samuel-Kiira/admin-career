<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonuses', function (Blueprint $table) {
            $table->id();
            $table->string('bonus_number')->unique();
            
            // Relationships
            $table->foreignId('employee_salary_id')->nullable()->constrained('employee_salaries')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            
            // Bonus Details
            $table->enum('bonus_type', [
                'performance', 'retention', 'commission', 'extraordinary', 
                'referral', 'signing', 'holiday', 'project', 'team'
            ])->default('performance');
            
            $table->enum('bonus_category', ['monthly', 'quarterly', 'annual', 'one_time'])->default('one_time');
            
            // Amounts
            $table->bigInteger('amount')->default(0);
            $table->decimal('percentage_of_salary', 5, 2)->nullable();
            
            // Performance
            $table->decimal('performance_score', 5, 2)->nullable();
            $table->decimal('target_achieved', 5, 2)->nullable();
            $table->string('target_metric')->nullable();
            
            // Descriptions
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            
            // Dates
            $table->date('bonus_date');
            $table->timestamp('paid_date')->nullable();
            $table->boolean('is_paid')->default(false);
            
            // Status & Approval
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Additional
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index('bonus_number');
            $table->index('user_id');
            $table->index('department_id');
            $table->index('bonus_type');
            $table->index('bonus_category');
            $table->index('status');
            $table->index('bonus_date');
            $table->index('is_paid');
            $table->index(['user_id', 'status']);
            $table->index(['department_id', 'status']);
            $table->index(['bonus_date', 'status']);
            $table->index(['bonus_type', 'status']);
            $table->index(['payment_method_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonuses');
    }
};