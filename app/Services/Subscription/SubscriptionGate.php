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
        'free' => [
            'auto_apply' => 0,
        ],
        'standard' => [
            'cv_screening'    => 200,
            'whatsapp_alerts' => true,
            'telegram_alerts' => true,
            'auto_apply'      => 30,
        ],
        'premium' => [
            'cv_screening'    => 1000,
            'whatsapp_alerts' => true,
            'telegram_alerts' => true,
            'auto_apply'      => 100,
        ],
        'enterprise' => [
            'cv_screening'    => null,
            'whatsapp_alerts' => true,
            'telegram_alerts' => true,
            'auto_apply'      => null,   // unlimited
        ],
    ];

    public function canWhatsApp(User $user): bool
    {
        return (bool) (self::FEATURES_BY_PLAN[$this->planFor($user)]['whatsapp_alerts'] ?? false);
    }

    public function canTelegram(User $user): bool
    {
        return (bool) (self::FEATURES_BY_PLAN[$this->planFor($user)]['telegram_alerts'] ?? false);
    }

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

    /**
     * Is auto-apply enabled on this user's plan?
     */
    public function hasAutoApply(User $user): bool
    {
        return $this->autoApplyQuota($user) !== 0;
    }

    /**
     * Monthly auto-apply quota. Null = unlimited, 0 = disabled.
     */
    public function autoApplyQuota(User $user): ?int
    {
        $plan = $this->planFor($user);
        return self::FEATURES_BY_PLAN[$plan]['auto_apply'] ?? 0;
    }
}