<?php

namespace App\Models\Job;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExperienceLevel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'min_years',
        'max_years',
        'meta_title',
        'meta_description',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'min_years' => 'integer',
        'max_years' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($experienceLevel) {
            if (empty($experienceLevel->slug)) {
                $experienceLevel->slug = Str::slug($experienceLevel->name);
                // Ensure uniqueness
                $slug = $experienceLevel->slug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $experienceLevel->slug . '-' . $counter++;
                }
                $experienceLevel->slug = $slug;
            }
            
            if (empty($experienceLevel->created_by) && auth()->check()) {
                $experienceLevel->created_by = auth()->id();
            }
            
            // Set meta title if not provided
            if (empty($experienceLevel->meta_title)) {
                $experienceLevel->meta_title = "{$experienceLevel->name} Jobs - Experience Level";
            }
            
            // Set meta description if not provided
            if (empty($experienceLevel->meta_description)) {
                $yearsRange = $experienceLevel->min_years && $experienceLevel->max_years 
                    ? "{$experienceLevel->min_years}-{$experienceLevel->max_years} years" 
                    : ($experienceLevel->min_years ? "{$experienceLevel->min_years}+ years" : "various years");
                $experienceLevel->meta_description = "Find {$experienceLevel->name} positions requiring {$yearsRange} of experience. Browse career opportunities and job vacancies.";
            }
        });

        static::updating(function ($experienceLevel) {
            if ($experienceLevel->isDirty('name') && !$experienceLevel->isDirty('slug')) {
                $experienceLevel->slug = Str::slug($experienceLevel->name);
                $slug = $experienceLevel->slug;
                $counter = 1;
                while (static::where('slug', $slug)->where('id', '!=', $experienceLevel->id)->exists()) {
                    $slug = $experienceLevel->slug . '-' . $counter++;
                }
                $experienceLevel->slug = $slug;
            }
        });
    }

    /**
     * Get the display name with years range.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->min_years && $this->max_years) {
            return "{$this->name} ({$this->min_years}-{$this->max_years} years)";
        } elseif ($this->min_years) {
            return "{$this->name} ({$this->min_years}+ years)";
        } elseif ($this->max_years) {
            return "{$this->name} (Up to {$this->max_years} years)";
        }
        
        return $this->name;
    }

    /**
     * Get the years range as string.
     */
    public function getYearsRangeAttribute(): ?string
    {
        if ($this->min_years && $this->max_years) {
            return "{$this->min_years} - {$this->max_years} years";
        } elseif ($this->min_years) {
            return "{$this->min_years}+ years";
        } elseif ($this->max_years) {
            return "0 - {$this->max_years} years";
        }
        
        return null;
    }

    /**
     * Check if this experience level is entry level.
     */
    public function getIsEntryLevelAttribute(): bool
    {
        return $this->min_years === null || $this->min_years <= 1;
    }

    /**
     * Check if this experience level is senior level.
     */
    public function getIsSeniorLevelAttribute(): bool
    {
        return $this->min_years !== null && $this->min_years >= 5;
    }

    /**
     * Get SEO attributes for the experience level.
     */
    public function getSeoAttributes(): array
    {
        return [
            'title' => $this->meta_title ?? "{$this->name} Jobs",
            'description' => $this->meta_description ?? "Find {$this->name} positions. Browse job opportunities requiring {$this->years_range} of experience.",
            'keywords' => "{$this->name} jobs, {$this->years_range} experience jobs, career opportunities",
        ];
    }

    /**
     * Get the URL for this experience level.
     */
    public function getUrlAttribute(): string
    {
        return url("/experience/{$this->slug}");
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

    public function scopeEntryLevel($query)
    {
        return $query->where(function($q) {
            $q->whereNull('min_years')
              ->orWhere('min_years', '<=', 1);
        });
    }

    public function scopeMidLevel($query)
    {
        return $query->where('min_years', '>=', 2)
                     ->where('min_years', '<=', 4);
    }

    public function scopeSeniorLevel($query)
    {
        return $query->where('min_years', '>=', 5);
    }

    public function scopeMinYears($query, $years)
    {
        return $query->where('min_years', '<=', $years)
                     ->where(function($q) use ($years) {
                         $q->whereNull('max_years')
                           ->orWhere('max_years', '>=', $years);
                     });
    }

    public function scopeMaxYears($query, $years)
    {
        return $query->where('max_years', '<=', $years);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'LIKE', "%{$search}%")
                     ->orWhere('description', 'LIKE', "%{$search}%");
    }

    // Relationships
    public function jobs()
    {
        return $this->hasMany(JobPost::class, 'experience_level_id');
    }

    public function jobPosts()
    {
        return $this->hasMany(JobPost::class, 'experience_level_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getActiveJobsCountAttribute(): int
    {
        return $this->jobs()->where('is_active', true)->count();
    }

    public function getMinYearsValueAttribute(): int
    {
        return $this->min_years ?? 0;
    }

    public function getMaxYearsValueAttribute(): int
    {
        return $this->max_years ?? 99;
    }

    // Methods
    public function matchesYears(int $years): bool
    {
        $minYears = $this->min_years ?? 0;
        $maxYears = $this->max_years ?? 99;
        
        return $years >= $minYears && $years <= $maxYears;
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'years_range' => $this->years_range,
            'display_name' => $this->display_name,
            'min_years' => $this->min_years,
            'max_years' => $this->max_years,
            'jobs_count' => $this->whenLoaded('jobs', function() {
                return $this->jobs->count();
            }, 0),
            'url' => $this->url,
        ];
    }
}