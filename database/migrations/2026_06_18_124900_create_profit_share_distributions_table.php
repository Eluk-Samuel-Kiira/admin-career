<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_share_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_profit_share_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_salary_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            
            $table->integer('units_held');
            $table->integer('vested_units');
            $table->bigInteger('unit_value');
            $table->bigInteger('total_amount');
            
            $table->date('distribution_date');
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('department_profit_share_id');
            $table->index('employee_salary_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('distribution_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_share_distributions');
    }
};