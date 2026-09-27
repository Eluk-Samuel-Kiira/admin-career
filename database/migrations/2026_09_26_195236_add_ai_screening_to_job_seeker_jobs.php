<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_seeker_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('job_seeker_jobs', 'ai_score')) {
                $table->unsignedTinyInteger('ai_score')->nullable()->after('ats_status');
            }
            if (!Schema::hasColumn('job_seeker_jobs', 'ai_recommendation')) {
                $table->enum('ai_recommendation', ['strong_yes', 'maybe', 'no'])->nullable()->after('ai_score');
            }
            if (!Schema::hasColumn('job_seeker_jobs', 'ai_summary')) {
                $table->text('ai_summary')->nullable()->after('ai_recommendation');
            }
            if (!Schema::hasColumn('job_seeker_jobs', 'ai_strengths')) {
                $table->json('ai_strengths')->nullable()->after('ai_summary');
            }
            if (!Schema::hasColumn('job_seeker_jobs', 'ai_gaps')) {
                $table->json('ai_gaps')->nullable()->after('ai_strengths');
            }
            if (!Schema::hasColumn('job_seeker_jobs', 'ai_red_flags')) {
                $table->json('ai_red_flags')->nullable()->after('ai_gaps');
            }
            if (!Schema::hasColumn('job_seeker_jobs', 'ai_screened_at')) {
                $table->timestamp('ai_screened_at')->nullable()->after('ai_red_flags');
            }
            if (!Schema::hasColumn('job_seeker_jobs', 'ai_job_fingerprint')) {
                $table->string('ai_job_fingerprint', 64)->nullable()->after('ai_screened_at');
            }

            $table->index(['job_post_id', 'ai_score']);
            $table->index(['job_post_id', 'ai_recommendation']);
        });
    }

    public function down(): void
    {
        Schema::table('job_seeker_jobs', function (Blueprint $table) {
            $cols = [
                'ai_score', 'ai_recommendation', 'ai_summary',
                'ai_strengths', 'ai_gaps', 'ai_red_flags',
                'ai_screened_at', 'ai_job_fingerprint',
            ];
            $existing = array_filter($cols, fn($c) => Schema::hasColumn('job_seeker_jobs', $c));
            if (!empty($existing)) {
                $table->dropColumn(array_values($existing));
            }
        });
    }
};