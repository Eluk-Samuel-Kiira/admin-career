<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Job\JobPost;
use App\Helpers\CountryHelper;

class JobCategory extends Model
{
    use HasFactory;

    protected $table = 'job_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'legacy_id',
        'country_code',
        'legacy_alias',
        'legacy_cat_value',
        'meta_title',
        'meta_description',
        'icon',
        'color',
        'is_active',
        'is_default',
        'sort_order',
        'legacy_metadata',
        'migrated_at',
        'migrated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'legacy_metadata' => 'array',
        'migrated_at' => 'datetime',
    ];

    // Relationships
    public function migratedBy()
    {
        return $this->belongsTo(User::class, 'migrated_by');
    }

    public function jobs()
    {
        return $this->hasMany(JobPost::class, 'job_category_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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

    // Accessors
    public function getStatusBadgeAttribute()
    {
        if ($this->is_active) {
            return '<span class="badge badge-light-success">Active</span>';
        }
        return '<span class="badge badge-light-danger">Inactive</span>';
    }

    public function getMigrationStatusBadgeAttribute()
    {
        if ($this->migrated_at) {
            return '<span class="badge badge-light-success">Migrated</span>';
        }
        return '<span class="badge badge-light-warning">Pending</span>';
    }

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

    public function getCountryCurrencyAttribute()
    {
        return CountryHelper::getCountryCurrency($this->country_code);
    }

    public function getPhoneCodeAttribute()
    {
        return CountryHelper::getPhoneCode($this->country_code);
    }
}