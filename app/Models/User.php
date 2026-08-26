<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use App\Models\Job\JobPost;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasRoles, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'department_id',
        'name',
        'first_name',
        'last_name',
        'email',
        'avatar',
        'phone',
        'password',
        'role_id',
        'email_verified_at',
        'magic_link_token',
        'magic_link_sent_at',
        'magic_link_expires_at',
        'country_code',
        'bio',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'magic_link_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'magic_link_sent_at' => 'datetime',
            'magic_link_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Boot function to generate UUID on creating.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Set the user's name from first and last name.
     */
    public function setNameAttribute($value): void
    {
        $parts = explode(' ', $value, 2);
        $this->attributes['first_name'] = $parts[0];
        $this->attributes['last_name'] = $parts[1] ?? '';
        $this->attributes['name'] = $value;
    }

    /**
     * Check if user is a job seeker
     */
    public function isJobSeeker(): bool
    {
        return $this->hasRole('job_seeker');
    }

    /**
     * Check if user is an employer
     */
    public function isEmployer(): bool
    {
        return $this->hasRole('employer');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    /**
     * Get the user's role name
     */
    public function getRoleNameAttribute(): string
    {
        return $this->roles->first()?->name ?? 'job_seeker';
    }

    /**
     * Calculate profile completion percentage.
     */
    public function getProfileCompletionAttribute(): int
    {
        $fields = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'country_code',
            'department_id',
        ];
        
        $completed = 0;
        $total = count($fields) + 1; // +1 for bio field
        
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completed++;
            }
        }
        
        // Check bio field
        if (!empty($this->bio)) {
            $completed++;
        }
        
        return round(($completed / $total) * 100);
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->forceFill([
            'last_login_at' => now(),
        ])->save();
    }

    /**
     * Get avatar URL.
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar 
            ? (str_starts_with($this->avatar, 'http') ? $this->avatar : asset($this->avatar))
            : asset('assets/media/avatars/300-1.jpg');
    }

    /**
     * Check if magic link is valid.
     */
    public function hasValidMagicLink(): bool
    {
        return $this->magic_link_token && 
               $this->magic_link_expires_at && 
               now()->lt($this->magic_link_expires_at);
    }

    /**
     * Clear magic link token.
     */
    public function clearMagicLinkToken(): void
    {
        $this->forceFill([
            'magic_link_token' => null,
            'magic_link_sent_at' => null,
            'magic_link_expires_at' => null,
        ])->save();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Relationship: User belongs to a department
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relationship: User has one employee profile
     */
    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    /**
     * Relationship: User has one employee salary
     */
    public function employeeSalary()
    {
        return $this->hasOne(EmployeeSalary::class, 'user_id');
    }

    /**
     * Relationship: User has one employer profile
     */
    public function employerProfile()
    {
        return $this->hasOne(EmployerProfile::class);
    }

    /**
     * Relationship: User has one seeker profile
     */
    public function seekerProfile()
    {
        return $this->hasOne(SeekerProfile::class);
    }

    /**
     * Get the job posts created by this user (as poster)
     */
    public function jobPosts()
    {
        return $this->hasMany(JobPost::class, 'poster_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Check methods
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if user is an employee
     */
    public function isEmployee()
    {
        return $this->employee()->exists();
    }

    public function getEmployeeAttribute()
    {
        return $this->employee;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Scope for active users only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter users by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope for job seekers
     */
    public function scopeJobSeekers($query)
    {
        return $query->whereHas('roles', function($q) {
            $q->where('name', 'job_seeker');
        });
    }

    /**
     * Scope for employers
     */
    public function scopeEmployers($query)
    {
        return $query->whereHas('roles', function($q) {
            $q->where('name', 'employer');
        });
    }

    

}