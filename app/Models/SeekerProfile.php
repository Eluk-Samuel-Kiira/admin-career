<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Job\JobSeekerJob;

class SeekerProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'seeker_profiles';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'postal_code',
        'date_of_birth',
        'nationality',
        'professional_summary',
        'professional_title',
        'years_of_experience',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'skills',
        'languages',
        'certifications',
        'education',
        'work_experience',
        'projects',
        'cv_file_path',
        'cv_files',
        'cv_original_name',
        'job_preferences',
        'is_public',
        'is_active',

        'job_category_id',
        'industry_id',
        'job_type_id',
        'job_location_id',
        'preferred_country_code',
        'experience_level_id',
        'education_level_id',
        'salary_range_id',
        'profile_completed_at',

    ];

    protected $casts = [
        'skills' => 'array',
        'languages' => 'array',
        'certifications' => 'array',
        'education' => 'array',
        'work_experience' => 'array',
        'projects' => 'array',
        'job_preferences' => 'array',
        'cv_files' => 'array',
        'date_of_birth' => 'date',
        'years_of_experience' => 'integer',
        'is_public' => 'boolean',
        'is_active' => 'boolean',

        'job_category_id'       => 'integer',
        'industry_id'           => 'integer',
        'job_type_id'           => 'integer',
        'job_location_id'       => 'integer',
        'experience_level_id'   => 'integer',
        'education_level_id'    => 'integer',
        'salary_range_id'       => 'integer',
        'profile_completed_at'  => 'datetime',

    ];

    // =================================================================
    // Filter relationships
    // =================================================================
    public function jobCategory()
    {
        return $this->belongsTo(\App\Models\Job\JobCategory::class, 'job_category_id');
    }

    public function industry()
    {
        return $this->belongsTo(\App\Models\Job\Industry::class, 'industry_id');
    }

    public function jobType()
    {
        return $this->belongsTo(\App\Models\Job\JobType::class, 'job_type_id');
    }

    public function jobLocation()
    {
        return $this->belongsTo(\App\Models\Job\JobLocation::class, 'job_location_id');
    }

    public function experienceLevel()
    {
        return $this->belongsTo(\App\Models\Job\ExperienceLevel::class, 'experience_level_id');
    }

    public function educationLevel()
    {
        return $this->belongsTo(\App\Models\Job\EducationLevel::class, 'education_level_id');
    }

    public function salaryRange()
    {
        return $this->belongsTo(\App\Models\Job\SalaryRange::class, 'salary_range_id');
    }

    public function preferredCountry()
    {
        return $this->belongsTo(\App\Models\Job\Country::class, 'preferred_country_code', 'code');
    }


    /**
     * Get the user that owns the seeker profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobSeekerJobs()
    {
        return $this->hasMany(JobSeekerJob::class, 'seeker_profile_id');
    }

    public function savedJobs()
    {
        return $this->hasMany(JobSeekerJob::class, 'seeker_profile_id')->where('is_saved', true);
    }

    public function appliedJobs()
    {
        return $this->hasMany(JobSeekerJob::class, 'seeker_profile_id')->where('is_applied', true);
    }

    public function hasSavedJob($jobPostId)
    {
        return $this->jobSeekerJobs()->where('job_post_id', $jobPostId)->where('is_saved', true)->exists();
    }

    public function hasAppliedJob($jobPostId)
    {
        return $this->jobSeekerJobs()->where('job_post_id', $jobPostId)->where('is_applied', true)->exists();
    }

    public function getIsProfileCompleteAttribute(): bool
    {
        return $this->job_category_id
            && $this->industry_id
            && $this->job_location_id
            && $this->experience_level_id
            && $this->education_level_id
            && $this->professional_title
            && !empty($this->skills);
    }

    /**
     * Get the user's full name (from profile or user)
     */
    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name . ' ' . $this->last_name;
        }
        return $this->user?->full_name ?? 'Unknown';
    }

    /**
     * Get the user's first name from profile or user
     */
    public function getFirstNameAttribute($value)
    {
        return $value ?? $this->user?->first_name;
    }

    /**
     * Get the user's last name from profile or user
     */
    public function getLastNameAttribute($value)
    {
        return $value ?? $this->user?->last_name;
    }

    /**
     * Get the user's email from profile or user
     */
    public function getEmailAttribute($value)
    {
        return $value ?? $this->user?->email;
    }

    /**
     * Get the user's phone from profile or user
     */
    public function getPhoneAttribute($value)
    {
        return $value ?? $this->user?->phone;
    }

    /**
     * Get CV URL
     */
    public function getCvUrlAttribute(): ?string
    {
        return $this->cv_file_path 
            ? (str_starts_with($this->cv_file_path, 'http') ? $this->cv_file_path : asset('storage/' . $this->cv_file_path))
            : null;
    }

    /**
     * Get skills as an array
     */
    public function getSkillsArrayAttribute(): array
    {
        if (is_array($this->skills)) {
            return $this->skills;
        }
        if (is_string($this->skills)) {
            return array_values(array_filter(array_map('trim', explode(',', $this->skills))));
        }
        return [];
    }

    /**
     * Get languages as an array
     */
    public function getLanguagesArrayAttribute(): array
    {
        if (is_array($this->languages)) {
            return $this->languages;
        }
        if (is_string($this->languages)) {
            return array_values(array_filter(array_map('trim', explode(',', $this->languages))));
        }
        return [];
    }

    /**
     * Get certifications as an array
     */
    public function getCertificationsArrayAttribute(): array
    {
        if (is_array($this->certifications)) {
            return $this->certifications;
        }
        if (is_string($this->certifications)) {
            return array_values(array_filter(array_map('trim', explode(',', $this->certifications))));
        }
        return [];
    }

    /**
     * Get education as an array
     */
    public function getEducationArrayAttribute(): array
    {
        if (is_array($this->education)) {
            return $this->education;
        }
        if (is_string($this->education)) {
            // Try to decode JSON first
            $decoded = json_decode($this->education, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            // If not JSON, split by new lines or commas
            $items = array_filter(array_map('trim', preg_split('/[\n,]+/', $this->education)));
            return array_values($items);
        }
        return [];
    }

    /**
     * Get work experience as an array
     */
    public function getWorkExperienceArrayAttribute(): array
    {
        if (is_array($this->work_experience)) {
            return $this->work_experience;
        }
        if (is_string($this->work_experience)) {
            $decoded = json_decode($this->work_experience, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            return [];
        }
        return [];
    }

    /**
     * Get projects as an array
     */
    public function getProjectsArrayAttribute(): array
    {
        if (is_array($this->projects)) {
            return $this->projects;
        }
        if (is_string($this->projects)) {
            $decoded = json_decode($this->projects, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            return [];
        }
        return [];
    }

    /**
     * Get job preferences as an array
     */
    public function getJobPreferencesArrayAttribute(): array
    {
        if (is_array($this->job_preferences)) {
            return $this->job_preferences;
        }
        if (is_string($this->job_preferences)) {
            $decoded = json_decode($this->job_preferences, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            return [];
        }
        return [];
    }

    /**
     * Get experience in years with label
     */
    public function getExperienceLabelAttribute(): string
    {
        if (!$this->years_of_experience) {
            return 'Entry Level';
        }
        
        if ($this->years_of_experience < 1) {
            return 'Entry Level';
        }
        if ($this->years_of_experience < 3) {
            return 'Junior (' . $this->years_of_experience . ' years)';
        }
        if ($this->years_of_experience < 5) {
            return 'Mid-Level (' . $this->years_of_experience . ' years)';
        }
        if ($this->years_of_experience < 10) {
            return 'Senior (' . $this->years_of_experience . ' years)';
        }
        return 'Expert (' . $this->years_of_experience . ' years)';
    }

    /**
     * Scope for public profiles
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for active seekers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for verified seekers
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope for seekers by country
     */
    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope for seekers by city
     */
    public function scopeByCity($query, $city)
    {
        return $query->where('city', 'LIKE', "%{$city}%");
    }

    /**
     * Scope for seekers with minimum experience
     */
    public function scopeMinExperience($query, $years)
    {
        return $query->where('years_of_experience', '>=', $years);
    }
}