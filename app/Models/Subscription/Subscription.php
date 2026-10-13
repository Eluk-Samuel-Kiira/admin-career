<?php

namespace App\Models\Subscription;

use App\Models\Service\Service;
use App\Models\Service\ServicePrice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscription extends Model
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'uuid', 'user_id', 'service_key',
        'price_id', 'country_code', 'currency', 'amount_cents', 'interval',
        'status', 'trial_ends_at', 'starts_at',
        'current_period_start', 'current_period_end',
        'cancelled_at', 'ends_at', 'cancel_at_period_end',
        'feature_snapshot',
        'gateway', 'gateway_customer_id', 'gateway_subscription_id',
    ];

    protected $casts = [
        'trial_ends_at'        => 'datetime',
        'starts_at'            => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end'   => 'datetime',
        'cancelled_at'         => 'datetime',
        'ends_at'              => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'feature_snapshot'     => 'array',
        'amount_cents'         => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) $model->uuid = (string) Str::uuid();
        });
    }

    // ── Relations ─────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_key', 'key');
    }

    public function price()
    {
        return $this->belongsTo(ServicePrice::class, 'price_id');
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    // ── Status ────────────────────────────────────────────────
    public function isActive(): bool
    {
        if (!in_array($this->status, ['trialing', 'active'], true)) {
            return false;
        }

        if ($this->status === 'trialing' && $this->trial_ends_at && $this->trial_ends_at->isPast()) {
            return false;
        }

        return $this->current_period_end && $this->current_period_end->isFuture();
    }

    // ── Feature checks ────────────────────────────────────────
    public function hasFeature(string $feature): bool
    {
        return array_key_exists($feature, $this->feature_snapshot ?? []);
    }

    public function featureQuota(string $feature): ?int
    {
        $value = $this->feature_snapshot[$feature] ?? null;
        if ($value === null) return 0;
        if ($value === true) return null;
        return is_numeric($value) ? (int) $value : 0;
    }

    // ── Accessors ─────────────────────────────────────────────
    public function getFormattedAmountAttribute(): string
    {
        return strtoupper($this->currency) . ' ' . number_format($this->amount_cents / 100, $this->currency === 'UGX' ? 0 : 2);
    }
}