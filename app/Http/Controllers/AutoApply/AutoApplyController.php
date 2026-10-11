<?php

namespace App\Http\Controllers\AutoApply;

use App\Http\Controllers\Controller;
use App\Models\AutoApplyRun;
use App\Models\AutoApplyRelay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutoApplyController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('manage job alerts')) abort(403);

        return view('job-seeker.auto-apply.index');
    }

    public function stats(): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $today = today();

        return response()->json([
            'success' => true,
            'stats' => [
                'runs_today'        => AutoApplyRun::where('run_date', $today)->count(),
                'completed_today'   => AutoApplyRun::where('run_date', $today)->where('status', 'completed')->count(),
                'failed_today'      => AutoApplyRun::where('run_date', $today)->where('status', 'failed')->count(),
                'applications_today'=> AutoApplyRun::where('run_date', $today)->sum('applied_count'),
                'relays_today'      => AutoApplyRun::where('run_date', $today)->sum('relayed_count'),
                'runs_this_month'   => AutoApplyRun::whereBetween('run_date', [now()->startOfMonth(), $today])->count(),
                'applications_this_month' => AutoApplyRun::whereBetween('run_date', [now()->startOfMonth(), $today])->sum('applied_count'),
                'relays_this_month' => AutoApplyRun::whereBetween('run_date', [now()->startOfMonth(), $today])->sum('relayed_count'),
            ],
        ]);
    }

    public function runs(Request $request): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $query = AutoApplyRun::with(['user:id,name,email'])
            ->orderByDesc('id');

        if ($d = $request->get('date')) {
            $query->where('run_date', $d);
        }
        if ($s = $request->get('status')) {
            $query->where('status', $s);
        }
        if ($search = $request->get('search')) {
            $query->whereHas('user', fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $runs = $query->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $runs->through(fn($r) => [
                'id'              => $r->id,
                'run_date'        => $r->run_date?->toDateString(),
                'user'            => $r->user?->name ?? 'Unknown',
                'email'           => $r->user?->email,
                'jobs_scanned'    => $r->jobs_scanned,
                'applied_count'   => $r->applied_count,
                'relayed_count'   => $r->relayed_count,
                'skipped_count'   => $r->skipped_count,
                'failed_count'    => $r->failed_count,
                'status'          => $r->status,
                'status_badge'    => $r->status_badge,
                'quota_total'     => $r->quota_total,
                'quota_remaining' => $r->quota_remaining,
                'started_at'      => $r->started_at?->toISOString(),
                'completed_at'    => $r->completed_at?->toISOString(),
            ]),
        ]);
    }

    public function relays(Request $request): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $relays = AutoApplyRelay::with(['user:id,name,email', 'jobPost:id,job_title,slug'])
            ->orderByDesc('id')
            ->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $relays->through(fn($r) => [
                'id'         => $r->id,
                'user'       => $r->user?->name ?? 'Unknown',
                'email'      => $r->user?->email,
                'job_title'  => $r->jobPost?->job_title,
                'job_slug'   => $r->jobPost?->slug,
                'reason'     => $r->reason,
                'channels_sent' => $r->channels_sent ?? [],
                'status'     => $r->status,
                'sent_at'    => $r->sent_at?->toISOString(),
            ]),
        ]);
    }
}