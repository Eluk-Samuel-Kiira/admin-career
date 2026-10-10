<?php

namespace App\Services\Subscription;

use App\Models\User;

class SubscriptionGate
{
    /**
     * Which plan features are unlocked for this user.
     * Stub for now — replace with a real lookup once billing is live.
     */
    protected const FEATURES_BY_PLAN = [
        'free'       => [],
        'standard'   => ['cv_screening' => 200],     // 200 CVs per month
        'premium'    => ['cv_screening' => 1000],
        'enterprise' => ['cv_screening' => null],    // unlimited
    ];

    public function planFor(User $user): string
    {
        // TODO: replace with real plan lookup
        // return $user->company?->subscription?->plan ?? 'free';
        return 'enterprise';
    }

    public function hasFeature(User $user, string $feature): bool
    {
        $plan = $this->planFor($user);
        return array_key_exists($feature, self::FEATURES_BY_PLAN[$plan] ?? []);
    }

    /**
     * Monthly CV quota. Null = unlimited.
     */
    public function cvScreeningQuota(User $user): ?int
    {
        $plan = $this->planFor($user);
        return self::FEATURES_BY_PLAN[$plan]['cv_screening'] ?? 0;
    }
}