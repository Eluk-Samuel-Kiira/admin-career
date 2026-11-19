<?php

namespace App\Http\Controllers\Api\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Subscription\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSubscriptionController extends Controller
{
    /**
     * The service keys that represent subscriptions in the deposits table.
     * Any purpose_type that is a SUBSCRIPTION payment (recurring) lives here.
     * Everything else is a one-time payment.
     */
    private const SUBSCRIPTION_PURPOSE_TYPES = [
        'subscription',
        'auto_apply_subscription',
        'premium_alerts',
        'featured_applicant',
    ];

    /**
     * One-time service purposes.
     */
    private const ONE_TIME_PURPOSE_TYPES = [
        'cv_review',
        'cv_rewrite',
        'cv_rewrite_human',
        'cover_letter',
        'job_submission',
    ];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $perPage = 10;

        $paymentsPage = max(1, (int) $request->get('payments_page', 1));
        $subsPage     = max(1, (int) $request->get('subs_page', 1));

        // ── Active subscription ──────────────────────────────
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
        $allPurposes = array_merge(self::SUBSCRIPTION_PURPOSE_TYPES, self::ONE_TIME_PURPOSE_TYPES);

        $depositsPaginator = Deposit::with('currency')
            ->where('paid_by_user_id', $user->id)
            ->whereIn('purpose_type', $allPurposes)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'payments_page', $paymentsPage);

        $payments = collect($depositsPaginator->items())
            ->map(fn ($d) => $this->formatDepositPayment($d))
            ->all();

        // ── Summary ──────────────────────────────────────────
        $totalPaidCents = Deposit::where('paid_by_user_id', $user->id)
            ->whereIn('purpose_type', $allPurposes)
            ->where('status', 'completed')
            ->sum('amount');

        $totalPayments = Deposit::where('paid_by_user_id', $user->id)
            ->whereIn('purpose_type', $allPurposes)
            ->where('status', 'completed')
            ->count();

        $subscriptionCount = Subscription::where('user_id', $user->id)->count();
        $activeSince       = $active?->starts_at?->toISOString();

        // Formatted total using the deposit's own currency (handles UGX correctly)
        $firstDeposit = Deposit::where('paid_by_user_id', $user->id)
            ->where('status', 'completed')
            ->with('currency')
            ->first();

        $totalPaidFormatted = $firstDeposit?->currency
            ? $firstDeposit->currency->formatAmount((int) $totalPaidCents)
            : ($active?->currency === 'UGX' || $active === null
                ? 'UGX ' . number_format($totalPaidCents, 0)
                : strtoupper($active->currency) . ' ' . number_format($totalPaidCents / 100, 2));

        return response()->json([
            'success'       => true,
            'active'        => $active ? $this->formatSubscription($active) : null,
            'subscriptions' => $subscriptions,
            'payments'      => $payments,
            'summary'       => [
                'total_paid_cents'     => (int) $totalPaidCents,
                'total_paid_formatted' => $totalPaidFormatted,
                'total_payments'       => $totalPayments,
                'subscription_count'   => $subscriptionCount,
                'active_since'         => $activeSince,
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
        // ✅ Use the currency-aware formatter
        $currencyModel = \App\Models\Currency::where('code', $s->currency)->first();
        $amountFormatted = $currencyModel
            ? $currencyModel->formatAmount((int) $s->amount_cents)
            : strtoupper($s->currency ?? 'UGX') . ' ' . number_format($s->amount_cents, 0);

        return [
            'uuid'                 => $s->uuid,
            'service_key'          => $s->service_key,
            'service_name'         => $s->service?->name ?? $s->service_key,
            'status'               => $s->status,
            'status_label'         => $this->subscriptionStatusLabel($s->status, (bool) $s->cancel_at_period_end),
            'is_active'            => $s->isActive(),
            'currency'             => $s->currency,
            'amount_cents'         => (int) $s->amount_cents,
            'amount_formatted'     => $s->formatted_amount,
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
     * GET /api/user/subscriptions/stats
     *
     * Returns the same usage/auto-apply performance block the admin sees
     * in EmployerSubscriptionController::show(), scoped to the current user.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        // ── 1. Active subscription? ────────────────────────────────
        $sub = Subscription::with('service:id,key,name')
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'trialing'])
            ->where('current_period_end', '>', now())
            ->orderByDesc('id')
            ->first();

        // ── 2. Auto-apply + CV delivery stats (same as admin) ──────
        $stat     = \App\Models\AutoApplyStat::firstOrCreate(['user_id' => $user->id], []);
        $seeker   = $user->seekerProfile;
        $seekerId = $seeker?->id;

        $windows = ['today' => 0, 'last_7' => 0, 'last_30' => 0, 'last_90' => 0];
        $recent  = [];
        $trend   = [];

        if ($seekerId) {
            $base = \App\Models\Job\JobSeekerJob::where('seeker_profile_id', $seekerId)
                ->where('is_applied', true);

            $windows['today']   = (clone $base)->whereDate('applied_at', today())->count();
            $windows['last_7']  = (clone $base)->where('applied_at', '>=', now()->subDays(7))->count();
            $windows['last_30'] = (clone $base)->where('applied_at', '>=', now()->subDays(30))->count();
            $windows['last_90'] = (clone $base)->where('applied_at', '>=', now()->subDays(90))->count();

            $recent = (clone $base)
                ->with(['jobPost:id,job_title,company_id', 'jobPost.company:id,name'])
                ->orderByDesc('applied_at')
                ->limit(10)
                ->get()
                ->map(fn ($r) => [
                    'id'         => $r->id,
                    'job_title'  => $r->jobPost?->job_title ?? '—',
                    'company'    => $r->jobPost?->company?->name ?? '—',
                    'source'     => $r->auto_apply_draft_type ?? 'manual',
                    'applied_at' => $r->applied_at?->toISOString(),
                ])->values()->all();

            for ($i = 5; $i >= 0; $i--) {
                $m = now()->subMonths($i);
                $trend[] = [
                    'month' => $m->format('M'),
                    'year'  => $m->format('Y'),
                    'count' => \App\Models\Job\JobSeekerJob::where('seeker_profile_id', $seekerId)
                        ->where('is_applied', true)
                        ->whereYear('applied_at', $m->year)
                        ->whereMonth('applied_at', $m->month)
                        ->count(),
                ];
            }
        }

        // ── 3. CV delivery log (cv_review_requests) ────────────────
        $cvLog = \App\Models\Service\CvReviewRequest::where('user_id', $user->id)
            ->whereNotNull('delivery_counted_at')
            ->orderByDesc('delivery_counted_at')
            ->limit(25)
            ->get()
            ->map(fn ($r) => [
                'id'           => $r->id,
                'reference'    => $r->uuid ?? $r->id,
                'channel'      => $r->delivery_channel ?? 'manual',
                'status'       => $r->status ?? null,
                'delivered_at' => ($r->delivered_at ?? $r->delivery_counted_at)?->toISOString(),
                'counted_at'   => $r->delivery_counted_at?->toISOString(),
            ])->values()->all();

        // ── 4. Plan + feature_snapshot for the "usage" bars ────────
        $snapshot = $sub?->feature_snapshot ?? [];
        $quota = function ($key) use ($snapshot) {
            if (!array_key_exists($key, $snapshot)) return null;
            $v = $snapshot[$key];
            if ($v === true) return 'unlimited';
            return is_numeric($v) ? (int) $v : null;
        };

        // Count what they've used during the current period
        $periodStart = $sub?->current_period_start ?? now()->startOfMonth();
        $periodEnd   = $sub?->current_period_end;

        $cvReviewUsed = \App\Models\Service\CvReviewRequest::where('user_id', $user->id)
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<=', $periodEnd ?? now())
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $coverLetterUsed = \App\Models\Service\LetterRequest::where('user_id', $user->id)
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<=', $periodEnd ?? now())
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->where('letter_type', 'cover')
            ->count();

        $feature = function ($used, $quota) {
            if ($quota === null)    return ['available' => false, 'used' => $used, 'limit' => null, 'remaining' => null];
            if ($quota === 'unlimited') return ['available' => true, 'used' => $used, 'limit' => null, 'remaining' => null, 'unlimited' => true];
            return [
                'available' => true,
                'used'      => $used,
                'limit'     => (int) $quota,
                'remaining' => max(0, (int) $quota - $used),
                'percent'   => $quota > 0 ? (int) round(($used / $quota) * 100) : 0,
            ];
        };

        return response()->json([
            'success'      => true,
            'has_seeker'   => (bool) $seekerId,
            'has_active'   => (bool) $sub,
            'plan'         => $sub ? [
                'service_key'   => $sub->service_key,
                'service_name'  => $sub->service?->name ?? $sub->service_key,
                'status'        => $sub->status,
                'status_label'  => ucfirst($sub->status),
                'amount_formatted' => $sub->formatted_amount,
                'interval'      => $sub->interval,
                'period_start'  => $sub->current_period_start?->toISOString(),
                'period_end'    => $sub->current_period_end?->toISOString(),
                'renewal_label' => $sub->current_period_end
                    ? $sub->current_period_end->diffForHumans(now(), ['parts' => 1, 'short' => true]) . ' left'
                    : null,
            ] : null,
            'usage' => [
                'cv_reviews'    => $feature($cvReviewUsed,    $quota('cv_review')),
                'cover_letters' => $feature($coverLetterUsed, $quota('cover_letter')),
            ],
            'auto_apply' => [
                'lifetime' => [
                    'total'    => (int) $stat->applied_total,
                    'email'    => (int) $stat->applied_via_email,
                    'whatsapp' => (int) $stat->applied_via_whatsapp,
                    'external' => (int) $stat->applied_via_external,
                ],
                'cv_delivery' => [
                    'total'    => (int) $stat->cv_reviews_delivered,
                    'email'    => (int) $stat->cv_delivered_via_email,
                    'whatsapp' => (int) $stat->cv_delivered_via_whatsapp,
                    'external' => (int) $stat->cv_delivered_via_external,
                    'manual'   => (int) ($stat->cv_delivered_via_manual ?? 0),
                    'log'      => $cvLog,
                ],
                'windows'              => $windows,
                'recent'               => $recent,
                'trend'                => $trend,
                'last_applied_at'      => $stat->last_applied_at?->toISOString(),
                'last_cv_delivered_at' => $stat->last_cv_delivered_at?->toISOString(),
            ],
            'generated_at' => now()->toISOString(),
        ]);
    }

    private function formatDepositPayment(Deposit $d): array
    {
        $currency = $d->currency?->code ?? 'UGX';

        $amountFormatted = $d->currency
            ? $d->currency->formatAmount((int) $d->amount)
            : strtoupper($currency) . ' ' . number_format($d->amount, 0);

        $isSubscription = in_array($d->purpose_type, self::SUBSCRIPTION_PURPOSE_TYPES, true);
        $isOneTime      = !$isSubscription;

        $serviceName = match ($d->purpose_type) {
            'subscription',
            'auto_apply_subscription',
            'premium_alerts'      => 'Auto Apply Subscription',
            'featured_applicant'  => 'Featured Applicant',
            'cv_review'           => 'CV Review',
            'cv_rewrite'          => 'CV Rewrite (AI)',
            'cv_rewrite_human'    => 'CV Rewrite (HR Expert)',
            'cover_letter'        => 'Cover Letter',
            'job_submission'      => 'Job Posting',
            default               => ucfirst((string) $d->purpose_type),
        };

        return [
            'uuid'           => $d->deposit_ref ?? (string) $d->id,
            'purpose_type'   => $d->purpose_type,
            'purpose_label'  => $serviceName,
            'billing_type'   => $isOneTime ? 'one_time' : 'recurring',
            'billing_label'  => $isOneTime ? 'One-time payment' : 'Subscription payment',
            'description'    => $d->purpose_description
                                ?? $d->description
                                ?? $serviceName,
            'reference'      => $d->deposit_ref,
            'gateway'        => $d->source_name_manual ?? 'MarzPay',
            'gateway_ref'    => $d->source_reference ?? $d->gateway_transaction_uuid,

            'currency'         => $currency,
            'amount_cents'     => (int) $d->amount,
            'amount_formatted' => $amountFormatted,

            'status'       => $d->status,
            'status_label' => $this->depositStatusLabel($d->status),

            'paid_at'      => $d->cleared_date?->toISOString() ?? $d->deposit_date?->toISOString(),
            'created_at'   => $d->created_at?->toISOString(),

            'purpose_uuid' => $d->purpose_reference,
        ];
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