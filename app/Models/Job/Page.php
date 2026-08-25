<?php

namespace App\Models\Job;

use App\Models\User;
use App\Models\Job\Country;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $table = 'pages';

    protected $fillable = [
        'slug',
        'title',
        'content',
        'meta_title',
        'meta_description',
        'is_active',
        'is_featured',
        'sort_order',
        'template',
        'featured_image',
        'created_by',
        'country_code',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
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

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Accessors
    public function getExcerptAttribute()
    {
        return Str::limit(strip_tags($this->content), 160);
    }

    public function getUrlAttribute()
    {
        return route('pages.show', $this->slug);
    }

    public function getStatusBadgeAttribute()
    {
        if ($this->is_active) {
            return '<span class="badge badge-light-success">Active</span>';
        }
        return '<span class="badge badge-light-danger">Inactive</span>';
    }
}