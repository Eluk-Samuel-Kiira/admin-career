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

use App\Services\Jobs\AiService;
use App\Services\Jobs\CvTextExtractionService;
use App\Services\Delivery\ReviewDeliveryService;



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

    /**
     * Shape a request for admin-side JSON.
     */
    private function shapeForAdmin(CvReviewRequest $r): array
    {
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
            ->orderBy('created_at', 'asc')
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
            'payment_reference' => 'required|string|max:255',
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

            $req->update([
                'status'            => 'paid',
                'payment_reference' => $request->input('payment_reference'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed. Request moved to Paid.',
                'data'    => $req->fresh(),
            ]);
        } catch (\Exception $e) {
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

        return response()->json([
            'success'   => true,
            'admins'    => $admins,
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
}