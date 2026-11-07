<?php

namespace App\Http\Controllers\Api\Seeker;

use App\Http\Controllers\Controller;
use App\Models\AutoApplyRun;
use App\Models\AutoApplyRelay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutoApplyController extends Controller
{
    /**
     * GET /api/seeker/auto-apply
     *
     * Returns:
     *  - feature_enabled (bool) — plan-gated
     *  - runs (paginated, 10 per page) with their relays
     *  - stats (global summary)
     *  - pagination
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $featureEnabled = $this->isFeatureEnabled($user);

        // If the plan doesn't include auto-apply, return early — no need to query.
        if (!$featureEnabled) {
            return response()->json([
                'success'         => true,
                'feature_enabled' => false,
                'runs'            => [],
                'stats'           => [],
                'pagination'      => [
                    'current_page' => 1, 'last_page' => 1,
                    'per_page' => 10, 'total' => 0,
                ],
            ]);
        }

        $perPage = max(1, min(50, (int) $request->get('per_page', 10)));
        $page    = max(1, (int) $request->get('page', 1));

        // ── Paginated runs ────────────────────────────────────
        $paginator = AutoApplyRun::where('user_id', $user->id)
            ->orderByDesc('run_date')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        // Load relays only for the current page's runs, filtering
        // soft-deleted jobs but keeping expired ones.
        $runIds = collect($paginator->items())->pluck('id')->all();

        $relaysByRun = AutoApplyRelay::with('jobPost')
            ->whereIn('auto_apply_run_id', $runIds)
            // Only hide soft-deleted jobs — expired ones stay visible.
            ->whereHas('jobPost')
            ->get()
            ->groupBy('auto_apply_run_id');

        $runs = collect($paginator->items())->map(function ($run) use ($relaysByRun) {
            $relays = $relaysByRun[$run->id] ?? collect();

            $expiredRelays = $relays->map(function ($relay) {
                $job = $relay->jobPost;

                return [
                    'id'         => $relay->id,
                    'job_title'  => $job?->job_title,
                    'company'    => $job?->company?->name,
                    'job_slug'   => $job?->slug,
                    'reason'     => $relay->reason,
                    'status'     => $relay->status,
                    // Extra: whether this job is now expired
                    'is_expired' => $job?->deadline && $job->deadline->isPast(),
                ];
            })->values()->all();

            return [
                'uuid'            => $run->uuid ?? (string) $run->id,
                'run_date'        => $run->run_date?->toDateString(),
                'applied_count'   => (int) $run->applied_count,
                'relayed_count'   => (int) $run->relayed_count,
                'skipped_count'   => (int) $run->skipped_count,
                'failed_count'    => (int) $run->failed_count,
                'status'          => $run->status,
                'status_label'    => $run->status_badge ?? ucfirst((string) $run->status),
                'relays'          => $expiredRelays,
            ];
        })->values()->all();

        // ── Global stats (across all runs — not paginated) ─────
        $stats = [
            'total_applied' => (int) AutoApplyRun::where('user_id', $user->id)->sum('applied_count'),
            'total_relayed' => (int) AutoApplyRun::where('user_id', $user->id)->sum('relayed_count'),
            'this_month'    => (int) AutoApplyRun::where('user_id', $user->id)
                                ->whereBetween('run_date', [now()->startOfMonth(), now()->endOfMonth()])
                                ->sum('applied_count'),
        ];

        return response()->json([
            'success'         => true,
            'feature_enabled' => true,
            'runs'            => $runs,
            'stats'           => $stats,
            'pagination'      => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * Feature gating — plan check. Adjust to your actual service.
     */
    private function isFeatureEnabled($user): bool
    {
        // If using Spatie permissions, adapt accordingly
        return $user->can('use auto apply')
            || (bool) ($user->activeSubscription?->hasFeature('auto_apply') ?? false);
    }
}