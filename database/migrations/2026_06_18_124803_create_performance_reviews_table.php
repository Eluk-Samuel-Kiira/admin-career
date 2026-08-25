<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_salary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            
            $table->enum('review_period', ['monthly', 'quarterly', 'annual']);
            $table->timestamp('review_date');
            
            // Core KPIs
            $table->decimal('score', 5, 2);
            $table->decimal('revenue_contribution', 5, 2)->nullable();
            $table->decimal('client_satisfaction', 5, 2)->nullable();
            $table->decimal('reporting_discipline', 5, 2)->nullable();
            $table->decimal('innovation_score', 5, 2)->nullable();
            $table->decimal('teamwork_score', 5, 2)->nullable();
            $table->decimal('quality_score', 5, 2)->nullable();
            $table->decimal('attendance_score', 5, 2)->nullable();
            
            $table->json('kpi_achievements')->nullable();
            $table->enum('overall_rating', ['excellent', 'good', 'average', 'below_average', 'poor']);
            $table->text('recommendations')->nullable();
            
            $table->boolean('bonus_eligible')->default(false);
            $table->boolean('promotion_recommended')->default(false);
            
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'completed', 'approved'])->default('pending');
            
            $table->timestamps();

            $table->index('employee_salary_id');
            $table->index('user_id');
            $table->index('review_period');
            $table->index('review_date');
            $table->index('status');
            $table->index('bonus_eligible');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};