<?php

namespace App\Models\Job;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\CountryHelper;

class SalaryRange extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'min_salary',
        'max_salary',
        'currency',
        'country_code',
        'meta_title',
        'meta_description',
        'is_active',
        'sort_order',
        'created_by'
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($salaryRange) {
            if (empty($salaryRange->slug)) {
                $salaryRange->slug = Str::slug($salaryRange->name);
                $slug = $salaryRange->slug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $salaryRange->slug . '-' . $counter++;
                }
                $salaryRange->slug = $slug;
            }
            
            if (empty($salaryRange->created_by) && auth()->check()) {
                $salaryRange->created_by = auth()->id();
            }
            
            // Set default currency based on country if not set
            if (empty($salaryRange->currency) && !empty($salaryRange->country_code)) {
                $salaryRange->currency = CountryHelper::getCountryCurrency($salaryRange->country_code);
            }
        });

        static::updating(function ($salaryRange) {
            if ($salaryRange->isDirty('country_code') && !$salaryRange->isDirty('currency')) {
                $salaryRange->currency = CountryHelper::getCountryCurrency($salaryRange->country_code);
            }
        });
    }

    /**
     * Get currency symbol from database
     */
    public function getCurrencySymbolAttribute(): string
    {
        $country = CountryHelper::getCountry($this->country_code);
        return $country?->currency_symbol ?? $this->currency ?? '$';
    }

    /**
     * Get currency for a given country code
     */
    public static function getCurrencyForCountry(string $countryCode): string
    {
        return CountryHelper::getCountryCurrency($countryCode);
    }

    /**
     * Get country name
     */
    public static function getCountryName(string $countryCode): string
    {
        return CountryHelper::getCountryName($countryCode);
    }

    /**
     * Get country flag
     */
    public static function getCountryFlag(string $countryCode): string
    {
        return CountryHelper::getCountryFlag($countryCode);
    }

    /**
     * Get all available countries
     */
    public static function getAvailableCountries(): array
    {
        return CountryHelper::getCountriesWithFlags();
    }

    /**
     * Get all available countries with currency
     */
    public static function getAvailableCountriesWithCurrency(): array
    {
        $countries = [];
        $allCountries = CountryHelper::getActiveCountries();
        
        foreach ($allCountries as $country) {
            $countries[$country->code] = [
                'name' => $country->name,
                'flag' => $country->flag_emoji,
                'currency' => $country->currency,
                'currency_symbol' => $country->currency_symbol,
            ];
        }
        
        return $countries;
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

    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

    public function scopeMinSalary($query, $amount)
    {
        return $query->where('min_salary', '>=', $amount);
    }

    public function scopeMaxSalary($query, $amount)
    {
        return $query->where('max_salary', '<=', $amount);
    }

    public function scopeSalaryBetween($query, $min, $max)
    {
        return $query->where('min_salary', '>=', $min)
                     ->where('max_salary', '<=', $max);
    }

    // Accessors
    public function getDisplayNameAttribute()
    {
        if ($this->min_salary && $this->max_salary) {
            $flag = $this->country_flag;
            return "{$flag} {$this->name} ({$this->currency})";
        }
        return $this->name;
    }

    public function getFormattedRangeAttribute()
    {
        $symbol = $this->currency_symbol;
        
        if ($this->min_salary && $this->max_salary) {
            return "{$symbol}{$this->min_salary} - {$symbol}{$this->max_salary} {$this->currency}";
        } elseif ($this->min_salary) {
            return "{$symbol}{$this->min_salary}+ {$this->currency}";
        } elseif ($this->max_salary) {
            return "Up to {$symbol}{$this->max_salary} {$this->currency}";
        }
        return "Negotiable";
    }

    public function getCountryNameAttribute()
    {
        return CountryHelper::getCountryName($this->country_code);
    }

    public function getCountryFlagAttribute()
    {
        return CountryHelper::getCountryFlag($this->country_code);
    }

    public function getSeoAttributes()
    {
        $countryName = $this->country_name;
        return [
            'title' => $this->meta_title ?? "{$this->name} Jobs in {$countryName}",
            'description' => $this->meta_description ?? "Find jobs paying {$this->name} in {$countryName}. Browse career opportunities with salaries ranging from {$this->formatted_range}.",
            'keywords' => "{$this->name} jobs, {$this->name} salary, jobs in {$countryName}, {$this->currency} jobs"
        ];
    }

    public function getUrlAttribute()
    {
        return url("/{$this->country_code}/salary/{$this->slug}");
    }

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function jobPosts()
    {
        return $this->hasMany(JobPost::class, 'salary_range_id');
    }
}