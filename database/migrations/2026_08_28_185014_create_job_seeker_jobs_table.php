<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('job_seeker_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seeker_profile_id')->constrained('seeker_profiles')->onDelete('cascade');
            $table->foreignId('job_post_id')->constrained('job_posts')->onDelete('cascade');
            
            // Status flags
            $table->boolean('is_saved')->default(false);
            $table->boolean('is_applied')->default(false);
            $table->boolean('is_called_for_interview')->default(false);
            $table->boolean('is_got_job')->default(false);
            $table->boolean('is_rejected')->default(false);
            $table->boolean('is_viewed')->default(false);
            
            // Application details
            $table->text('application_letter')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('cv_used_path')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('saved_at')->nullable();
            
            // Interview details
            $table->timestamp('interview_date')->nullable();
            $table->text('interview_notes')->nullable();
            
            // Tracking
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Unique constraint to prevent duplicates
            $table->unique(['seeker_profile_id', 'job_post_id']);
            
            $table->index(['seeker_profile_id', 'is_saved']);
            $table->index(['seeker_profile_id', 'is_applied']);
            $table->index('job_post_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_seeker_jobs');
    }
};