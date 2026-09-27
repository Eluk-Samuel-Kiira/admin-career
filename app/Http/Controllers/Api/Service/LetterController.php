<?php

namespace App\Http\Controllers\Api\Service;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateLetterJob;
use App\Models\Job\Company;
use App\Models\Job\JobPost;
use App\Models\Service\LetterRequest;
use App\Services\Jobs\CvTextExtractionService;
use App\Services\Jobs\LetterGeneratorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LetterController extends Controller
{
    public function __construct(
        protected CvTextExtractionService $extractor,
        protected LetterGeneratorService $generator,
    ) {}

    // ─────────────────────────────────────────────────────────────
    // List
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);

        $letters = LetterRequest::where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn($l) => $this->format($l));

        return response()->json(['success' => true, 'data' => $letters]);
    }

    // ─────────────────────────────────────────────────────────────
    // Show
    // ─────────────────────────────────────────────────────────────
    public function show(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $letter = LetterRequest::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->first();

        if (!$letter) return response()->json(['success' => false, 'message' => 'Letter not found'], 404);

        return response()->json(['success' => true, 'data' => $this->format($letter, true)]);
    }

    // ─────────────────────────────────────────────────────────────
    // Create (store)
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);

        $seeker = $user->seekerProfile;
        if (!$seeker) return response()->json(['success' => false, 'message' => 'Seeker profile not found'], 404);

        $validated = $request->validate([
            'cv_source'       => ['required', Rule::in(['existing', 'uploaded'])],
            'cv_path'         => 'required_if:cv_source,existing|nullable|string',
            'cv_file'         => 'required_if:cv_source,uploaded|nullable|file|mimes:pdf,doc,docx|max:5120',

            'job_source'      => ['required', Rule::in(['db', 'manual', 'paste'])],
            'job_post_id'     => 'required_if:job_source,db|nullable|integer|exists:job_posts,id',
            'job_title'       => 'required_unless:job_source,db|nullable|string|max:255',
            'company_name'    => 'required_unless:job_source,db|nullable|string|max:255',
            'job_description' => 'required_if:job_source,paste|nullable|string|max:20000',

            'letter_type'     => ['required', Rule::in(['cover', 'application'])],
        ]);

        $cvPath = null;

        // ── Existing CV: verify it exists on the public disk
        if ($validated['cv_source'] === 'existing') {
            $cvPath = $validated['cv_path'];
            if (!Storage::disk('public')->exists($cvPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected CV could not be found.',
                ], 422);
            }
        }

        // ── Uploaded CV: store to tmp, extract, validate, reject if not a real CV
        if ($validated['cv_source'] === 'uploaded') {
            $file = $request->file('cv_file');
            $tmpPath = 'tmp/letters/' . Str::uuid() . '.' . $file->getClientOriginalExtension();

            try {
                $file->storeAs('tmp/letters', basename($tmpPath), 'public');
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not save the uploaded CV. Please try again.',
                ], 500);
            }

            // Extract
            try {
                $absolute = Storage::disk('public')->path($tmpPath);
                $mime     = Storage::disk('public')->mimeType($tmpPath) ?: 'application/pdf';
                $text     = $this->extractor->extract($absolute, $mime);
            } catch (\Throwable $e) {
                Storage::disk('public')->delete($tmpPath);
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file could not be read. Please upload a valid PDF or Word CV.',
                ], 422);
            }

            if (!$this->generator->isPlausibleCv($text)) {
                Storage::disk('public')->delete($tmpPath);
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file does not look like a real CV. Please upload your actual CV (PDF or Word).',
                ], 422);
            }

            $cvPath = $tmpPath;
        }

        // ── Resolve job title / company / job_post_id
        $jobPostId    = null;
        $jobTitle     = $validated['job_title']      ?? null;
        $companyName  = $validated['company_name']   ?? null;
        $jobDesc      = $validated['job_description']?? null;

        if ($validated['job_source'] === 'db') {
            $job = JobPost::find($validated['job_post_id']);
            if (!$job) {
                if ($cvPath && $validated['cv_source'] === 'uploaded') {
                    Storage::disk('public')->delete($cvPath);
                }
                return response()->json(['success' => false, 'message' => 'Selected job was not found.'], 422);
            }
            $jobPostId   = $job->id;
            $jobTitle    = $job->job_title;
            $companyName = optional($job->company)->name ?? 'Company';
        }

        // ── Create the request
        $letter = LetterRequest::create([
            'user_id'           => $user->id,
            'seeker_profile_id' => $seeker->id,
            'cv_source'         => $validated['cv_source'],
            'cv_path'           => $cvPath,
            'job_post_id'       => $jobPostId,
            'job_title'         => $jobTitle,
            'company_name'      => $companyName,
            'job_description'   => $jobDesc,
            'letter_type'       => $validated['letter_type'],
            'price'             => 3000,
            'currency'          => 'UGX',
            'status'            => 'pending_payment',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Letter request created. Please proceed to payment.',
            'data'    => $this->format($letter),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Pay (stub)
    // ─────────────────────────────────────────────────────────────
    public function pay(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $letter = LetterRequest::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->first();

        if (!$letter) return response()->json(['success' => false, 'message' => 'Letter not found'], 404);

        if ($letter->status === 'pending_payment') {
            $letter->update([
                'status'            => 'paid',
                'payment_reference' => 'STUB-' . Str::uuid(),
                'paid_at'           => now(),
            ]);

            GenerateLetterJob::dispatch($letter->id)->onQueue('default');
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment received. Your letter is being generated.',
            'data'    => $this->format($letter->fresh()),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Download PDF (generated on demand, never stored)
    // ─────────────────────────────────────────────────────────────
    public function download(Request $request, string $uuid)
    {
        \Log::info('Yes the best');
        $user = $request->user();
        $letter = LetterRequest::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($letter->status !== 'generated' || empty($letter->content)) {
            abort(404, 'Letter is not ready yet.');
        }

        $pdf = Pdf::loadView('pdf.letter', [
            'letter'      => $letter,
            'candidate'   => $letter->seekerProfile,
            'generatedAt' => optional($letter->generated_at)->format('F j, Y'),
        ])->setPaper('a4');

        $filename = 'letter-' . Str::slug($letter->job_title) . '-' . $letter->uuid . '.pdf';

        return $pdf->download($filename);
    }

    // ─────────────────────────────────────────────────────────────
    // Delete
    // ─────────────────────────────────────────────────────────────
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $letter = LetterRequest::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->first();

        if (!$letter) {
            return response()->json(['success' => false, 'message' => 'Letter not found'], 404);
        }

        // Cleanup temp CV if the request never finished generating
        if ($letter->cv_source === 'uploaded'
            && $letter->cv_path
            && str_starts_with($letter->cv_path, 'tmp/letters/')
            && Storage::disk('public')->exists($letter->cv_path)) {
            Storage::disk('public')->delete($letter->cv_path);
        }

        $letter->delete();

        return response()->json(['success' => true, 'message' => 'Letter deleted.']);
    }

    // ─────────────────────────────────────────────────────────────
    // Search jobs for the picker
    // ─────────────────────────────────────────────────────────────
    public function searchJobs(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $jobs = JobPost::with('company:id,name')
            ->where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('job_title', 'like', "%{$q}%")
                      ->orWhereHas('company', fn($cq) => $cq->where('name', 'like', "%{$q}%"));
            })
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get(['id', 'job_title', 'company_id', 'slug']);

        return response()->json([
            'success' => true,
            'data'    => $jobs->map(fn($j) => [
                'id'         => $j->id,
                'job_title'  => $j->job_title,
                'company_id' => $j->company_id,
                'company'    => optional($j->company)->name,
                'label'      => $j->job_title . ' — ' . (optional($j->company)->name ?? 'Company'),
            ]),
        ]);
    }

    public function companies(Request $request): JsonResponse
    {
        $companies = Company::where('is_active', true)
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]);

        return response()->json(['success' => true, 'data' => $companies]);
    }

    // ─────────────────────────────────────────────────────────────
    // Format helper
    // ─────────────────────────────────────────────────────────────
    protected function format(LetterRequest $l, bool $full = false): array
    {
        $data = [
            'uuid'                => $l->uuid,
            'letter_type'         => $l->letter_type,
            'letter_type_label'   => $l->letter_type_label,
            'job_title'           => $l->job_title,
            'company_name'        => $l->company_name,
            'status'              => $l->status,
            'status_badge'        => $l->status_badge,
            'price'               => (float) $l->price,
            'price_label'         => $l->price_label,
            'currency'            => $l->currency,
            'is_paid'             => $l->is_paid,
            'error_message'       => $l->error_message,
            'created_at'          => $l->created_at?->toISOString(),
            'paid_at'             => $l->paid_at?->toISOString(),
            'generated_at'        => $l->generated_at?->toISOString(),
        ];

        if ($full && $l->status === 'generated') {
            $data['content'] = $l->content;
        }

        return $data;
    }
}