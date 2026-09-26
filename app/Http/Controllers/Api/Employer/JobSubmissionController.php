<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\EmployerProfile;
use App\Models\Service\JobSubmission;
use App\Models\Service\Service;
use App\Services\Payment\PricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JobSubmissionController extends Controller
{
    /**
     * Services that represent job posting packages.
     * Only these can be submitted through this controller.
     */
    private const JOB_PACKAGE_KEYS = [
        'job_post_free',
        'job_post_standard',
        'job_post_popular',
        'job_post_priority',
        'job_post_enterprise',
    ];

    public function __construct(protected PricingService $pricing) {}

    // =================================================================
    // PACKAGES — returns the picker list with prices for the employer
    // =================================================================
    public function packages(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = EmployerProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Employer profile not found.'], 404);
        }

        $countryCode = strtoupper($profile->country_code ?? 'XX');

        $services = Service::whereIn('key', self::JOB_PACKAGE_KEYS)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $packages = [];

        foreach ($services as $service) {
            $price = $this->pricing->priceFor($service->key, $countryCode);

            if (!$price) continue;   // no price for this region — skip

            $packages[] = [
                'key'          => $service->key,
                'name'         => $service->name,
                'description'  => $service->description,
                'meta'         => $service->meta ?? [],
                'price' => [
                    'amount_cents'  => $price['amount_cents'],
                    'currency_code' => $price['currency_code'],
                    'formatted'     => $price['formatted'],
                    'is_free'       => $price['amount_cents'] === 0,
                ],
            ];
        }

        return response()->json([
            'success'      => true,
            'country_code' => $countryCode,
            'packages'     => $packages,
        ]);
    }

    // =================================================================
    // INDEX — employer's own submissions
    // =================================================================
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->get('status', '');

        $query = JobSubmission::where('user_id', $user->id)
            ->with(['service', 'currency', 'company', 'jobPost'])  // ← must include 'jobPost'
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        $submissions = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => collect($submissions->items())->map(fn($s) => $this->formatSubmission($s)),
            'meta'    => [
                'total'        => $submissions->total(),
                'current_page' => $submissions->currentPage(),
                'last_page'    => $submissions->lastPage(),
            ],
        ]);
    }

    public function counts(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $counts = JobSubmission::where('user_id', $userId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'success' => true,
            'counts'  => array_merge([
                'all'             => array_sum($counts),
                'draft'           => 0,
                'pending_payment' => 0,
                'pending_review'  => 0,
                'published'       => 0,
                'rejected'        => 0,
                'cancelled'       => 0,
            ], $counts),
        ]);
    }

    // =================================================================
    // SHOW — one submission
    // =================================================================
    public function show(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        $submission = JobSubmission::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->with(['service', 'currency', 'company'])
            ->firstOrFail();

        return response()->json([
            'success'    => true,
            'submission' => $this->formatSubmission($submission),
        ]);
    }

    // =================================================================
    // STORE — create a new submission (draft or straight to pending)
    // =================================================================
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = EmployerProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Employer profile not found.'], 404);
        }

        if (!$profile->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Complete your company profile before posting jobs.',
            ], 422);
        }

        $validated = $request->validate([
            'service_key'    => ['required', 'string', \Illuminate\Validation\Rule::in(self::JOB_PACKAGE_KEYS)],
            'job_title'      => 'required|string|max:255',
            'content'        => 'required|string|min:30|max:50000',
            'target_country' => 'nullable|string|size:2',
        ]);

        // Resolve price
        $countryCode = strtoupper(
            $validated['target_country']
            ?? $profile->country_code
            ?? 'XX'
        );

        $price = $this->pricing->priceFor($validated['service_key'], $countryCode);

        if (!$price) {
            return response()->json([
                'success' => false,
                'message' => 'Selected package is not available in your region.',
            ], 422);
        }

        $service = Service::where('key', $validated['service_key'])->firstOrFail();

        $currency = Currency::where('code', $price['currency_code'])->first();
        if (!$currency) {
            return response()->json([
                'success' => false,
                'message' => "Currency '{$price['currency_code']}' not configured.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            $isFree = $price['amount_cents'] === 0;

            $submission = JobSubmission::create([
                'user_id'          => $user->id,
                'company_id'       => $profile->company_id,
                'job_title'        => $validated['job_title'],
                'content'          => $validated['content'],
                'target_country'   => $countryCode,

                'service_key'      => $validated['service_key'],
                'amount_cents'     => $price['amount_cents'],
                'currency_id'      => $currency->id,
                'package_meta'     => $service->meta,   // snapshot features at submit time

                // Free packages skip payment entirely
                'payment_status'   => $isFree ? 'not_required' : 'pending',

                // Free → straight to review. Paid → wait for payment first.
                'status'           => $isFree ? 'pending_review' : 'pending_payment',
            ]);

            DB::commit();

            return response()->json([
                'success'    => true,
                'message'    => $isFree
                    ? 'Job submitted for review. Our team will publish it shortly.'
                    : 'Job saved. Please complete payment to proceed.',
                'submission' => $this->formatSubmission($submission->fresh(['service', 'currency', 'company'])),
                'next_step'  => $isFree ? 'awaiting_review' : 'payment',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Job submission failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =================================================================
    // PAYMENT — record a payment reference
    // =================================================================
    public function recordPayment(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        $submission = JobSubmission::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($submission->status !== 'pending_payment') {
            return response()->json([
                'success' => false,
                'message' => "Payment is not expected for status '{$submission->status}'.",
            ], 422);
        }

        $validated = $request->validate([
            'payment_reference' => 'required|string|max:255',
        ]);

        $submission->update([
            'payment_reference' => $validated['payment_reference'],
            'payment_status'    => 'paid',              // employer declares paid; admin verifies
            'paid_at'           => now(),
            'status'            => 'pending_review',
        ]);

        return response()->json([
            'success'    => true,
            'message'    => 'Payment recorded. Awaiting admin verification and review.',
            'submission' => $this->formatSubmission($submission->fresh(['service', 'currency', 'company'])),
        ]);
    }

    // =================================================================
    // CANCEL — employer cancels a pending submission
    // =================================================================
    public function cancel(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        $submission = JobSubmission::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!in_array($submission->status, ['draft', 'pending_payment', 'rejected'], true)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot cancel a submission in status '{$submission->status}'.",
            ], 422);
        }

        $submission->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Submission cancelled.',
        ]);
    }

    // =================================================================
    // DESTROY — delete a submission
    // =================================================================
    public function destroy(Request $request, $uuid): JsonResponse
    {
        $user = $request->user();

        $submission = JobSubmission::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!$submission->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'This submission can no longer be deleted.',
            ], 422);
        }

        $submission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Submission deleted.',
        ]);
    }

    // =================================================================
    // FORMATTER
    // =================================================================
    private function formatSubmission(JobSubmission $s): array
    {
        return [
            'uuid'                => $s->uuid,
            'job_title'           => $s->job_title,
            'content_preview'     => \Illuminate\Support\Str::limit(strip_tags($s->content), 150),
            'content'             => $s->content,
            'target_country'      => $s->target_country,

            'service_key'         => $s->service_key,
            'service_name'        => $s->service_name,
            'package_meta'        => $s->package_meta,          // ← #1 REQUIRED

            'amount_cents'        => $s->amount_cents,
            'currency_code'       => $s->currency->code ?? null,
            'formatted_amount'    => $s->formatted_amount,
            'is_free'             => $s->is_free,

            'payment_status'      => $s->payment_status,
            'payment_badge'       => $s->payment_badge,
            'payment_reference'   => $s->payment_reference,
            'paid_at'             => $s->paid_at?->toISOString(),

            'status'              => $s->status,                // ← #2 REQUIRED
            'status_badge'        => $s->status_badge,
            'rejection_reason'    => $s->rejection_reason,

            'company_name'        => $s->company->name ?? null,
            'company_logo'        => $s->company->logo_url ?? null,

            'job_post_id'         => $s->job_post_id,           // ← #3 REQUIRED
            'job_post_slug'       => $s->jobPost?->slug,        // ← #4 REQUIRED

            'can_edit'            => $s->canBeEdited(),
            'can_delete'          => $s->canBeDeleted(),

            'created_at'          => $s->created_at?->toISOString(),
            'updated_at'          => $s->updated_at?->toISOString(),
        ];
    }


}