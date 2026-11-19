<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\Service\CvReviewRequest;
use App\Models\Currency;
use App\Models\Service\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use App\Models\Subscription\Subscription;

use App\Services\Jobs\AiService;
use App\Services\Jobs\CvTextExtractionService;
use App\Services\Delivery\ReviewDeliveryService;
use Illuminate\Support\Facades\DB;
use App\Jobs\RunAiReviewJob;
use App\Jobs\GenerateCvRewriteJob;
use App\Jobs\GenerateCoverLetterJob;
use App\Services\Jobs\CvHtmlSanitizer;


class CvReviewRequestController extends Controller
{

    /**
     * Run AI gap review — one click, stores JSON, advances status.
     */
    public function runAiReview($id)
    {
        if (!auth()->user()->can('edit cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $req = CvReviewRequest::findOrFail($id);

            if (!in_array($req->status, ['submitted', 'ai_reviewed'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => "AI review can only run from 'submitted' or re-run from 'ai_reviewed'. Current: {$req->status}.",
                ], 422);
            }

            if (!Storage::disk('public')->exists($req->original_cv_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Original CV file is missing from storage.',
                ], 422);
            }

            // 1. Extract text
            $extractor = app(CvTextExtractionService::class);
            $resumeText = $extractor->extract(
                Storage::disk('public')->path($req->original_cv_path),
                Storage::disk('public')->mimeType($req->original_cv_path) ?? 'application/pdf'
            );

            if (trim($resumeText) === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'No text could be read from the CV. It may be a scanned image.',
                ], 422);
            }

            // 2. AI review
            $ai = app(AiService::class);
            $gapReview = $ai->reviewCvGaps($resumeText, $req->target_job_description);

