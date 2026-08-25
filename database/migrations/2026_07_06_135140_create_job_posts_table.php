<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('job_posts');
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            
            // Core Job Information
            $table->string('job_title');
            $table->string('slug')->unique();
            $table->longText('job_description'); // Changed to longText for large descriptions
            $table->text('responsibilities')->nullable();
            $table->text('skills')->nullable();
            $table->text('qualifications')->nullable();
            $table->date('deadline');
            $table->text('application_procedure')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            
            // ALL FOREIGN KEYS - NO CONSTRAINTS, just nullable columns
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('job_category_id')->nullable();
            $table->unsignedBigInteger('industry_id')->nullable();
            $table->unsignedBigInteger('job_location_id')->nullable();
            $table->unsignedBigInteger('job_type_id')->nullable();
            $table->unsignedBigInteger('experience_level_id')->nullable();
            $table->unsignedBigInteger('education_level_id')->nullable();
            $table->unsignedBigInteger('salary_range_id')->nullable();
            $table->unsignedBigInteger('poster_id')->nullable();
            
            // Country Code
            $table->string('country_code', 2)->nullable()->default('AU')->comment('ISO 3166-1 alpha-2 country code');
            
            // Legacy Tracking
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->unsignedBigInteger('legacy_company_id')->nullable();
            $table->string('legacy_alias')->nullable();
            $table->string('legacy_uid')->nullable();
            $table->json('legacy_metadata')->nullable();
            
            // Location Details
            $table->string('duty_station')->nullable();
            $table->text('street_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('zipcode')->nullable();
            
            // Salary Information
            $table->decimal('salary_amount', 12, 2)->nullable();
            $table->string('currency')->default('AUD');
            $table->string('payment_period')->nullable();
            $table->string('job_source')->nullable();
            $table->decimal('base_salary', 12, 2)->nullable();
            $table->string('salary_range_from')->nullable();
            $table->string('salary_range_to')->nullable();
            
            // Job Specifications
            $table->string('location_type')->default('on-site');
            $table->text('applicant_location_requirements')->nullable();
            $table->string('work_hours')->nullable();
            $table->string('employment_type')->default('full-time');
            
            // SEO & AI Optimization
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->json('structured_data')->nullable();
            $table->text('focus_keyphrase')->nullable();
            $table->text('seo_synonyms')->nullable();
            
            // Advanced SEO Features
            $table->boolean('is_pinged')->default(false);
            $table->boolean('is_indexed')->default(false);
            $table->boolean('is_whatsapp_contact')->default(false);
            $table->boolean('is_telephone_call')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_urgent')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_simple_job')->default(false);
            $table->boolean('is_quick_gig')->default(false);
            $table->integer('view_count')->default(0);
            $table->integer('application_count')->default(0);
            $table->integer('click_count')->default(0);
            
            // Application Requirements
            $table->boolean('is_application_required')->default(false);
            $table->boolean('is_academic_documents_required')->default(false); 
            $table->boolean('is_cover_letter_required')->default(false); 
            $table->boolean('is_resume_required')->default(true);
            
            // AI Optimization
            $table->string('ai_optimized_title')->nullable();
            $table->text('ai_optimized_description')->nullable();
            $table->text('ai_content_analysis')->nullable();
            $table->decimal('seo_score', 8, 2)->default(0);
            $table->decimal('content_quality_score', 8, 2)->default(0);
            $table->json('search_terms')->nullable();
            $table->json('competitor_analysis')->nullable();
            $table->text('ai_recommendations')->nullable();
            
            // Performance Tracking
            $table->integer('search_impressions')->default(0);
            $table->integer('search_clicks')->default(0);
            $table->decimal('click_through_rate', 8, 2)->default(0);
            $table->integer('google_rank')->nullable();
            $table->json('ranking_keywords')->nullable();
            
            // Social Signals
            $table->integer('social_shares')->default(0);
            $table->integer('backlinks_count')->default(0);
            $table->json('social_metrics')->nullable();
            
            // Additional Legacy Fields
            $table->string('job_reference')->nullable();
            $table->string('duration')->nullable();
            $table->string('heighest_finished_education')->nullable();
            $table->integer('experience_months')->default(0);
            $table->string('job_type_legacy')->nullable();
            $table->string('job_status_legacy')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->timestamp('published_at')->nullable();            
            $table->datetime('published_until')->nullable();            
            $table->datetime('featured_until')->nullable();
            $table->datetime('last_pinged_at')->nullable();
            $table->datetime('last_indexed_at')->nullable();
            $table->datetime('migrated_at')->nullable();
            $table->softDeletes();

            // Indexes (no foreign key constraints)
            $table->index(['is_active', 'is_verified', 'published_at', 'seo_score']);
            $table->index(['deadline', 'is_active', 'seo_score']);
            $table->index('company_id');
            $table->index('job_location_id');
            $table->index('job_category_id');
            $table->index('industry_id');
            $table->index(['is_featured', 'is_active', 'seo_score']);
            $table->index(['is_urgent', 'is_active', 'seo_score']);
            $table->index(['salary_amount', 'is_active', 'seo_score']);
            $table->index('slug');
            $table->index('created_at');
            $table->index('view_count');
            $table->index('seo_score');
            $table->index('content_quality_score');
            $table->index(['published_at', 'seo_score']);
            $table->index(['job_location_id', 'job_category_id', 'seo_score']);
            $table->index('legacy_id');
            $table->index('country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};