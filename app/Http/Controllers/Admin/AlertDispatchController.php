<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendSeekerAlertJob;
use App\Models\AlertDispatch;
use App\Models\SeekerProfile;
use App\Services\Alerts\AlertResolver;
use App\Services\Subscription\SubscriptionGate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AlertDispatchController extends Controller
{
    public function __construct(
        protected AlertResolver $resolver,
        protected SubscriptionGate $gate,
    ) {}

    public function index()
    {
        if (!auth()->user()->can('manage job alerts')) {
            abort(403);
        }

        return view('job-seeker.alerts.index');
    }

    public function preview(Request $request): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $validated = $request->validate([
            'country' => 'nullable|string|size:2',
        ]);

        $country = $validated['country'] ?? null;
        $country = $country ? strtoupper($country) : null;

        $query = SeekerProfile::with('user')->where('is_active', true);

        if ($country) {
            $query->where(function ($q) use ($country) {
                $q->where('preferred_country_code', $country)
                ->orWhere('country', $country)
                ->orWhereHas('user', fn($uq) => $uq->where('country_code', $country));
            });
        }

        $seekers = $query->get();

        $summary = [
            'total'              => $seekers->count(),
            'jobs'               => 0,
            'preferences_prompt' => 0,
            'profile_prompt'     => 0,
            'cv_prompt'          => 0,
            'skip'               => 0,
            'by_channel' => [
                'email'    => 0,
                'whatsapp' => 0,
                'telegram' => 0,
            ],
        ];

        foreach ($seekers as $s) {
            $prefs = $s->job_preferences_array;
            $decision = $this->resolver->decide($s, $prefs);
            $summary[$decision['kind']] = ($summary[$decision['kind']] ?? 0) + 1;

            if ($prefs['send_alert_by_email'] ?? true) {
                $summary['by_channel']['email']++;
            }
            if (!empty($prefs['send_alert_by_whatsapp']) && $this->gate->canWhatsApp($s->user)) {
                $summary['by_channel']['whatsapp']++;
            }
            if (!empty($prefs['send_alert_by_telegram']) && $this->gate->canTelegram($s->user)) {
                $summary['by_channel']['telegram']++;
            }
        }

        return response()->json(['success' => true, 'summary' => $summary]);
    }

    public function dispatch(Request $request): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $validated = $request->validate([
            'channels'   => 'nullable|array',
            'channels.*' => 'in:email,whatsapp,telegram',
            'mode'       => 'nullable|in:dry_run,real',
            'country'    => 'required|string|size:2',
        ]);

        $channels = $validated['channels'] ?? ['email'];
        $mode     = $validated['mode'] ?? 'real';
        $country  = strtoupper($validated['country']);

        $query = SeekerProfile::with('user')->where('is_active', true);

        $query->where(function ($q) use ($country) {
            $q->where('preferred_country_code', $country)
            ->orWhere('country', $country)
            ->orWhereHas('user', fn($uq) => $uq->where('country_code', $country));
        });

        $seekers = $query->get();

        $queued = 0;
        foreach ($seekers as $s) {
            if (!$s->user || !$s->user->is_active) continue;

            SendSeekerAlertJob::dispatch($s->id, $channels, $mode)->onQueue('default');
            $queued++;
        }

        return response()->json([
            'success' => true,
            'message' => "Queued {$queued} alert jobs for {$country}.",
            'queued'  => $queued,
            'country' => $country,
        ]);
    }


    public function log(Request $request): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $query = AlertDispatch::with('user:id,name,email');

        // Filters
        if ($channel = $request->get('channel')) {
            $query->where('channel', $channel);
        }
        if ($kind = $request->get('kind')) {
            $query->where('kind', $kind);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('error_message', 'like', "%{$search}%");
            });
        }
        if ($from = $request->get('from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to = $request->get('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $dispatches = $query->orderByDesc('id')->paginate($perPage);

        // Aggregate counters
        $counts = [
            'all'    => AlertDispatch::count(),
            'sent'   => AlertDispatch::where('status', 'sent')->count(),
            'failed' => AlertDispatch::where('status', 'failed')->count(),
            'queued' => AlertDispatch::where('status', 'queued')->count(),
        ];

        return response()->json([
            'success' => true,
            'data'    => $dispatches->through(fn($d) => [
                'id'            => $d->id,
                'user_id'       => $d->user_id,
                'user'          => $d->user?->name ?? 'Unknown',
                'email'         => $d->user?->email,
                'channel'       => $d->channel,
                'kind'          => $d->kind,
                'jobs_sent'     => $d->jobs_sent,
                'status'        => $d->status,
                'error_message' => $d->error_message,
                'sent_at'       => $d->sent_at?->toISOString(),
                'created_at'    => $d->created_at?->toISOString(),
            ]),
            'counts'  => $counts,
        ]);
    }

    /**
     * Bulk delete alert dispatches.
     *
     * Body:
     *   - ids:    array of dispatch ids
     *   - mode:   'selected' | 'filtered' | 'all' | 'older_than'
     *   - older_than_days: integer (used with mode='older_than')
     *   - filters: optional, applies when mode='filtered' (channel, kind, status, from, to)
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $validated = $request->validate([
            'mode'            => 'required|in:selected,filtered,all,older_than',
            'ids'             => 'array',
            'ids.*'           => 'integer',
            'older_than_days' => 'nullable|integer|min:1',
            'filters'         => 'array',
        ]);

        $mode = $validated['mode'];
        $deleted = 0;

        \DB::transaction(function () use ($mode, $validated, &$deleted) {
            $query = AlertDispatch::query();

            if ($mode === 'selected') {
                $ids = $validated['ids'] ?? [];
                if (empty($ids)) {
                    return;
                }
                $query->whereIn('id', $ids);
            } elseif ($mode === 'filtered') {
                $filters = $validated['filters'] ?? [];
                if (!empty($filters['channel'])) {
                    $query->where('channel', $filters['channel']);
                }
                if (!empty($filters['kind'])) {
                    $query->where('kind', $filters['kind']);
                }
                if (!empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }
                if (!empty($filters['from'])) {
                    $query->where('created_at', '>=', $filters['from'] . ' 00:00:00');
                }
                if (!empty($filters['to'])) {
                    $query->where('created_at', '<=', $filters['to'] . ' 23:59:59');
                }
            } elseif ($mode === 'older_than') {
                $days = $validated['older_than_days'] ?? 30;
                $query->where('created_at', '<', now()->subDays($days));
            }
            // mode === 'all' -> no filter, delete everything

            $deleted = $query->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deleted} dispatch record(s).",
            'deleted' => $deleted,
        ]);

    }

}