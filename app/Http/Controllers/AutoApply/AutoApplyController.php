<?php

namespace App\Http\Controllers\AutoApply;

use App\Http\Controllers\Controller;
use App\Models\AutoApplyRun;
use App\Models\AutoApplyRelay;
use App\Models\AutoApplyExternalDraft;
use App\Models\SeekerProfile;
use App\Models\User;
use App\Jobs\DraftAutoApplyJob;
use App\Services\AutoApply\Channels\EmailChannel;
use App\Services\AutoApply\Channels\WhatsAppChannel;
use App\Services\Subscription\SubscriptionGate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\AutoApply\EmployerApplicationMail;
use App\Services\AutoApply\ShareToSeekerService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Job\JobSeekerJob;

class AutoApplyController extends Controller
{
    public function __construct(protected SubscriptionGate $gate) {}

    public function index()
    {
        if (!auth()->user()->can('manage job alerts')) {
            abort(403);
        }

        $stats = [
            'drafts_ready' => AutoApplyRelay::where('status', 'queued')->count() 
                            + AutoApplyExternalDraft::where('status', 'drafted')->count(),
            'sent_today'   => AutoApplyRelay::where('status', 'sent')->whereDate('sent_at', today())->count(),
            'external_pending' => AutoApplyExternalDraft::where('status', 'drafted')->count(),
            'failed_today' => AutoApplyRun::whereDate('created_at', today())->sum('failed_count'),
        ];

        return view('job-seeker.auto-apply.index', compact('stats'));
    }

