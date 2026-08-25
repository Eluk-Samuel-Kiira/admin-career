<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialMediaFollowerHistory extends Model
{
    use HasFactory;

    protected $table = 'social_media_follower_histories';

    protected $fillable = [
        'social_media_platform_id',
        'followers_count',
        'recorded_at',
        'note',
    ];

    protected $casts = [
        'followers_count' => 'integer',
        'recorded_at' => 'datetime',
    ];

    // Relationships
    public function platform()
    {
        return $this->belongsTo(SocialMediaPlatform::class, 'social_media_platform_id');
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('recorded_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('recorded_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('recorded_at', now()->month)
            ->whereYear('recorded_at', now()->year);
    }

    // Accessors
    public function getFormattedRecordedAtAttribute()
    {
        return $this->recorded_at ? $this->recorded_at->format('Y-m-d H:i:s') : null;
    }
}