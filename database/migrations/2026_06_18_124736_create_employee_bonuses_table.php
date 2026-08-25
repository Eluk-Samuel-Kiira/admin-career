<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_salary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            
            $table->enum('bonus_type', [
                'performance', 'retention', 'commission', 'extraordinary',
                'recruitment', 'training', 'automation', 'client_acquisition',
                'placement', 'retention_worker', 'management', 'revenue_target'
            ]);
            $table->enum('bonus_category', ['monthly', 'quarterly', 'annual', 'one_time']);
            
            $table->bigInteger('amount');
            $table->decimal('percentage_of_salary', 5, 2)->nullable();
            $table->decimal('performance_score', 5, 2)->nullable();
            $table->decimal('target_achieved', 5, 2)->nullable();
            $table->string('target_metric')->nullable();
            
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            
            $table->timestamp('bonus_date');
            $table->timestamp('paid_date')->nullable();
            $table->boolean('is_paid')->default(false);
            
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('employee_salary_id');
            $table->index('user_id');
            $table->index('bonus_type');
            $table->index('bonus_category');
            $table->index('is_paid');
            $table->index('bonus_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_bonuses');
    }
};