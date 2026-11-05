<?php

namespace App\Http\Controllers\Api\Service;

use App\Http\Controllers\Controller;
use App\Models\Service\CvReviewRequest;
use App\Models\Service\Service;
use App\Models\Currency;
use App\Models\User;
use App\Services\Payment\PricingService;
use App\Services\Jobs\AiService;
use App\Services\Jobs\CvTextExtractionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Jobs\GenerateCvRewriteJob;
use App\Services\Jobs\CvRewriteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use App\Services\Jobs\CvHtmlSanitizer;

class CvReviewRequestController extends Controller
{
    private const MAX_ACTIVE_REVIEWS = 3;

    public function __construct(
        protected PricingService $pricing,
        protected AiService $ai,
        protected CvTextExtractionService $textExtractor,
        protected CvRewriteService $rewrite,
    ) {}

    // =================================================================
    // QUOTE
    // =================================================================
    public function quote(Request $request): JsonResponse
    {
        $request->validate([
            'country_code' => 'nullable|string|size:2',
        ]);

        $user = $request->user();

        $countryCode = strtoupper(
            $request->input('country_code')
            ?? $user?->seekerProfile?->country
            ?? 'XX'
        );

        $service = Service::where('key', 'cv_review')
            ->where('is_active', true)
            ->first();

        if (!$service) {
            return response()->json([
                'success'   => true,
                'available' => false,
                'message'   => 'CV Review is currently unavailable.',
            ]);
        }

        // PricingService returns ?array — null means no price available
        $price = $this->pricing->priceFor('cv_review', $countryCode);

        if ($price === null) {
            return response()->json([
                'success'   => true,
                'available' => false,
                'message'   => 'CV Review is not available in your region yet.',
            ]);
        }

        return response()->json([
            'success'   => true,
            'available' => true,
            'service'   => [
                'key'                      => $service->key,
                'name'                     => $service->name,
                'description'              => $service->description,
                'default_turnaround_hours' => $service->default_turnaround_hours,
                'turnaround_label'         => $service->turnaround_label,
            ],
            'price' => [
                'amount_cents'  => $price['amount_cents'],
                'currency_code' => $price['currency_code'],
                'formatted'     => $price['formatted'],
                'is_free'       => $price['amount_cents'] === 0,
                'is_fallback'   => $price['is_fallback'] ?? false,
            ],
        ]);
    }

    // =================================================================
    // STORE
    // =================================================================
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        // ── Limit check ──────────────────────────────────────────────
        $activeCount = CvReviewRequest::where('user_id', $user->id)
            ->whereNotIn('status', ['paid', 'completed'])
            ->count();

        if ($activeCount >= self::MAX_ACTIVE_REVIEWS) {
            return response()->json([
                'success'      => false,
                'code'         => 'max_active_reviews',
                'message'      => 'You have ' . self::MAX_ACTIVE_REVIEWS . ' CV reviews that are still in progress. Please delete one or more before submitting a new CV.',
                'active_count' => $activeCount,
                'max_allowed'  => self::MAX_ACTIVE_REVIEWS,
            ], 422);
        }

        $request->validate([
            'cv_file'                => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'existing_cv_path'       => 'nullable|string',
            'target_job_title'       => 'nullable|string|max:255',
            'target_job_description' => 'nullable|string|max:10000',
            'country_code'           => 'nullable|string|size:2',
        ]);

        if (!$request->hasFile('cv_file') && !$request->filled('existing_cv_path')) {
            return response()->json([
                'success' => false,
                'message' => 'Please upload a CV or select one of your existing CVs.',
            ], 422);
        }

        $service = Service::where('key', 'cv_review')->where('is_active', true)->firstOrFail();

        $countryCode = strtoupper(
            $request->input('country_code')
            ?? $user->seekerProfile?->country
            ?? 'XX'
        );

        $price = $this->pricing->priceFor('cv_review', $countryCode);

