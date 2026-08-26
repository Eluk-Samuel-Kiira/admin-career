<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployerProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employer_profiles';

    protected $fillable = [
        'user_id',
        'company_name',
        'company_logo',
        'company_website',
        'company_description',
        'company_size',
        'contact_name',
        'contact_position',
        'contact_phone',
        'contact_email',
        'industry',
        'address',
        'city',
        'state',
        'country_code',
        'postal_code',
        'is_verified',
        'is_active',
        'verification_status',
        'subscription_plan',
        'subscription_expires_at',
        'job_postings_remaining',
        'featured_until',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'subscription_expires_at' => 'datetime',
        'featured_until' => 'datetime',
        'job_postings_remaining' => 'integer',
    ];

    /**
     * Get the user that owns the employer profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the jobs posted by this employer
     */
    public function jobPosts()
    {
        return $this->hasMany(JobPost::class, 'employer_id');
    }

    /**
     * Scope for verified employers
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for active employers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get full company address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country_code,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get company logo URL
     */
    public function getLogoUrlAttribute(): string
    {
        return $this->company_logo 
            ? (str_starts_with($this->company_logo, 'http') ? $this->company_logo : asset('storage/' . $this->company_logo))
            : asset('assets/media/avatars/blank.png');
    }

    /**
     * Check if subscription is active
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_expires_at && $this->subscription_expires_at->isFuture();
    }

    /**
     * Check if employer can post more jobs
     */
    public function canPostJob(): bool
    {
        return $this->is_active && ($this->job_postings_remaining === null || $this->job_postings_remaining > 0);
    }
}