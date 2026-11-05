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
use Illuminate\Support\Facades\{ Storage, Log };
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


    public function saveEdited(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $letter = LetterRequest::where('uuid', $uuid)->where('user_id', $user->id)->first();

        if (!$letter) {
            return response()->json(['success' => false, 'message' => 'Letter not found'], 404);
        }

        if ($letter->status !== 'generated') {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit a generated letter.',
            ], 422);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:50000',
        ]);

        $clean = $this->sanitizeLetterHtml($validated['content']);

        if (trim(strip_tags($clean)) === '') {
            return response()->json([
                'success' => false,
                'message' => 'The letter cannot be empty.',
            ], 422);
        }

        $updates = [
            'content'        => $clean,
            'is_user_edited' => true,
            'user_edited_at' => now(),
        ];

        // Snapshot the AI content the FIRST time the user edits
        if (!$letter->is_user_edited && empty($letter->content_original)) {
            $updates['content_original'] = $letter->content;
        }

        $letter->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Your edits have been saved.',
            'data'    => $this->format($letter->fresh(), true),
        ]);
    }

    protected function sanitizeLetterHtml(string $raw): string
    {
        $clean = str_replace(["\r\n", "\r"], "\n", trim($raw));

        // Allow basic rich text tags from TinyMCE
        $allowed = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'a'];

        // Strip disallowed tags but keep their content
        $clean = preg_replace_callback('#<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>(.*?)</\1>|<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*/?>#is', function ($m) use ($allowed) {
            $tag = strtolower($m[1] ?: $m[3]);
            if (!in_array($tag, $allowed, true)) {
                // Drop the tag, keep the inner text if there is one
                return $m[2] ?? '';
            }
            return $m[0];
        }, $clean);

        // Strip all attributes from allowed tags except <a href>
        $clean = preg_replace_callback('#<(/?)([a-zA-Z][a-zA-Z0-9]*)\b([^>]*)>#i', function ($m) use ($allowed) {
            $slash = $m[1];
            $tag   = strtolower($m[2]);

            if (!in_array($tag, $allowed, true)) {
                return '';
            }

            if ($tag === 'a' && !$slash) {
                // Keep href and target, drop everything else
                $attrs = $m[3];
                $href  = '';
                if (preg_match('/href="([^"]*)"/i', $attrs, $h)) {
                    $href = ' href="' . htmlspecialchars($h[1], ENT_QUOTES, 'UTF-8') . '"';
                }
                return '<a' . $href . ' target="_blank" rel="noopener noreferrer">';
            }

            return $slash ? "</{$tag}>" : "<{$tag}>";
        }, $clean);

        return trim($clean);
    }

    public function resetEdited(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $letter = LetterRequest::where('uuid', $uuid)->where('user_id', $user->id)->first();

        if (!$letter) {
            return response()->json(['success' => false, 'message' => 'Letter not found'], 404);
        }

        if (empty($letter->content_original)) {
            return response()->json([
                'success' => false,
                'message' => 'There is no AI version to restore.',
            ], 422);
        }

        $letter->update([
            'content'          => $letter->content_original,
            'is_user_edited'   => false,
            'user_edited_at'   => null,
            'content_original' => null,
        ]);

        Log::info('LetterController::resetEdited', [
            'uuid'    => $uuid,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Restored to the AI version.',
            'data'    => $this->format($letter->fresh(), true),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Create (store)
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $seeker = $user->seekerProfile;
        if (!$seeker) {
            return response()->json(['success' => false, 'message' => 'Seeker profile not found'], 404);
        }

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

        // ── Resolve cover_letter price for the user's country ─────
        $countryCode = strtoupper(
            $user->country_code
            ?? $seeker->country
            ?? config('app.country_code', 'UG')
        );

        $coverPrice = \App\Models\Service\ServicePrice::resolve('cover_letter', $countryCode);

        if (!$coverPrice) {
            return response()->json([
                'success' => false,
                'message' => 'Cover letter pricing is not available in your region yet.',
            ], 422);
        }

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
            $file    = $request->file('cv_file');
            $tmpPath = 'tmp/letters/' . Str::uuid() . '.' . $file->getClientOriginalExtension();

            try {
                $file->storeAs('tmp/letters', basename($tmpPath), 'public');
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not save the uploaded CV. Please try again.',
                ], 500);
            }

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
        $jobPostId   = null;
        $jobTitle    = $validated['job_title']       ?? null;
        $companyName = $validated['company_name']    ?? null;
        $jobDesc     = $validated['job_description'] ?? null;

        if ($validated['job_source'] === 'db') {
            $job = JobPost::find($validated['job_post_id']);
            if (!$job) {
                if ($cvPath && $validated['cv_source'] === 'uploaded') {
                    Storage::disk('public')->delete($cvPath);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Selected job was not found.',
                ], 422);
            }
            $jobPostId   = $job->id;
            $jobTitle    = $job->job_title;
            $companyName = optional($job->company)->name ?? 'Company';
        }

        // ── Prevent duplicate pending_payment letters (same user + job + type, last 15 min)
        $existing = LetterRequest::where('user_id', $user->id)
            ->where('status', 'pending_payment')
            ->where('letter_type', $validated['letter_type'])
            ->where('job_title', $jobTitle)
            ->where('company_name', $companyName)
            ->where('created_at', '>', now()->subMinutes(15))
            ->first();

        if ($existing) {
            // Clean up the freshly uploaded tmp CV since we're reusing the old request
            if ($cvPath && $validated['cv_source'] === 'uploaded' && Storage::disk('public')->exists($cvPath)) {
                Storage::disk('public')->delete($cvPath);
            }

            return response()->json([
                'success' => true,
                'reused'  => true,
                'message' => 'You already have an unpaid letter for this job. Continue with payment.',
                'data'    => $this->format($existing),
            ]);
        }

        // ── Create the request — price snapshotted from service_prices
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
            'price'             => $coverPrice->amount,             // decimal, e.g. 15000 for UGX
            'currency'          => $coverPrice->currency->code,     // 'UGX', 'KES', 'USD'
            'status'            => 'pending_payment',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Letter request created. Please proceed to payment.',
            'data'    => $this->format($letter),
        ]);
    }

    public function regenerate(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();

        $letter = LetterRequest::where('uuid', $uuid)->where('user_id', $user->id)->first();

        if (!$letter) {
            return response()->json(['success' => false, 'message' => 'Letter not found'], 404);
        }

        if (!in_array($letter->status, ['failed', 'generated'], true)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot regenerate from status '{$letter->status}'.",
            ], 422);
        }

        if ($letter->regeneration_count >= $letter->max_regenerations) {
            return response()->json([
                'success' => false,
                'code'    => 'max_regenerations_reached',
                'message' => "You've reached the regeneration limit ({$letter->max_regenerations}). Update the CV or job details instead.",
            ], 422);
        }

        $letter->update([
            'status'             => 'paid',
            'error_message'      => null,
            'regeneration_count' => $letter->regeneration_count + 1,
            'is_user_edited'     => false,
            'user_edited_at'     => null,
            'content_original'   => null,
        ]);

        try {
            GenerateLetterJob::dispatch($letter->id)->onQueue('default');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not queue the regeneration. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Regenerating your letter...',
            'data'    => $this->format($letter->fresh()),
        ]);
    }

    // public function generate(LetterRequest $request): void
    // {
    //     $t0  = microtime(true);
    //     $tag = "[letter:{$request->uuid}]";

    //     $request->update(['status' => 'processing', 'error_message' => null]);

    //     Log::info("{$tag} generate() START", [
    //         'cv_source'   => $request->cv_source,
    //         'cv_path'     => $request->cv_path,
    //         'letter_type' => $request->letter_type,
    //     ]);

    //     try {
    //         // 1. Extract
    //         Log::info("{$tag} STEP 1 extracting CV");
    //         $cvText = $this->resolveCvText($request);

    //         Log::info("{$tag} STEP 1 done", [
    //             'chars' => strlen($cvText),
    //             'words' => str_word_count($cvText),
    //             'secs'  => round(microtime(true) - $t0, 2),
    //         ]);

    //         if ($cvText === '' || !$this->isPlausibleCv($cvText)) {
    //             throw new \Exception(
    //                 'We could not read your CV. It may be a scanned image, password-protected, or not a CV. '
    //                 . 'Please upload a PDF or Word version of your actual CV and try again.'
    //             );
    //         }

    //         // 2. Job context
    //         $jobContext = [
    //             'job_title'       => $request->job_title,
    //             'company_name'    => $request->company_name,
    //             'job_description' => $this->resolveJobDescription($request),
    //         ];

    //         // 3. Single AI attempt
    //         Log::info("{$tag} STEP 2 calling AI (single attempt)");
    //         $aiStart = microtime(true);
    //         $html = $this->ai->generateLetterOnce($cvText, $jobContext, $request->letter_type);
    //         Log::info("{$tag} STEP 2 AI done", [
    //             'secs'  => round(microtime(true) - $aiStart, 2),
    //             'words' => str_word_count(strip_tags($html)),
    //         ]);

    //         // 4. Persist
    //         $request->update([
    //             'content'      => $html,
    //             'status'       => 'generated',
    //             'generated_at' => now(),
    //         ]);

    //         Log::info("{$tag} generate() COMPLETE", [
    //             'total_secs' => round(microtime(true) - $t0, 2),
    //         ]);

    //     } catch (\Throwable $e) {
    //         Log::warning("{$tag} generate() FAILED", [
    //             'elapsed_secs' => round(microtime(true) - $t0, 2),
    //             'class'        => get_class($e),
    //             'message'      => $e->getMessage(),
    //         ]);

    //         $request->update([
    //             'status'        => 'failed',
    //             'error_message' => $e->getMessage(),
    //         ]);

    //         throw $e;

    //     } finally {
    //         $this->cleanupTempCv($request);
    //     }
    // }

    public function updateInputs(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $letter = LetterRequest::where('uuid', $uuid)->where('user_id', $user->id)->first();
        if (!$letter) {
            return response()->json(['success' => false, 'message' => 'Letter not found'], 404);
        }

        // Only editable when: pending_payment (fix inputs before paying)
        //                  OR failed with retries exhausted (fix inputs to retry)
        $attempts    = (int) ($letter->regeneration_count ?? 0);
        $maxAttempts = (int) ($letter->max_regenerations ?? 2);

        $isPending   = $letter->status === 'pending_payment';
        $isFailedOut = $letter->status === 'failed' && $attempts >= $maxAttempts;

        if (!$isPending && !$isFailedOut) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit a letter that is pending payment or has exhausted its retries.',
            ], 422);
        }

        $validated = $request->validate([
            'job_title'    => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'cv_file'      => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $updates = [];

        if (!empty($validated['job_title'])) {
            $updates['job_title'] = $validated['job_title'];
        }
        if (!empty($validated['company_name'])) {
            $updates['company_name'] = $validated['company_name'];
        }

        // ── New CV uploaded → save, validate, replace old tmp ─────
        if ($request->hasFile('cv_file')) {
            $file    = $request->file('cv_file');
            $tmpPath = 'tmp/letters/' . \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();

            try {
                $file->storeAs('tmp/letters', basename($tmpPath), 'public');
                $absolute = Storage::disk('public')->path($tmpPath);
                $mime     = Storage::disk('public')->mimeType($tmpPath) ?: 'application/pdf';
                $text     = $this->extractor->extract($absolute, $mime);

                if (!$this->generator->isPlausibleCv($text)) {
                    Storage::disk('public')->delete($tmpPath);
                    return response()->json([
                        'success' => false,
                        'message' => 'The uploaded file does not look like a real CV. Please upload your actual CV (PDF or Word).',
                    ], 422);
                }

                // Delete old tmp CV (never touch profile CVs)
                if ($letter->cv_source === 'uploaded'
                    && $letter->cv_path
                    && str_starts_with($letter->cv_path, 'tmp/letters/')
                    && Storage::disk('public')->exists($letter->cv_path)) {
                    Storage::disk('public')->delete($letter->cv_path);
                }

                $updates['cv_source'] = 'uploaded';
                $updates['cv_path']   = $tmpPath;
            } catch (\Throwable $e) {
                if (Storage::disk('public')->exists($tmpPath)) {
                    Storage::disk('public')->delete($tmpPath);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Could not read the uploaded CV. Please try a different file.',
                ], 422);
            }
        }

        if (empty($updates)) {
            return response()->json([
                'success' => false,
                'message' => 'Nothing to update — change the job details or upload a new CV.',
            ], 422);
        }

        // ── Reset for a fresh attempt if this was a failed+exhausted letter ──
        if ($isFailedOut) {
            $updates['status']             = 'paid';
            $updates['error_message']      = null;
            $updates['regeneration_count'] = 0;   // ← fresh retry budget
            $updates['generated_at']       = null;
            $updates['content']            = null;
            $updates['content_original']   = null;
            $updates['is_user_edited']     = false;
            $updates['user_edited_at']     = null;
        }

        $letter->update($updates);

        // Only retry generation for the failed+exhausted path.
        // pending_payment stays pending → the user must still pay.
        if ($isFailedOut) {
            GenerateLetterJob::dispatch($letter->id)->onQueue('default');
        }

        return response()->json([
            'success' => true,
            'message' => $isPending
                ? 'Changes saved. You can now proceed with payment.'
                : 'Updated. Regenerating your letter…',
            'data'    => $this->format($letter->fresh()),
        ]);
    }
            

    // ─────────────────────────────────────────────────────────────
    // Pay (stub)
    // ─────────────────────────────────────────────────────────────
    public function pay(Request $request, string $uuid): JsonResponse
    {
        $user   = $request->user();
        $letter = LetterRequest::where('uuid', $uuid)->where('user_id', $user->id)->first();

        if (!$letter) {
            return response()->json(['success' => false, 'message' => 'Letter not found'], 404);
        }

        if ($letter->status !== 'pending_payment') {
            return response()->json([
                'success' => false,
                'message' => "Payment is not expected for status '{$letter->status}'.",
            ], 422);
        }

        $countryCode = strtoupper($letter->country_code ?: config('app.country_code', 'UG'));
        $price = \App\Models\Service\ServicePrice::resolve('cover_letter', $countryCode);

        if (!$price) {
            return response()->json([
                'success' => false,
                'message' => 'Cover letter pricing is not available in your region yet.',
            ], 422);
        }

        // Free path
        if ((int) $price->amount_cents === 0) {
            $letter->update([
                'status'            => 'paid',
                'payment_reference' => 'FREE-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'paid_at'           => now(),
            ]);
            \App\Jobs\GenerateLetterJob::dispatch($letter->id)->onQueue('default');

            return response()->json([
                'success' => true,
                'is_free' => true,
                'message' => 'This service is free right now. Your letter is being generated.',
            ]);
        }

        // Snapshot price into the letter
        $letter->update([
            'price'    => $price->amount,
            'currency' => $price->currency->code,
        ]);

        $initRequest = new \Illuminate\Http\Request();
        $initRequest->merge([
            'amount_cents'      => (int) $price->amount_cents,
            'currency_code'     => $price->currency->code,
            'purpose_type'      => 'cover_letter',
            'purpose_reference' => $letter->uuid,
            'description'       => 'Cover Letter — ' . $letter->job_title,
            'phone'             => $request->input('phone') ?? $user->phone,
            'method'            => $request->input('method', 'mobile_money'),
        ]);
        $initRequest->setUserResolver(fn() => $user);

        $gatewayResponse = app(\App\Http\Controllers\Api\Payment\PaymentInitController::class)
            ->initiate($initRequest);

        $payload = $gatewayResponse->getData(true);

        if (!empty($payload['success']) && empty($payload['is_free'])) {
            $payload['next_service_key']  = 'cover_letter';
            $payload['next_service_name'] = 'Cover Letter';
            $payload['amount_formatted']  = $price->formatted_amount;
            $payload['amount_cents']      = (int) $price->amount_cents;
            $payload['currency_code']     = $price->currency->code;

            return response()->json($payload, $gatewayResponse->getStatusCode());
        }

        return $gatewayResponse;
    }

    // ─────────────────────────────────────────────────────────────
    // Download PDF (generated on demand, never stored)
    // ─────────────────────────────────────────────────────────────
    public function download(Request $request, string $uuid)
    {
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

        $userName   = $this->resolveUserName($letter, $user);
        $letterType = $letter->letter_type === 'application'
            ? 'application-letter'
            : 'cover-letter';
        $jobTitle   = $letter->job_title ?: 'untitled';
        $shortId    = substr($letter->uuid, 0, 8);

        $filename = sprintf(
            '%s_%s_%s_%s.pdf',
            Str::slug($userName),
            $letterType,
            Str::slug($jobTitle),
            $shortId
        );

        // Cap total length for cross-filesystem safety
        $filename = mb_substr($filename, 0, 200) . '.pdf';

        return $pdf->download($filename);
    }

    protected function resolveUserName(LetterRequest $letter, $user): string
    {
        $seeker = $letter->seekerProfile;

        if ($seeker) {
            $first = trim((string) ($seeker->first_name ?? ''));
            $last  = trim((string) ($seeker->last_name  ?? ''));
            $full  = trim("{$first} {$last}");
            if ($full !== '') return $full;
            if (!empty($seeker->full_name)) return (string) $seeker->full_name;
        }

        if ($user && !empty($user->name)) {
            return (string) $user->name;
        }

        return 'candidate';
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
            'is_user_edited' => (bool) ($l->is_user_edited ?? false),
            'user_edited_at' => $l->user_edited_at?->toISOString(),
            'has_original'   => !empty($l->content_original),

            'price_label'         => $l->price_label,
            'currency'            => $l->currency,
            'is_paid'             => $l->is_paid,
            'error_message'       => $l->error_message,
            'created_at'          => $l->created_at?->toISOString(),
            'paid_at'             => $l->paid_at?->toISOString(),
            'generated_at'        => $l->generated_at?->toISOString(),
            'regeneration_count' => (int) ($l->regeneration_count ?? 0),
            'max_regenerations'  => (int) ($l->max_regenerations ?? 2),
        ];

        if ($full && $l->status === 'generated') {
            $data['content'] = $l->content;
        }

        return $data;
    }


}