<?php

namespace App\Models\Job;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SocialMediaPlatform extends Model
{
    use HasFactory;

    protected $table = 'social_media_platforms';

    protected $fillable = [
        'name',
        'slug',
        'platform',
        'url',
        'icon',
        'description',
        'handle',
        'country_code',
        'is_active',
        'is_verified',
        'is_featured',
        'sort_order',
        'meta_title',
        'meta_description',
        'created_by',
    ];

    protected $casts = [
        'sort_order'      => 'integer',
        'is_active'       => 'boolean',
        'is_verified'     => 'boolean',
        'is_featured'     => 'boolean',
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

    public function followerHistories()
    {
        return $this->hasMany(SocialMediaFollowerHistory::class);
    }

    public function latestFollowerRecord()
    {
        return $this->hasOne(SocialMediaFollowerHistory::class)->latest('recorded_at');
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

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Accessors
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
            return '<span class="badge badge-light-info">Verified</span>';
        }
        return '<span class="badge badge-light-secondary">Pending</span>';
    }

    public function getFeaturedBadgeAttribute()
    {
        if ($this->is_featured) {
            return '<span class="badge badge-light-warning">Featured</span>';
        }
        return null;
    }

    public function getPlatformIconAttribute()
    {
        $icons = [
            'facebook' => 'ki-facebook',
            'twitter' => 'ki-twitter',
            'instagram' => 'ki-instagram',
            'linkedin' => 'ki-linkedin',
            'youtube' => 'ki-youtube',
            'whatsapp' => 'ki-whatsapp',
            'tiktok' => 'ki-tiktok',
            'telegram' => 'ki-telegram',
        ];
        return $icons[$this->platform] ?? 'ki-share';
    }

    public function getPlatformColorAttribute()
    {
        $colors = [
            'facebook' => '#1877F2',
            'twitter' => '#000000',
            'instagram' => '#E4405F',
            'linkedin' => '#0A66C2',
            'youtube' => '#FF0000',
            'whatsapp' => '#25D366',
            'tiktok' => '#000000',
            'telegram' => '#26A5E4',
        ];
        return $colors[$this->platform] ?? '#6c757d';
    }

    /**
     * Get current followers count
     */
    public function getCurrentFollowersAttribute()
    {
        $latest = $this->latestFollowerRecord;
        return $latest ? $latest->followers_count : 0;
    }

    /**
     * Get followers change (increase/decrease)
     */
    public function getFollowersChangeAttribute()
    {
        $latest = $this->latestFollowerRecord;
        $previous = $this->followerHistories()
            ->where('recorded_at', '<', $latest?->recorded_at ?? now())
            ->latest('recorded_at')
            ->first();

        if (!$latest || !$previous) {
            return 0;
        }

        return $latest->followers_count - $previous->followers_count;
    }

    /**
     * Get percentage change
     */
    public function getFollowersPercentageChangeAttribute()
    {
        $latest = $this->latestFollowerRecord;
        $previous = $this->followerHistories()
            ->where('recorded_at', '<', $latest?->recorded_at ?? now())
            ->latest('recorded_at')
            ->first();

        if (!$latest || !$previous || $previous->followers_count == 0) {
            return 0;
        }

        return round((($latest->followers_count - $previous->followers_count) / $previous->followers_count) * 100, 2);
    }

    /**
     * Record a new follower count
     */
    public function recordFollowers(int $count, ?string $note = null)
    {
        return $this->followerHistories()->create([
            'followers_count' => $count,
            'recorded_at' => now(),
            'note' => $note,
        ]);
    }

    // Boot method for slug generation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $slug = Str::slug($model->name);
                $count = static::where('slug', 'like', $slug . '%')->count();
                $model->slug = $count ? $slug . '-' . ($count + 1) : $slug;
            }
        });
    }
}