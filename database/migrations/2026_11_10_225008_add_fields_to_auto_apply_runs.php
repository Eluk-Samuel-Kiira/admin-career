<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add AI drafting columns to relays
        Schema::table('auto_apply_relays', function (Blueprint $table) {
            // Stores the HTML of the tailored CV
            $table->longText('tailored_cv_html')->nullable()->after('cv_path');
            
            // Stores the HTML of the generated cover letter
            $table->longText('cover_letter_html')->nullable()->after('tailored_cv_html');
            
            // The external link (if the job uses a hyperlink)
            $table->string('external_application_url', 500)->nullable()->after('status');
        });

        // 2. Create a new table for external link drafts
        Schema::create('auto_apply_external_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auto_apply_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_post_id')->constrained('job_posts')->cascadeOnDelete();
            
            $table->longText('tailored_cv_html'); // For viewing in the drawer
            $table->longText('cover_letter_html')->nullable();
            $table->string('external_url', 500);
            
            $table->string('status')->default('drafted'); // drafted | downloaded | applied
            $table->timestamps();

            // Prevents duplicate drafts for the same job by the same user
            $table->unique(['user_id', 'job_post_id'], 'unique_user_job_draft');
        });
    }

    public function down(): void
    {
        Schema::table('auto_apply_relays', function (Blueprint $table) {
            $table->dropColumn(['tailored_cv_html', 'cover_letter_html', 'external_application_url']);
        });
        Schema::dropIfExists('auto_apply_external_drafts');
    }
};