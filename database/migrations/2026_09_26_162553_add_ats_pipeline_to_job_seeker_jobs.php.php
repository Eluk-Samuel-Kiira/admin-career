<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_seeker_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('job_seeker_jobs', 'ats_status')) {
                $table->enum('ats_status', [
                    'new', 'screening', 'shortlisted',
                    'interview', 'offer', 'hired',
                    'rejected', 'withdrawn',
                ])->default('new')->after('is_applied');
            }

            if (!Schema::hasColumn('job_seeker_jobs', 'employer_rating')) {
                $table->unsignedTinyInteger('employer_rating')->nullable()->after('ats_status');
            }

            if (!Schema::hasColumn('job_seeker_jobs', 'employer_notes')) {
                $table->text('employer_notes')->nullable()->after('employer_rating');
            }

            if (!Schema::hasColumn('job_seeker_jobs', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('employer_notes')
                    ->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('job_seeker_jobs', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }

            // Index for filtering by status within a job
            $table->index(['job_post_id', 'ats_status']);
        });
    }

    public function down(): void
    {
        Schema::table('job_seeker_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('job_seeker_jobs', 'reviewed_by')) {
                $table->dropForeign(['reviewed_by']);
            }

            $cols = ['ats_status', 'employer_rating', 'employer_notes', 'reviewed_by', 'reviewed_at'];
            $existing = array_filter($cols, fn($c) => Schema::hasColumn('job_seeker_jobs', $c));
            if (!empty($existing)) {
                $table->dropColumn(array_values($existing));
            }
        });
    }
};