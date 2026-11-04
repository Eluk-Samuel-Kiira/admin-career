<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service\LetterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoverLetterController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('view cv review requests')) {
            abort(403);
        }

        // Stats (global counts, not filtered)
        $stats = [
            'total'           => LetterRequest::count(),
            'pending_payment' => LetterRequest::where('status', 'pending_payment')->count(),
            'processing'      => LetterRequest::whereIn('status', ['paid', 'processing'])->count(),
            'generated'       => LetterRequest::where('status', 'generated')->count(),
            'failed'          => LetterRequest::where('status', 'failed')->count(),
            'revenue'         => (float) LetterRequest::whereNotNull('paid_at')->sum('price'),
        ];

        return view('job-seeker.cover-letters.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        if (!auth()->user()->can('view cv review requests')) {
            return response()->json(['success' => false], 403);
        }

        $query = LetterRequest::with(['user:id,name,email', 'seekerProfile:id,first_name,last_name'])
            ->orderByDesc('id');

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('job_title', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('uuid', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->get('letter_type')) {
            $query->where('letter_type', $type);
        }

        if ($from = $request->get('from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }

        if ($to = $request->get('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $letters = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $letters->through(fn (LetterRequest $l) => [
                'id'               => $l->id,
                'uuid'             => $l->uuid,
                'user_id'          => $l->user_id,
                'user_name'        => $l->user?->name ?? 'Unknown',
                'user_email'       => $l->user?->email,
                'job_title'        => $l->job_title,
                'company_name'     => $l->company_name,
                'letter_type'      => $l->letter_type,
                'letter_type_label'=> $l->letter_type_label,
                'status'           => $l->status,
                'status_badge'     => $l->status_badge,
                'price'            => (float) $l->price,
                'price_label'      => $l->price_label,
                'currency'         => $l->currency,
                'is_paid'          => $l->is_paid,
                'payment_reference'=> $l->payment_reference,
                'paid_at'          => $l->paid_at?->toISOString(),
                'generated_at'     => $l->generated_at?->toISOString(),
                'error_message'    => $l->error_message,
                'created_at'       => $l->created_at?->toISOString(),
                'regeneration_count' => $l->regeneration_count,
                'max_regenerations'  => $l->max_regenerations,
                'is_user_edited'     => (bool) $l->is_user_edited,
            ]),
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        if (!auth()->user()->can('view cv review requests')) {
            return response()->json(['success' => false], 403);
        }

        $letter = LetterRequest::with([
            'user:id,name,email',
            'seekerProfile:id,first_name,last_name,phone,country',
            'jobPost:id,job_title,slug',
        ])->where('uuid', $uuid)->first();

        if (!$letter) {
            return response()->json(['success' => false, 'message' => 'Letter not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'uuid'              => $letter->uuid,
                'user_name'         => $letter->user?->name ?? 'Unknown',
                'user_email'        => $letter->user?->email,
                'seeker_name'       => $letter->seekerProfile
                    ? trim(($letter->seekerProfile->first_name ?? '') . ' ' . ($letter->seekerProfile->last_name ?? ''))
                    : null,
                'seeker_phone'      => $letter->seekerProfile?->phone,
                'job_title'         => $letter->job_title,
                'company_name'      => $letter->company_name,
                'job_post_id'       => $letter->job_post_id,
                'job_post_slug'     => $letter->jobPost?->slug,
                'letter_type'       => $letter->letter_type,
                'letter_type_label' => $letter->letter_type_label,
                'status'            => $letter->status,
                'status_badge'      => $letter->status_badge,
                'price'             => (float) $letter->price,
                'price_label'       => $letter->price_label,
                'currency'          => $letter->currency,
                'payment_reference' => $letter->payment_reference,
                'paid_at'           => $letter->paid_at?->toISOString(),
                'generated_at'      => $letter->generated_at?->toISOString(),
                'error_message'     => $letter->error_message,
                'cv_source'         => $letter->cv_source,
                'cv_path'           => $letter->cv_path,
                'content'           => $letter->status === 'generated' ? $letter->content : null,
                'regeneration_count'=> $letter->regeneration_count,
                'max_regenerations' => $letter->max_regenerations,
                'is_user_edited'    => (bool) $letter->is_user_edited,
                'user_edited_at'    => $letter->user_edited_at?->toISOString(),
                'created_at'        => $letter->created_at?->toISOString(),
            ],
        ]);
    }

    public function download(string $uuid)
    {
        if (!auth()->user()->can('view cv review requests')) {
            abort(403);
        }

        $letter = LetterRequest::where('uuid', $uuid)->firstOrFail();

        if ($letter->status !== 'generated' || empty($letter->content)) {
            abort(404, 'Letter is not ready yet.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.letter', [
            'letter'      => $letter,
            'candidate'   => $letter->seekerProfile,
            'generatedAt' => optional($letter->generated_at)->format('F j, Y'),
        ])->setPaper('a4');

        $name = str_replace(' ', '-', strtolower($letter->user?->name ?? 'candidate'));
        $type = $letter->letter_type === 'application' ? 'application-letter' : 'cover-letter';
        $job  = \Illuminate\Support\Str::slug($letter->job_title ?: 'untitled');

        return $pdf->download("{$name}_{$type}_{$job}.pdf");
    }
}