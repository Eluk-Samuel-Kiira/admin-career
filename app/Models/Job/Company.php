<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'logo_path',
        'description',
        'website',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address1',
        'company_size',
        'industry_id',
        'location_id',
        'created_by',
        'legacy_id',
        'country_code',
        'legacy_alias',
        'legacy_uid',
        'legacy_metadata',
        'is_active',
        'is_verified',
        'is_gold',
        'is_featured',
        'gold_start_date',
        'gold_end_date',
        'featured_start_date',
        'featured_end_date',
        'package_id',
        'payment_history_id',
        'hits',
        'migrated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'is_gold' => 'boolean',
        'is_featured' => 'boolean',
        'gold_start_date' => 'datetime',
        'gold_end_date' => 'date',
        'featured_start_date' => 'datetime',
        'featured_end_date' => 'date',
        'legacy_metadata' => 'array',
        'migrated_at' => 'datetime',
        'hits' => 'integer',
    ];

    // Relationships
    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }

    public function location()
    {
        return $this->belongsTo(JobLocation::class, 'location_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function jobs()
    {
        return $this->hasMany(JobPost::class);
    }

    /**
     * Get the logo URL - Handles both legacy and new companies
     */
    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return asset('assets/media/avatars/blank.png');
        }

        $countryCode = strtolower($this->country_code ?? 'au');
        $logoName = $this->logo;

        // Check if logo_path exists (new upload path)
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            return asset('storage/' . $this->logo_path);
        }

        // For legacy companies (with legacy_id)
        if ($this->legacy_id) {
            $legacyPath = "{$countryCode}-companies/comp_{$this->legacy_id}/logo/{$logoName}";
            if (Storage::disk('public')->exists($legacyPath)) {
                return asset('storage/' . $legacyPath);
            }
        }

        // For new companies (without legacy_id) - stored by ID
        if ($this->id) {
            $newPath = "{$countryCode}-companies/{$this->id}/logo/{$logoName}";
            if (Storage::disk('public')->exists($newPath)) {
                return asset('storage/' . $newPath);
            }
        }

        // Fallback: check if logo exists directly in the country folder
        $directPath = "{$countryCode}-companies/{$logoName}";
        if (Storage::disk('public')->exists($directPath)) {
            return asset('storage/' . $directPath);
        }

        return asset('assets/media/avatars/blank.png');
    }

    /**
     * Get logo HTML for display
     */
    public function getLogoHtmlAttribute()
    {
        $logoUrl = $this->logo_url;
        if ($logoUrl && $logoUrl !== asset('assets/media/avatars/blank.png')) {
            return '<img src="' . $logoUrl . '" alt="' . e($this->name) . '" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" />';
        }
        $firstLetter = $this->name ? strtoupper(substr($this->name, 0, 1)) : '?';
        return '<div class="symbol symbol-40px symbol-circle bg-light-primary"><span class="symbol-label fs-3 fw-bold text-primary">' . $firstLetter . '</span></div>';
    }

    public function getStatusBadgeAttribute()
    {
        if ($this->is_active) {
            return '<span class="badge badge-light-success">Active</span>';
        }
        return '<span class="badge badge-light-danger">Inactive</span>';
    }

    public function getVerifiedBadgeAttribute()
    {
        if ($this->is_verified) {
            return '<span class="badge badge-light-success">Verified</span>';
        }
        return '<span class="badge badge-light-warning">Unverified</span>';
    }

    public function getMigrationBadgeAttribute()
    {
        if ($this->migrated_at) {
            return '<span class="badge badge-light-success">Migrated</span>';
        }
        return '<span class="badge badge-light-warning">Pending</span>';
    }

    public function getGoldBadgeAttribute()
    {
        if ($this->is_gold) {
            return '<span class="badge badge-light-primary">Gold</span>';
        }
        return null;
    }

    public function getFeaturedBadgeAttribute()
    {
        if ($this->is_featured) {
            return '<span class="badge badge-light-info">Featured</span>';
        }
        return null;
    }

    public function getCompanySizeLabelAttribute()
    {
        $sizes = [
            '1-10' => '1-10 employees',
            '11-50' => '11-50 employees',
            '51-200' => '51-200 employees',
            '201-500' => '201-500 employees',
            '500+'=> '500+ employees',
        ];
        return $sizes[$this->company_size] ?? $this->company_size ?? 'N/A';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeGold($query)
    {
        return $query->where('is_gold', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCountry($query, $countryCode)
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    public function scopeLegacy($query)
    {
        return $query->whereNotNull('legacy_id');
    }

    public function scopeMigrated($query)
    {
        return $query->whereNotNull('migrated_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('migrated_at');
    }
}