        if ($price === null) {
            return response()->json([
                'success' => false,
                'message' => 'CV Review is not available in your region yet.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            [$path, $originalName] = $this->resolveCvFile($request, $user);

            $currency = Currency::where('code', $price['currency_code'])->first();
            if (!$currency) {
                throw new \Exception("Currency '{$price['currency_code']}' is not configured.");
            }

            $cvRequest = CvReviewRequest::create([
                'user_id'                => $user->id,
                'service_id'             => $service->id,
                'country_code'           => $countryCode,
                'currency_id'            => $currency->id,
                'amount_cents'           => $price['amount_cents'],
                'target_job_title'       => $request->input('target_job_title'),
                'target_job_description' => $request->input('target_job_description'),
                'original_cv_path'       => $path,
                'original_cv_name'       => $originalName,
                'ai_gap_review'          => null,
                'status'                 => 'submitted',
            ]);

            DB::commit();

            return response()->json([
                'success'   => true,
                'message'   => 'Your CV has been submitted. Our team will review it shortly.',
                'request'   => $this->formatRequest($cvRequest),
                'next_step' => 'awaiting_admin_review',
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('CV review request failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit request: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =================================================================
    // SUBMIT ANSWERS
    // =================================================================
    public function submitAnswers(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        $cvRequest = CvReviewRequest::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $request->validate([
            'answers' => 'required|array',
        ]);

        if (!in_array($cvRequest->status, ['ai_reviewed', 'awaiting_payment'], true)) {
            return response()->json([
                'success' => false,
                'message' => "Answers can only be submitted or edited before payment. Current status: '{$cvRequest->status}'.",
            ], 422);
        }

        $wasEdit = $cvRequest->status === 'awaiting_payment' && !empty($cvRequest->seeker_gap_answers);

        $cvRequest->update([
            'seeker_gap_answers' => $request->input('answers'),
            'status'             => 'awaiting_payment',
        ]);

        return response()->json([
            'success'   => true,
            'message'   => $wasEdit
                ? 'Your answers have been updated.'
                : 'Answers saved. Proceed to payment.',
            'request'   => $this->formatRequest($cvRequest->fresh()),
            'next_step' => 'payment',
        ]);
    }

    public function runAiReview(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();
        $cvRequest = CvReviewRequest::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Only run once, and only when the CV has just been submitted
        if ($cvRequest->status !== 'submitted') {
            return response()->json([
                'success' => false,
                'message' => "AI review already exists for status '{$cvRequest->status}'.",
            ], 422);
        }

        if (!empty($cvRequest->ai_gap_review)) {
            return response()->json([
                'success' => false,
                'message' => 'AI review already generated.',
            ], 422);
        }

        try {
            $cvText = app(\App\Services\Jobs\CvRewriteService::class)
                ->resolveCvText($cvRequest);

            if ($cvText === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not read your CV. Please re-upload a PDF or Word file.',
                ], 422);
            }

            $review = $this->ai->reviewCvGaps(
                $cvText,
                $cvRequest->target_job_description ?: null
            );

            $cvRequest->update([
                'ai_gap_review' => $review,
                'status'        => 'ai_reviewed',
            ]);

            \Log::info('Seeker ran AI review', [
                'uuid'  => $cvRequest->uuid,
                'score' => $review['overall_score'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'AI review complete.',
                'request' => $this->formatRequest($cvRequest->fresh()),
            ]);

        } catch (\Throwable $e) {
            \Log::error('Seeker AI review failed', [
                'uuid'  => $uuid,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'AI review failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function initPayment(Request $request, $uuid): JsonResponse
    {
        $request->validate([
            'mode'   => 'nullable|in:ai,human',
            'phone'  => 'nullable|string|max:25',
            'method' => 'nullable|in:mobile_money,card',
        ]);

        $user = $request->user();
        $cvRequest = CvReviewRequest::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($cvRequest->status !== 'awaiting_payment') {
            return response()->json([
                'success' => false,
                'message' => "Payment is not expected for status '{$cvRequest->status}'.",
            ], 422);
        }

        $mode        = $request->input('mode', 'ai');
        $countryCode = strtoupper($cvRequest->country_code ?: config('app.country_code', 'UG'));

        $basePrice = \App\Models\Service\ServicePrice::resolve('cv_rewrite', $countryCode);
        if (!$basePrice) {
            return response()->json([
                'success' => false,
                'message' => 'CV rewrite pricing is not available in your region.',
            ], 422);
        }

        // HR expert = base × 3 (i.e. +200%)
        $amountCents = (int) $basePrice->amount_cents;
        if ($mode === 'human') {
            $amountCents *= 3;
        }

        // ─────────────────────────────────────────────────────────────
        // Idempotency: if a deposit is already pending for this request
        // within the last 15 minutes...
        //   - same amount (same mode)  → reuse it, don't create a duplicate
        //   - different amount (mode changed AI↔HR) → mark old failed, create new
        // ─────────────────────────────────────────────────────────────
        $existingDeposit = \App\Models\Deposit::where('purpose_type', 'cv_review')
            ->where('purpose_reference', $cvRequest->uuid)
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subMinutes(15))
            ->orderByDesc('id')
            ->first();

        if ($existingDeposit) {
            $existingAmountCents = (int) $existingDeposit->amount;

            if ($existingAmountCents === $amountCents) {
                // Same mode, same amount → reuse the in-flight deposit
                $cvRequest->update([
                    'amount_cents'         => $amountCents,
                    'currency_id'          => $basePrice->currency_id,
                    'paid_for_service_key' => $mode === 'human' ? 'cv_rewrite_human' : 'cv_rewrite',
                    'rewrite_mode'         => $mode,
                ]);

                return response()->json([
                    'success'          => true,
                    'reused'           => true,
                    'deposit_ref'      => $existingDeposit->deposit_ref,
                    'mode'             => $mode,
                    'next_service_key' => $mode === 'human' ? 'cv_rewrite_human' : 'cv_rewrite',
                    'next_service_name'=> $mode === 'human' ? 'HR Expert CV Rewrite' : 'AI CV Rewrite',
                    'amount_formatted' => $this->formatAmountFor($amountCents, $basePrice->currency->code),
                    'amount_cents'     => $amountCents,
                    'currency_code'    => $basePrice->currency->code,
                    'message'          => 'A payment for this CV is already in progress.',
                ]);
            }

            // Amount changed → user switched modes. Void the old pending deposit
            // so it can't be charged, then create a fresh one.
            $existingDeposit->update([
                'status' => 'failed',
                'notes'  => trim(
                    ($existingDeposit->notes ?? '') .
                    "\nSuperseded: user changed mode to {$mode} (was " .
                    ($existingDeposit->amount === $amountCents ? 'same' : "{$existingDeposit->amount} cents") . ')'
                ),
            ]);

            \Log::info('CV payment: superseding pending deposit due to mode change', [
                'uuid'             => $cvRequest->uuid,
                'old_deposit_ref'  => $existingDeposit->deposit_ref,
                'old_amount_cents' => $existingAmountCents,
                'new_amount_cents' => $amountCents,
                'new_mode'         => $mode,
            ]);
        }

        // Snapshot the price + mode on the CV request BEFORE hitting the gateway
        $cvRequest->update([
            'amount_cents'         => $amountCents,
            'currency_id'          => $basePrice->currency_id,
            'paid_for_service_key' => $mode === 'human' ? 'cv_rewrite_human' : 'cv_rewrite',
            'rewrite_mode'         => $mode,
        ]);

        $initRequest = new \Illuminate\Http\Request();
        $initRequest->merge([
            'amount_cents'      => $amountCents,
            'currency_code'     => $basePrice->currency->code,
            'purpose_type'      => 'cv_review',
            'purpose_reference' => $cvRequest->uuid,
            'description'       => $mode === 'human'
                ? 'HR Expert CV Rewrite for #' . substr($cvRequest->uuid, 0, 8)
                : 'AI CV Rewrite for #' . substr($cvRequest->uuid, 0, 8),
            'phone'             => $request->filled('phone') ? $request->input('phone') : $user->phone,
            'method'            => $request->input('method', 'mobile_money'),
        ]);
        $initRequest->setUserResolver(fn() => $user);

        $gatewayResponse = app(\App\Http\Controllers\Api\Payment\PaymentInitController::class)
            ->initiate($initRequest);

        $payload = $gatewayResponse->getData(true);

        if (!empty($payload['success'])) {
            $payload['mode']              = $mode;
            $payload['next_service_key']  = $mode === 'human' ? 'cv_rewrite_human' : 'cv_rewrite';
            $payload['next_service_name'] = $mode === 'human' ? 'HR Expert CV Rewrite' : 'AI CV Rewrite';
            $payload['amount_formatted']  = $this->formatAmountFor($amountCents, $basePrice->currency->code);
            $payload['amount_cents']      = $amountCents;
            $payload['currency_code']     = $basePrice->currency->code;
            return response()->json($payload, $gatewayResponse->getStatusCode());
        }

        return $gatewayResponse;
    }

    private function formatAmountFor(int $cents, string $currencyCode): string
    {
        $currency = \App\Models\Currency::where('code', $currencyCode)->first();
        if ($currency && method_exists($currency, 'formatAmount')) {
            return $currency->formatAmount($cents);
        }
        return $currencyCode . ' ' . number_format($cents);
    }
    
    // =================================================================
    // PAYMENT — webhook callback
    // =================================================================
    public function paymentCallback(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => 'required|string',
            'uuid'      => 'required|string',
            'status'    => 'required|in:success,failed',
        ]);

        $cvRequest = CvReviewRequest::where('uuid', $request->uuid)->firstOrFail();

        if ($request->status !== 'success') {
            Log::warning('CV review payment failed', ['uuid' => $request->uuid]);
            return response()->json(['success' => false, 'message' => 'Payment not successful']);
        }

        if ($cvRequest->isPaid()) {
            return response()->json(['success' => true, 'message' => 'Already processed']);
        }

        $cvRequest->update([
            'status'            => 'paid',
            'payment_reference' => $request->reference,
        ]);

        return response()->json(['success' => true, 'message' => 'Payment confirmed']);
    }

    // =================================================================
    // INDEX
    // =================================================================
    public function index(Request $request): JsonResponse
    {
        $user   = $request->user();
        $status = $request->get('status', '');

        $activeCount = CvReviewRequest::where('user_id', $user->id)
            ->whereNotIn('status', ['paid', 'completed'])
            ->count();

        $query = CvReviewRequest::where('user_id', $user->id)
            ->with(['service', 'currency']);

        if ($status) {
            $query->where('status', $status);
        }

        $requests = $query->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => collect($requests->items())->map(fn($r) => $this->formatRequest($r)),
            'meta'    => [
                'total'        => $requests->total(),
                'current_page' => $requests->currentPage(),
                'last_page'    => $requests->lastPage(),
                'active_count' => $activeCount,
                'max_active'   => self::MAX_ACTIVE_REVIEWS,
                'can_create'   => $activeCount < self::MAX_ACTIVE_REVIEWS,
            ],
        ]);
    }

    // =================================================================
    // SHOW
    // =================================================================
    public function show(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();
        $cvRequest = CvReviewRequest::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->with(['service', 'currency'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'request' => $this->formatRequest($cvRequest),
        ]);
    }

    // =================================================================
    // REQUEST REVISION
    // =================================================================
    public function requestRevision(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        $cvRequest = CvReviewRequest::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $request->validate([
            'notes' => 'required|string|max:2000',
        ]);

        if (!in_array($cvRequest->status, ['delivered', 'revision_requested'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'You can only request a revision on a delivered CV.',
            ], 422);
        }

        $history = $cvRequest->revision_history ?? [];

        if ($cvRequest->status === 'revision_requested' && !empty($history)) {
            $lastIdx = count($history) - 1;
            $history[$lastIdx]['notes']     = $request->notes;
            $history[$lastIdx]['edited_at'] = now()->toISOString();
        } else {
            $history[] = [
                'requested_at' => now()->toISOString(),
                'notes'        => $request->notes,
            ];
        }

        $cvRequest->update([
            'status'           => 'revision_requested',
            'revision_history' => $history,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Revision request saved.',
            'request' => $this->formatRequest($cvRequest->fresh()),
        ]);
    }

    // =================================================================
    // DESTROY
    // =================================================================
    public function destroy(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $cvRequest = CvReviewRequest::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($cvRequest->isPaid() || in_array($cvRequest->status, ['in_progress', 'delivered', 'revision_requested'], true)) {
            return response()->json([
                'success' => false,
                'code'    => 'cannot_delete_paid',
                'message' => 'This request can no longer be deleted because work has already started. Contact support if you need it cancelled.',
            ], 422);
        }

        try {
            $deletedFiles = [];

            if ($cvRequest->original_cv_path && Storage::disk('public')->exists($cvRequest->original_cv_path)) {
                Storage::disk('public')->delete($cvRequest->original_cv_path);
                $deletedFiles[] = $cvRequest->original_cv_path;
            }

            if ($cvRequest->seeker_cv_path
                && $cvRequest->seeker_cv_path !== $cvRequest->original_cv_path
                && Storage::disk('public')->exists($cvRequest->seeker_cv_path)) {
                Storage::disk('public')->delete($cvRequest->seeker_cv_path);
                $deletedFiles[] = $cvRequest->seeker_cv_path;
            }

            if ($cvRequest->delivered_cv_path && Storage::disk('public')->exists($cvRequest->delivered_cv_path)) {
                Storage::disk('public')->delete($cvRequest->delivered_cv_path);
                $deletedFiles[] = $cvRequest->delivered_cv_path;
            }

            $requestFolder = "cv-review/{$cvRequest->uuid}";
            if (Storage::disk('public')->exists($requestFolder)) {
                Storage::disk('public')->deleteDirectory($requestFolder);
                $deletedFiles[] = $requestFolder . '/ (whole folder)';
            }

            $uuidCopy = $cvRequest->uuid;
            $cvRequest->delete();

            Log::info('CV review request deleted', [
                'uuid'          => $uuidCopy,
                'user_id'       => $user->id,
                'deleted_files' => $deletedFiles,
            ]);

            $remaining = CvReviewRequest::where('user_id', $user->id)
                ->whereNotIn('status', ['paid', 'completed'])
                ->count();

            return response()->json([
                'success'       => true,
                'message'       => 'CV review request deleted.',
                'deleted_files' => $deletedFiles,
                'active_count'  => $remaining,
                'max_allowed'   => self::MAX_ACTIVE_REVIEWS,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to delete CV review request', [
                'uuid'  => $uuid,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete request: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =================================================================
    // HELPERS
    // =================================================================

    private function resolveCvFile(Request $request, User $user): array
    {
        if ($request->hasFile('cv_file')) {
            $file = $request->file('cv_file');
            $path = $file->store("cv-review/{$user->id}/source", 'public');
            return [$path, $file->getClientOriginalName()];
        }

        $existingPath = $request->input('existing_cv_path');
        $profile      = $user->seekerProfile;
        $cvFiles      = $profile->cv_files ?? [];

        $found = null;
        foreach ($cvFiles as $cv) {
            if (($cv['path'] ?? null) === $existingPath) {
                $found = $cv;
                break;
            }
        }

        if (!$found || !Storage::disk('public')->exists($existingPath)) {
            throw new \Exception('Selected CV file not found.');
        }

        return [$existingPath, $found['original_name'] ?? 'CV.pdf'];
    }

    private function resolveNextService(CvReviewRequest $r): array
    {
        $isPurchaseStep = in_array($r->status, ['awaiting_payment'], true);

        if (!$isPurchaseStep) {
            return [
                'next_service_key'         => null,
                'next_service_name'        => null,
                'next_service_price'       => null,
                'next_service_price_human' => null,
            ];
        }

        $countryCode = strtoupper($r->country_code ?: config('app.country_code', 'UG'));
        $basePrice   = \App\Models\Service\ServicePrice::resolve('cv_rewrite', $countryCode);

        if (!$basePrice) {
            return [
                'next_service_key'         => 'cv_rewrite',
                'next_service_name'        => 'CV Rewrite',
                'next_service_price'       => null,
                'next_service_price_human' => null,
            ];
        }

        $currencyCode = $basePrice->currency?->code ?? 'UGX';
        $baseCents    = (int) $basePrice->amount_cents;

        // HR expert = base price × 3 (i.e. +200%)
        $humanCents   = $baseCents * 3;

        return [
            'next_service_key'   => 'cv_rewrite',
            'next_service_name'  => 'CV Rewrite',
            'next_service_price' => [
                'amount_cents'  => $baseCents,
                'currency_code' => $currencyCode,
                'formatted'     => $basePrice->formatted_amount,
            ],
            'next_service_price_human' => [
                'amount_cents'  => $humanCents,
                'currency_code' => $currencyCode,
                'formatted'     => $this->formatAmountFor($humanCents, $currencyCode),
            ],
        ];
    }


    private function formatRequest(CvReviewRequest $r): array
    {
        $next = $this->resolveNextService($r);

        // Once answers are submitted, the card represents the CV Rewrite purchase
        $isRewriteStep = $r->status === 'awaiting_payment'
            && !empty($r->seeker_gap_answers);

        $displayServiceName = $isRewriteStep
            ? ($next['next_service_name'] ?? 'CV Rewrite')
            : ($r->service->name ?? '—');

        $displayFormattedAmount = $isRewriteStep && $next['next_service_price']
            ? $next['next_service_price']['formatted']
            : ($r->currency ? $r->currency->formatAmount($r->amount_cents) : '—');

        return [
            'uuid'                  => $r->uuid,
            'status'                => $r->status,
            'status_label'          => ucwords(str_replace('_', ' ', $r->status)),

            'service_name'          => $displayServiceName,
            'formatted_amount'      => $displayFormattedAmount,   // <-- keep THIS one
            'has_answers'           => !empty($r->seeker_gap_answers),
            'target_job_title'      => $r->target_job_title,
            'original_cv_name'      => $r->original_cv_name,
            'payment_method_id'     => $r->payment_method_id,
            'payment_method'        => $r->paymentMethod?->name,
            'original_cv_url'       => $r->cv_download_url,
            'delivered_cv_url'      => $r->delivered_cv_url,
            'amount_cents'          => $r->amount_cents,
            'currency_code'         => $r->currency->code ?? '—',
            'next_service_price_human' => $next['next_service_price_human'],
            'country_code'          => $r->country_code,
            'ai_gap_review'         => $r->ai_gap_review,
            'seeker_gap_answers'    => $r->seeker_gap_answers,
            'payment_reference'     => $r->payment_reference,
            'sla_due_at'            => $r->sla_due_at?->toISOString(),
            'delivered_at'          => $r->delivered_at?->toISOString(),
            'created_at'            => $r->created_at?->toISOString(),
            'allowed_next_statuses' => $r->allowedNextStatuses(),
            'next_service_key'      => $next['next_service_key'],
            'next_service_name'     => $next['next_service_name'],
            'next_service_price'    => $next['next_service_price'],

            'rewrite_mode'              => $r->rewrite_mode,
            'rewrite_is_ai_mode'        => $r->rewrite_is_ai_mode,
            'rewrite_is_human_mode'     => $r->rewrite_is_human_mode,
            'rewrite_is_ready'          => $r->rewrite_is_ready,
            'rewrite_content'           => $r->rewrite_content,
            'rewrite_content_original'  => $r->rewrite_content_original,
            'rewrite_generated_at'      => $r->rewrite_generated_at?->toISOString(),
            'rewrite_regeneration_count'=> $r->rewrite_regeneration_count,
            'rewrite_max_regenerations' => $r->rewrite_max_regenerations,
            'rewrite_is_user_edited'    => $r->rewrite_is_user_edited,
            'rewrite_user_edited_at'    => $r->rewrite_user_edited_at?->toISOString(),
            'rewrite_can_regenerate'    => $r->rewrite_can_regenerate,
            'rewrite_error_message'     => $r->rewrite_error_message,
            'rewrite_download_url'      => $r->rewrite_download_url,

        ];
    }

    public function markComplete(Request $request, string $uuid): JsonResponse
    {
        $req = $this->findOwn($request, $uuid);
        if (!$req) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        if ($req->rewrite_mode !== 'ai' || $req->status !== 'generated') {
            return response()->json([
                'success' => false,
                'message' => 'Only AI-generated CVs can be marked complete.',
            ], 422);
        }

        $req->update(['status' => 'completed']);

        return response()->json(['success' => true, 'message' => 'Marked complete. Thank you!']);
    }

    // ── Get the rewritten CV ────────────────────────────────────
    public function rewriteShow(Request $request, string $uuid): JsonResponse
    {
        $req = $this->findOwn($request, $uuid);
        if (!$req) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        if ($req->rewrite_mode !== 'ai') {
            return response()->json([
                'success' => false,
                'message' => 'This request is not using AI rewrite.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'uuid'              => $req->uuid,
                'status'            => $req->status,
                'is_ready'          => $req->rewrite_is_ready,
                'content'           => $req->rewrite_content['html'] ?? null,
                'is_user_edited'    => $req->rewrite_is_user_edited,
                'user_edited_at'    => $req->rewrite_user_edited_at?->toISOString(),
                'regeneration_count'=> $req->rewrite_regeneration_count,
                'max_regenerations' => $req->rewrite_max_regenerations,
                'can_regenerate'    => $req->rewrite_can_regenerate,
                'error_message'     => $req->rewrite_error_message,
                'generated_at'      => $req->rewrite_generated_at?->toISOString(),
            ],
        ]);
    }

    // ── Trigger / retry generation ──────────────────────────────
    public function regenerate(Request $request, string $uuid): JsonResponse
    {
        $req = $this->findOwn($request, $uuid);
        if (!$req) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        if ($req->rewrite_mode !== 'ai') {
            return response()->json(['success' => false, 'message' => 'Not an AI rewrite request.'], 422);
        }

        if (!in_array($req->status, ['paid_ai', 'generating', 'generated', 'failed'], true)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot regenerate in status '{$req->status}'.",
            ], 422);
        }

        // Only increment regeneration_count when the user is retrying
        // an already-generated CV, not when the system failed and they're retrying
        if ($req->status === 'generated' && !empty($req->rewrite_generated_at)) {
            if ($req->rewrite_regeneration_count >= $req->rewrite_max_regenerations) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have used all ' . $req->rewrite_max_regenerations . ' regenerations.',
                ], 422);
            }
            $req->increment('rewrite_regeneration_count');
            $req->update(['rewrite_is_user_edited' => false, 'rewrite_user_edited_at' => null]);
        }

        // Always reset state on retry
        $req->update([
            'status'                => 'paid_ai',
            'rewrite_error_message' => null,
            'rewrite_content'       => null,
            'rewrite_generated_at'  => null,
        ]);

        GenerateCvRewriteJob::dispatch($req->id)->onQueue('letters');

        return response()->json([
            'success' => true,
            'message' => 'Generating your rewritten CV…',
        ]);
    }

    // ── Save edits (TinyMCE) ────────────────────────────────────
    public function saveEdited(Request $request, string $uuid): JsonResponse
    {
        $req = $this->findOwn($request, $uuid);
        if (!$req) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        if ($req->status !== 'generated') {
            return response()->json(['success' => false, 'message' => 'CV not ready.'], 422);
        }

        $request->validate(['content' => 'required|string|min:100']);

        $html = $request->input('content');

        $req->update([
            'rewrite_content'         => ['html' => $html],
            'rewrite_is_user_edited'  => true,
            'rewrite_user_edited_at'  => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Changes saved.']);
    }

    /**
     * Fallback when AI generation keeps failing: promote the last saved
     * AI original (rewrite_content_original) to the current content,
     * mark the request as generated, and let the user edit / download.
     *
     * Allowed when:
     *   - rewrite_mode = 'ai'
     *   - status = 'failed', OR status = 'generated' with empty content (corrupt)
     *   - rewrite_content_original is not empty
     */
    public function promoteOriginal(Request $request, string $uuid): JsonResponse
    {
        $req = $this->findOwn($request, $uuid);
        if (!$req) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        if ($req->rewrite_mode !== 'ai') {
            return response()->json([
                'success' => false,
                'message' => 'Not an AI rewrite request.',
            ], 422);
        }

        $isFailed     = $req->status === 'failed';
        $isCorruptGen = $req->status === 'generated' && empty($req->rewrite_content);

        if (!$isFailed && !$isCorruptGen) {
            return response()->json([
                'success' => false,
                'message' => "Cannot promote original from status '{$req->status}'.",
            ], 422);
        }

        $original = $req->rewrite_content_original['html'] ?? null;
        if (empty($original)) {
            return response()->json([
                'success' => false,
                'message' => 'No original content is available to restore.',
            ], 422);
        }

        $req->update([
            'rewrite_content'        => ['html' => $original],
            'status'                 => 'generated',
            'rewrite_generated_at'   => now(),
            'rewrite_error_message'  => null,
            'rewrite_is_user_edited' => true,
            'rewrite_user_edited_at' => now(),
        ]);

        \Log::info('CV rewrite: promoted original as fallback', [
            'uuid'  => $req->uuid,
            'user'  => $req->user_id,
            'words' => str_word_count(strip_tags($original)),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Restored the last saved version. You can now edit it.',
            'data'    => [
                'status'                 => $req->status,
                'rewrite_is_ready'       => $req->rewrite_is_ready,
                'rewrite_is_user_edited' => $req->rewrite_is_user_edited,
            ],
        ]);
    }

    // ── Reset to AI original ───────────────────────────────────
    public function resetEdited(Request $request, string $uuid): JsonResponse
    {
        $req = $this->findOwn($request, $uuid);
        if (!$req) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        if (empty($req->rewrite_content_original)) {
            return response()->json(['success' => false, 'message' => 'No original to reset to.'], 422);
        }

        $req->update([
            'rewrite_content'        => $req->rewrite_content_original,
            'rewrite_is_user_edited' => false,
            'rewrite_user_edited_at' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Restored the AI version.']);
    }

    // ── Download PDF ───────────────────────────────────────────
    public function download(Request $request, string $uuid)
    {
        $user = $request->user();
        $req  = CvReviewRequest::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($req->rewrite_mode !== 'ai' || empty($req->rewrite_content['html'])) {
            abort(404, 'CV not ready.');
        }

        // Clean the saved HTML again, so the PDF matches the sanitized preview
        $html = CvHtmlSanitizer::clean($req->rewrite_content['html']);

        if ($html === '') {
            abort(404, 'CV not ready.');
        }

        $pdf = Pdf::loadView('pdf.cv-rewrite', [
            'title'   => $req->target_job_title ?: 'Curriculum Vitae',
            'content' => $html,
        ])->setPaper('a4');

        $name  = Str::slug($user->name ?: 'candidate');
        $title = Str::slug($req->target_job_title ?: 'cv');

        return $pdf->download("{$name}_{$title}_cv.pdf");
    }

    // ── Helper ─────────────────────────────────────────────────
    protected function findOwn(Request $request, string $uuid): ?CvReviewRequest
    {
        return CvReviewRequest::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->first();
    }
}