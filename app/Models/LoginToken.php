<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class LoginToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isValid()
    {
        // Check if token is not used and not expired
        return !$this->used_at && $this->expires_at && $this->expires_at->isFuture();
    }

    public function markAsUsed()
    {
        $this->update(['used_at' => now()]);
    }

    /**
     * Check if token is expired (hard expiry)
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if token is older than 7 days (absolute max lifetime)
     */
    public function isTooOld(): bool
    {
        return $this->created_at && $this->created_at->diffInDays(now()) > 7;
    }

    /**
     * Delete all expired or used tokens for a user
     */
    public static function cleanupUserTokens(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where(function ($query) {
                $query->where('expires_at', '<', now())
                    ->orWhereNotNull('used_at')
                    ->orWhere('created_at', '<', now()->subDays(7));
            })
            ->delete();
    }

    /**
     * Delete ALL expired or used tokens across all users
     */
    public static function cleanupAllTokens(): int
    {
        return self::where(function ($query) {
            $query->where('expires_at', '<', now())
                ->orWhereNotNull('used_at')
                ->orWhere('created_at', '<', now()->subDays(7));
        })->delete();
    }

    /**
     * Scope for valid tokens only
     */
    public function scopeValid($query)
    {
        return $query->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->where('created_at', '>', now()->subDays(7));
    }

    /**
     * Scope for expired tokens
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('expires_at', '<', now())
                ->orWhereNotNull('used_at')
                ->orWhere('created_at', '<', now()->subDays(7));
        });
    }
}