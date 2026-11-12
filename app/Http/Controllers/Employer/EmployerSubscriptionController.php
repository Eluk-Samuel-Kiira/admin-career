<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Subscription\Subscription;
use App\Models\Subscription\PaymentWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployerSubscriptionController extends Controller
{
    /**
     * Every purpose_type that represents a real payment.
     * MUST match the list used by Api\Subscription\UserSubscriptionController::index().
     */
    private const PAYMENT_PURPOSES = [
        'subscription',
        'auto_apply_subscription',
        'premium_alerts',
        'featured_applicant',
        'cv_review',
        'cover_letter',
        'job_submission',
        'cv_rewrite',
        'cv_rewrite_human',
    ];

    /**
     * Admin listing page.
     *
     * Query params:
     *   role=employer | seeker | all  (defaults to all)
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('view subscriptions')) {
            abort(403, 'You do not have permission to view subscriptions.');
        }

        $role = $request->get('role', 'all');

        return view('employer.subscriptions.index', [
            'initialRole' => in_array($role, ['employer', 'seeker', 'all'], true) ? $role : 'all',
        ]);
    }



    // ═════════════════════════════════════════════════════════════
    // STATS
    // ═════════════════════════════════════════════════════════════
    public function stats(Request $request): JsonResponse
    {
        if (!auth()->user()->can('view subscriptions')) {
            return response()->json(['success' => false], 403);
        }

        $role = $request->get('role');

        $subsQuery = Subscription::query();
        $this->applyRoleScope($subsQuery, $role);

        // Same payment-total semantics as the API: ALL service deposits,
        // not just 'subscription' purpose.
        $depositQuery = Deposit::whereIn('purpose_type', self::PAYMENT_PURPOSES);
        $this->applyRoleScopeToDeposits($depositQuery, $role);

        $paidThisMonth = (clone $depositQuery)
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->where('cleared_date', '>=', now()->startOfMonth())
                ->orWhere(function ($q2) {
                    $q2->whereNull('cleared_date')
                        ->where('created_at', '>=', now()->startOfMonth());
                });
            })
            ->sum('amount');

        $paidToday = (clone $depositQuery)
            ->where('status', 'completed')
            ->whereDate('cleared_date', today())
            ->sum('amount');

        $failed30d = (clone $depositQuery)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $refunds30d = (clone $depositQuery)
            ->where('status', 'refunded')
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        return response()->json([
            'success' => true,
            'stats'   => [
                'active_subs'         => (clone $subsQuery)->where('status', 'active')->where('current_period_end', '>', now())->count(),
                'trialing_subs'       => (clone $subsQuery)->where('status', 'trialing')->where('trial_ends_at', '>', now())->count(),
                'past_due_subs'       => (clone $subsQuery)->where('status', 'past_due')->count(),
                'cancelled_subs'      => (clone $subsQuery)->where('cancel_at_period_end', true)->count(),
                'failed_payments_30d' => $failed30d,
                'refunds_30d'         => $refunds30d,
                'paid_this_month'     => (int) $paidThisMonth,
                'paid_today'          => (int) $paidToday,
                'currency'            => $role === 'employer' ? 'UGX' : 'UGX',
            ],
        ]);
    }

    // ═════════════════════════════════════════════════════════════
    // SUBSCRIPTIONS (paginated)
    // ═════════════════════════════════════════════════════════════
    public function data(Request $request): JsonResponse
    {
        if (!auth()->user()->can('view subscriptions')) {
            return response()->json(['success' => false], 403);
        }

        $role = $request->get('role');

        $query = Subscription::with(['user:id,name,email,country_code', 'service:id,key,name']);
        $this->applyRoleScope($query, $role);

        // Filters
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('service_key', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        if ($status = $request->get('status')) $query->where('status', $status);
        if ($country = $request->get('country')) $query->where('country_code', strtoupper($country));
        if ($serviceKey = $request->get('service_key')) $query->where('service_key', $serviceKey);
        if ($kind = $request->get('kind')) {
            if ($kind === 'one_time') $query->where('interval', 'one_time');
            elseif ($kind === 'subscription') $query->where('interval', '!=', 'one_time');
        }
        if ($from = $request->get('from')) $query->where('created_at', '>=', $from . ' 00:00:00');
        if ($to   = $request->get('to'))   $query->where('created_at', '<=', $to   . ' 23:59:59');

        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $subs = $query->orderByDesc('id')->paginate($perPage);

        // Match deposits by purpose_reference = subscription.uuid.
        // Matches the API logic exactly — no purpose_type filter here.
        $uuids = collect($subs->items())->pluck('uuid')->all();
        $paymentCounts = Deposit::query()
            ->whereIn('purpose_reference', $uuids)
            ->selectRaw('purpose_reference, COUNT(*) as c, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as ok')
            ->groupBy('purpose_reference')
            ->get()
            ->keyBy('purpose_reference');

        // Tab counters
        $countsQuery = Subscription::query();
        $this->applyRoleScope($countsQuery, $role);

        $counts = [
            'all'       => (clone $countsQuery)->count(),
            'active'    => (clone $countsQuery)->where('status', 'active')->count(),
            'trialing'  => (clone $countsQuery)->where('status', 'trialing')->count(),
            'past_due'  => (clone $countsQuery)->where('status', 'past_due')->count(),
            'cancelled' => (clone $countsQuery)->where('status', 'cancelled')->count(),
            'expired'   => (clone $countsQuery)->where('status', 'expired')->count(),
        ];

        return response()->json([
            'success' => true,
            'data'    => $subs->through(fn($s) => $this->formatSubscriptionRow($s, $paymentCounts[$s->uuid] ?? null)),
            'counts'  => $counts,
            'pagination' => [
                'current_page' => $subs->currentPage(),
                'last_page'    => $subs->lastPage(),
                'per_page'     => $subs->perPage(),
                'total'        => $subs->total(),
                'from'         => $subs->firstItem(),
                'to'           => $subs->lastItem(),
            ],
        ]);
    }

    // ═════════════════════════════════════════════════════════════
    // PAYMENTS (paginated) — same shape as API
    // ═════════════════════════════════════════════════════════════
    public function payments(Request $request): JsonResponse
    {
        if (!auth()->user()->can('view subscriptions')) {
            return response()->json(['success' => false], 403);
        }

        $role = $request->get('role');

        $query = Deposit::with(['currency', 'paidBy:id,name,email'])
            ->whereIn('purpose_type', self::PAYMENT_PURPOSES)
            ->orderByDesc('created_at');

        $this->applyRoleScopeToDeposits($query, $role);

        // Filters — mirror the API's `search` behaviour (refs + user)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('deposit_ref', 'like', "%{$search}%")
                  ->orWhere('source_reference', 'like', "%{$search}%")
                  ->orWhereHas('paidBy', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->get('status'))      $query->where('status', $status);
        if ($country = $request->get('country'))    $query->where('country_code', strtoupper($country));
        if ($purpose = $request->get('purpose_type')) $query->where('purpose_type', $purpose);

        if ($from = $request->get('from')) $query->where('created_at', '>=', $from . ' 00:00:00');
        if ($to   = $request->get('to'))   $query->where('created_at', '<=', $to   . ' 23:59:59');

        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $paginator = $query->paginate($perPage);

        $data = collect($paginator->items())
            ->map(fn($d) => $this->formatPaymentRow($d))
            ->values();

        // Status counters — same purpose scope
        $countsBase = Deposit::whereIn('purpose_type', self::PAYMENT_PURPOSES);
        $this->applyRoleScopeToDeposits($countsBase, $role);

        $counts = [
            'all'       => (clone $countsBase)->count(),
            'completed' => (clone $countsBase)->where('status', 'completed')->count(),
            'pending'   => (clone $countsBase)->where('status', 'pending')->count(),
            'failed'    => (clone $countsBase)->where('status', 'failed')->count(),
            'cancelled' => (clone $countsBase)->where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'success'    => true,
            'data'       => $data,
            'counts'     => $counts,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ]);
    }

    // ═════════════════════════════════════════════════════════════
    // SUBSCRIPTION DETAIL
    // ═══════════════════════════════════════════════════════════
    public function show(string $uuid): JsonResponse
    {
        if (!auth()->user()->can('view subscriptions')) {
            return response()->json(['success' => false], 403);
        }

        // 1. Try to find the subscription by UUID first
        $sub = Subscription::with(['user:id,name,email,phone,country_code', 'service:id,key,name,description'])
            ->where('uuid', $uuid)
            ->first();

        // 2. Fallback: the reference might belong to a deposit (e.g., auto_apply_subscription),
        //    so look up the deposit and find its owner's subscription instead
        if (!$sub) {
            $deposit = Deposit::where('purpose_reference', $uuid)
                ->orWhere('deposit_ref', $uuid)
                ->first();

            if ($deposit) {
                // Match the deposit's user to their most recent subscription
                $sub = Subscription::with(['user:id,name,email,phone,country_code', 'service:id,key,name,description'])
                    ->where('user_id', $deposit->paid_by_user_id)
                    ->orderByDesc('id')
                    ->first();
            }
        }

        if (!$sub) {
            return response()->json([
                'success' => false,
                'message' => 'No subscription found for this reference. '
                        . 'This payment is for a different service (CV review, cover letter, etc.).',
                'code'    => 'not_a_subscription',
            ], 404);
        }

        // Match deposits by purpose_reference = subscription.uuid.
        // The API does the same — a subscription's payments may carry any
        // purpose_type (e.g. bundled cv_review), so filter by reference only.
        $payments = Deposit::with('currency')
            ->where('purpose_reference', $sub->uuid)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($d) => $this->formatPaymentDetail($d));

        $references = $payments->pluck('deposit_ref')->filter()->values()->all();

        $webhooks = PaymentWebhook::whereIn('gateway_reference', $references)
            ->orderByDesc('id')
            ->get()
            ->map(fn($w) => [
                'id'               => $w->id,
                'gateway'          => $w->gateway,
                'event_type'       => $w->event_type,
                'gateway_reference'=> $w->gateway_reference,
                'signature_valid'  => $w->signature_valid,
                'status'           => $w->status,
                'error_message'    => $w->error_message,
                'processed_at'     => $w->processed_at?->toISOString(),
                'created_at'       => $w->created_at?->toISOString(),
            ]);

        return response()->json([
            'success'      => true,
            'subscription' => $this->formatSubscriptionDetail($sub),
            'payments'     => $payments,
            'webhooks'     => $webhooks,
        ]);
    }

    // ═════════════════════════════════════════════════════════════
    // ROLE SCOPING
    // ═════════════════════════════════════════════════════════════
    protected function applyRoleScope($query, ?string $role): void
    {
        if ($role === 'employer') {
            $query->whereHas('user', function ($q) {
                $q->whereHas('roles', fn($r) => $r->where('name', 'employer'));
            });
        } elseif ($role === 'seeker') {
            $query->whereHas('user', function ($q) {
                $q->whereHas('roles', fn($r) => $r->where('name', 'job_seeker'));
            });
        }
    }

    protected function applyRoleScopeToDeposits($query, ?string $role): void
    {
        if ($role === 'employer') {
            $query->whereHas('paidBy', function ($q) {
                $q->whereHas('roles', fn($r) => $r->where('name', 'employer'));
            });
        } elseif ($role === 'seeker') {
            $query->whereHas('paidBy', function ($q) {
                $q->whereHas('roles', fn($r) => $r->where('name', 'job_seeker'));
            });
        }
    }

    // ═════════════════════════════════════════════════════════════
    // FORMATTERS — mirror the API's shape exactly
    // ═════════════════════════════════════════════════════════════

    private function formatSubscriptionRow(Subscription $s, $paymentCount = null): array
    {
        // Identity block from the API's formatSubscription + admin extras
        $base = $this->formatSubscriptionDetail($s);

        return array_merge($base, [
            // Admin-only fields
            'user'             => $s->user?->name ?? 'Unknown',
            'email'            => $s->user?->email,
            'user_id'          => $s->user_id,
            'user_role'        => $base['user_role'],
            'user_role_label'  => $base['user_role_label'],

            // Payment counters from deposits
            'payment_count'    => $paymentCount->c  ?? 0,
            'payment_success'  => $paymentCount->ok ?? 0,
        ]);
    }

    private function formatSubscriptionDetail(Subscription $s): array
    {
        $userRole = $s->user?->roles->first()?->name ?? 'unknown';

        return [
            'uuid'                 => $s->uuid,
            'user'                 => $s->user?->name,
            'email'                => $s->user?->email,
            'phone'                => $s->user?->phone,
            'user_role'            => $userRole,
            'user_role_label'      => $userRole === 'employer'
                ? 'Employer'
                : ($userRole === 'job_seeker' ? 'Seeker' : ucfirst($userRole)),

            'country_code'         => $s->country_code,
            'service_key'          => $s->service_key,
            'service_name'         => $s->service?->name ?? $s->service_key,
            'service_description'  => $s->service?->description,

            'kind'                 => $s->interval === 'one_time' ? 'one_time' : 'subscription',
            'kind_label'           => $s->interval === 'one_time' ? 'One-Time' : 'Subscription',
            'status'               => $s->status,
            'status_label'         => $this->subscriptionStatusLabel($s->status, (bool) $s->cancel_at_period_end),
            'status_badge'         => $this->statusBadge($s->status, (bool) $s->cancel_at_period_end),
            'is_active'            => $s->isActive(),

            'currency'             => $s->currency,
            'amount_cents'         => (int) $s->amount_cents,
            'amount_formatted' => $s->formatted_amount,
            'interval'             => $s->interval,

            'starts_at'            => $s->starts_at?->toISOString(),
            'trial_ends_at'        => $s->trial_ends_at?->toISOString(),
            'current_period_start' => $s->current_period_start?->toISOString(),
            'current_period_end'   => $s->current_period_end?->toISOString(),
            'cancelled_at'         => $s->cancelled_at?->toISOString(),
            'cancel_at_period_end' => (bool) $s->cancel_at_period_end,

            'gateway'                 => $s->gateway,
            'gateway_subscription_id' => $s->gateway_subscription_id,
            'feature_snapshot'        => $s->feature_snapshot,
            'created_at'              => $s->created_at?->toISOString(),
        ];
    }

    private function formatMoney(?string $currencyCode, int $amountCents): string
    {
        $code = strtoupper($currencyCode ?? 'UGX');
        $currency = \App\Models\Currency::where('code', $code)->first();

        if ($currency) {
            return $currency->formatAmount($amountCents);
        }

        return $code . ' ' . number_format($amountCents, 0);
    }

    /**
     * Payment row for the admin listing — same shape as the API's
     * formatDepositPayment(), plus payer identity and purpose labels.
     */
    private function formatPaymentRow(Deposit $d): array
    {
        $user = $d->paidBy;
        $userRole = $user?->roles->first()?->name ?? 'unknown';
        $currency = $d->currency?->code ?? 'UGX';
        $amountFormatted = $d->currency
            ? $d->currency->formatAmount((int) $d->amount)
            : strtoupper($currency) . ' ' . number_format($d->amount / 100, 2);

        return [
            'id'                => $d->id,
            'deposit_ref'       => $d->deposit_ref,
            'uuid'              => $d->deposit_ref ?? (string) $d->id,

            'user'              => $user?->name ?? 'Unknown',
            'email'             => $user?->email,
            'user_role'         => $userRole,
            'user_role_label'   => $userRole === 'employer'
                ? 'Employer'
                : ($userRole === 'job_seeker' ? 'Seeker' : ucfirst($userRole)),

            'purpose_type'      => $d->purpose_type,
            'purpose_label'     => $this->purposeLabel($d->purpose_type),
            'description'       => $d->purpose_description
                                    ?? $d->description
                                    ?? $this->purposeLabel($d->purpose_type),
            'purpose_reference' => $d->purpose_reference,

            'gateway'           => $d->source_name_manual ?? 'MarzPay',
            'reference'         => $d->deposit_ref,
            'gateway_reference' => $d->source_reference ?? $d->gateway_transaction_uuid,

            'currency'          => $currency,
            'amount_cents'      => (int) $d->amount,
            'amount_formatted'  => $amountFormatted,

            'status'            => $d->status,
            'status_label'      => $this->depositStatusLabel($d->status),
            'status_badge'      => $this->depositBadge($d->status),

            'paid_at'           => $d->cleared_date?->toISOString() ?? $d->deposit_date?->toISOString(),
            'created_at'        => $d->created_at?->toISOString(),
        ];
    }

    /**
     * Compact payment entry used inside the subscription detail modal.
     */
    private function formatPaymentDetail(Deposit $d): array
    {
        $currency = $d->currency?->code ?? 'UGX';

        return [
            'id'                     => $d->id,
            'deposit_ref'            => $d->deposit_ref,
            'purpose_type'           => $d->purpose_type,
            'purpose_label'          => $this->purposeLabel($d->purpose_type),
            'gateway'                => $d->source_name_manual ?? 'MarzPay',
            'gateway_reference'      => $d->source_reference ?? $d->gateway_transaction_uuid,
            'gateway_transaction_id' => $d->gateway_transaction_uuid,
            'currency'               => $currency,
            'amount_cents'           => (int) $d->amount,
            'amount_formatted' => $d->currency
                ? $d->currency->formatAmount((int) $d->amount)
                : strtoupper($currency) . ' ' . number_format($d->amount / 100, 2),
            'status'                 => $d->status,
            'status_label'           => $this->depositStatusLabel($d->status),
            'status_badge'           => $this->depositBadge($d->status),
            'failure_reason'         => null,
            'paid_at'                => $d->cleared_date?->toISOString(),
            'created_at'             => $d->created_at?->toISOString(),
        ];
    }

    // ═════════════════════════════════════════════════════════════
    // LABELS — kept in sync with the API
    // ═════════════════════════════════════════════════════════════
    private function purposeLabel(?string $type): string
    {
        return match ($type) {
            'subscription'     => 'Subscription',
            'cv_review'        => 'CV Review',
            'cv_rewrite'       => 'CV Rewrite (AI)',
            'cv_rewrite_human' => 'CV Rewrite (HR Expert)',
            'cover_letter'     => 'Cover Letter',
            'job_submission'   => 'Job Posting',
            default            => ucfirst((string) $type),
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
            'refunded'  => 'Refunded',
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

    // ═════════════════════════════════════════════════════════════
    // BADGES
    // ═════════════════════════════════════════════════════════════
    private function statusBadge(string $status, bool $cancelAtPeriodEnd = false): string
    {
        $map = [
            'trialing'  => ['light-info',      'Trialing'],
            'active'    => ['light-success',   'Active'],
            'past_due'  => ['light-danger',    'Past Due'],
            'cancelled' => ['light-secondary', 'Cancelled'],
            'expired'   => ['light-secondary', 'Expired'],
        ];

        [$color, $label] = $map[$status] ?? ['light-secondary', ucfirst($status)];

        if ($status === 'active' && $cancelAtPeriodEnd) {
            $label = 'Active · Cancels at period end';
            $color = 'light-warning';
        }

        return "<span class=\"badge badge-{$color}\">{$label}</span>";
    }

    private function depositBadge(?string $status): string
    {
        return match ($status) {
            'completed' => '<span class="badge badge-light-success">Paid</span>',
            'pending'   => '<span class="badge badge-light-info">Pending</span>',
            'failed'    => '<span class="badge badge-light-danger">Failed</span>',
            'cancelled' => '<span class="badge badge-light-secondary">Cancelled</span>',
            'approved'  => '<span class="badge badge-light-primary">Approved</span>',
            'refunded'  => '<span class="badge badge-light-warning">Refunded</span>',
            default     => '<span class="badge badge-light-secondary">' . e((string) $status) . '</span>',
        };
    }
}