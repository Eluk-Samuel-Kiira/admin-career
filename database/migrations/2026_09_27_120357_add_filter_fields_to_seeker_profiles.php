<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seeker_profiles', function (Blueprint $table) {
            // Content: what the seeker is looking for
            if (!Schema::hasColumn('seeker_profiles', 'job_category_id')) {
                $table->foreignId('job_category_id')->nullable()->after('professional_title')
                    ->constrained('job_categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('seeker_profiles', 'industry_id')) {
                $table->foreignId('industry_id')->nullable()->after('job_category_id')
                    ->constrained('industries')->nullOnDelete();
            }
            if (!Schema::hasColumn('seeker_profiles', 'job_type_id')) {
                $table->foreignId('job_type_id')->nullable()->after('industry_id')
                    ->constrained('job_types')->nullOnDelete();
            }

            // Location: where they want to work
            if (!Schema::hasColumn('seeker_profiles', 'job_location_id')) {
                $table->foreignId('job_location_id')->nullable()->after('job_type_id')
                    ->constrained('job_locations')->nullOnDelete();
            }
            if (!Schema::hasColumn('seeker_profiles', 'preferred_country_code')) {
                $table->char('preferred_country_code', 2)->nullable()->after('job_location_id');
            }

            // Level
            if (!Schema::hasColumn('seeker_profiles', 'experience_level_id')) {
                $table->foreignId('experience_level_id')->nullable()->after('preferred_country_code')
                    ->constrained('experience_levels')->nullOnDelete();
            }
            if (!Schema::hasColumn('seeker_profiles', 'education_level_id')) {
                $table->foreignId('education_level_id')->nullable()->after('experience_level_id')
                    ->constrained('education_levels')->nullOnDelete();
            }
            if (!Schema::hasColumn('seeker_profiles', 'salary_range_id')) {
                $table->foreignId('salary_range_id')->nullable()->after('education_level_id')
                    ->constrained('salary_ranges')->nullOnDelete();
            }

            // Profile completion / required fields tracking
            if (!Schema::hasColumn('seeker_profiles', 'profile_completed_at')) {
                $table->timestamp('profile_completed_at')->nullable()->after('is_active');
            }

            // Indexes for filter performance
            $table->index(['job_category_id', 'is_active'], 'sp_jobcat_active_idx');
            $table->index(['industry_id', 'is_active'], 'sp_industry_active_idx');
            $table->index(['job_location_id', 'is_active'], 'sp_jobloc_active_idx');
            $table->index(['experience_level_id', 'is_active'], 'sp_explvl_active_idx');
            $table->index(['country', 'is_active'], 'sp_country_active_idx');
            $table->index('preferred_country_code', 'sp_preferred_country_idx');
        });
    }

    public function down(): void
    {
        Schema::table('seeker_profiles', function (Blueprint $table) {
            // Drop foreign keys before columns
            foreach ([
                'job_category_id', 'industry_id', 'job_type_id', 'job_location_id',
                'experience_level_id', 'education_level_id', 'salary_range_id',
            ] as $col) {
                if (Schema::hasColumn('seeker_profiles', $col)) {
                    $table->dropForeign([$col]);
                }
            }

            // Drop indexes
            foreach ([
                'sp_jobcat_active_idx', 'sp_industry_active_idx', 'sp_jobloc_active_idx',
                'sp_explvl_active_idx', 'sp_country_active_idx', 'sp_preferred_country_idx',
            ] as $idx) {
                try { $table->dropIndex($idx); } catch (\Throwable $e) { /* ignore */ }
            }

            // Drop columns
            $cols = [
                'job_category_id', 'industry_id', 'job_type_id', 'job_location_id',
                'preferred_country_code', 'experience_level_id', 'education_level_id',
                'salary_range_id', 'profile_completed_at',
            ];
            $existing = array_filter($cols, fn($c) => Schema::hasColumn('seeker_profiles', $c));
            if (!empty($existing)) {
                $table->dropColumn(array_values($existing));
            }
        });
    }
};