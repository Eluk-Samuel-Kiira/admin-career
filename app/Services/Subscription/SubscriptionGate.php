<?php

namespace App\Services\Subscription;

use App\Models\Subscription\Subscription;
use App\Models\User;

class SubscriptionGate
{
    protected array $cache = [];

    public function activeSubscription(User $user): ?Subscription
    {
        $key = 'sub.' . $user->id;

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $subscription = Subscription::with('service')
            ->where('user_id', $user->id)
            ->whereIn('status', ['trialing', 'active'])
            ->where('current_period_end', '>', now())
            ->latest('id')
            ->first();

        return $this->cache[$key] = $subscription;
    }

    /**
     * Plan identifier for display purposes. Returns the service_key of the
     * active subscription, or 'free' when there is none.
     */
    public function planFor(User $user): string
    {
        return $this->activeSubscription($user)?->service_key ?? 'free';
    }

    public function hasFeature(User $user, string $feature): bool
    {
        return $this->activeSubscription($user)?->hasFeature($feature) ?? false;
    }

    public function quota(User $user, string $feature): ?int
    {
        return $this->activeSubscription($user)?->featureQuota($feature) ?? 0;
    }

    public function canWhatsApp(User $user): bool
    {
        return $this->hasFeature($user, 'whatsapp_alerts');
    }

    public function canTelegram(User $user): bool
    {
        return $this->hasFeature($user, 'telegram_alerts');
    }

    public function cvScreeningQuota(User $user): ?int
    {
        return $this->quota($user, 'cv_screening');
    }

    public function hasAutoApply(User $user): bool
    {
        return ($this->autoApplyQuota($user) ?? 0) !== 0;
    }

    public function autoApplyQuota(User $user): ?int
    {
        return $this->quota($user, 'auto_apply');
    }

    public function forget(?User $user = null): void
    {
        if ($user) { unset($this->cache['sub.' . $user->id]); return; }
        $this->cache = [];
    }
}