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

class CvReviewRequestController extends Controller
{
    private const MAX_ACTIVE_REVIEWS = 3;

    public function __construct(
        protected PricingService $pricing,
        protected AiService $ai,
        protected CvTextExtractionService $textExtractor,
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

    // =================================================================
    // PAYMENT — init
    // =================================================================
    public function initPayment(Request $request, $uuid): JsonResponse
    {
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

        $paymentReference = 'CVR-' . strtoupper(\Illuminate\Support\Str::random(10));

        return response()->json([
            'success'           => true,
            'payment_reference' => $paymentReference,
            'amount_cents'      => $cvRequest->amount_cents,
            'currency_code'     => $cvRequest->currency->code,
            'formatted'         => $cvRequest->currency->formatAmount($cvRequest->amount_cents),
            'callback_url'      => route('api.cv-review.callback'),
        ]);
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

    private function formatRequest(CvReviewRequest $r): array
    {
        return [
            'uuid'                  => $r->uuid,
            'status'                => $r->status,
            'status_label'          => ucwords(str_replace('_', ' ', $r->status)),
            'service_name'          => $r->service->name ?? '—',
            'target_job_title'      => $r->target_job_title,
            'original_cv_name'      => $r->original_cv_name,
            'original_cv_url'       => $r->cv_download_url,     // ⬅️ use the model accessor
            'delivered_cv_url'      => $r->delivered_cv_url,    // ⬅️ use the model accessor
            'amount_cents'          => $r->amount_cents,
            'currency_code'         => $r->currency->code ?? '—',
            'formatted_amount'      => $r->currency ? $r->currency->formatAmount($r->amount_cents) : '—',
            'country_code'          => $r->country_code,
            'ai_gap_review'         => $r->ai_gap_review,
            'seeker_gap_answers'    => $r->seeker_gap_answers,
            'payment_reference'     => $r->payment_reference,
            'sla_due_at'            => $r->sla_due_at?->toISOString(),
            'delivered_at'          => $r->delivered_at?->toISOString(),
            'created_at'            => $r->created_at?->toISOString(),
            'allowed_next_statuses' => $r->allowedNextStatuses(),
        ];
    }
}