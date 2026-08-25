<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phantom_equity_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_salary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            
            $table->enum('transaction_type', [
                'allocation', 'award', 'vesting', 'forfeiture', 'payout'
            ]);
            $table->integer('units');
            $table->integer('vested_units')->default(0);
            $table->bigInteger('unit_value')->nullable();
            $table->bigInteger('total_value')->nullable();
            
            $table->decimal('performance_score', 5, 2)->nullable();
            $table->decimal('performance_multiplier', 5, 2)->default(1.0);
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->timestamp('transaction_date');
            $table->boolean('is_vested')->default(false);
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('employee_salary_id');
            $table->index('user_id');
            $table->index('transaction_type');
            $table->index('transaction_date');
            $table->index('is_vested');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phantom_equity_transactions');
    }
};