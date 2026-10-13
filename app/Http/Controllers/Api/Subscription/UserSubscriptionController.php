<?php

namespace App\Http\Controllers\Api\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Subscription\Subscription;
use App\Models\Subscription\SubscriptionPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSubscriptionController extends Controller
{
    /**
     * GET /api/user/subscriptions
     * Full history for the authenticated user — subscriptions and payments.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Active subscription (used for the top card)
        $active = Subscription::with('service:id,key,name')
            ->where('user_id', $user->id)
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->orderByDesc('id')
            ->first();

        // Full subscription history — every row the user has ever had
        $subscriptions = Subscription::with('service:id,key,name')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn($s) => $this->formatSubscription($s));

        // Payments, newest first, grouped by their parent subscription
        $payments = SubscriptionPayment::where('user_id', $user->id)
            ->with('subscription.service:id,key,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => $this->formatPayment($p));

        // Aggregate counters
        $totalPaid = SubscriptionPayment::where('user_id', $user->id)
            ->where('status', 'succeeded')
            ->sum('amount_cents');

        return response()->json([
            'success' => true,
            'active' => $active ? $this->formatSubscription($active) : null,
            'subscriptions' => $subscriptions,
            'payments' => $payments,
            'summary' => [
                'total_paid_cents' => (int) $totalPaid,
                'total_payments'   => $payments->count(),
                'subscription_count' => $subscriptions->count(),
                'active_since'     => $active?->starts_at?->toISOString(),
            ],
        ]);
    }

    private function formatSubscription(Subscription $s): array
    {
        return [
            'uuid'                 => $s->uuid,
            'service_key'          => $s->service_key,
            'service_name'         => $s->service?->name ?? $s->service_key,
            'status'               => $s->status,
            'status_label'         => $this->subscriptionStatusLabel($s->status, $s->cancel_at_period_end),
            'is_active'            => $s->isActive(),
            'currency'             => $s->currency,
            'amount_formatted'     => strtoupper($s->currency) . ' ' . number_format($s->amount_cents / 100, $s->currency === 'UGX' ? 0 : 2),
            'interval'             => $s->interval,
            'starts_at'            => $s->starts_at?->toISOString(),
            'trial_ends_at'        => $s->trial_ends_at?->toISOString(),
            'current_period_start' => $s->current_period_start?->toISOString(),
            'current_period_end'   => $s->current_period_end?->toISOString(),
            'cancel_at_period_end' => (bool) $s->cancel_at_period_end,
            'cancelled_at'         => $s->cancelled_at?->toISOString(),
        ];
    }

    private function formatPayment(SubscriptionPayment $p): array
    {
        return [
            'uuid'               => $p->uuid,
            'subscription_uuid'  => $p->subscription?->uuid,
            'service_key'        => $p->subscription?->service_key,
            'service_name'       => $p->subscription?->service?->name ?? $p->subscription?->service_key,
            'gateway'            => $p->gateway,
            'reference'          => $p->gateway_reference,
            'currency'           => $p->currency,
            'amount_formatted'   => strtoupper($p->currency) . ' ' . number_format($p->amount_cents / 100, $p->currency === 'UGX' ? 0 : 2),
            'status'             => $p->status,
            'status_label'       => ucfirst($p->status),
            'failure_reason'     => $p->failure_reason,
            'paid_at'            => $p->paid_at?->toISOString(),
            'created_at'         => $p->created_at?->toISOString(),
        ];
    }

    private function subscriptionStatusLabel(string $status, bool $cancelAtPeriodEnd = false): string
    {
        if ($status === 'active' && $cancelAtPeriodEnd) {
            return 'Cancels at period end';
        }

        return match ($status) {
            'trialing'  => 'Free trial',
            'active'    => 'Active',
            'past_due'  => 'Payment failed',
            'cancelled' => 'Cancelled',
            'expired'   => 'Expired',
            default     => ucfirst($status),
        };
    }
}