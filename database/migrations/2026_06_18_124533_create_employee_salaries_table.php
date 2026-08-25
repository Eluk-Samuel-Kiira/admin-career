<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, check if the table exists and drop it if needed
        if (Schema::hasTable('employee_salaries')) {
            Schema::dropIfExists('employee_salaries');
        }

        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            
            // Link to employee (the employee record)
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            
            // Link to user (the main authentication model)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // Department relationship
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            
            // Salary structure reference
            $table->foreignId('salary_structure_id')->nullable()->constrained('salary_structures')->nullOnDelete();
            
            // Salary Details
            $table->bigInteger('base_salary')->default(0);
            $table->enum('salary_type', ['fixed', 'hourly', 'commission'])->default('fixed');
            $table->boolean('is_recurring')->default(true);
            $table->integer('recurring_day')->nullable(); // Day of month (1-31)
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            
            // Performance tracking
            $table->decimal('performance_rating', 5, 2)->nullable(); // 0-100
            $table->decimal('performance_multiplier', 5, 2)->default(1.0); // 0.0 - 2.0
            
            // Phantom Equity
            $table->integer('phantom_equity_units')->default(0);
            $table->integer('vested_units')->default(0);
            $table->decimal('units_vested_percentage', 5, 2)->default(0); // 0-100
            $table->bigInteger('current_balance')->default(0); // In cents
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Soft Delete
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('employee_id');
            $table->index('user_id');
            $table->index('department_id');
            $table->index('salary_structure_id');
            $table->index('is_active');
            $table->index(['hire_date', 'termination_date']);
            $table->index(['user_id', 'is_active']);
            $table->index(['department_id', 'is_active']);
            $table->index(['employee_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};