<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Hiring\HiringRun;
use App\Models\Hiring\HiringRunCandidate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class HiringRunsAdminController extends Controller
{
    /**
     * Admin listing page.
     */
    public function index()
    {
        if (!auth()->user() || !auth()->user()->can('view employer')) {
            abort(403, 'You do not have permission to view CV shortlisting runs.');
        }

        return view('employer.hiring-runs.index');
    }

    /**
     * DataTable endpoint.
     */
    public function data(Request $request): JsonResponse
    {
        if (!auth()->user()->can('view employer')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $sort   = $request->get('sort', 'recent');

        $query = HiringRun::query()->with(['user:id,name,email', 'company:id,name']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('uuid', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('company', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        $runs = $query->paginate(30);

        return response()->json([
            'success' => true,
            'data'    => $runs->through(fn($r) => $this->formatRun($r)),
            'meta'    => [
                'total'        => $runs->total(),
                'current_page' => $runs->currentPage(),
                'last_page'    => $runs->lastPage(),
            ],
            'counts'  => [
                'all'        => HiringRun::count(),
                'queued'     => HiringRun::where('status', 'queued')->count(),
                'processing' => HiringRun::where('status', 'processing')->count(),
                'completed'  => HiringRun::where('status', 'completed')->count(),
                'failed'     => HiringRun::where('status', 'failed')->count(),
            ],
        ]);
    }

    /**
     * Aggregate stats for the top cards.
     */
    public function stats(): JsonResponse
    {
        if (!auth()->user()->can('view employer')) {
            return response()->json(['success' => false], 403);
        }

        return response()->json([
            'success' => true,
            'stats'   => [
                'total_runs'       => HiringRun::count(),
                'total_cvs'        => HiringRunCandidate::count(),
                'runs_this_month'  => HiringRun::where('created_at', '>=', now()->startOfMonth())->count(),
                'cvs_this_month'   => HiringRunCandidate::whereHas('run', fn($q) => $q->where('created_at', '>=', now()->startOfMonth()))->count(),
                'active_employers' => HiringRun::distinct('user_id')->count('user_id'),
                'top_employers'    => HiringRun::selectRaw('user_id, COUNT(*) as runs_count, SUM(total) as cvs_count')
                    ->with('user:id,name')
                    ->groupBy('user_id')
                    ->orderByDesc('runs_count')
                    ->limit(5)
                    ->get()
                    ->map(fn($row) => [
                        'user' => $row->user?->name ?? 'Unknown',
                        'runs' => (int) $row->runs_count,
                        'cvs'  => (int) $row->cvs_count,
                    ]),
            ],
        ]);
    }

    /**
     * Detail page for a single run.
     */
    public function show(string $uuid)
    {
        if (!auth()->user()->can('view employer')) {
            abort(403, 'You do not have permission to view this run.');
        }

        $run = HiringRun::where('uuid', $uuid)->firstOrFail();

        return view('employer.hiring-runs.show', compact('uuid', 'run'));
    }

    /**
     * Detail payload for the show page.
     */
    public function detail(string $uuid): JsonResponse
    {
        if (!auth()->user()->can('view employer')) {
            return response()->json(['success' => false], 403);
        }

        $run = HiringRun::with(['user:id,name,email', 'company:id,name'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $candidates = HiringRunCandidate::where('hiring_run_id', $run->id)
            ->orderByDesc('score')
            ->paginate(100);

        return response()->json([
            'success' => true,
            'run'     => $this->formatRun($run, true),
            'data'    => $candidates->through(fn($c) => $this->formatCandidate($c)),
            'meta'    => [
                'total'        => $candidates->total(),
                'current_page' => $candidates->currentPage(),
                'last_page'    => $candidates->lastPage(),
            ],
        ]);
    }

    /**
     * CSV export.
     */
    public function export(string $uuid)
    {
        if (!auth()->user()->can('view employer')) {
            abort(403);
        }

        $run = HiringRun::where('uuid', $uuid)->firstOrFail();

        $candidates = HiringRunCandidate::where('hiring_run_id', $run->id)
            ->orderByDesc('score')
            ->get();

        $filename = 'shortlist-' . \Illuminate\Support\Str::slug($run->title)
                  . '-' . $run->uuid . '.csv';

        return response()->streamDownload(function () use ($candidates) {
            $fh = fopen('php://output', 'w');

            fputcsv($fh, [
                'Rank', 'Name', 'Email', 'Phone', 'Current Title',
                'Years Exp', 'Education', 'Score', 'Recommendation',
                'Matched Skills', 'Missing Skills', 'Summary', 'File',
            ]);

            foreach ($candidates as $i => $c) {
                fputcsv($fh, [
                    $i + 1,
                    $c->candidate_name ?: $c->original_name,
                    $c->candidate_email,
                    $c->candidate_phone,
                    $c->current_title,
                    $c->years_of_experience,
                    $c->highest_education,
                    $c->score,
                    $c->recommendation,
                    implode(', ', $c->matched_skills ?? []),
                    implode(', ', $c->missing_skills ?? []),
                    $c->summary,
                    $c->original_name,
                ]);
            }

            fclose($fh);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ─────────────────────────────────────────────────────────────
    // Formatters
    // ─────────────────────────────────────────────────────────────
    private function formatRun(HiringRun $r, bool $detailed = false): array
    {
        $base = [
            'uuid'              => $r->uuid,
            'title'             => $r->title,
            'status'            => $r->status,
            'status_badge'      => $this->statusBadge($r->status),
            'total'             => $r->total,
            'processed'         => $r->processed,
            'failed'            => $r->failed,
            'progress_percent'  => $r->progress_percent,
            'employer'          => [
                'id'    => $r->user?->id,
                'name'  => $r->user?->name ?? 'Unknown',
                'email' => $r->user?->email,
            ],
            'company'           => $r->company ? [
                'id'   => $r->company->id,
                'name' => $r->company->name,
            ] : null,
            'job_post_id'       => $r->job_post_id,
            'created_at'        => $r->created_at?->toISOString(),
            'started_at'        => $r->started_at?->toISOString(),
            'completed_at'      => $r->completed_at?->toISOString(),
        ];

        if ($detailed) {
            $base['job_description_preview'] = \Illuminate\Support\Str::limit($r->job_description, 500);
            $base['job_description_source']  = $r->job_description_source;
        }

        return $base;
    }

    private function formatCandidate(HiringRunCandidate $c): array
    {
        return [
            'id'                    => $c->id,
            'name'                  => $c->candidate_name ?: pathinfo($c->original_name, PATHINFO_FILENAME),
            'email'                 => $c->candidate_email,
            'phone'                 => $c->candidate_phone,
            'current_title'         => $c->current_title,
            'years_of_experience'   => $c->years_of_experience,
            'highest_education'     => $c->highest_education,
            'score'                 => $c->score,
            'score_color'           => $c->score_color,
            'recommendation'        => $c->recommendation,
            'recommendation_badge'  => $c->recommendation_badge,
            'summary'               => $c->summary,
            'strengths'             => $c->strengths ?? [],
            'gaps'                  => $c->gaps ?? [],
            'red_flags'             => $c->red_flags ?? [],
            'matched_skills'        => $c->matched_skills ?? [],
            'missing_skills'        => $c->missing_skills ?? [],
            'status'                => $c->status,
            'error_message'         => $c->error_message,
            'file_name'             => $c->original_name,
            'file_url'              => $c->stored_path ? Storage::disk('public')->url($c->stored_path) : null,
            'screened_at'           => $c->screened_at?->toISOString(),
        ];
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            'queued'     => '<span class="badge badge-light-secondary">Queued</span>',
            'processing' => '<span class="badge badge-light-primary">Processing</span>',
            'completed'  => '<span class="badge badge-light-success">Completed</span>',
            'failed'     => '<span class="badge badge-light-danger">Failed</span>',
            default      => '<span class="badge badge-light-secondary">' . e($status) . '</span>',
        };
    }
}