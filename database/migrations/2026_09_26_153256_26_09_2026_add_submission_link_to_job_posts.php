<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('job_posts', 'job_submission_id')) {
                $table->foreignId('job_submission_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('job_submissions')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('job_posts', 'package_key')) {
                $table->string('package_key', 100)
                    ->nullable()
                    ->after('job_submission_id');
            }

            if (!Schema::hasColumn('job_posts', 'has_ats')) {
                $table->boolean('has_ats')
                    ->default(false)
                    ->after('package_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_posts', function (Blueprint $table) {
            if (Schema::hasColumn('job_posts', 'job_submission_id')) {
                $table->dropForeign(['job_submission_id']);
                $table->dropColumn('job_submission_id');
            }
            if (Schema::hasColumn('job_posts', 'package_key')) {
                $table->dropColumn('package_key');
            }
            if (Schema::hasColumn('job_posts', 'has_ats')) {
                $table->dropColumn('has_ats');
            }
        });
    }
};