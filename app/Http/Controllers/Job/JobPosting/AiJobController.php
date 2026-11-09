<?php

namespace App\Http\Controllers\Job\JobPosting;

use App\Http\Controllers\Controller;
use App\Services\Jobs\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiJobController extends Controller
{
    public function __construct(protected AiService $aiService) {}

    public function getModels(): JsonResponse
    {
        return $this->success([
            'data'    => $this->aiService->getAvailableModels(),
            'default' => config('ai.default', 'gemini'),
        ]);
    }

    public function extractJobData(Request $request): JsonResponse
    {
        if ($bad = $this->invalid($request, [
            'model'       => 'nullable|string',
            'content'     => 'required|string',
            'source_type' => 'required|in:text,url',
            'country'     => 'nullable|string|size:2',
            'position'    => 'nullable|string|max:150',
        ])) {
            return $bad;
        }

        $model = $request->input('model') ?: config('ai.default');

        try {
            $result = $this->aiService->extractJobData(
                $request->input('content'),
                $request->input('source_type'),
                $model,
                $request->input('country'),
                $request->input('position')
            );

            return $this->success(['data' => $result]);
        } catch (\Throwable $e) {
            return $this->failure($e, 'extract_job', $model);
        }
    }

    public function extractFromImage(Request $request): JsonResponse
    {
        if ($bad = $this->invalid($request, [
            'model'        => 'nullable|string',
            'image_base64' => 'required|string',
            'country'      => 'nullable|string|size:2',
        ])) {
            return $bad;
        }

        $model = $request->input('model') ?: config('ai.default');

        try {
            $result = $this->aiService->extractFromImage(
                $request->input('image_base64'),
                $model,
                $request->input('country')
            );

            return $this->success(['data' => $result]);
        } catch (\Throwable $e) {
            return $this->failure($e, 'extract_image', $model);
        }
    }

    public function enhanceField(Request $request): JsonResponse
    {
        if ($bad = $this->invalid($request, [
            'model'       => 'nullable|string',
            'field_name'  => 'required|string',
            'content'     => 'required|string',
            'instruction' => 'required|string',
        ])) {
            return $bad;
        }

        $model = $request->input('model') ?: config('ai.default');

        try {
            $enhanced = $this->aiService->enhanceField(
                $request->input('field_name'),
                $request->input('content'),
                $request->input('instruction'),
                $model
            );

            return $this->success(['enhanced' => $enhanced]);
        } catch (\Throwable $e) {
            return $this->failure($e, 'enhance_field', $model);
        }
    }

    public function generateFromTitle(Request $request): JsonResponse
    {
        if ($bad = $this->invalid($request, [
            'model'   => 'nullable|string',
            'title'   => 'required|string',
            'company' => 'nullable|string',
            'country' => 'nullable|string|size:2',
        ])) {
            return $bad;
        }

        $model = $request->input('model') ?: config('ai.default');

        try {
            $result = $this->aiService->generateFromTitle(
                $request->input('title'),
                $request->input('company'),
                $request->input('country'),
                $model
            );

            return $this->success(['data' => $result]);
        } catch (\Throwable $e) {
            return $this->failure($e, 'generate_from_title', $model);
        }
    }

    /* ==================================================================
     | Response helpers
     * ================================================================== */

    private function success(array $payload): JsonResponse
    {
        return response()->json(['success' => true] + $payload);
    }

    /**
     * Validation failures are always returned as JSON, never as a redirect or HTML page.
     */
    private function invalid(Request $request, array $rules): ?JsonResponse
    {
        $validator = Validator::make($request->all(), $rules);

        if (!$validator->fails()) {
            return null;
        }

        return response()->json([
            'success'   => false,
            'type'      => 'validation',
            'message'   => 'Some details are missing or invalid. Please check the form and try again.',
            'fields'    => $validator->errors(),
            'errors'    => [],
            'retryable' => false,
        ], 422);
    }

    /**
     * Every failure goes through here. The response always carries:
     *  - type      : a machine-readable category the front end can branch on
     *  - message   : a plain-English explanation for the user
     *  - errors    : per-model reasons, e.g. ["Gemini" => "quota exceeded"]
     *  - retryable : whether trying again might work
     */
    private function failure(\Throwable $e, string $context, ?string $model): JsonResponse
    {
        $raw    = $e->getMessage();
        $errors = $this->parseModelErrors($raw);
        $type   = $this->classify($raw, $errors);

        Log::warning('AI request failed', [
            'context' => $context,
            'model'   => $model,
            'type'    => $type,
            'error'   => mb_substr($raw, 0, 500),
        ]);

        $payload = [
            'success'   => false,
            'type'      => $type,
            'message'   => $this->friendlyMessage($type),
            'errors'    => $errors,
            'retryable' => in_array($type, ['timeout', 'quota', 'model_unavailable', 'incomplete_reply', 'unknown'], true),
        ];

        // Show the raw technical detail only on a development server
        if (config('app.debug')) {
            $payload['debug'] = mb_substr($raw, 0, 1000);
        }

        return response()->json($payload, 422);
    }

    /* ==================================================================
     | Error interpretation
     * ================================================================== */

    /**
     * Turns the exception text into per-model reasons. Always returns at least one entry.
     */
    private function parseModelErrors(string $message): array
    {
        $decoded = json_decode($message, true);

        if (is_array($decoded) && ($decoded['type'] ?? null) === 'model_errors' && !empty($decoded['errors'])) {
            return array_map(fn ($e) => $this->cleanError((string) $e), $decoded['errors']);
        }

        // A single-model error, e.g. "API error (gemini): ..."
        $model = 'AI Service';
        if (preg_match('/API error \((\w+)\)/i', $message, $m)) {
            $model = ucfirst(strtolower($m[1]));
        }

        return [$model => $this->cleanError($message)];
    }

    private function cleanError(string $text): string
    {
        $text = preg_replace('/^API error \([^)]+\):\s*/', '', $text);
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return $text !== '' ? mb_substr($text, 0, 300) : 'No details were returned.';
    }

    /**
     * Works out what kind of failure this was, so the page can show the right advice.
     * Timeouts are checked first, so a URL that timed out is not reported as "no posting".
     */
    private function classify(string $raw, array $errors): string
    {
        $text = strtolower($raw . ' ' . implode(' ', $errors));

        if (preg_match('/timed out|timeout/', $text)) {
            return 'timeout';
        }
        if (preg_match('/status code 429|quota|resource_exhausted|rate limit|too many requests/', $text)) {
            return 'quota';
        }

        if (!empty($errors)) {
            $notEnabled = count(array_filter($errors, fn ($e) => str_contains(strtolower($e), 'not enabled')));
            if ($notEnabled === count($errors)) {
                return 'not_configured';
            }
        }

        if (preg_match('/no valid job posting|no job posting could be read|does not exist|not a real job/', $text)) {
            return 'no_posting';
        }
        if (preg_match('/cut off|cut short|not valid json|could not be parsed|too short|is missing/', $text)) {
            return 'incomplete_reply';
        }
        if (!empty($errors)) {
            return 'model_unavailable';
        }

        return 'unknown';
    }

    private function friendlyMessage(string $type): string
    {
        return match ($type) {
            'no_posting'       => 'No job advert was found in the text or page you provided. Paste the full advert, or check that the link opens in a browser.',
            'timeout'          => 'The AI took too long to reply. Very long adverts can take a few minutes, so please try again.',
            'quota'            => 'The AI providers have reached their usage limit for now. Please try again in a few minutes.',
            'not_configured'   => 'No AI model is set up on the server. Check the API keys in the settings.',
            'incomplete_reply' => 'The AI reply was incomplete, so nothing was saved. Please try again.',
            'model_unavailable'=> 'The AI models could not complete this request. See the details for each model below.',
            default            => 'Extraction failed for an unexpected reason. Please try again.',
        };
    }
}