    /**
     * Trigger the drafting process for a specific user or all active subscribers.
     * POST /admin/auto-apply/trigger
     */
    public function triggerDraft(Request $request): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'all'     => 'nullable|boolean',
        ]);

        $queued = 0;

        if ($request->boolean('all')) {
            // Get all users with an active subscription that includes auto_apply
            $activeSubs = \App\Models\Subscription\Subscription::whereIn('status', ['active', 'trialing'])
                ->where('current_period_end', '>', now())
                ->pluck('user_id');

            $seekers = SeekerProfile::whereIn('user_id', $activeSubs)
                ->where('is_active', true)
                ->get();

            foreach ($seekers as $seeker) {
                DraftAutoApplyJob::dispatch($seeker->id)->onQueue('auto-apply');
                $queued++;
            }
        } else if ($request->filled('user_id')) {
            $seeker = SeekerProfile::where('user_id', $request->user_id)->first();
            if ($seeker) {
                DraftAutoApplyJob::dispatch($seeker->id)->onQueue('auto-apply');
                $queued = 1;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Queued {$queued} auto-apply drafts.",
            'queued'  => $queued,
        ]);
    }


    // ── 1. Send Email (CC Seeker, Attach Docs, Log WhatsApp) ──
    public function sendRelay(Request $request, $id): JsonResponse
    {
        $relay = AutoApplyRelay::with(['user', 'jobPost'])->findOrFail($id);
        $seeker = SeekerProfile::where('user_id', $relay->user_id)->first();

        if (!$seeker) {
            return response()->json(['success' => false, 'message' => 'Seeker not found.'], 404);
        }

        // 1. Resolve Employer Email
        $toEmail = $relay->jobPost->email;
        if (!$toEmail) {
            $text = strip_tags(($relay->jobPost->qualifications ?? '') . ' ' . ($relay->jobPost->application_procedure ?? ''));
            if (preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $text, $matches)) {
                $toEmail = $matches[0];
            }
        }

        if (!$toEmail) {
            return response()->json(['success' => false, 'message' => 'No employer email found in the job post.'], 422);
        }

        // 2. Send Email with CC
        try {
            Mail::to($toEmail)
                ->cc($seeker->user->email)
                ->send(new EmployerApplicationMail(
                    seeker: $seeker,
                    job: $relay->jobPost,
                    cvHtml: $relay->tailored_cv_html,
                    coverHtml: $relay->cover_letter_html
                ));

            // 3. Update Relay
            $relay->update([
                'status' => 'sent',
                'sent_at' => now(),
                'employer_email' => $toEmail,
                'seeker_cc_email' => $seeker->user->email,
            ]);

            return response()->json(['success' => true, 'message' => 'Application sent successfully.']);

        } catch (\Throwable $e) {
            \Log::error('Send failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send: ' . $e->getMessage()], 500);
        }
    }

    // ── 2. Mark External as Applied AND Share to WhatsApp ──
    public function markApplied(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $draft = AutoApplyExternalDraft::findOrFail($id);
        
        // 1. Mark the draft as applied
        $draft->update(['status' => 'applied']);

        // 2. Log into history with the Draft ID
        $seekerId = SeekerProfile::where('user_id', $draft->user_id)->value('id');
        if ($seekerId) {
            \App\Models\Job\JobSeekerJob::updateOrCreate(
                [
                    'seeker_profile_id' => $seekerId,
                    'job_post_id'       => $draft->job_post_id,
                ],
                [
                    'is_applied'            => true,
                    'applied_at'            => now(),
                    'notes'                 => 'Applied via External Link',
                    'auto_apply_draft_id'   => $draft->id,      // <--- Save ID
                    'auto_apply_draft_type' => 'external',      // <--- Save Type
                ]
            );
            \App\Models\Job\JobPost::where('id', $draft->job_post_id)->increment('application_count');
        }

        return response()->json([
            'success' => true,
            'message' => 'Marked as applied.',
        ]);
    }

    /**
     * GET /admin/auto-apply/status
     * Returns the current drafting status for the last run.
     * Used by the frontend polling to know when AI is done.
     */
    public function status(Request $request): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        // Get the most recent run across all users (the one we just triggered)
        $run = AutoApplyRun::where('run_date', today())
            ->orderByDesc('id')
            ->first();

        if (!$run) {
            return response()->json([
                'success' => true,
                'status'  => 'idle',
                'message' => 'No runs today yet.',
            ]);
        }

        // Count queued/pending relays and drafts
        $draftsReady = AutoApplyRelay::where('status', 'queued')->count()
                    + AutoApplyExternalDraft::where('status', 'drafted')->count();

        // Count still-processing relays (queued jobs on the auto-apply queue)
        $queueSize = \DB::table('jobs')->where('queue', 'auto-apply')->count();

        return response()->json([
            'success' => true,
            'run'     => [
                'id'            => $run->id,
                'status'        => $run->status,
                'relayed_count' => (int) $run->relayed_count,
                'skipped_count' => (int) $run->skipped_count,
                'failed_count'  => (int) $run->failed_count,
                'completed_at'  => $run->completed_at?->toISOString(),
            ],
            'drafts_ready' => $draftsReady,
            'queue_size'   => $queueSize,
            'is_done'      => $run->status === 'drafted' && $queueSize === 0,
        ]);
    }


    /**
     * GET /admin/auto-apply/drafts
     *
     * Query params:
     *   - search   : string (seeker name/email, job title, employer email)
     *   - type     : 'relay' | 'external' | '' (all)
     *   - channel  : 'email' | 'whatsapp' | '' (only applies to relay)
     *   - page     : int
     *   - per_page : int (default 20)
     */
    public function drafts(Request $request): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $search  = trim((string) $request->input('search', ''));
        $type    = $request->input('type');       // relay | external | null
        $channel = $request->input('channel');    // email | whatsapp | null

        $like = '%' . $search . '%';

        // ── Relay query ──
        $relayQuery = AutoApplyRelay::with([
                'user:id,name,email',
                'jobPost:id,job_title,slug,email,qualifications,application_procedure,is_resume_required,is_cover_letter_required,is_academic_documents_required,is_application_required',
            ])
            ->where('status', 'queued');

        if ($search !== '') {
            $relayQuery->where(function ($q) use ($like) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', $like)->orWhere('email', 'like', $like))
                ->orWhereHas('jobPost', fn($jq) => $jq->where('job_title', 'like', $like)->orWhere('email', 'like', $like));
            });
        }

        // channel filter — stored as an array in channels_attempted, so use JSON LIKE
        if (in_array($channel, ['email', 'whatsapp'], true)) {
            $relayQuery->where('channels_attempted', 'like', '%"' . $channel . '"%');
        }

        // ── External query ──
        $externalQuery = AutoApplyExternalDraft::with([
                'user:id,name,email',
                'jobPost:id,job_title,slug,email,qualifications,application_procedure,is_resume_required,is_cover_letter_required,is_academic_documents_required,is_application_required',
            ])
            ->where('status', 'drafted');

        if ($search !== '') {
            $externalQuery->where(function ($q) use ($like) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', $like)->orWhere('email', 'like', $like))
                ->orWhereHas('jobPost', fn($jq) => $jq->where('job_title', 'like', $like)->orWhere('email', 'like', $like));
            });
        }

        // ── Decide what to query based on type filter ──
        $includeRelays   = !in_array($type, ['external'], true);
        $includeExternal = !in_array($type, ['relay'], true);

        $relays = $includeRelays
            ? $relayQuery->orderByDesc('id')->get()->map(fn($r) => [
                'id'              => $r->id,
                'type'            => 'relay',
                'user_name'       => $r->user?->name ?? 'Unknown',
                'user_email'      => $r->user?->email,
                'job_title'       => $r->jobPost?->job_title ?? '—',
                'job_slug'        => $r->jobPost?->slug,
                'channel'         => $r->channels_attempted[0] ?? 'email',
                'cv_html'         => $r->tailored_cv_html ?? '',
                'cover_html'      => $r->cover_letter_html ?? '',
                'employer_email'  => $r->jobPost?->email,
                'qualifications'  => $r->jobPost?->qualifications,
                'application_procedure' => $r->jobPost?->application_procedure,
                'job_requirements'=> [
                    'is_resume_required'             => (bool) ($r->jobPost?->is_resume_required ?? false),
                    'is_cover_letter_required'       => (bool) ($r->jobPost?->is_cover_letter_required ?? false),
                    'is_academic_documents_required' => (bool) ($r->jobPost?->is_academic_documents_required ?? false),
                    'is_application_required'        => (bool) ($r->jobPost?->is_application_required ?? false),
                ],
                'created_at'      => $r->created_at->toISOString(),
            ])
            : collect();

        $external = $includeExternal
            ? $externalQuery->orderByDesc('id')->get()->map(fn($e) => [
                'id'              => $e->id,
                'type'            => 'external',
                'user_name'       => $e->user?->name ?? 'Unknown',
                'user_email'      => $e->user?->email,
                'job_title'       => $e->jobPost?->job_title ?? '—',
                'job_slug'        => $e->jobPost?->slug,
                'external_url'    => $e->external_url,
                'cv_html'         => $e->tailored_cv_html ?? '',
                'cover_html'      => $e->cover_letter_html ?? '',
                'employer_email'  => $e->jobPost?->email,
                'qualifications'  => $e->jobPost?->qualifications,
                'application_procedure' => $e->jobPost?->application_procedure,
                'job_requirements'=> [
                    'is_resume_required'             => (bool) ($e->jobPost?->is_resume_required ?? false),
                    'is_cover_letter_required'       => (bool) ($e->jobPost?->is_cover_letter_required ?? false),
                    'is_academic_documents_required' => (bool) ($e->jobPost?->is_academic_documents_required ?? false),
                    'is_application_required'        => (bool) ($e->jobPost?->is_application_required ?? false),
                ],
                'created_at'      => $e->created_at->toISOString(),
            ])
            : collect();

        // ── Merge, sort, then paginate manually ──
        $merged = $relays->concat($external)
            ->sortByDesc('created_at')
            ->values();

        $total    = $merged->count();
        $page     = max(1, (int) $request->input('page', 1));
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page     = min($page, $lastPage);

        $items = $merged->forPage($page, $perPage)->values();

        // Keep the same shape the frontend already expects (relays/external split)
        $paginatedRelays   = $items->where('type', 'relay')->values();
        $paginatedExternal = $items->where('type', 'external')->values();

        // Build a Laravel paginator for the footer partial
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $paginationHtml = $paginator->hasPages()
            ? view('job-seeker.auto-apply.partials.pagination', ['paginator' => $paginator])->render()
            : '';

        return response()->json([
            'success' => true,
            'data'    => [
                'relays'   => $paginatedRelays,
                'external' => $paginatedExternal,
                'total'    => $total,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_pages'    => $paginator->hasPages(),
                'html'         => $paginationHtml,
            ],
            'filters' => [
                'search'  => $search,
                'type'    => $type,
                'channel' => $channel,
            ],
        ]);
    }


    
    /**
     * GET /admin/auto-apply/history
     * Returns successfully applied jobs for seekers with active auto-apply subscription.
     *
     * Query params:
     *   - search  : string (matches seeker name, email, job title, company)
     *   - source  : 'relay' | 'external' (filter by draft type)
     *   - from    : date (applied_at >= from)
     *   - to      : date (applied_at <= to)
     *   - page    : int
     *   - per_page: int (default 20)
     */
    public function history(Request $request): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $search  = trim((string) $request->input('search', ''));
        $source  = $request->input('source'); // 'relay' | 'external'
        $from    = $request->input('from');
        $to      = $request->input('to');

        // ── 1. Resolve user IDs that currently have an active auto-apply subscription ──
        $activeSubUserIds = $this->gate->userIdsWithAutoApply();

        if (empty($activeSubUserIds)) {
            return response()->json([
                'success' => true,
                'data'    => ['data' => []],
                'pagination' => [
                    'current_page' => 1,
                    'last_page'    => 1,
                    'total'        => 0,
                    'per_page'     => $perPage,
                ],
                'filters' => [
                    'search' => $search,
                    'source' => $source,
                    'from'   => $from,
                    'to'     => $to,
                ],
            ]);
        }

        // ── 2. Resolve seeker profile IDs for those users ──
        $seekerProfileIds = SeekerProfile::whereIn('user_id', $activeSubUserIds)
            ->pluck('id')
            ->all();

        if (empty($seekerProfileIds)) {
            return response()->json([
                'success' => true,
                'data'    => ['data' => []],
                'pagination' => [
                    'current_page' => 1,
                    'last_page'    => 1,
                    'total'        => 0,
                    'per_page'     => $perPage,
                ],
                'filters' => [
                    'search' => $search,
                    'source' => $source,
                    'from'   => $from,
                    'to'     => $to,
                ],
            ]);
        }

        // ── 3. Build the query ──
        $query = JobSeekerJob::with([
                'seekerProfile:id,user_id,first_name,last_name,phone',
                'seekerProfile.user:id,name,email',
                'jobPost:id,job_title,slug,country_code,email,telephone,application_procedure,qualifications,company_id',
                'jobPost.company:id,name',
            ])
            ->where('is_applied', true)
            ->whereIn('seeker_profile_id', $seekerProfileIds);

        // Date range (default: last 30 days)
        $query->where('applied_at', '>=', $from ? \Carbon\Carbon::parse($from)->startOfDay() : now()->subDays(30));
        if ($to) {
            $query->where('applied_at', '<=', \Carbon\Carbon::parse($to)->endOfDay());
        }

        // Source filter (relay vs external)
        if (in_array($source, ['relay', 'external'], true)) {
            $query->where('auto_apply_draft_type', $source);
        }

        // Search: seeker name/email, job title, company name
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('seekerProfile', function ($sq) use ($like) {
                    $sq->where('first_name', 'like', $like)
                       ->orWhere('last_name', 'like', $like)
                       ->orWhere('phone', 'like', $like)
                       ->orWhereHas('user', function ($uq) use ($like) {
                           $uq->where('name', 'like', $like)
                              ->orWhere('email', 'like', $like);
                       });
                })
                ->orWhereHas('jobPost', function ($jq) use ($like) {
                    $jq->where('job_title', 'like', $like)
                       ->orWhereHas('company', function ($cq) use ($like) {
                           $cq->where('name', 'like', $like);
                       });
                });
            });
        }

        $history = $query->orderByDesc('applied_at')->paginate($perPage);

        // ── 4. Transform the paginated results ──
        $transformed = $history->through(function ($item) {
            $draft = null;
            if ($item->auto_apply_draft_type === 'relay') {
                $draft = AutoApplyRelay::find($item->auto_apply_draft_id);
            } elseif ($item->auto_apply_draft_type === 'external') {
                $draft = AutoApplyExternalDraft::find($item->auto_apply_draft_id);
            }

            $jobUrl = $item->jobPost ? $item->jobPost->generateUrl() : null;

            return [
                'id'          => $item->id,
                'seeker_name' => $item->seekerProfile?->full_name ?? 'Unknown',
                'seeker_email'=> $item->seekerProfile?->user?->email,
                'seeker_phone'=> $item->seekerProfile?->phone,
                'job_title'   => $item->jobPost?->job_title ?? '—',
                'company'     => $item->jobPost?->company?->name ?? '—',
                'job_url'     => $jobUrl,
                'applied_at'  => $item->applied_at?->toISOString(),
                'source'      => $item->notes ?? 'Auto-Apply',
                'draft_type'  => $item->auto_apply_draft_type,
                'cv_html'     => $draft?->tailored_cv_html ?? '',
                'cover_html'  => $draft?->cover_letter_html ?? '',
                'employer_email' => $item->jobPost?->email,
                'employer_phone' => $item->jobPost?->telephone,
                'application_procedure' => $item->jobPost?->application_procedure,
            ];
        });

        // ── 5. Build pagination links (Metronic-friendly) ──
        $paginator = $history->appends($request->only(['search', 'source', 'from', 'to', 'per_page']));

        $paginationHtml = '';
        if ($paginator->hasPages()) {
            $paginationHtml = view('job-seeker.auto-apply.partials.pagination', [
                'paginator' => $paginator,
            ])->render();
        }

        return response()->json([
            'success' => true,
            'data'    => $transformed,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'has_pages'    => $paginator->hasPages(),
                'html'         => $paginationHtml,   // ready-to-inject Metronic pagination
            ],
            'filters' => [
                'search' => $search,
                'source' => $source,
                'from'   => $from,
                'to'     => $to,
            ],
        ]);
    }

    public function downloadDraft(Request $request, $id)
    {
        if (!auth()->user()->can('manage job alerts')) abort(403);

        $type     = $request->query('type', 'relay');
        $document = $request->query('document', 'cv');

        if ($type === 'external') {
            $model = AutoApplyExternalDraft::findOrFail($id);
            $html = $document === 'cv' ? $model->tailored_cv_html : $model->cover_letter_html;
            $baseName = 'External_' . $model->id;
        } else {
            $model = AutoApplyRelay::findOrFail($id);
            $html = $document === 'cv' ? $model->tailored_cv_html : $model->cover_letter_html;
            $baseName = 'Relay_' . $model->id;
        }

        if (!$html) abort(404, 'Document not available.');

        // Render → stream → discard. NOTHING is written to disk.
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.cv-rewrite', [
            'title'   => $document === 'cv' ? 'Tailored CV' : 'Cover Letter',
            'content' => $html,
        ])->setPaper('a4');

        return $pdf->download("{$baseName}_{$document}.pdf");
    }

    /**
     * Update the HTML content of a draft (CV or Cover Letter).
     * POST /admin/auto-apply/update-draft/{id}
     */
    public function updateDraftContent(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $validated = $request->validate([
            'type'      => 'required|in:relay,external',
            'document'  => 'required|in:cv,cover',
            'content'   => 'required|string',
        ]);

        if ($validated['type'] === 'external') {
            $model = AutoApplyExternalDraft::findOrFail($id);
        } else {
            $model = AutoApplyRelay::findOrFail($id);
        }

        if ($validated['document'] === 'cv') {
            $model->tailored_cv_html = $validated['content'];
        } else {
            $model->cover_letter_html = $validated['content'];
        }

        $model->save();

        return response()->json(['success' => true, 'message' => 'Draft updated successfully.']);
    }

    public function retryRelay(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $relay = AutoApplyRelay::findOrFail($id);

        // Reset the attempt counter so the admin can force a fresh start
        $relay->update([
            'status'        => 'queued',
            'send_attempts' => 0,
            'next_retry_at' => null,
            'error_message' => null,
        ]);

        \App\Jobs\RetrySendRelayJob::dispatch($relay->id);

        return response()->json([
            'success' => true,
            'message' => "Retry queued.",
        ]);
    }

    public function markFailed(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $relay = AutoApplyRelay::findOrFail($id);
        $relay->update([
            'status'        => 'failed',
            'next_retry_at' => null,
            'error_message' => $request->input('reason', 'Manually marked failed by admin.'),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Marked as failed.",
        ]);
    }

    /**
     * Delete a draft / log.
     * DELETE /admin/auto-apply/draft/{id}?type=relay|external
     */
    public function deleteDraft(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $type = $request->query('type', 'relay');

        if ($type === 'external') {
            AutoApplyExternalDraft::findOrFail($id)->delete();
        } else {
            AutoApplyRelay::findOrFail($id)->delete();
        }

        return response()->json(['success' => true, 'message' => 'Draft deleted.']);
    }

    /**
     * Retry drafting for a specific seeker.
     * Deletes their failed drafts for today, resets the run, and re-queues.
     * POST /admin/auto-apply/retry-user/{userId}
     */
    public function retryUserRun(Request $request, $userId): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $seeker = SeekerProfile::where('user_id', $userId)->first();
        if (!$seeker) {
            return response()->json(['success' => false, 'message' => 'Seeker not found.'], 404);
        }

        // Clear today's drafts for this user
        AutoApplyRelay::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->delete();

        AutoApplyExternalDraft::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->delete();

        // Delete today's run so firstOrCreate starts fresh
        AutoApplyRun::where('user_id', $userId)
            ->whereDate('run_date', today())
            ->delete();

        // Re-dispatch
        \App\Jobs\DraftAutoApplyJob::dispatch($seeker->id)->onQueue('auto-apply');

        return response()->json([
            'success' => true,
            'message' => 'Retry queued. Drafts will regenerate shortly.',
        ]);
    }

    /**
     * GET /admin/auto-apply/logs
     *
     * Query params:
     *   - search   : string (user name/email)
     *   - status   : 'queued'|'drafting'|'drafted'|'completed'|'failed'|'quota_exhausted'|'no_cv'|'no_preferences'
     *   - from     : date
     *   - to       : date
     *   - page     : int
     *   - per_page : int (default 20)
     */
    public function logs(Request $request): JsonResponse
    {
        if (!auth()->user()->can('manage job alerts')) {
            return response()->json(['success' => false], 403);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $search  = trim((string) $request->input('search', ''));
        $status  = $request->input('status');
        $from    = $request->input('from');
        $to      = $request->input('to');

        $query = AutoApplyRun::with('user:id,name,email');

        // Search: user name or email
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->whereHas('user', fn($uq) => $uq->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }

        if (in_array($status, [
            'queued', 'drafting', 'drafted', 'processing', 'completed',
            'failed', 'quota_exhausted', 'no_cv', 'no_preferences',
        ], true)) {
            $query->where('status', $status);
        }

        if ($from) {
            $query->whereDate('run_date', '>=', \Carbon\Carbon::parse($from)->toDateString());
        }
        if ($to) {
            $query->whereDate('run_date', '<=', \Carbon\Carbon::parse($to)->toDateString());
        }

        $runs = $query->orderByDesc('id')->paginate($perPage)->appends($request->query());

        $transformed = $runs->through(fn($run) => [
            'id'            => $run->id,
            'user_id'       => $run->user_id,
            'run_date'      => $run->run_date?->toDateString(),
            'user_name'     => $run->user?->name,
            'user_email'    => $run->user?->email,
            'jobs_scanned'  => (int) $run->jobs_scanned,
            'applied_count' => (int) $run->applied_count,
            'relayed_count' => (int) $run->relayed_count,
            'skipped_count' => (int) $run->skipped_count,
            'failed_count'  => (int) $run->failed_count,
            'status'        => $run->status,
            'status_badge'  => $run->status_badge,
            'error_message' => $run->error_message,
            'created_at'    => $run->created_at->toISOString(),
        ]);

        // ── Stats remain global (not filtered) so the numbers are always meaningful ──
        $stats = [
            'runs_today'         => AutoApplyRun::whereDate('created_at', today())->count(),
            'applications_today' => AutoApplyRun::whereDate('created_at', today())->sum('applied_count'),
            'relays_today'       => AutoApplyRun::whereDate('created_at', today())->sum('relayed_count'),
            'failed_today'       => AutoApplyRun::whereDate('created_at', today())->sum('failed_count'),
        ];

        $paginationHtml = $runs->hasPages()
            ? view('job-seeker.auto-apply.partials.pagination', ['paginator' => $runs])->render()
            : '';

        return response()->json([
            'success' => true,
            'data'    => $transformed,
            'pagination' => [
                'current_page' => $runs->currentPage(),
                'last_page'    => $runs->lastPage(),
                'total'        => $runs->total(),
                'per_page'     => $runs->perPage(),
                'from'         => $runs->firstItem(),
                'to'           => $runs->lastItem(),
                'has_pages'    => $runs->hasPages(),
                'html'         => $paginationHtml,
            ],
            'stats'   => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'from'   => $from,
                'to'     => $to,
            ],
        ]);
    }


}