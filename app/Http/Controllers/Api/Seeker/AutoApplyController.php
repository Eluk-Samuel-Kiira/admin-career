<?php

namespace App\Http\Controllers\Api\Seeker;

use App\Http\Controllers\Controller;
use App\Models\AutoApplyRelay;
use App\Models\AutoApplyRun;
use App\Models\Job\JobPost;
use App\Services\Subscription\SubscriptionGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class AutoApplyController extends Controller
{
    public function __construct(protected SubscriptionGate $gate) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);

        $seeker = $user->seekerProfile;
        if (!$seeker) return response()->json(['success' => false, 'message' => 'Seeker profile not found'], 404);

        $featureEnabled = $this->gate->hasAutoApply($user);

        // Runs, last 30 days
        $runs = AutoApplyRun::where('user_id', $user->id)
            ->orderByDesc('run_date')
            ->limit(30)
            ->get();

        // Relay details grouped by run
        $relaysByRun = AutoApplyRelay::where('user_id', $user->id)
            ->with(['jobPost:id,job_title,slug,company_id', 'jobPost.company:id,name'])
            ->orderByDesc('id')
            ->get()
            ->groupBy('auto_apply_run_id');

        $runsOut = $runs->map(function (AutoApplyRun $run) use ($relaysByRun) {
            $relays = $relaysByRun[$run->id] ?? collect();

            return [
                'id'              => $run->id,
                'run_date'        => $run->run_date?->toDateString(),
                'status'          => $run->status,
                'status_label'    => $this->statusLabel($run->status),
                'applied_count'   => $run->applied_count,
                'relayed_count'   => $run->relayed_count,
                'skipped_count'   => $run->skipped_count,
                'failed_count'    => $run->failed_count,
                'quota_total'     => $run->quota_total,
                'quota_remaining' => $run->quota_remaining,
                'relays' => $relays->map(fn($r) => [
                    'id'        => $r->id,
                    'job_title' => $r->jobPost?->job_title,
                    'job_slug'  => $r->jobPost?->slug,
                    'company'   => $r->jobPost?->company?->name,
                    'reason'    => $r->reason,
                    'channels'  => $r->channels_sent ?? [],
                    'status'    => $r->status,
                    'sent_at'   => $r->sent_at?->toISOString(),
                ])->values(),
            ];
        });

        return response()->json([
            'success'         => true,
            // 'feature_enabled' => $featureEnabled,
            'stats' => [
                'total_applied' => $runs->sum('applied_count'),
                'total_relayed' => $runs->sum('relayed_count'),
                'this_month'    => $runs->where('run_date', '>=', now()->startOfMonth())->sum('applied_count'),
                'quota_total'   => $this->gate->autoApplyQuota($user),
            ],
            'runs' => $runsOut,
        ]);
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'queued'          => 'Queued',
            'processing'      => 'Processing',
            'completed'       => 'Completed',
            'failed'          => 'Failed',
            'quota_exhausted' => 'Monthly quota reached',
            'no_cv'           => 'Upload a CV to enable',
            'no_preferences'  => 'Set your job preferences',
            default           => ucfirst($status),
        };
    }
}