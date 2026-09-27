<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\Service\JobSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class JobSubmissionController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('view job submissions')) {
            abort(403);
        }

        return view('employer.job-submissions.index');
    }

    public function data(Request $request)
    {
        if (!auth()->user()->can('view job submissions')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $status  = $request->get('status', '');
        $payment = $request->get('payment', '');
        $search  = $request->get('search', '');
        $page    = (int) $request->get('page', 1);

        $query = JobSubmission::with(['user', 'company', 'currency', 'service'])
            ->orderByRaw("
                FIELD(status,
                    'pending_payment',
                    'pending_review',
                    'rejected',
                    'published',
                    'draft',
                    'approved',
                    'cancelled'
                ) ASC
            ")
            ->orderByDesc('created_at');

        if ($status)  $query->where('status', $status);
        if ($payment === 'paid')   $query->where('payment_status', 'paid');
        if ($payment === 'unpaid') $query->where('payment_status', 'pending');
        if ($payment === 'free')   $query->where('payment_status', 'not_required');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('job_title', 'like', "%{$search}%")
                  ->orWhere('uuid', 'like', "%{$search}%")
                  ->orWhereHas('company', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $submissions = $query->paginate(20, ['*'], 'page', $page);

        $submissions->getCollection()->transform(function ($s) {
            return [
                'uuid'            => $s->uuid,
                'job_title'       => $s->job_title,
                'content_preview' => \Illuminate\Support\Str::limit(strip_tags($s->content), 120),
                'company_name'    => $s->company->name ?? '—',
                'company_logo'    => $s->company->logo_url ?? null,
                'user_email'      => $s->user->email ?? '—',
                'service_name'    => $s->service_name,
                'package_meta'    => $s->package_meta,
                'formatted_amount'=> $s->formatted_amount,
                'is_free'         => $s->is_free,
                'payment_badge'   => $s->payment_badge,
                'status_badge'    => $s->status_badge,
                'status'          => $s->status,
                'payment_status'  => $s->payment_status,
                'created_at'      => $s->created_at?->toISOString(),
                'reviewed_at'     => $s->reviewed_at?->toISOString(),
                'admin_notes'     => $s->admin_notes,
            ];
        });

        return response()->json($submissions);
    }

    public function stats()
    {
        if (!auth()->user()->can('view job submissions')) {
            return response()->json(['success' => false], 403);
        }

        return response()->json([
            'success' => true,
            'stats'   => [
                'awaiting_payment'  => JobSubmission::where('status', 'pending_payment')->count(),
                'ready_review'      => JobSubmission::where('status', 'pending_review')->count(),
                'published'         => JobSubmission::where('status', 'published')->count(),
                'rejected'          => JobSubmission::where('status', 'rejected')->count(),
                'paid_amount_cents' => JobSubmission::where('payment_status', 'paid')
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                    ->sum('amount_cents'),
            ],
        ]);
    }

    public function show($uuid)
    {
        if (!auth()->user()->can('view job submissions')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $s = JobSubmission::where('uuid', $uuid)
            ->with(['user', 'company', 'currency', 'service', 'reviewer'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => [
                'uuid'             => $s->uuid,
                'job_title'        => $s->job_title,
                'content'          => $s->content,
                'target_country'   => $s->target_country,

                'service_key'      => $s->service_key,
                'service_name'     => $s->service_name,
                'package_meta'     => $s->package_meta,
                'is_free'          => $s->is_free,

                'formatted_amount' => $s->formatted_amount,
                'amount_cents'     => $s->amount_cents,
                'currency_code'    => $s->currency->code ?? null,

                'payment_status'   => $s->payment_status,
                'payment_badge'    => $s->payment_badge,
                'payment_reference'=> $s->payment_reference,
                'paid_at'          => $s->paid_at?->toISOString(),

                'status'           => $s->status,
                'status_badge'     => $s->status_badge,
                'rejection_reason' => $s->rejection_reason,
                'admin_notes'      => $s->admin_notes,

                'company_id'       => $s->company_id,
                'company_name'     => $s->company->name ?? '—',
                'company_logo'     => $s->company->logo_url ?? null,
                'company_slug'     => $s->company->slug ?? null,

                'user_name'        => $s->user->name ?? '—',
                'user_email'       => $s->user->email ?? '—',
                'user_phone'       => $s->user->phone ?? '—',

                'reviewer_name'    => $s->reviewer->name ?? null,
                'reviewed_at'      => $s->reviewed_at?->toISOString(),

                'created_at'       => $s->created_at?->toISOString(),
            ],
        ]);
    }

    public function markPaid(Request $request, $uuid)
    {
        if (!auth()->user()->can('edit job submissions')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate(['payment_reference' => 'nullable|string|max:255']);

        $s = JobSubmission::where('uuid', $uuid)->firstOrFail();

        if ($s->payment_status === 'not_required') {
            return response()->json(['success' => false, 'message' => 'Free submission — no payment to record.'], 422);
        }

        $s->update([
            'payment_status'    => 'paid',
            'payment_reference' => $request->input('payment_reference') ?? $s->payment_reference,
            'paid_at'           => now(),
            'status'            => $s->status === 'pending_payment' ? 'pending_review' : $s->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Payment confirmed.']);
    }

    /**
     * Mark the submission as published — but do NOT create a job_posts row.
     * The admin will copy the content and publish through the normal job creation flow.
     */
    public function markPublished(Request $request, $uuid)
    {
        if (!auth()->user()->can('edit job submissions')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'job_post_id' => 'nullable|integer|exists:job_posts,id',
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $s = JobSubmission::where('uuid', $uuid)->firstOrFail();

        if ($s->status === 'published') {
            return response()->json(['success' => false, 'message' => 'Already marked as published.'], 422);
        }

        if ($s->payment_status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot mark as published — payment is still pending.',
            ], 422);
        }

        $note = trim(
            ($s->admin_notes ? $s->admin_notes . "\n" : '') .
            '[' . now()->toDateTimeString() . '] Marked published by ' . (auth()->user()->name ?? 'admin') . '.'
        );

        $update = [
            'status'      => 'published',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_notes' => $note,
        ];

        // Optional: if admin already created the job_post, link it
        if ($request->filled('job_post_id')) {
            $update['job_post_id'] = $request->input('job_post_id');
        }

        if ($request->filled('admin_notes')) {
            $update['admin_notes'] = $note . "\n" . $request->input('admin_notes');
        }

        $s->update($update);

        return response()->json([
            'success' => true,
            'message' => 'Marked as published. Remember to create the job post through the normal flow.',
            'warning' => 'This submission does NOT create a job_posts row. Publish separately using the job creation form.',
        ]);
    }

    public function reject(Request $request, $uuid)
    {
        if (!auth()->user()->can('edit job submissions')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate(['reason' => 'required|string|max:2000']);

        $s = JobSubmission::where('uuid', $uuid)->firstOrFail();

        $s->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->input('reason'),
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Rejected.']);
    }

    /**
     * Return submissions that can be linked to a job post.
     * Same company, not yet linked, published or pending.
     */
    public function linkable(Request $request): JsonResponse
    {
        if (!auth()->user()->can('view job submissions')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'search'     => 'nullable|string|max:100',
        ]);

        $companyId = (int) $request->get('company_id');
        $search    = $request->get('search', '');

        $query = JobSubmission::where('company_id', $companyId)
            ->whereNull('job_post_id')     // not already linked
            ->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('job_title', 'like', "%{$search}%")
                ->orWhere('uuid', 'like', "%{$search}%");
            });
        }

        $submissions = $query->limit(50)->get()->map(fn($s) => [
            'id'            => $s->id,
            'uuid'          => $s->uuid,
            'job_title'     => $s->job_title,
            'company_name'  => $s->company->name ?? '—',
            'status'        => $s->status,
            'status_label'  => ucwords(str_replace('_', ' ', $s->status)),
            'created_at'    => $s->created_at?->toISOString(),
        ]);

        return response()->json([
            'success'     => true,
            'submissions' => $submissions,
        ]);
    }
}