            if (empty($gapReview)) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI returned an empty review. Check API keys or try again.',
                ], 500);
            }

            // 3. Store — leave admin_edited_review intact if admin already edited
            $updates = [
                'ai_gap_review' => $gapReview,
                'status'        => 'ai_reviewed',
            ];

            // Only clear the admin-edited version if it doesn't exist yet
            if (empty($req->admin_edited_review)) {
                $updates['admin_edited_review'] = null; // no-op, but explicit
            }

            $req->update($updates);

            return response()->json([
                'success' => true,
                'message' => 'AI gap review completed.',
                'data'    => $this->shapeForAdmin($req->fresh()),
            ]);

        } catch (\Throwable $e) {
            \Log::error('AI review failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'AI review failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save admin-edited version of the review.
     * This is what the seeker will actually see.
     */
    public function saveEditedReview(Request $request, $id)
    {
        if (!auth()->user()->can('edit cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'overall_score'      => 'nullable|integer|min:0|max:100',
            'summary'            => 'nullable|string|max:2000',
            'sections'           => 'nullable|array',
            'sections.*.section' => 'required_with:sections|string',
            'sections.*.status'  => 'required_with:sections|in:ok,weak,missing',
            'sections.*.note'    => 'nullable|string',
            'missing_fields'     => 'nullable|array',
            'missing_fields.*'   => 'string',
        ]);

        try {
            $req = CvReviewRequest::findOrFail($id);

            $edited = [
                'overall_score'  => (int) ($request->overall_score ?? 0),
                'summary'        => $request->summary ?? '',
                'sections'       => $request->sections ?? [],
                'missing_fields' => $request->missing_fields ?? [],
            ];

            $req->update(['admin_edited_review' => $edited]);

            return response()->json([
                'success' => true,
                'message' => 'Edited review saved. This is what the seeker will see.',
                'data'    => $this->shapeForAdmin($req->fresh()),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Save failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deliver the review via email, WhatsApp, or both.
     */
    public function deliver(Request $request, $id)
    {
        if (!auth()->user()->can('edit cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'channels'   => 'required|array|min:1',
            'channels.*' => 'in:email,whatsapp',
            'message'    => 'nullable|string|max:2000',
        ]);

        try {
            $req = CvReviewRequest::findOrFail($id);

            if (!$req->has_review) {
                return response()->json([
                    'success' => false,
                    'message' => 'No review exists yet. Run AI review first.',
                ], 422);
            }

            $delivery = app(ReviewDeliveryService::class);
            $results = $delivery->deliver(
                $req,
                $request->channels,
                $request->message
            );

            // Log the delivery
            $log = $req->delivery_log ?? [];
            $log[] = [
                'at'       => now()->toISOString(),
                'by'       => auth()->id(),
                'channels' => $request->channels,
                'results'  => $results,
                'message'  => $request->message,
            ];

            $req->update([
                'review_delivered_at'      => now(),
                'review_delivery_channel'  => implode(',', $request->channels),
                'delivery_log'             => $log,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Review delivered.',
                'results' => $results,
                'data'    => $this->shapeForAdmin($req->fresh()),
            ]);

        } catch (\Throwable $e) {
            \Log::error('Delivery failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Delivery failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function resolveNextService(CvReviewRequest $r): array
    {
        // After answers have been submitted, the seeker is not paying for the review —
        // they are paying for the rewrite. Before that, they still owe nothing.
        $isPurchaseStep = in_array($r->status, ['awaiting_payment'], true);

        if (!$isPurchaseStep) {
            return [
                'next_service_key'   => null,
                'next_service_name'  => null,
                'next_service_price' => null,
            ];
        }

        $countryCode = strtoupper($r->country_code ?: config('app.country_code', 'UG'));
        $rewritePrice = \App\Models\Service\ServicePrice::resolve('cv_rewrite', $countryCode);

        return [
            'next_service_key'   => 'cv_rewrite',
            'next_service_name'  => 'CV Rewrite',
            'next_service_price' => $rewritePrice ? [
                'amount_cents' => (int) $rewritePrice->amount_cents,
                'currency_code' => $rewritePrice->currency->code ?? 'UGX',
                'formatted'    => $rewritePrice->formatted_amount,
            ] : null,
        ];
    }

    /**
     * Shape a request for admin-side JSON.
     */
    private function shapeForAdmin(CvReviewRequest $r): array
    {
        $next = $this->resolveNextService($r);

        return [
            'id'                    => $r->id,
            'uuid'                  => $r->uuid,
            'status'                => $r->status,
            'status_badge'          => $r->status_badge,
            'payment_badge'         => $r->payment_badge,
            'country_label'         => $r->country_label,
            'sla_badge'             => $r->sla_badge,
            'formatted_price'       => $r->formatted_amount,
            'client_name'           => $r->user->name ?? '—',
            'client_email'          => $r->user->email ?? '—',
            'client_phone'          => $r->user->phone ?? null,
            'service_name'          => $r->service->name ?? '—',
            'assignee_name'         => $r->assignedAdmin->name ?? null,
            'cv_download_url'        => $r->cv_download_url,   
            'cv_name'                => $r->cv_display_name,   
            'has_cv_file'            => $r->has_cv_file,  
            'ai_gap_review'         => $r->ai_gap_review,
            'admin_edited_review'   => $r->admin_edited_review,
            'effective_review'      => $r->effective_review,
            'has_review'            => $r->has_review,
            'review_delivered'      => $r->review_delivered,
            'review_delivered_at'   => $r->review_delivered_at?->toISOString(),
            'review_delivery_channel'=> $r->review_delivery_channel,
            'seeker_gap_answers'    => $r->seeker_gap_answers,
            'allowed_next_statuses' => $r->allowedNextStatuses(),
            'target_job_title'      => $r->target_job_title,
            'next_service_key'   => $next['next_service_key'],
            'next_service_name'  => $next['next_service_name'],
            'next_service_price' => $next['next_service_price'],

            'rewrite_mode'                  => $r->rewrite_mode,
            'rewrite_is_ai_mode'            => $r->rewrite_is_ai_mode,
            'rewrite_is_human_mode'         => $r->rewrite_is_human_mode,
            'rewrite_is_ready'              => $r->rewrite_is_ready,
            'rewrite_is_user_edited'        => $r->rewrite_is_user_edited,
            'rewrite_user_edited_at'        => $r->rewrite_user_edited_at?->toISOString(),
            'rewrite_generated_at'          => $r->rewrite_generated_at?->toISOString(),
            'rewrite_regeneration_count'    => $r->rewrite_regeneration_count,
            'rewrite_max_regenerations'     => $r->rewrite_max_regenerations,
            'rewrite_error_message'         => $r->rewrite_error_message,
            'rewrite_can_regenerate'        => $r->rewrite_can_regenerate,
            'rewrite_download_url'          => $r->rewrite_download_url,
            // Include the raw HTML so admin modal can preview without a second fetch
            'rewrite_content_html'          => $r->rewrite_content['html'] ?? null,
            'rewrite_content_original_html' => $r->rewrite_content_original['html'] ?? null,
        ];
    }

    /**
     * Display the listing page.
     */
    public function index()
    {
        if (!auth()->user()->can('view cv review requests')) {
            abort(403, 'You do not have permission to view CV review requests.');
        }

        return view('service.cv-review.index');
    }

    /**
     * DataTable JSON.
     */
    public function getData(Request $request)
    {
        $search       = $request->get('search', '');
        $status       = $request->get('status', '');
        $paymentState = $request->get('payment_state', ''); // all | paid | awaiting | not_required
        $countryCode  = $request->get('country_code', '');
        $assignedTo   = $request->get('assigned_admin_id', '');
        $slaFilter    = $request->get('sla', ''); // '' | overdue | due_soon
        $page         = (int) $request->get('page', 1);
        $perPage      = (int) $request->get('per_page', 15);
        

        $query = CvReviewRequest::with(['user', 'service', 'currency', 'country', 'assignedAdmin']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                  ->orWhere('target_job_title', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhere('original_cv_name', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if ($mode = $request->get('rewrite_mode')) {
            if (in_array($mode, ['ai', 'human'], true)) {
                $query->where('rewrite_mode', $mode);
            }
        }

        if ($origin = $request->get('origin')) {
            if ($origin === 'seeker') {
                $query->whereNull('created_by_admin_id');
            } elseif ($origin === 'subscriber') {
                $query->where(function ($q) {
                    $q->whereNotNull('created_by_admin_id')
                    ->orWhere('covered_by_subscription', true);
                });
            }
        }

        if (!empty($countryCode)) {
            $query->where('country_code', strtoupper($countryCode));
        }

        if (!empty($assignedTo)) {
            $query->where('assigned_admin_id', $assignedTo);
        }

        // Payment-state filter — this is the "check payments received" switch
        if ($paymentState === 'paid') {
            $query->paid();
        } elseif ($paymentState === 'awaiting') {
            $query->awaitingPayment();
        } elseif ($paymentState === 'not_required') {
            $query->whereIn('status', ['submitted', 'ai_reviewed', 'cancelled']);
        }

        if ($slaFilter === 'overdue') {
            $query->slaBreached();
        } elseif ($slaFilter === 'due_soon') {
            $query->whereNotNull('sla_due_at')
                ->whereBetween('sla_due_at', [now(), now()->addHours(6)])
                ->whereNotIn('status', ['delivered', 'completed', 'cancelled']);
        }

        $requests = $query->orderByRaw("
                FIELD(status,
                    'awaiting_payment',
                    'submitted',
                    'ai_reviewed',
                    'revision_requested',
                    'paid',
                    'in_progress',
                    'delivered',
                    'completed',
                    'cancelled'
                ) ASC
            ")
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);


        $requests->getCollection()->transform(function ($item) {
            $item->status_badge    = $item->status_badge;
            $item->payment_badge   = $item->payment_badge;
            $item->country_label   = $item->country_label;
            $item->sla_badge       = $item->sla_badge;
            $item->formatted_price = $item->formatted_amount;
            $item->client_name     = $item->user->name ?? '—';
            $item->client_email    = $item->user->email ?? '—';
            $item->service_name    = $item->service->name ?? '—';
            $item->assignee_name   = $item->assignedAdmin->name ?? null;

            // ⬇️ ADD THESE
            $item->cv_download_url = $item->cv_download_url;   // triggers accessor
            $item->cv_name         = $item->cv_display_name;
            $item->has_cv_file     = $item->has_cv_file;
            $item->seeker_gap_answers = $item->seeker_gap_answers;

            return $item;
        });

        return response()->json($requests);
    }

    /**
     * Show single request (returns JSON for edit modal prefill).
     */
    public function show($id)
    {
        if (!auth()->user()->can('view cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $req = CvReviewRequest::with(['user', 'service', 'currency', 'country', 'assignedAdmin'])->findOrFail($id);

            $req->status_badge    = $req->status_badge;
            $req->payment_badge   = $req->payment_badge;
            $req->country_label   = $req->country_label;
            $req->sla_badge       = $req->sla_badge;
            $req->formatted_price = $req->formatted_amount;
            $req->client_name     = $req->user->name ?? '—';
            $req->client_email    = $req->user->email ?? '—';
            $req->service_name    = $req->service->name ?? '—';
            $req->cv_download_url = $req->cv_download_url;
            $req->allowed_next_statuses = $req->allowedNextStatuses();

            return response()->json($req);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
    }

    /**
     * Download the AI-rewritten CV as a PDF — by request ID (admin).
     * Falls back to the cover letter if the request has no rewrite content
     * but a cover letter exists.
     */
    public function downloadRewritePdf($id)
    {
        if (!auth()->user()->can('view cv review requests')) {
            abort(403);
        }

        $req = CvReviewRequest::findOrFail($id);

        // Read the CURRENT stored content — this is what you edited
        $html = $req->rewrite_content['html'] ?? null;

        if (empty($html)) {
            abort(404, 'No CV content has been generated or edited for this request yet.');
        }

        // Soft sanitize: strip <script>, <style>, <iframe>, and on* handlers only.
        // Preserves layout, inline styles, tags you added.
        $html = $this->pdfSafeHtml($html);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.cv-rewrite', [
            'title'   => $req->target_job_title ?: 'Curriculum Vitae',
            'content' => $html,
        ])->setPaper('a4');

        $slug  = \Illuminate\Support\Str::slug($req->user->name ?? 'candidate');
        $title = \Illuminate\Support\Str::slug($req->target_job_title ?: 'cv');

        return $pdf->download("{$slug}_{$title}_cv.pdf");
    }

    /**
     * Only strips things that would break PDF rendering or execute scripts.
     * Leaves your edited formatting, inline styles, and layout intact.
     */
    protected function pdfSafeHtml(string $html): string
    {
        // Remove script/style/iframe blocks entirely
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is',   '', $html);
        $html = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $html);

        // Strip inline event handlers (onclick="...", onload="...", etc.)
        $html = preg_replace('#\son\w+\s*=\s*"[^"]*"#i', '', $html);
        $html = preg_replace("#\son\w+\s*=\s*'[^']*'#i", '', $html);

        // Strip javascript: URLs
        $html = preg_replace('#href\s*=\s*"javascript:[^"]*"#i', 'href="#"', $html);

        return $html;
    }

    public function downloadCoverLetterPdf($id)
    {
        if (!auth()->user()->can('view cv review requests')) {
            abort(403);
        }

        $req = CvReviewRequest::findOrFail($id);

        if (empty($req->cover_letter_uuid)) {
            abort(404, 'No cover letter for this request.');
        }

        $letter = \App\Models\Service\LetterRequest::where('uuid', $req->cover_letter_uuid)->first();
        if (!$letter) {
            abort(404, 'Cover letter record not found.');
        }

        // Read the CURRENT stored content
        $html = (string) $letter->content;

        if (trim(strip_tags($html)) === '') {
            abort(404, 'The cover letter has no content yet.');
        }

        $html = $this->pdfSafeHtml($html);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.cv-rewrite', [
            'title'   => 'Cover Letter',
            'content' => $html,
        ])->setPaper('a4');

        $slug  = \Illuminate\Support\Str::slug($req->user->name ?? 'candidate');
        $title = \Illuminate\Support\Str::slug($req->target_job_title ?: 'cover-letter');

        return $pdf->download("{$slug}_{$title}_cover-letter.pdf");
    }

    /**
     * Save admin edits to either the CV rewrite or the cover letter.
     * Body: { content: string, doc: 'cv' | 'cover' }
     */
    public function saveDocument(Request $request, $id)
    {
        if (!auth()->user()->can('edit cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'doc'     => ['required', Rule::in(['cv', 'cover'])],
            'content' => ['required', 'string', 'max:200000'],
        ]);

        if (mb_strlen(trim(strip_tags($data['content']))) < 20) {
            return response()->json(['success' => false, 'message' => 'The document is too short to save.'], 422);
        }

        try {
            $req   = CvReviewRequest::findOrFail($id);
            $entry = [
                'at'      => now()->toISOString(),
                'type'    => 'document_edited',
                'doc'     => $data['doc'],
                'by'      => auth()->id(),
                'by_name' => auth()->user()->name,
            ];
            $log = $req->delivery_log ?? [];

                        if ($data['doc'] === 'cv') {
                if ($req->rewrite_mode !== 'ai') {
                    return response()->json(['success' => false, 'message' => 'Only AI rewrites can be edited here.'], 422);
                }

                // Saved exactly as the admin edited it. No sanitising.
                $html = $data['content'];

                $req->update([
                    'rewrite_content'      => ['html' => $html],
                    'rewrite_is_user_edited' => false,   // admin edit, not seeker edit
                    'delivery_log'         => [...$log, $entry],
                ]);
            } else {
                $letter = $req->coverLetter;
                if (!$letter) {
                    return response()->json(['success' => false, 'message' => 'There is no cover letter to edit yet.'], 422);
                }

                $html = $data['content'];

                $letter->update(['content' => $html]);
                $req->update(['delivery_log' => [...$log, $entry]]);
            }

            return response()->json(['success' => true, 'message' => 'Saved.', 'html' => $html]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        } catch (\Throwable $e) {
            \Log::error('Save document failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }




    /**
     * Return the full detail HTML used by the "View" drawer/modal.
     */
    public function detail($id)
    {
        if (!auth()->user()->can('view cv review requests')) {
            abort(403);
        }

        $request = CvReviewRequest::with(['user', 'service', 'currency', 'country', 'assignedAdmin'])
            ->findOrFail($id);

        return view('service.cv-review.partials.detail', compact('request'));
    }

    /**
     * Update editable fields (assignee, SLA, notes, target job info, status).
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $req = CvReviewRequest::findOrFail($id);

            $validated = $request->validate([
                'status'              => ['nullable', Rule::in(array_keys(CvReviewRequest::statuses()))],
                'assigned_admin_id'   => 'nullable|exists:users,id',
                'sla_due_at'          => 'nullable|date',
                'admin_notes'         => 'nullable|string|max:5000',
                'target_job_title'    => 'nullable|string|max:255',
                'target_job_description' => 'nullable|string|max:10000',
                'payment_reference'   => 'nullable|string|max:255',
            ]);

            // Guard: only allow legal status transitions
            if (!empty($validated['status']) && $validated['status'] !== $req->status) {
                $allowed = $req->allowedNextStatuses();
                if (!in_array($validated['status'], $allowed, true)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot move status from '{$req->status}' to '{$validated['status']}'.",
                    ], 422);
                }

                // When marked paid, stamp delivered_at only when moving to delivered
                if ($validated['status'] === 'delivered' && !$req->delivered_at) {
                    $validated['delivered_at'] = now();
                }
            }

            $req->update(array_filter($validated, fn($v) => $v !== null));

            return response()->json([
                'success' => true,
                'message' => 'Request updated successfully.',
                'data'    => $req->fresh(['user', 'service', 'currency', 'country', 'assignedAdmin']),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update CV request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set the status directly — admin only.
     *
     * The admin can jump to ANY status (payments may come in off-platform,
     * mistakes get made, requests get cancelled and re-opened, etc.). Every
     * change is recorded in the delivery_log for the audit trail.
     */
    public function updateStatus(Request $request, $id)
    {
        if (!auth()->user()->can('edit cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }


        $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::in(CvReviewRequest::allStatuses())],
            'note'   => 'nullable|string|max:500',
        ]);

        try {
            $req      = CvReviewRequest::findOrFail($id);
            $newStatus = $request->input('status');
            $oldStatus = $req->status;
            $note      = $request->input('note');

            if ($newStatus === $oldStatus) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status unchanged.',
                    'data'    => $req->fresh(['user', 'service', 'currency', 'country', 'assignedAdmin']),
                ]);
            }

            
            if ($req->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'code'    => 'status_locked',
                    'message' => 'This request is completed. Its status cannot be changed.',
                ], 422);
            }

            // Side-effect timestamps
            $updates = ['status' => $newStatus];

            if ($newStatus === 'delivered' && !$req->delivered_at) {
                $updates['delivered_at'] = now();
            }

            if ($newStatus === 'paid' && !$req->review_delivered_at) {
                // Optional: nothing to stamp here — kept for symmetry
            }

            // Append to delivery_log so there's a paper trail
            $log   = $req->delivery_log ?? [];
            $log[] = [
                'at'         => now()->toISOString(),
                'type'       => 'status_change',
                'by'         => auth()->id(),
                'by_name'    => auth()->user()->name,
                'from'       => $oldStatus,
                'to'         => $newStatus,
                'note'       => $note,
            ];
            $updates['delivery_log'] = $log;

            $req->update($updates);

            return response()->json([
                'success' => true,
                'message' => "Status changed from '{$oldStatus}' to '{$newStatus}'.",
                'data'    => $this->shapeForAdmin($req->fresh(['user', 'service', 'currency', 'country', 'assignedAdmin'])),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Request not found'], 404);
        } catch (\Throwable $e) {
            \Log::error('Failed to update status', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm payment manually (admin marks paid + saves reference).
     * Used when the payment was received outside the automated flow.
     */
    public function confirmPayment(Request $request, $id)
    {
        if (!auth()->user()->can('edit cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'payment_reference' => 'required|string|max:255',
            'amount_cents'      => 'nullable|integer|min:1',
            'deposit_method'    => 'required|in:cash,mobile_money,bank_transfer,cheque',  // mirror the ENUM
            'note'              => 'nullable|string|max:500',
        ]);

        try {
            $req = CvReviewRequest::findOrFail($id);

            if ($req->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is already marked as paid.',
                ], 422);
            }

            if (!in_array('paid', $req->allowedNextStatuses(), true)) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot mark as paid from status '{$req->status}'.",
                ], 422);
            }

            $paymentMethod = \App\Models\PaymentMethod::find($request->input('payment_method_id'));
            if (!$paymentMethod) {
                return response()->json(['success' => false, 'message' => 'Invalid payment method.'], 422);
            }

            // The amount is whatever is currently on the CV review request
            // (which was snapshotted from cv_rewrite's price at Pay Now time).
            $amountCents = $request->input('amount_cents') ?: $req->amount_cents;

            if (!$amountCents || $amountCents < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has no amount set. Please provide amount_cents.',
                ], 422);
            }

            $source = \App\Models\PaymentSource::active()->where('slug', 'cv_shortlisting')->first();
            if (!$source) {
                return response()->json([
                    'success' => false,
                    'message' => 'cv_shortlisting payment source not configured. Run PaymentSourceSeeder.',
                ], 500);
            }

            $deposit = \Illuminate\Support\Facades\DB::transaction(function () use ($req, $request, $paymentMethod, $amountCents, $source) {
                // 1. Create the deposit — marked completed immediately, is_manual = true
                $channel = $request->input('deposit_method');

                $deposit = \App\Models\Deposit::create([
                    'deposit_ref'         => (string) \Illuminate\Support\Str::uuid(),
                    'payment_method_id'   => $paymentMethod->id,
                    'currency_id'         => $req->currency_id,
                    'amount'              => $amountCents,
                    'fee'                 => 0,
                    'net_amount'          => $amountCents,
                    'deposit_method'      => $channel,     // ← selected value, not 'manual'
                    'payment_channel'     => $channel,     // ← same
                    'source_id'           => $source->id,
                    'source_name_manual'  => 'Manual / Offline',
                    'purpose_type'        => 'cv_review',
                    'purpose_reference'   => $req->uuid,
                    'purpose_description' => 'CV Rewrite — admin confirmed',
                    'status'              => 'completed',
                    'is_manual'           => true,         // ← this is what identifies it as admin-recorded
                    'manual_reason'       => $request->input('note') ?? 'Admin confirmed payment',
                    'deposit_date'        => now(),
                    'cleared_date'        => now(),
                    'reference_number'    => $request->input('payment_reference'),
                    'source_reference'    => $request->input('payment_reference'),
                    'depositor_id'        => $req->user_id,
                    'paid_by_user_id'     => $req->user_id,
                    'depositor_name'      => $req->user->name ?? 'Seeker',
                    'depositor_email'     => $req->user->email ?? null,
                    'depositor_phone'     => $req->user->phone ?? null,
                    'country_code'        => $req->country_code,
                    'verified_by'         => auth()->id(),
                    'verified_at'         => now(),
                    'created_by'          => auth()->id(),
                ]);

                // 2. Record the accounting entry (credits the wallet, writes payment_transaction_logs)
                app(\App\Services\Payment\PaymentTransactionService::class)->recordDeposit($deposit->fresh());

                // 3. Flip the CV review request to paid
                $log   = $req->delivery_log ?? [];
                $log[] = [
                    'at'      => now()->toISOString(),
                    'type'    => 'payment_confirmed_manually',
                    'by'      => auth()->id(),
                    'by_name' => auth()->user()->name,
                    'from'    => $req->status,
                    'to'      => 'paid',
                    'note'    => $request->input('note'),
                    'deposit_ref' => $deposit->deposit_ref,
                ];

                $isAi = $req->rewrite_mode === 'ai';

                $req->update([
                    'status'             => $isAi ? 'paid_ai' : 'paid',
                    'payment_reference'  => $request->input('payment_reference'),
                    'payment_method_id'  => $paymentMethod->id,
                    'delivery_log'       => $log,
                ]);

                // Kick off generation if we're on the AI path
                if ($isAi) {
                    \App\Jobs\GenerateCvRewriteJob::dispatch($req->id)->onQueue('default');
                    \App\Jobs\GenerateCoverLetterJob::dispatch($req->id)->onQueue('default');
                }

                return $deposit;
            });

            return response()->json([
                'success'     => true,
                'message'     => 'Payment recorded and request moved to Paid.',
                'deposit_ref' => $deposit->deposit_ref,
                'data'        => $req->fresh(['user', 'service', 'currency', 'country', 'assignedAdmin']),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Request not found'], 404);
        } catch (\Throwable $e) {
            \Log::error('Confirm payment failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload the delivered (revised) CV.
     */
    public function uploadDelivered(Request $request, $id)
    {
        if (!auth()->user()->can('edit cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'delivered_cv' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        try {
            $req = CvReviewRequest::findOrFail($id);

            $path = $request->file('delivered_cv')
                ->store("cv-review/{$req->uuid}/delivered", 'public');

            $req->update([
                'delivered_cv_path' => $path,
                'delivered_at'      => now(),
                'status'            => 'delivered',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivered CV uploaded. Status set to Delivered.',
                'data'    => $req->fresh(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Delete a request (soft guard: only if not paid).
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $req = CvReviewRequest::findOrFail($id);

            if ($req->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a paid request. Cancel it instead to keep the audit trail.',
                ], 422);
            }

            $req->delete();

            return response()->json([
                'success' => true,
                'message' => 'Request deleted.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Small stats widget (top of the listing page).
     */
    public function stats()
    {
        return response()->json([
            'success' => true,
            'stats'   => [
                'pending_payment' => CvReviewRequest::where('status', 'awaiting_payment')->count(),
                'in_progress'     => CvReviewRequest::activeWork()->count(),
                'sla_overdue'     => CvReviewRequest::slaBreached()->count(),
                'revenue_today'   => CvReviewRequest::paid()
                    ->whereDate('updated_at', today())
                    ->sum('amount_cents'),
            ],
        ]);
    }

    /**
     * Helpers for the view — countries with activity + admin users.
     */
    public function filters()
    {
        $admins = User::whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'super-admin']))
            ->orderBy('name')
            ->get(['id', 'name']);

        $countries = \App\Models\Job\Country::active()
            ->orderBy('name')
            ->get(['code', 'name', 'flag'])
            ->map(fn($c) => ['code' => $c->code, 'name' => $c->name, 'flag' => $c->flag ?? '🌍'])
            ->prepend(['code' => 'XX', 'name' => 'Global', 'flag' => '🌍'])
            ->values();
        
        $payment_methods = \App\Models\PaymentMethod::active()
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'code'])
            ->map(fn($pm) => [
                'id'   => $pm->id,
                'name' => $pm->name,
                'type' => $pm->type,
                'code' => $pm->code,
            ]);

        return response()->json([
            'success'   => true,
            'admins'    => $admins,
            'payment_methods'    => $payment_methods,
            'countries' => $countries,
        ]);
    }

    // ---------------------------------------------------------------
    // Static map used for validation — keeps statuses DRY
    // ---------------------------------------------------------------
    public static function statuses(): array
    {
        return [
            'submitted'          => 'Submitted',
            'ai_reviewed'        => 'AI Reviewed',
            'awaiting_payment'   => 'Awaiting Payment',
            'paid'               => 'Paid',
            'in_progress'        => 'In Progress',
            'delivered'          => 'Delivered',
            'revision_requested' => 'Revision Requested',
            'completed'          => 'Completed',
            'cancelled'          => 'Cancelled',
        ];
    }


    // ═════════════════════════════════════════════════════════════
    // ADMIN CREATE — subscriber or non-subscriber, payment differs
    // ═════════════════════════════════════════════════════════════

    public function createForUser(Request $request)
    {
        if (!auth()->user()->can('edit cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'user_id'                => 'nullable|integer|exists:users,id',
            'new_user'               => 'nullable|array',
            'new_user.name'          => 'required_with:new_user|string|max:255',
            'new_user.email'         => 'required_with:new_user|email|max:255|unique:users,email',
            'new_user.phone'         => 'nullable|string|max:30',
            'new_user.country_code'  => 'nullable|string|size:2',

            'cv_source'              => ['required', Rule::in(['existing', 'uploaded'])],
            'cv_path'                => 'required_if:cv_source,existing|nullable|string',
            'cv_file'                => 'required_if:cv_source,uploaded|nullable|file|mimes:pdf,doc,docx|max:10240',

            'job_source'             => ['required', Rule::in(['db', 'manual', 'paste', 'none'])],
            'job_post_id'            => 'required_if:job_source,db|nullable|integer|exists:job_posts,id',
            'target_job_title'       => 'required_unless:job_source,db,none|nullable|string|max:255',
            'target_company_name'    => 'required_unless:job_source,db,none|nullable|string|max:255',
            'target_job_description' => 'required_if:job_source,paste|nullable|string|max:20000',
        ]);

        // Files are not part of the database transaction, so track the stored file
        // and delete it manually if anything fails.
        $storedCvPath = null;

        try {
            [$req, $isSubscriber] = DB::transaction(function () use ($request, $validated, &$storedCvPath) {

                // ── 1. User ───────────────────────────────────────
                if (!empty($validated['user_id'])) {
                    $user = \App\Models\User::findOrFail($validated['user_id']);
                } else {
                    $user = \App\Models\User::create([
                        'name'         => $validated['new_user']['name'],
                        'email'        => $validated['new_user']['email'],
                        'phone'        => $validated['new_user']['phone'] ?? null,
                        'country_code' => strtoupper($validated['new_user']['country_code'] ?? 'UG'),
                        'password'     => bcrypt(\Illuminate\Support\Str::random(32)),
                    ]);
                    $user->assignRole('job_seeker');
                }

                // ── 2. CV ─────────────────────────────────────────
                if ($validated['cv_source'] === 'existing') {
                    $cvPath = $this->normalizeCvPath($validated['cv_path']);

                    if (!Storage::disk('public')->exists($cvPath)) {
                        throw ValidationException::withMessages([
                            'cv_path' => ['The selected CV was not found on disk.'],
                        ]);
                    }

                    $cvName = basename($cvPath);
                } else {
                    $file    = $request->file('cv_file');
                    $cvName  = $file->getClientOriginalName();
                    $cvPath  = $file->store("cv-review/{$user->id}/original", 'public');
                    $storedCvPath = $cvPath;   // remember it so a failure can clean it up

                    $text = app(\App\Services\Jobs\CvTextExtractionService::class)->extract(
                        Storage::disk('public')->path($cvPath),
                        Storage::disk('public')->mimeType($cvPath) ?? 'application/pdf'
                    );

                    if (trim($text) === '' || mb_strlen($text) < 100) {
                        throw ValidationException::withMessages([
                            'cv_file' => ['That file does not look like a readable CV. Upload a PDF or Word CV.'],
                        ]);
                    }
                }

                // ── 3. Job context ────────────────────────────────
                $jobPostId      = null;
                $jobTitle       = $validated['target_job_title']       ?? null;
                $companyName    = $validated['target_company_name']    ?? null;
                $jobDescription = $validated['target_job_description'] ?? null;

                if ($validated['job_source'] === 'db') {
                    $job = \App\Models\Job\JobPost::with('company:id,name')->find($validated['job_post_id']);

                    if ($job) {
                        $jobPostId   = $job->id;
                        $jobTitle    = $job->job_title;
                        $companyName = $job->company?->name ?? 'Company';

                        // Merge every useful field so the AI gets the full picture —
                        // same composition the seeker flow uses.
                        $sections = array_filter([
                            !empty($job->job_description)
                                ? trim(strip_tags((string) $job->job_description))
                                : null,
                            !empty($job->responsibilities)
                                ? "Key Responsibilities:\n" . trim(strip_tags((string) $job->responsibilities))
                                : null,
                            !empty($job->qualifications)
                                ? "Qualifications & Requirements:\n" . trim(strip_tags((string) $job->qualifications))
                                : null,
                            !empty($job->skills)
                                ? "Required Skills:\n" . trim(strip_tags((string) $job->skills))
                                : null,
                        ]);

                        $jobDescription = implode("\n\n", $sections) ?: null;
                    }
                } elseif ($validated['job_source'] === 'paste') {
                    // Also strip tags on the pasted description so the prompt stays clean.
                    $jobDescription = !empty($validated['target_job_description'])
                        ? trim(strip_tags((string) $validated['target_job_description']))
                        : null;
                }

                // ── 4. Service and price ──────────────────────────
                $countryCode = strtoupper($user->country_code ?? config('app.country_code', 'UG'));
                $service     = \App\Models\Service\Service::where('key', 'cv_review')->firstOrFail();
                $price       = \App\Models\Service\ServicePrice::resolve('cv_rewrite', $countryCode);
                $isSubscriber = $this->isSubscriber($user);

                // ── 5. Request ────────────────────────────────────
                $req = CvReviewRequest::create([
                    'user_id'                 => $user->id,
                    'created_by_admin_id'     => auth()->id(),
                    'service_id'              => $service->id,
                    'country_code'            => $countryCode,
                    'currency_id'             => $price?->currency_id ?? 2,
                    'amount_cents'            => $isSubscriber ? 0 : ($price?->amount_cents ?? 0),
                    'covered_by_subscription' => $isSubscriber,
                    'target_job_title'        => $jobTitle,
                    'target_company_name'     => $companyName,
                    'target_job_description'  => $jobDescription,
                    'job_post_id'             => $jobPostId,
                    'original_cv_path'        => $cvPath,
                    'original_cv_name'        => $cvName,
                    'status'                  => 'submitted',
                    'rewrite_mode'            => 'ai',
                ]);

                $req->update(['delivery_log' => [[
                    'at'         => now()->toISOString(),
                    'type'       => 'created_by_admin',
                    'by'         => auth()->id(),
                    'by_name'    => auth()->user()->name,
                    'subscriber' => $isSubscriber,
                ]]]);

                return [$req, $isSubscriber];
            });

            // Dispatch only after the transaction has committed
            \App\Jobs\RunAiReviewJob::dispatch($req->id)->onQueue('default');

            return response()->json([
                'success'       => true,
                'message'       => 'CV review created. AI review is running…',
                'data'          => $this->shapeForAdmin($req->fresh(['user', 'service', 'currency', 'country'])),
                'is_subscriber' => $isSubscriber,
            ]);

        } catch (ValidationException $e) {
            $this->deleteStoredCv($storedCvPath);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            $this->deleteStoredCv($storedCvPath);
            \Log::error('Admin create CV review failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function deleteStoredCv(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Accept a storage path, a /storage/ URL, or a full URL and reduce it
     * to the canonical path inside the public disk.
     */
    protected function normalizeCvPath(?string $raw): string
    {
        $raw = trim((string) $raw);

        // Full URL: https://site/storage/cvs/xyz.pdf  →  cvs/xyz.pdf
        if (preg_match('#/storage/(.+)$#i', $raw, $m)) {
            return ltrim($m[1], '/');
        }

        // Leading slash: /cvs/xyz.pdf  →  cvs/xyz.pdf
        return ltrim($raw, '/');
    }

    private function isSubscriber(\App\Models\User $user): bool
    {
        return \App\Models\Subscription\Subscription::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['trialing', 'active'])
            ->where('current_period_end', '>', now())
            ->exists();
    }

    // ═════════════════════════════════════════════════════════════
    // ADMIN ANSWER SUBMIT — after AI review
    // Mirrors proxy logic, but the payment step differs:
    //   subscriber → skip payment, go straight to paid_ai
    //   non-subscriber → status = awaiting_payment, admin confirms later
    // ═════════════════════════════════════════════════════════════

    public function submitAnswers(Request $request, $id)
    {
        if (!auth()->user()->can('edit cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'answers'   => 'required|array|min:1',
            'answers.*' => 'string|max:5000',
        ]);

        try {
            [$req, $isSubscriber] = DB::transaction(function () use ($id, $validated) {

                // Lock the row so two admins can't submit answers at the same time
                $req = CvReviewRequest::lockForUpdate()->findOrFail($id);

                if (!in_array($req->status, ['submitted', 'ai_reviewed'], true)) {
                    throw ValidationException::withMessages([
                        'status' => ["Cannot submit answers from status '{$req->status}'."],
                    ]);
                }

                $isSubscriber = $this->isSubscriber($req->user);

                $updates = [
                    'seeker_gap_answers' => $validated['answers'],
                ];

                if ($isSubscriber) {
                    // Subscriber: no payment needed, go straight to generation
                    $updates['status']                  = 'paid_ai';
                    $updates['covered_by_subscription'] = true;
                    $updates['payment_reference']       = 'SUB-' . $req->uuid;
                } else {
                    // Non-subscriber: wait for manual payment confirmation
                    $updates['status'] = 'awaiting_payment';
                }

                $log   = $req->delivery_log ?? [];
                $log[] = [
                    'at'         => now()->toISOString(),
                    'type'       => 'answers_submitted',
                    'by'         => auth()->id(),
                    'by_name'    => auth()->user()->name,
                    'subscriber' => $isSubscriber,
                    'count'      => count($validated['answers']),
                ];
                $updates['delivery_log'] = $log;

                $req->update($updates);

                return [$req->fresh(), $isSubscriber];
            });

            // Dispatch after the commit, so a rolled-back save never starts generation
            if ($isSubscriber) {
                GenerateCvRewriteJob::dispatch($req->id)->onQueue('default');
                GenerateCoverLetterJob::dispatch($req->id)->onQueue('default');
            }

            return response()->json([
                'success'       => true,
                'message'       => $isSubscriber
                    ? 'Answers saved. Generating your CV and cover letter now…'
                    : 'Answers saved. Confirm payment to start generation.',
                'data'          => $this->shapeForAdmin($req->fresh(['user', 'service', 'currency', 'country'])),
                'is_subscriber' => $isSubscriber,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Request not found.'], 404);

        } catch (\Throwable $e) {
            \Log::error('Submit answers failed', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save answers: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Runs the CV rewrite first, then the cover letter. The letter starts only
     * after the CV job succeeds, because a chain stops when a job fails.
     */
    private function dispatchGeneration(CvReviewRequest $req): void
    {
        \Illuminate\Support\Facades\Bus::chain([
            new \App\Jobs\GenerateCvRewriteJob($req->id),
            new \App\Jobs\GenerateCoverLetterJob($req->id),
        ])->onQueue('default')->dispatch();
    }

    public function generateMissing(Request $request, $id)
    {
        if (!auth()->user()->can('edit cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $req = CvReviewRequest::findOrFail($id);

            if ($req->rewrite_mode !== 'ai') {
                return response()->json(['success' => false, 'message' => 'Only AI rewrites can be generated here.'], 422);
            }

            // Generation needs the gap answers (unless the review had no questions)
            $reviewQuestions = (array) ($req->ai_gap_review['missing_fields'] ?? []);
            if (!empty($reviewQuestions) && empty($req->seeker_gap_answers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Answers to the gap questions are required before generating.',
                ], 422);
            }

            if (in_array($req->status, ['cancelled', 'completed'], true)) {
                return response()->json(['success' => false, 'message' => "Cannot generate when the status is '{$req->status}'."], 422);
            }

            // A status of paid_ai or generating only means "running" if it changed recently.
            // Older than 15 minutes, the worker has stopped without recording a failure.
            $isBusy = in_array($req->status, ['paid_ai', 'generating'], true);

            if ($isBusy) {
                $recent = $req->updated_at && $req->updated_at->gt(now()->subMinutes(15));

                if ($recent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Generation started a few minutes ago and is still running. Try again shortly.',
                    ], 422);
                }

                // Stale: mark it so the attempt below is allowed to start
                $req->update([
                    'status'           => 'failed',
                    'generation_error' => 'The previous attempt stopped without finishing. Starting a new attempt.',
                ]);
                $req->refresh();
            }

            $cvMissing    = empty($req->rewrite_content['html'] ?? null);
            $coverMissing = empty($req->cover_letter_uuid);

            if (!$cvMissing && !$coverMissing) {
                return response()->json(['success' => false, 'message' => 'Both the CV and the cover letter already exist.'], 422);
            }

            $log   = $req->delivery_log ?? [];
            $log[] = [
                'at'            => now()->toISOString(),
                'type'          => 'generate_missing_requested',
                'by'            => auth()->id(),
                'by_name'       => auth()->user()->name,
                'cv_missing'    => $cvMissing,
                'cover_missing' => $coverMissing,
            ];

            if ($cvMissing) {
                // The CV is generated first. The chain then runs the cover letter,
                // which skips itself if the letter already exists.
                $req->update([
                    'status'                => 'paid_ai',
                    'rewrite_error_message' => null,
                    'generation_error'      => null,
                    'generation_attempts'   => 0,
                    'delivery_log'          => $log,
                ]);

                $this->dispatchGeneration($req->fresh());
            } else {
                // Only the letter is missing and the CV exists
                $req->update(['delivery_log' => $log]);

                \App\Jobs\GenerateCoverLetterJob::dispatch($req->id)->onQueue('default');
            }

            return response()->json([
                'success' => true,
                'message' => $cvMissing
                    ? 'Generating the CV and cover letter…'
                    : 'Generating the cover letter…',
                'data'    => $this->shapeForAdmin($req->fresh(['user', 'service', 'currency', 'country'])),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        } catch (\Throwable $e) {
            \Log::error('Generate missing failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function coverLetterDownload($id)
    {
        if (!auth()->user()->can('view cv review requests')) {
            abort(403);
        }
        $req = CvReviewRequest::findOrFail($id);
        if (!$req->cover_letter_uuid) {
            abort(404, 'No cover letter has been generated for this request.');
        }
        return redirect()->route('api.cv-review.rewrite.download', ['uuid' => $req->cover_letter_uuid]);
    }

    public function searchSeekers(Request $request)
    {
        if (!auth()->user()->can('view cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $q = trim($request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $gate = app(\App\Services\Subscription\SubscriptionGate::class);

        $users = \App\Models\User::query()
            ->with('seekerProfile:id,user_id,first_name,last_name')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhereHas('seekerProfile', fn ($s) => $s
                        ->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%"));
            })
            ->limit(30)
            ->get(['id', 'name', 'email', 'phone']);

        $rows = $users->map(function ($u) use ($gate) {
            $sub    = $gate->activeSubscription($u);
            $plan   = $sub?->service?->name ?? $sub?->service_key;
            $name   = $u->name ?: trim(($u->seekerProfile?->first_name ?? '') . ' ' . ($u->seekerProfile?->last_name ?? ''));

            return [
                'id'            => $u->id,
                'label'         => $name . ($u->email ? " — {$u->email}" : ''),
                'email'         => $u->email,
                'is_subscriber' => (bool) $sub,
                'plan'          => $plan,
            ];
        });

        // Subscribers first, then everyone else, each group sorted by name
        $sorted = $rows->sortBy([
            ['is_subscriber', 'desc'],
            ['label', 'asc'],
        ])->values();

        return response()->json(['data' => $sorted]);
    }

    public function cvSearch(Request $request)
    {
        if (!auth()->user()->can('view cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $userId = (int) $request->query('user_id');
        if (!$userId) {
            return response()->json(['data' => []]);
        }

        $seeker = \App\Models\SeekerProfile::where('user_id', $userId)->first();
        if (!$seeker) {
            return response()->json(['data' => []]);
        }

        $items = [];

        // 1. Primary CV (cv_file_path)
        if (!empty($seeker->cv_file_path)) {
            $items[] = [
                'id'    => $seeker->cv_file_path,
                'label' => $seeker->cv_original_name ?: basename($seeker->cv_file_path),
                'path'  => $seeker->cv_file_path,
                'kind'  => 'primary',
            ];
        }

        // 2. Additional CVs (cv_files JSON array)
        $files = is_array($seeker->cv_files) ? $seeker->cv_files : [];
        foreach ($files as $cv) {
            $path = $cv['path'] ?? null;
            if (!$path) continue;
            $items[] = [
                'id'    => $path,
                'label' => $cv['original_name'] ?? basename($path),
                'path'  => $path,
                'kind'  => 'library',
            ];
        }

        return response()->json(['data' => $items]);
    }

    public function searchJobs(Request $request)
    {
        if (!auth()->user()->can('view cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $q = trim($request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $jobs = \App\Models\Job\JobPost::with('company:id,name')
            ->where(function ($query) use ($q) {
                $query->where('job_title', 'like', "%{$q}%")
                    ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$q}%"));
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'job_title', 'company_id']);

        return response()->json([
            'data' => $jobs->map(fn ($j) => [
                'id'    => $j->id,
                'label' => $j->job_title . ($j->company?->name ? " — {$j->company->name}" : ''),
            ]),
        ]);
    }

    /**
     * Edit already-submitted gap answers.
     * Changes ONLY seeker_gap_answers (+ audit log). No status change, no generation.
     * POST /admin/cv-review/{id}/update-answers
     */
    public function updateAnswers(Request $request, $id)
    {
        if (!auth()->user()->can('edit cv review requests')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'answers'   => 'required|array|min:1',
            'answers.*' => 'nullable|string|max:5000',
        ]);

        try {
            $req = DB::transaction(function () use ($id, $validated) {
                $req = CvReviewRequest::lockForUpdate()->findOrFail($id);

                if (empty($req->seeker_gap_answers)) {
                    throw ValidationException::withMessages([
                        'answers' => ['No answers have been submitted yet. Use the submit form first.'],
                    ]);
                }

                if (in_array($req->status, ['cancelled', 'completed'], true)) {
                    throw ValidationException::withMessages([
                        'status' => ["Answers cannot be edited when the status is '{$req->status}'."],
                    ]);
                }

                // Trim and drop blanks
                $new = collect($validated['answers'])
                    ->map(fn ($v) => trim((string) $v))
                    ->filter(fn ($v) => $v !== '')
                    ->all();

                if (empty($new)) {
                    throw ValidationException::withMessages([
                        'answers' => ['At least one answer is required.'],
                    ]);
                }

                $old     = (array) $req->seeker_gap_answers;
                $changed = array_values(array_filter(
                    array_unique(array_merge(array_keys($old), array_keys($new))),
                    fn ($k) => ($old[$k] ?? null) !== ($new[$k] ?? null)
                ));

                if (empty($changed)) {
                    return $req; // nothing changed
                }

                $log   = $req->delivery_log ?? [];
                $log[] = [
                    'at'      => now()->toISOString(),
                    'type'    => 'answers_edited',
                    'by'      => auth()->id(),
                    'by_name' => auth()->user()->name,
                    'changed' => $changed,
                ];

                $req->update([
                    'seeker_gap_answers' => $new,
                    'delivery_log'       => $log,
                ]);

                return $req;
            });

            return response()->json([
                'success' => true,
                'message' => 'Answers updated.',
                'data'    => $this->shapeForAdmin($req->fresh(['user', 'service', 'currency', 'country', 'assignedAdmin'])),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        } catch (\Throwable $e) {
            \Log::error('Update answers failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

}