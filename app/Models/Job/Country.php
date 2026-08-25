<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Country extends Model
{
    use HasFactory;

    protected $table = 'countries';

    protected $fillable = [
        'code',
        'name',
        'region',
        'timezone',
        'currency',
        'currency_symbol',
        'default_lat',
        'default_lng',
        'flag',
        'capital',
        'capital_lat',
        'frontend_url',
        'domain',
        'capital_lng',
        'phone_code',
        
        // Feature Flags - Job Seeker & Employer Services
        'can_view_casual_workers',
        'can_view_blue_collar_workers',
        'can_accept_cv_services',
        'can_offer_exam_services',
        'can_view_salary_insights',
        'can_view_cost_of_living_tools',
        'can_use_social_media_services',
        'can_view_employer_services',
        'can_view_jobseeker_services',
        'can_access_subscription',
        
        // Additional Traffic & Engagement Features
        'can_view_company_profiles',
        'can_view_industry_insights',
        'can_access_career_advice',
        'can_view_job_alerts',
        'can_use_resume_builder',
        'can_view_employer_reviews',
        'can_access_skill_assessment',
        'can_view_market_trends',
        'can_use_job_comparison_tools',
        'can_access_networking_events',
        'can_view_training_courses',
        'can_use_chat_support',
        
        // Premium/Paid Features
        'can_access_premium_content',
        'can_view_verified_employers',
        'can_use_priority_application',
        'can_view_exclusive_jobs',
        'can_access_interview_coaching',
        'can_view_salary_negotiation_tips',
        
        // Job Posting Features
        'can_post_jobs',
        'can_post_featured_jobs',
        'can_post_urgent_jobs',
        'can_use_job_analytics',
        'can_manage_applications',
        
        // Core fields
        'is_active',
        'sort_order',
        'created_by',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_lat' => 'decimal:8',
        'default_lng' => 'decimal:8',
        'capital_lat' => 'decimal:8',
        'capital_lng' => 'decimal:8',
        'sort_order' => 'integer',
        
        // Feature Flags - boolean casting
        'can_view_casual_workers' => 'boolean',
        'can_view_blue_collar_workers' => 'boolean',
        'can_accept_cv_services' => 'boolean',
        'can_offer_exam_services' => 'boolean',
        'can_view_salary_insights' => 'boolean',
        'can_view_cost_of_living_tools' => 'boolean',
        'can_use_social_media_services' => 'boolean',
        'can_view_employer_services' => 'boolean',
        'can_view_jobseeker_services' => 'boolean',
        'can_access_subscription' => 'boolean',
        'can_view_company_profiles' => 'boolean',
        'can_view_industry_insights' => 'boolean',
        'can_access_career_advice' => 'boolean',
        'can_view_job_alerts' => 'boolean',
        'can_use_resume_builder' => 'boolean',
        'can_view_employer_reviews' => 'boolean',
        'can_access_skill_assessment' => 'boolean',
        'can_view_market_trends' => 'boolean',
        'can_use_job_comparison_tools' => 'boolean',
        'can_access_networking_events' => 'boolean',
        'can_view_training_courses' => 'boolean',
        'can_use_chat_support' => 'boolean',
        'can_access_premium_content' => 'boolean',
        'can_view_verified_employers' => 'boolean',
        'can_use_priority_application' => 'boolean',
        'can_view_exclusive_jobs' => 'boolean',
        'can_access_interview_coaching' => 'boolean',
        'can_view_salary_negotiation_tips' => 'boolean',
        'can_post_jobs' => 'boolean',
        'can_post_featured_jobs' => 'boolean',
        'can_post_urgent_jobs' => 'boolean',
        'can_use_job_analytics' => 'boolean',
        'can_manage_applications' => 'boolean',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function jobLocations()
    {
        return $this->hasMany(JobLocation::class, 'country_code', 'code');
    }

    public function jobPosts()
    {
        return $this->hasMany(JobPost::class, 'country_code', 'code');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Accessors
    public function getFlagEmojiAttribute()
    {
        return $this->flag ?? '🌍';
    }

    public function getStatusBadgeAttribute()
    {
        if ($this->is_active) {
            return '<span class="badge badge-light-success">Active</span>';
        }
        return '<span class="badge badge-light-danger">Inactive</span>';
    }

    public function getFullNameAttribute()
    {
        return $this->flag_emoji . ' ' . $this->name;
    }
}