<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Safely add columns to auto_apply_relays
        Schema::table('auto_apply_relays', function (Blueprint $table) {
            if (!Schema::hasColumn('auto_apply_relays', 'employer_email')) {
                $table->string('employer_email')->nullable()->after('job_post_id');
            }
            if (!Schema::hasColumn('auto_apply_relays', 'seeker_cc_email')) {
                $table->string('seeker_cc_email')->nullable()->after('employer_email');
            }
            if (!Schema::hasColumn('auto_apply_relays', 'shared_via_whatsapp')) {
                $table->boolean('shared_via_whatsapp')->default(false)->after('status');
            }
        });

        // 2. Safely add columns to job_seeker_jobs
        Schema::table('job_seeker_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('job_seeker_jobs', 'auto_apply_draft_id')) {
                $table->unsignedBigInteger('auto_apply_draft_id')->nullable()->after('job_post_id');
            }
            if (!Schema::hasColumn('job_seeker_jobs', 'auto_apply_draft_type')) {
                $table->string('auto_apply_draft_type')->nullable()->after('auto_apply_draft_id');
            }
        });
    }

    public function down(): void
    {
        // We use dropColumnIfExists if available, otherwise check first.
        // Laravel 10+ doesn't have dropColumnIfExists natively on all DBs, so we check.
        Schema::table('auto_apply_relays', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('auto_apply_relays', 'employer_email')) $columnsToDrop[] = 'employer_email';
            if (Schema::hasColumn('auto_apply_relays', 'seeker_cc_email')) $columnsToDrop[] = 'seeker_cc_email';
            if (Schema::hasColumn('auto_apply_relays', 'shared_via_whatsapp')) $columnsToDrop[] = 'shared_via_whatsapp';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('job_seeker_jobs', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('job_seeker_jobs', 'auto_apply_draft_id')) $columnsToDrop[] = 'auto_apply_draft_id';
            if (Schema::hasColumn('job_seeker_jobs', 'auto_apply_draft_type')) $columnsToDrop[] = 'auto_apply_draft_type';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};