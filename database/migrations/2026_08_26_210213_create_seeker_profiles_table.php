<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('seeker_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Personal Information
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            
            // Professional Information
            $table->text('professional_summary')->nullable();
            $table->string('professional_title')->nullable();
            $table->integer('years_of_experience')->nullable();
            
            // Links
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('portfolio_url')->nullable();
            
            // Skills & Qualifications
            $table->json('skills')->nullable();
            $table->json('languages')->nullable();
            $table->json('certifications')->nullable();
            
            // Experience & Education
            $table->json('education')->nullable();
            $table->json('work_experience')->nullable();
            $table->json('projects')->nullable();
            
            // CV / Resume
            $table->string('cv_file_path')->nullable();
            $table->string('cv_original_name')->nullable();
            
            // Preferences
            $table->json('job_preferences')->nullable();
            
            // Status
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id');
            $table->index('country');
            $table->index('city');
            $table->index('professional_title');
            $table->index('years_of_experience');
            $table->index('is_public');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('seeker_profiles');
    }
};