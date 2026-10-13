<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_apply_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seeker_profile_id')->constrained('seeker_profiles')->cascadeOnDelete();

            $table->date('run_date');

            // Quota snapshot for the day this run represents
            $table->integer('quota_total')->default(0);       // user's monthly quota
            $table->integer('quota_used_before')->default(0); // applied count before this run (this month)
            $table->integer('quota_remaining')->default(0);   // what this run may consume

            // Outcomes
            $table->integer('jobs_scanned')->default(0);
            $table->integer('dispatched')->default(0);
            $table->integer('applied_count')->default(0);
            $table->integer('relayed_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->integer('failed_count')->default(0);

            $table->string('status')->default('queued');
            // queued | processing | completed | failed | quota_exhausted | no_cv | no_preferences

            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'run_date'], 'auto_apply_runs_user_date_unique');
            $table->index(['run_date', 'status'], 'auto_apply_runs_date_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_apply_runs');
    }
};