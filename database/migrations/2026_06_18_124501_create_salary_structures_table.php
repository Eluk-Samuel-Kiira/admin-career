<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_title');
            $table->string('role_code')->unique();
            $table->bigInteger('base_salary')->default(0);
            $table->enum('salary_type', ['fixed', 'hourly', 'commission'])->default('fixed');
            $table->decimal('performance_bonus_percentage', 5, 2)->default(0);
            $table->bigInteger('performance_bonus_max')->nullable();
            $table->integer('phantom_equity_units')->default(0);
            $table->decimal('profit_share_percentage', 5, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->bigInteger('retention_bonus')->nullable();
            $table->bigInteger('min_salary')->nullable();
            $table->bigInteger('max_salary')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('department_id');
            $table->index('role_code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};