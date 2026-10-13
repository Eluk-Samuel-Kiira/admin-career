<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Subscription\Subscription;
use App\Models\Subscription\SubscriptionPayment;
use App\Models\Subscription\PaymentWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployerSubscriptionController extends Controller
{
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

    /**
     * Aggregate stats for the top cards. Respects the role filter.
     */
    public function stats(Request $request): JsonResponse
    {
        if (!auth()->user()->can('view subscriptions')) {
            return response()->json(['success' => false], 403);
        }

        $role = $request->get('role');

        $subsQuery = Subscription::query();
        $paymentsQuery = SubscriptionPayment::query();

        $this->applyRoleScope($subsQuery, $role);
        $this->applyRoleScopeToPayments($paymentsQuery, $role);

        $paidThisMonth = (clone $paymentsQuery)
            ->where('status', 'succeeded')
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount_cents');

        $paidToday = (clone $paymentsQuery)
            ->where('status', 'succeeded')
            ->whereDate('paid_at', today())
            ->sum('amount_cents');

        return response()->json([
            'success' => true,
            'stats'   => [
                'active_subs'          => (clone $subsQuery)->where('status', 'active')->where('current_period_end', '>', now())->count(),
                'trialing_subs'        => (clone $subsQuery)->where('status', 'trialing')->where('trial_ends_at', '>', now())->count(),
                'past_due_subs'        => (clone $subsQuery)->where('status', 'past_due')->count(),
                'cancelled_subs'       => (clone $subsQuery)->where('cancel_at_period_end', true)->count(),
                'failed_payments_30d'  => (clone $paymentsQuery)->where('status', 'failed')
                    ->where('created_at', '>=', now()->subDays(30))->count(),
                'refunds_30d'          => (clone $paymentsQuery)->where('status', 'refunded')
                    ->where('updated_at', '>=', now()->subDays(30))->count(),
                'paid_this_month'      => (int) $paidThisMonth,
                'paid_today'           => (int) $paidToday,
            ],
        ]);
    }

    /**
     * Paginated list of subscriptions. Respects the role filter.
     */
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

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($country = $request->get('country')) {
            $query->where('country_code', strtoupper($country));
        }

        if ($serviceKey = $request->get('service_key')) {
            $query->where('service_key', $serviceKey);
        }

        if ($kind = $request->get('kind')) {
            if ($kind === 'one_time') {
                $query->where('interval', 'one_time');
            } elseif ($kind === 'subscription') {
                $query->where('interval', '!=', 'one_time');
            }
        }

        if ($from = $request->get('from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to = $request->get('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $subs = $query->orderByDesc('id')->paginate($perPage);

        $ids = collect($subs->items())->pluck('id')->all();
        $paymentCounts = SubscriptionPayment::selectRaw('subscription_id, COUNT(*) as c, SUM(CASE WHEN status = "succeeded" THEN 1 ELSE 0 END) as ok')
            ->whereIn('subscription_id', $ids)
            ->groupBy('subscription_id')
            ->get()
            ->keyBy('subscription_id');

        // Counters respect the role filter too
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
            'data'    => $subs->through(fn($s) => $this->formatRow($s, $paymentCounts[$s->id] ?? null)),
            'counts'  => $counts,
        ]);
    }

    /**
     * Full detail for a single subscription.
     */
    public function show(string $uuid): JsonResponse
    {
        if (!auth()->user()->can('view subscriptions')) {
            return response()->json(['success' => false], 403);
        }

        $sub = Subscription::with(['user:id,name,email,phone,country_code', 'service:id,key,name,description'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $payments = SubscriptionPayment::where('subscription_id', $sub->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => [
                'id'                     => $p->id,
                'uuid'                   => $p->uuid,
                'gateway'                => $p->gateway,
                'gateway_reference'      => $p->gateway_reference,
                'gateway_transaction_id' => $p->gateway_transaction_id,
                'currency'               => $p->currency,
                'amount_cents'           => $p->amount_cents,
                'amount_formatted'       => strtoupper($p->currency) . ' ' . number_format($p->amount_cents / 100, $p->currency === 'UGX' ? 0 : 2),
                'status'                 => $p->status,
                'status_badge'           => $this->paymentBadge($p->status),
                'failure_reason'         => $p->failure_reason,
                'paid_at'                => $p->paid_at?->toISOString(),
                'created_at'             => $p->created_at?->toISOString(),
            ]);

        $references = $payments->pluck('gateway_reference')->filter()->values()->all();

        $webhooks = PaymentWebhook::whereIn('gateway_reference', $references)
            ->orderByDesc('id')
            ->get()
            ->map(fn($w) => [
                'id'              => $w->id,
                'gateway'         => $w->gateway,
                'event_type'      => $w->event_type,
                'gateway_reference'=> $w->gateway_reference,
                'signature_valid' => $w->signature_valid,
                'status'          => $w->status,
                'error_message'   => $w->error_message,
                'processed_at'    => $w->processed_at?->toISOString(),
                'created_at'      => $w->created_at?->toISOString(),
            ]);

        return response()->json([
            'success'      => true,
            'subscription' => $this->formatDetail($sub),
            'payments'     => $payments,
            'webhooks'     => $webhooks,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Role scoping
    // ─────────────────────────────────────────────────────────────

    /**
     * Constrain a Subscription query to a role: employer | seeker | null.
     */
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
        // role === null or 'all' -> no filter
    }

    /**
     * Constrain a SubscriptionPayment query to a role.
     */
    protected function applyRoleScopeToPayments($query, ?string $role): void
    {
        if ($role === 'employer') {
            $query->whereHas('subscription.user', function ($q) {
                $q->whereHas('roles', fn($r) => $r->where('name', 'employer'));
            });
        } elseif ($role === 'seeker') {
            $query->whereHas('subscription.user', function ($q) {
                $q->whereHas('roles', fn($r) => $r->where('name', 'job_seeker'));
            });
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Formatters
    // ─────────────────────────────────────────────────────────────
    private function formatRow(Subscription $s, $paymentCount = null): array
    {
        // Identify the user's role for the row badge
        $userRole = $s->user?->roles->first()?->name ?? 'unknown';

        return [
            'uuid'             => $s->uuid,
            'user'             => $s->user?->name ?? 'Unknown',
            'email'            => $s->user?->email,
            'user_id'          => $s->user_id,
            'user_role'        => $userRole,
            'user_role_label'  => $userRole === 'employer' ? 'Employer' : ($userRole === 'job_seeker' ? 'Seeker' : ucfirst($userRole)),
            'service_key'      => $s->service_key,
            'service_name'     => $s->service?->name ?? $s->service_key,
            'kind'             => $s->interval === 'one_time' ? 'one_time' : 'subscription',
            'kind_label'       => $s->interval === 'one_time' ? 'One-Time' : 'Subscription',
            'country_code'     => $s->country_code,
            'currency'         => $s->currency,
            'amount_cents'     => $s->amount_cents,
            'amount_formatted' => strtoupper($s->currency) . ' ' . number_format($s->amount_cents / 100, $s->currency === 'UGX' ? 0 : 2),
            'interval'         => $s->interval,
            'status'           => $s->status,
            'status_badge'     => $this->statusBadge($s->status, $s->cancel_at_period_end),
            'trial_ends_at'    => $s->trial_ends_at?->toISOString(),
            'current_period_end' => $s->current_period_end?->toISOString(),
            'cancel_at_period_end' => $s->cancel_at_period_end,
            'cancelled_at'     => $s->cancelled_at?->toISOString(),
            'created_at'       => $s->created_at?->toISOString(),
            'payment_count'    => $paymentCount->c ?? 0,
            'payment_success'  => $paymentCount->ok ?? 0,
        ];
    }

    private function formatDetail(Subscription $s): array
    {
        $userRole = $s->user?->roles->first()?->name ?? 'unknown';

        return [
            'uuid'                 => $s->uuid,
            'user'                 => $s->user?->name,
            'email'                => $s->user?->email,
            'phone'                => $s->user?->phone,
            'user_role'            => $userRole,
            'user_role_label'      => $userRole === 'employer' ? 'Employer' : ($userRole === 'job_seeker' ? 'Seeker' : ucfirst($userRole)),
            'country_code'         => $s->country_code,
            'service_key'          => $s->service_key,
            'service_name'         => $s->service?->name,
            'service_description'  => $s->service?->description,
            'kind'                 => $s->interval === 'one_time' ? 'one_time' : 'subscription',
            'status'               => $s->status,
            'status_badge'         => $this->statusBadge($s->status, $s->cancel_at_period_end),
            'currency'             => $s->currency,
            'amount_cents'         => $s->amount_cents,
            'amount_formatted'     => strtoupper($s->currency) . ' ' . number_format($s->amount_cents / 100, $s->currency === 'UGX' ? 0 : 2),
            'interval'             => $s->interval,
            'starts_at'            => $s->starts_at?->toISOString(),
            'trial_ends_at'        => $s->trial_ends_at?->toISOString(),
            'current_period_start' => $s->current_period_start?->toISOString(),
            'current_period_end'   => $s->current_period_end?->toISOString(),
            'cancelled_at'         => $s->cancelled_at?->toISOString(),
            'cancel_at_period_end' => $s->cancel_at_period_end,
            'gateway'              => $s->gateway,
            'gateway_subscription_id' => $s->gateway_subscription_id,
            'feature_snapshot'     => $s->feature_snapshot,
            'created_at'           => $s->created_at?->toISOString(),
        ];
    }

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

    private function paymentBadge(string $status): string
    {
        return match ($status) {
            'succeeded' => '<span class="badge badge-light-success">Succeeded</span>',
            'pending'   => '<span class="badge badge-light-info">Pending</span>',
            'failed'    => '<span class="badge badge-light-danger">Failed</span>',
            'refunded'  => '<span class="badge badge-light-warning">Refunded</span>',
            default     => '<span class="badge badge-light-secondary">' . e($status) . '</span>',
        };
    }
}