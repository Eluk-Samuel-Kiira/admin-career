<?php

namespace App\Models\Job;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Job\JobPost;

class JobType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'meta_title',
        'meta_description',
        'is_active',
        'sort_order',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Common job type icons
    protected const ICON_MAP = [
        'Full-time' => 'ki-briefcase',
        'Part-time' => 'ki-time',
        'Contract' => 'ki-file',
        'Temporary' => 'ki-calendar',
        'Internship' => 'ki-graduation-2',
        'Freelance' => 'ki-laptop',
        'Remote' => 'ki-home-2',
        'Hybrid' => 'ki-building-2',
        'Shift' => 'ki-night',
        'Volunteer' => 'ki-heart',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jobType) {
            if (empty($jobType->slug)) {
                $jobType->slug = Str::slug($jobType->name);
                // Ensure uniqueness
                $slug = $jobType->slug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $jobType->slug . '-' . $counter++;
                }
                $jobType->slug = $slug;
            }
            
            if (empty($jobType->meta_title)) {
                $jobType->meta_title = "{$jobType->name} Jobs - Employment Opportunities";
            }
            
            if (empty($jobType->meta_description)) {
                $jobType->meta_description = "Find {$jobType->name} jobs and employment opportunities. Browse career positions across various industries and companies.";
            }
            
            // Auto-set icon if not provided and matches common types
            if (empty($jobType->icon) && isset(self::ICON_MAP[$jobType->name])) {
                $jobType->icon = self::ICON_MAP[$jobType->name];
            }
            
            if (empty($jobType->created_by) && auth()->check()) {
                $jobType->created_by = auth()->id();
            }
        });

        static::updating(function ($jobType) {
            if ($jobType->isDirty('name') && !$jobType->isDirty('slug')) {
                $jobType->slug = Str::slug($jobType->name);
                $slug = $jobType->slug;
                $counter = 1;
                while (static::where('slug', $slug)->where('id', '!=', $jobType->id)->exists()) {
                    $slug = $jobType->slug . '-' . $counter++;
                }
                $jobType->slug = $slug;
            }
            
            // Auto-set icon if name changed and matches common types
            if ($jobType->isDirty('name') && empty($jobType->icon) && isset(self::ICON_MAP[$jobType->name])) {
                $jobType->icon = self::ICON_MAP[$jobType->name];
            }
        });
    }

    /**
     * Get the icon HTML
     */
    public function getIconHtmlAttribute(): string
    {
        if ($this->icon) {
            return '<i class="ki-duotone ' . $this->icon . ' fs-2"><span class="path1"></span><span class="path2"></span></i>';
        }
        return '<i class="ki-duotone ki-briefcase fs-2"><span class="path1"></span><span class="path2"></span></i>';
    }

    /**
     * Get icon class
     */
    public function getIconClassAttribute(): string
    {
        return $this->icon ?? 'ki-briefcase';
    }

    /**
     * Get display name with icon
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get SEO attributes
     */
    public function getSeoAttributes(): array
    {
        return [
            'title' => $this->meta_title,
            'description' => $this->meta_description,
            'keywords' => "{$this->name} jobs, {$this->name} employment, {$this->name} opportunities, career jobs"
        ];
    }

    /**
     * Get the URL for this job type
     */
    public function getUrlAttribute(): string
    {
        return url("/jobs/type/{$this->slug}");
    }

    /**
     * Get available icons list
     */
    public static function getAvailableIcons(): array
    {
        return [
            'ki-people' => 'People',
            'ki-time' => 'Time',
            'ki-file' => 'File',
            'ki-calendar' => 'Calendar',
            'ki-graduation-2' => 'Graduation',
            'ki-laptop' => 'Laptop',
            'ki-home-2' => 'Home',
            'ki-building-2' => 'Building',
            'ki-night' => 'Night',
            'ki-heart' => 'Heart',
            'ki-briefcase' => 'Briefcase',
            'ki-chart' => 'Chart',
            'ki-code' => 'Code',
            'ki-design' => 'Design',
            'ki-rocket' => 'Rocket',
            'ki-user' => 'User',
            'ki-group' => 'Group',
            'ki-shield' => 'Shield',
            'ki-tag' => 'Tag',
            'ki-star' => 'Star',
            'ki-cloud' => 'Cloud',
            'ki-server' => 'Server',
            'ki-database' => 'Database',
            'ki-pencil' => 'Pencil',
            'ki-pen' => 'Pen',
            'ki-document' => 'Document',
            'ki-book' => 'Book',
            'ki-education' => 'Education',
            'ki-medical' => 'Medical',
            'ki-cash' => 'Cash',
            'ki-coin' => 'Coin',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('sort_order', '<=', 5);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'LIKE', "%{$search}%")
                     ->orWhere('description', 'LIKE', "%{$search}%");
    }

    // Relationships
    public function jobPosts()
    {
        return $this->hasMany(JobPost::class, 'job_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getActiveJobsCountAttribute(): int
    {
        return $this->jobPosts()->where('is_active', true)->count();
    }

    public function getTotalJobsCountAttribute(): int
    {
        return $this->jobPosts()->count();
    }

    /**
     * To API array
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'icon_class' => $this->icon_class,
            'jobs_count' => $this->whenLoaded('jobPosts', function() {
                return $this->jobPosts->count();
            }, 0),
            'url' => $this->url,
        ];
    }
}