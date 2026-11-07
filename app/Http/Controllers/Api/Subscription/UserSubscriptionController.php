<?php

namespace App\Http\Controllers\Api\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Subscription\Subscription;
use App\Models\Subscription\SubscriptionPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSubscriptionController extends Controller
{
    /**
     * GET /api/user/subscriptions
     *
     * Returns the seeker's subscription state + their complete payment trail.
     *
     * Payments come from `deposits` — the source of truth for all money
     * movement in this system. `subscription_payments` is an optional audit
     * mirror; we use it to cross-reference but deposit is authoritative.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $perPage = 10;

        $paymentsPage = max(1, (int) $request->get('payments_page', 1));
        $subsPage     = max(1, (int) $request->get('subs_page', 1));

        // ── Active subscription (for the top card) ───────────
        $active = Subscription::with('service:id,key,name')
            ->where('user_id', $user->id)
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->orderByDesc('id')
            ->first();

        // ── Subscriptions (paginated) ────────────────────────
        $subscriptionsPaginator = Subscription::with('service:id,key,name')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'subs_page', $subsPage);

        $subscriptions = collect($subscriptionsPaginator->items())
            ->map(fn ($s) => $this->formatSubscription($s))
            ->all();

        // ── Payments from DEPOSITS (paginated) ───────────────
        $depositsPaginator = Deposit::with('currency')
            ->where('paid_by_user_id', $user->id)
            ->whereIn('purpose_type', [
                'subscription',
                'cv_review',
                'cover_letter',
                'job_submission',
                'cv_rewrite',
                'cv_rewrite_human',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'payments_page', $paymentsPage);

        $payments = collect($depositsPaginator->items())
            ->map(fn ($d) => $this->formatDepositPayment($d))
            ->all();

        // ── Summary (unchanged totals) ───────────────────────
        $totalPaidCents = Deposit::where('paid_by_user_id', $user->id)
            ->whereIn('purpose_type', [
                'subscription', 'cv_review', 'cover_letter',
                'job_submission', 'cv_rewrite', 'cv_rewrite_human',
            ])
            ->where('status', 'completed')
            ->sum('amount');

        $totalPayments = Deposit::where('paid_by_user_id', $user->id)
            ->whereIn('purpose_type', [
                'subscription', 'cv_review', 'cover_letter',
                'job_submission', 'cv_rewrite', 'cv_rewrite_human',
            ])
            ->where('status', 'completed')
            ->count();

        $subscriptionCount = Subscription::where('user_id', $user->id)->count();

        $activeSince = $active?->starts_at?->toISOString();

        return response()->json([
            'success'       => true,
            'active'        => $active ? $this->formatSubscription($active) : null,
            'subscriptions' => $subscriptions,
            'payments'      => $payments,
            'summary'       => [
                'total_paid_cents'   => (int) $totalPaidCents,
                'total_payments'     => $totalPayments,
                'subscription_count' => $subscriptionCount,
                'active_since'       => $activeSince,
            ],
            'pagination' => [
                'payments' => [
                    'current_page' => $depositsPaginator->currentPage(),
                    'last_page'    => $depositsPaginator->lastPage(),
                    'per_page'     => $depositsPaginator->perPage(),
                    'total'        => $depositsPaginator->total(),
                    'has_more'     => $depositsPaginator->hasMorePages(),
                ],
                'subscriptions' => [
                    'current_page' => $subscriptionsPaginator->currentPage(),
                    'last_page'    => $subscriptionsPaginator->lastPage(),
                    'per_page'     => $subscriptionsPaginator->perPage(),
                    'total'        => $subscriptionsPaginator->total(),
                    'has_more'     => $subscriptionsPaginator->hasMorePages(),
                ],
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────
    // Formatters
    // ────────────────────────────────────────────────────────

    private function formatSubscription(Subscription $s): array
    {
        return [
            'uuid'                 => $s->uuid,
            'service_key'          => $s->service_key,
            'service_name'         => $s->service?->name ?? $s->service_key,
            'status'               => $s->status,
            'status_label'         => $this->subscriptionStatusLabel($s->status, (bool) $s->cancel_at_period_end),
            'is_active'            => $s->isActive(),
            'currency'             => $s->currency,
            'amount_cents'         => (int) $s->amount_cents,
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

    /**
     * Format a Deposit row as a payment-history entry.
     */
    private function formatDepositPayment(Deposit $d): array
    {
        $currency = $d->currency?->code ?? 'UGX';
        $amountFormatted = $d->currency
            ? $d->currency->formatAmount((int) $d->amount)
            : strtoupper($currency) . ' ' . number_format($d->amount / 100, 2);

        return [
            'uuid'          => $d->deposit_ref ?? (string) $d->id,
            'purpose_type'  => $d->purpose_type,
            'purpose_label' => $this->purposeLabel($d->purpose_type),
            'description'   => $d->purpose_description
                                ?? $d->description
                                ?? $this->purposeLabel($d->purpose_type),
            'reference'     => $d->deposit_ref,
            'gateway'       => $d->source_name_manual ?? 'MarzPay',
            'gateway_ref'   => $d->source_reference ?? $d->gateway_transaction_uuid,

            'currency'        => $currency,
            'amount_cents'    => (int) $d->amount,
            'amount_formatted'=> $amountFormatted,

            'status'          => $d->status,
            'status_label'    => $this->depositStatusLabel($d->status),

            'paid_at'         => $d->cleared_date?->toISOString() ?? $d->deposit_date?->toISOString(),
            'created_at'      => $d->created_at?->toISOString(),

            // Handy links (frontend can show "View subscription" for subs)
            'purpose_uuid'    => $d->purpose_reference,
        ];
    }

    private function purposeLabel(?string $type): string
    {
        return match ($type) {
            'subscription'   => 'Subscription',
            'cv_review'      => 'CV Review',
            'cv_rewrite'     => 'CV Rewrite (AI)',
            'cv_rewrite_human' => 'CV Rewrite (HR Expert)',
            'cover_letter'   => 'Cover Letter',
            'job_submission' => 'Job Posting',
            default          => ucfirst((string) $type),
        };
    }

    private function depositStatusLabel(?string $status): string
    {
        return match ($status) {
            'completed' => 'Paid',
            'pending'   => 'Pending',
            'failed'    => 'Failed',
            'cancelled' => 'Cancelled',
            'approved'  => 'Approved',
            default     => ucfirst((string) $status),
        };
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