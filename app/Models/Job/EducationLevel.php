<?php

namespace App\Models\Job;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\CountryHelper;

class EducationLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'country_code',
        'meta_title',
        'meta_description',
        'is_active',
        'sort_order',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($educationLevel) {
            if (empty($educationLevel->slug)) {
                $educationLevel->slug = Str::slug($educationLevel->name . '-' . $educationLevel->country_code);
                $slug = $educationLevel->slug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $educationLevel->slug . '-' . $counter++;
                }
                $educationLevel->slug = $slug;
            }
            
            if (empty($educationLevel->meta_title)) {
                $countryName = CountryHelper::getCountryName($educationLevel->country_code);
                $educationLevel->meta_title = "{$educationLevel->name} Jobs in {$countryName} - Education Requirements";
            }
            
            if (empty($educationLevel->created_by) && auth()->check()) {
                $educationLevel->created_by = auth()->id();
            }
        });
    }

    /**
     * Get country name using CountryHelper
     */
    public static function getCountryName(string $countryCode): string
    {
        return CountryHelper::getCountryName($countryCode);
    }

    /**
     * Get country flag using CountryHelper
     */
    public static function getCountryFlag(string $countryCode): string
    {
        return CountryHelper::getCountryFlag($countryCode);
    }

    /**
     * Get all available countries using CountryHelper
     */
    public static function getAvailableCountries(): array
    {
        return CountryHelper::getCountriesWithFlags();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCountry($query, $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'LIKE', "%{$search}%")
                     ->orWhere('description', 'LIKE', "%{$search}%");
    }

    // Accessors
    public function getCountryNameAttribute()
    {
        return CountryHelper::getCountryName($this->country_code);
    }

    public function getCountryFlagAttribute()
    {
        return CountryHelper::getCountryFlag($this->country_code);
    }

    public function getDisplayNameAttribute()
    {
        $flag = $this->country_flag;
        return "{$flag} {$this->name} ({$this->country_code})";
    }

    public function getSeoAttributes()
    {
        $countryName = $this->country_name;
        return [
            'title' => $this->meta_title,
            'description' => $this->meta_description,
            'keywords' => "{$this->name} jobs {$countryName}, {$this->name} required, {$this->name} qualifications, education level {$countryName}"
        ];
    }

    public function getUrlAttribute()
    {
        return url("/{$this->country_code}/education/{$this->slug}");
    }

    // Relationships
    public function jobPosts()
    {
        return $this->hasMany(JobPost::class, 'education_level_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}