<?php

namespace App\Services\Jobs;

use Illuminate\Support\Facades\{ Http, Log, Cache };
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Job\{Industry, JobCategory, JobLocation, JobType, ExperienceLevel, EducationLevel, SalaryRange};

class AiService
{



    protected array $config;
    protected array $fallbackModels;
    protected string $defaultModel;
    protected int $timeout;
    protected int $retryAttempts;
    protected int $retryDelay;
    protected array $countrySettings;
    



    public function __construct()
    {
        $this->config = config('ai.models', []);
        $this->fallbackModels = config('ai.fallback_models', ['openai', 'claude', 'cohere']);
        $this->defaultModel = config('ai.default', 'gemini');
        $this->timeout = config('ai.timeout', 90);
        $this->retryAttempts = config('ai.retries.attempts', 2);
        $this->retryDelay = config('ai.retries.delay', 200);
        $this->countrySettings = config('ai.country_settings', []);
    }




    public function getAvailableModels(): array
    {
        $models = [];
        foreach ($this->config as $key => $model) {
            if ($this->isModelEnabled($key)) {
                $models[$key] = [
                    'name' => $model['name'] ?? ucfirst($key),
                    'icon' => $model['icon'] ?? 'ti-robot',
                    'color' => $model['color'] ?? '#6c757d',
                    'supports' => $model['supports'] ?? ['text'],
                    'is_default' => $key === $this->defaultModel,
                ];
            }
        }
        return $models;
    }

    public function isModelEnabled(string $model): bool
    {
        $config = $this->config[$model] ?? null;
        if (!$config || empty($config['api_key'])) {
            return false;
        }

        // A key like "sk-proj-xxxx" or "your-api-key" is a placeholder, not a real key.
        $key = (string) $config['api_key'];
        if (stripos($key, 'xxxx') !== false || stripos($key, 'your-') !== false || strlen($key) < 20) {
            return false;
        }

        return true;
    }

    /**
     * A model that just answered 401/403 (bad key) or 429 (quota used up) will answer
     * the same for the next few minutes. Skipping it saves a wasted round trip on every
     * request instead of trying it first every time.
     */
    protected function coolDownSeconds(string $errorMessage): int
    {
        $m = strtolower($errorMessage);

        if (preg_match('/status code (401|403)|invalid[\s_-]*(x-)?api[\s_-]*key|incorrect api key|authentication_error|unauthori[sz]ed/', $m)) {
            return 600;
        }
        if (preg_match('/status code 429|quota|rate[\s_-]*limit|resource_exhausted|too many requests/', $m)) {
            return 300;
        }
        if (preg_match('/status code 400.*api key|api key.*(invalid|incorrect)/', $m)) {
            return 600;
        }

        return 0;
    }

    protected function isCoolingDown(string $model): bool
    {
        try {
            return Cache::has("ai_model_cooldown:{$model}");
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function startCoolDown(string $model, string $errorMessage): void
    {
        $seconds = $this->coolDownSeconds($errorMessage);
        if ($seconds <= 0) {
            return;
        }
        try {
            Cache::put("ai_model_cooldown:{$model}", true, $seconds);
        } catch (\Throwable $e) {
            // cooldown is an optimisation only
        }
    }

    public function getModelConfig(string $model): ?array
    {
        return $this->config[$model] ?? null;
    }



    /**
     * Extract the text content from the API response.
     *
     * Each provider returns text in a different place; this method normalises
     * them all into one string. Called by callAiApi() immediately after the
     * response is validated.
     */
    protected function extractResponseText(string $model, array $data): string
    {
        $text = match ($model) {
            'openai', 'grok', 'mistral' => $data['choices'][0]['message']['content'] ?? '',
            'claude'                    => $data['content'][0]['text']              ?? '',
            'gemini'                    => $data['candidates'][0]['content']['parts'][0]['text'] ?? '',
            'cohere'                    => $data['message']['content'][0]['text']
                                        ?? $data['text']
                                        ?? $data['response']
                                        ?? $data['generation']
                                        ?? '',
            default                     => json_encode($data),
        };

        if (empty($text)) {
            \Illuminate\Support\Facades\Log::warning("Empty response from AI model '{$model}'", [
                'full_response' => json_encode($data),
            ]);
        }

        return $text;
    }

    

    protected function countWords(string $text): int
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));

        return $plain === '' ? 0 : count(explode(' ', $plain));
    }


    private function getNestedValue(array $data, string $path): mixed
    {
        $keys    = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (!is_array($current) || !isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }




    /**
     * Call AI API with fallback support.
     *
     * When $expectJson is true, a malformed/empty response from one model is
     * treated as that model's failure and the NEXT model in the fallback
     * chain is tried automatically - previously a parse failure just quietly
     * returned an empty array disguised as a success.
     */
    protected function callWithFallback(string $primaryModel, string $prompt, callable $callFn, bool $expectJson = true): array|string
    {
        $errors = [];

        @set_time_limit(300);

        $deadline = microtime(true) + self::CHAIN_DEADLINE_SECONDS;

        $models = array_values(array_unique(array_merge(
            [$primaryModel],
            ['gemini', 'claude', 'openai'],
            ['grok', 'mistral'],   // only tried if enabled
            ['cohere'],            // guaranteed last resort
        )));

        foreach ($models as $model) {
            if (!$this->isModelEnabled($model)) {
                $errors[$model] = 'not enabled (missing or placeholder API key)';
                continue;
            }
            if ($this->isCoolingDown($model)) {
                $errors[$model] = 'skipped for a few minutes after its last quota/API-key error';
                continue;
            }

            $remaining = (int) floor($deadline - microtime(true));
            if ($remaining < self::MIN_ATTEMPT_SECONDS) {
                $errors[$model] = 'skipped: the time limit for this request was reached';
                continue;
            }
            $this->timeout = min(self::PER_MODEL_TIMEOUT_SECONDS, $remaining);

            $t0 = microtime(true);

            try {
                $config = $this->getModelConfig($model);
                $raw    = $callFn($model, $config['api_key']);

                Log::info("AI call '{$model}' succeeded in " . round(microtime(true) - $t0, 1) . 's');

                if (!$expectJson) {
                    return is_array($raw) ? $this->extractTextFromResult($raw) : (string) $raw;
                }

                return $this->parseJsonOrFail($raw, $model);
            } catch (\Throwable $e) {
                $errorMessage   = $e->getMessage();
                $cleanError     = preg_replace('/^API error \([^)]+\):\s*/', '', $errorMessage);
                $errors[$model] = $cleanError ?: $errorMessage;
                $this->startCoolDown($model, $errorMessage);

                Log::warning("AI call failed for model '{$model}' after " . round(microtime(true) - $t0, 1) . 's', [
                    'error' => mb_substr($errorMessage, 0, 300),
                ]);
            }
        }

        throw new \Exception(json_encode([
            'type'   => 'model_errors',
            'errors' => $errors,
        ]));
    }

    /**
     * JSON mode makes models return real JSON arrays where the prompt asked for HTML
     * strings (skills as ["A","B"], responsibilities as ["..","..."]). Later code calls
     * trim()/strip_tags() on these, and handing them an array is a fatal TypeError, which
     * PHP reports as an HTML 500 page. The browser then fails with
     * "JSON.parse: unexpected character at line 1 column 1". Turn every field back into
     * the string shape the rest of the service expects.
     */
    protected function coerceModelOutput(array $d): array
    {
        $flat = function ($v) use (&$flat): array {
            if (is_array($v)) {
                $out = [];
                foreach ($v as $item) {
                    $out = array_merge($out, $flat($item));
                }
                return $out;
            }
            if ($v === null || is_bool($v)) {
                return [];
            }
            $t = trim((string) $v);
            return $t === '' ? [] : [$t];
        };

        foreach (['responsibilities', 'qualifications'] as $f) {
            if (isset($d[$f]) && is_array($d[$f])) {
                $items = $flat($d[$f]);
                $d[$f] = $items ? '<ul>' . implode('', array_map(fn($i) => "<li>{$i}</li>", $items)) . '</ul>' : null;
            }
        }
        foreach (['job_description', 'application_procedure'] as $f) {
            if (isset($d[$f]) && is_array($d[$f])) {
                $items = $flat($d[$f]);
                $d[$f] = $items ? '<p>' . implode('</p><p>', $items) . '</p>' : null;
            }
        }
        if (isset($d['other_positions'])) {
            $d['other_positions'] = is_array($d['other_positions']) ? $flat($d['other_positions']) : [];
        }
        foreach (['skills', 'keywords', 'email', 'telephone'] as $f) {
            if (isset($d[$f]) && is_array($d[$f])) {
                $d[$f] = implode(', ', $flat($d[$f])) ?: null;
            }
        }
        foreach ([
            'job_title', 'company_name', 'duty_station', 'meta_description', 'application_link', 'deadline',
            'location_type', 'employment_type', 'salary_amount', 'payment_period', 'currency', 'work_hours',
            'experience_level_name', 'education_level_name', 'industry_name', 'category_name',
            'job_type_name', 'job_location_name', 'salary_range_name', 'country_code',
        ] as $f) {
            if (isset($d[$f]) && is_array($d[$f])) {
                $d[$f] = implode(' ', $flat($d[$f])) ?: null;
            } elseif (isset($d[$f]) && !is_string($d[$f]) && !is_null($d[$f])) {
                $d[$f] = (string) $d[$f];
            }
        }

        // application_links may arrive as an array, a string, or the old single "application_link"
        $links = [];
        foreach (['application_links', 'application_link'] as $f) {
            if (isset($d[$f])) {
                $links = array_merge($links, is_array($d[$f]) ? $flat($d[$f]) : [(string) $d[$f]]);
            }
        }
        $d['application_links'] = $links;
        unset($d['application_link']);

        return $d;
    }

    /**
     * Strictly parse a model's output into a non-empty array, or throw.
     * Throwing here (rather than returning []) is what lets callWithFallback
     * fall through to the next model instead of silently "succeeding" with
     * nothing to show on the form.
     */
    protected function parseJsonOrFail(mixed $result, string $model): array
    {
        if (is_array($result) && !empty($result)) {
            return $result;
        }

        if (is_string($result)) {
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $result);
            $clean = preg_replace('/\s*```\s*$/i', '', $clean);
            $clean = trim($clean);

            $decoded = $this->decodeJsonLenient($clean);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }

            // Say WHY: a reply cut off by the output limit has unbalanced braces
            $opens  = substr_count($clean, '{') + substr_count($clean, '[');
            $closes = substr_count($clean, '}') + substr_count($clean, ']');
            $why = $opens > $closes
                ? 'the reply was cut off (output limit reached), ' . mb_strlen($clean) . ' characters received'
                : 'the reply is not valid JSON';
            throw new \Exception("Response could not be parsed as JSON: {$why}. Start: "
                . substr($clean, 0, 200) . ' ... End: ' . substr($clean, -200));
        }

        throw new \Exception('Empty or invalid response.');
    }

    /**
     * Decode a model's JSON reply, tolerating the usual slips on long outputs:
     * chatter before/after the object, and raw line breaks or tabs inside string
     * values (which JSON forbids). A reply that is cut off is NOT repaired, so a
     * truncated posting can never be saved as if it were complete.
     */
    protected function decodeJsonLenient(string $text): ?array
    {
        $data = json_decode($text, true);
        if (is_array($data)) {
            return $data;
        }

        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $text = substr($text, $start, $end - $start + 1);

        $data = json_decode($text, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        if (is_array($data)) {
            return $data;
        }

        // Escape control characters that sit inside string values.
        $out = '';
        $inString = false;
        $escaped = false;
        for ($i = 0, $n = strlen($text); $i < $n; $i++) {
            $c = $text[$i];
            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($c === '\\') {
                    $escaped = true;
                } elseif ($c === '"') {
                    $inString = false;
                } elseif ($c === "\n") {
                    $c = '\\n';
                } elseif ($c === "\r") {
                    $c = '';
                } elseif ($c === "\t") {
                    $c = ' ';
                }
            } elseif ($c === '"') {
                $inString = true;
            }
            $out .= $c;
        }

        $data = json_decode($out, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

        return is_array($data) ? $data : null;
    }

    protected function callAiApi(string $model, string $apiKey, string $prompt, ?string $imageBase64 = null, bool $json = false): array|string
    {
        $config = $this->getModelConfig($model);
        if (!$config) {
            throw new \Exception("Model '{$model}' not configured.");
        }

        $endpoint  = $config['endpoint'];
        $modelName = $config['model'];
        $maxTokens = $config['max_tokens'] ?? 4096;

        $maxTokens = 8192;

        $headers = ['Content-Type' => 'application/json'];

        if (in_array($model, ['openai', 'grok', 'mistral', 'cohere'], true)) {
            $headers['Authorization'] = "Bearer {$apiKey}";
        } elseif ($model === 'claude') {
            $headers['x-api-key']         = $apiKey;
            $headers['anthropic-version'] = '2023-06-01';
        }

        $body = $this->buildRequestBody($model, $apiKey, $modelName, $maxTokens, $prompt, $imageBase64, $json);

        if ($model === 'gemini') {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";
        }

        // ─────────────────────────────────────────────────────────────
        // DEBUG — log the exact request before it goes out
        // ─────────────────────────────────────────────────────────────
        if ($model === 'cohere') {
            $maskedAuth = isset($headers['Authorization'])
                ? 'Bearer ' . substr($apiKey, 0, 6) . '...' . substr($apiKey, -4) . ' (' . strlen($apiKey) . ' chars)'
                : '(missing)';

            $bodyPreview = [
                'model'               => $body['model']       ?? null,
                'max_tokens'          => $body['max_tokens']  ?? null,
                'has_messages'        => isset($body['messages']),
                'messages_count'      => isset($body['messages']) ? count((array) $body['messages']) : 0,
                'has_prompt'          => isset($body['prompt']),
                'has_response_format' => isset($body['response_format']),
                'body_keys'           => array_keys($body),
            ];

            \Illuminate\Support\Facades\Log::info('COHERE REQUEST', [
                'endpoint'    => $endpoint,
                'auth_header' => $maskedAuth,
                'headers'     => array_merge($headers, array_intersect_key(['Authorization' => '[masked]', 'x-api-key' => '[masked]'], $headers)),
                'body'        => $bodyPreview,
                'prompt_chars'=> strlen($prompt),
                'prompt_head' => substr($prompt, 0, 200),
                'prompt_tail' => substr($prompt, -120),
            ]);
        }

        $response = Http::timeout($this->timeout)
            ->retry(3, 2000, function ($exception) {
                if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                    return !str_contains(strtolower($exception->getMessage()), 'timed out');
                }
                if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                    return in_array($exception->response->status(), [500, 502, 503, 504], true);
                }
                return false;
            })
            ->withHeaders($headers)
            ->post($endpoint, $body);

        // ─────────────────────────────────────────────────────────────
        // DEBUG — log Cohere's exact response when it fails
        // ─────────────────────────────────────────────────────────────
        if ($model === 'cohere' && !$response->successful()) {
            \Illuminate\Support\Facades\Log::warning('COHERE RESPONSE (failed)', [
                'status'      => $response->status(),
                'reason'      => $response->reason(),
                'body_500'    => substr($response->body(), 0, 500),
                'headers_sel' => [
                    'content-type' => $response->header('Content-Type'),
                    'x-request-id' => $response->header('X-Request-Id'),
                    'www-authenticate' => $response->header('WWW-Authenticate'),
                ],
            ]);
        }

        if (!$response->successful()) {
            throw $this->buildApiException($model, $response);
        }

        // Also log success, so you know when it works (helps spot flakiness)
        if ($model === 'cohere') {
            \Illuminate\Support\Facades\Log::info('COHERE RESPONSE (success)', [
                'status'     => $response->status(),
                'body_chars' => strlen($response->body()),
            ]);
        }

        $json = $response->json();
        $this->assertNotTruncated($model, is_array($json) ? $json : []);

        return $this->extractResponseText($model, $json);
    }

    /**
     * A queue worker is one long-lived process. set_time_limit(300) would keep counting after
     * the job ends and kill the idle worker, so in the console the limit is removed instead
     * (the job's own $timeout still applies). In a web request the limit is raised as before.
     */
    protected function allowLongRun(int $seconds): void
    {
        @set_time_limit(app()->runningInConsole() ? 0 : $seconds);
    }

    /**
     * A reply that stopped at the output limit is incomplete, even if it parses.
     */
    protected function assertNotTruncated(string $model, array $data): void
    {
        $reason = match ($model) {
            'gemini'                    => $data['candidates'][0]['finishReason'] ?? null,
            'cohere'                    => $data['finish_reason'] ?? null,
            'openai', 'grok', 'mistral' => $data['choices'][0]['finish_reason'] ?? null,
            'claude'                    => $data['stop_reason'] ?? null,
            default                     => null,
        };

        if (in_array(strtoupper((string) $reason), ['MAX_TOKENS', 'LENGTH'], true)) {
            throw new \Exception("Reply cut short by the output limit ({$model} stopped at {$reason}).");
        }
    }

    /**
     * Build the request body for each AI model
     */
    private function buildRequestBody(string $model, string $apiKey, string $modelName, int $maxTokens, string $prompt, ?string $imageBase64, bool $json = false): array
    {
        switch ($model) {
            case 'openai':
                return [
                    'model' => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $imageBase64
                                ? [
                                    ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$imageBase64}"]],
                                    ['type' => 'text', 'text' => $prompt],
                                ]
                                : $prompt
                        ]
                    ],
                ];

            case 'claude':
                $content = $imageBase64
                    ? [
                        ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $imageBase64]],
                        ['type' => 'text', 'text' => $prompt],
                    ]
                    : $prompt;

                return [
                    'model' => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages' => [['role' => 'user', 'content' => $content]],
                ];

            case 'gemini':
                $parts = $imageBase64
                    ? [
                        ['inline_data' => ['mime_type' => 'image/jpeg', 'data' => $imageBase64]],
                        ['text' => $prompt],
                    ]
                    : [['text' => $prompt]];

                $generationConfig = array_filter([
                    'maxOutputTokens'  => $maxTokens,
                    'responseMimeType' => $json ? 'application/json' : null,
                ]);

                if (str_contains($modelName, '2.5-flash')) {
                    $generationConfig['thinkingConfig'] = ['thinkingBudget' => 0];
                } elseif (str_contains($modelName, 'gemini-3')) {
                    // Gemini 3 uses levels instead of a token budget. Extraction needs no deep reasoning.
                    $generationConfig['thinkingConfig'] = ['thinkingLevel' => 'minimal'];
                }

                return [
                    'contents'         => [['parts' => $parts]],
                    'generationConfig' => $generationConfig,
                ];

            case 'grok':
            case 'cohere':
            case 'mistral':
                $body = [
                    'model' => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ];
                if ($json && $model === 'cohere') {
                    $body['response_format'] = ['type' => 'json_object']; // no code fences, no chatter
                }
                return $body;

            default:
                throw new \Exception("Unsupported model: {$model}");
        }
    }





    // CV Extraction

    


    /**
     * Order to try models in for CV extraction
     */
    protected array $cvFallbackOrder = ['cohere', 'gemini', 'openai', 'claude', 'grok', 'mistral'];


    /**
     * Extract structured CV data from raw resume text, trying each configured
     * AI model in order until one returns usable JSON.
     *
     * Returns empty data structure instead of throwing on failure.
     */
    public function extractCvData(string $resumeText): array
    {
        $prompt        = $this->buildCvExtractionPrompt($resumeText);
        $lastException = null;
        $models        = $this->getOrderedCvModels();

        foreach ($models as $model) {
            $apiKey = $this->getApiKeyFor($model);

            if (!$this->isModelUsable($model, $apiKey)) {
                continue;
            }

            try {
                $raw = $this->callAiApi($model, $apiKey, $prompt, null, true);

                if (!$raw) {
                    continue;
                }

                $data = $this->parseJsonResponse($raw);

                if ($data === null || empty($data)) {
                    Log::warning("{$model} returned unparseable response - trying next model");
                    continue;
                }

                // ────────────────────────────────────────────────────────
                // Honour the AI's validity verdict
                // ────────────────────────────────────────────────────────
                if (array_key_exists('_valid', $data)) {
                    if ($data['_valid'] === false) {
                        $reason = $data['_reason']
                            ?? 'This does not look like a CV. Please upload your CV as a PDF or Word document.';

                        Log::info("CV validation rejected by {$model}", ['reason' => $reason]);

                        // Throw a dedicated exception the controller can catch
                        throw new \App\Exceptions\NotACvException($reason);
                    }

                    // Strip the meta fields before returning
                    unset($data['_valid'], $data['_reason']);
                }

                if ($this->hasValidCvData($data)) {
                    return $data;
                }

                Log::warning("{$model} returned empty data - trying next model");

            } catch (\App\Exceptions\NotACvException $e) {
                // Non-CV verdict is authoritative — do NOT try other models.
                // Trying another model could produce a different answer, which
                // would confuse the caller.
                throw $e;

            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning("CV extraction failed with {$model}: " . $e->getMessage());
            }
        }

        Log::error('All AI models failed to extract CV data', [
            'last_error' => $lastException ? $lastException->getMessage() : 'Unknown error',
        ]);

        return $this->getEmptyCvData();
    }

    /**
     * Extract CV data from an uploaded file (PDF / DOC / DOCX).
     *
     * The file goes through CvTextExtractionService, then the extracted
     * text is passed into extractCvData() as if it had been pasted. Callers
     * get the same normalised array back, or NotACvException when the file
     * is not a CV.
     */
    public function extractCvDataFromFile(
        \Illuminate\Http\UploadedFile $file,
        ?CvTextExtractionService $textExtractor = null
    ): array {
        $textExtractor = $textExtractor ?? app(CvTextExtractionService::class);

        $text = $textExtractor->extract($file->getRealPath(), $file->getMimeType());

        if (trim($text) === '') {
            Log::warning('CV text extraction returned empty; sending to AI anyway', [
                'original_name' => $file->getClientOriginalName(),
                'mime'          => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);
            $text = "(No readable text could be extracted from this file. The user uploaded: {$file->getClientOriginalName()}.)";
        }

        return $this->extractCvData($text);
    }

    /**
     * Resolve whatever the AI returns for country (name, ISO code, phone
     * code, adjective, or "Kampala, Uganda") into a 2-letter ISO code that
     * matches `countries.code`.
     *
     * Priority:
     *   1. Already a 2-letter code that exists in the DB   → use as-is
     *   2. Exact match on Country.name                     → code
     *   3. Case-insensitive match on Country.name          → code
     *   4. Substring match ("Kampala, Uganda" → "Uganda")  → code
     *   5. Demonym ("Ugandan", "Kenyan", "British")        → code
     *   6. Phone code ("+256", "256")                      → code
     *   7. Fallback                                        → the passed default (or null)
     *
     * Cached per request so 200 extracted CVs in one import don't hammer
     * the DB with repeated lookups.
     */
    protected function resolveCountryCode(?string $raw, ?string $default = null): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return $default;
        }

        $raw  = trim($raw);
        $up   = strtoupper($raw);

        // 1. Already looks like an ISO code
        if (preg_match('/^[A-Z]{2}$/', $up)) {
            $exists = \App\Models\Job\Country::where('code', $up)->exists();
            if ($exists) return $up;
        }

        // 2. Exact match on Country.name
        $exact = \App\Models\Job\Country::where('name', $raw)->value('code');
        if ($exact) return strtoupper($exact);

        // 3. Case-insensitive match
        $ci = \App\Models\Job\Country::whereRaw('LOWER(name) = ?', [mb_strtolower($raw)])
            ->value('code');
        if ($ci) return strtoupper($ci);

        // 4. Substring — the AI sometimes returns "Kampala, Uganda" or
        //    "Uganda, East Africa". Look for any Country.name inside the string.
        foreach (\App\Models\Job\Country::where('is_active', true)->get(['code', 'name']) as $country) {
            if ($country->name && mb_stripos($raw, $country->name) !== false) {
                return strtoupper($country->code);
            }
        }

        // 5. Demonym → country. A short, high-value map for the markets
        //    you actually operate in. Extend this if needed.
        $demonyms = [
            'ugandan'    => 'UG',
            'kenyan'     => 'KE',
            'tanzanian'  => 'TZ',
            'rwandan'    => 'RW',
            'south sudanese' => 'SS',
            'nigerian'   => 'NG',
            'ghanaian'   => 'GH',
            'south african' => 'ZA',
            'zambian'    => 'ZM',
            'zimbabwean' => 'ZW',
            'malawian'   => 'MW',
            'british'    => 'GB',
            'english'    => 'GB',
            'australian' => 'AU',
            'american'   => 'US',
            'canadian'   => 'CA',
            'indian'     => 'IN',
            'pakistani'  => 'PK',
            'filipino'   => 'PH',
        ];
        $lower = mb_strtolower($raw);
        if (isset($demonyms[$lower])) return $demonyms[$lower];

        // 6. Phone code ("+256", "256")
        if (preg_match('/^\+?(\d{1,4})$/', $raw, $m)) {
            $phone = $m[1];
            $byPhone = \App\Models\Job\Country::where('phone_code', '+' . $phone)
                ->orWhere('phone_code', $phone)
                ->value('code');
            if ($byPhone) return strtoupper($byPhone);
        }

        return $default;
    }

    /**
     * The order in which models are tried for all CV work.
     *
     * Design intent:
     *   gemini  → primary (fast, cheap, usually available)
     *   claude  → first fallback (better on long-form writing)
     *   openai  → second fallback (reliable structured output)
     *   cohere  → LAST RESORT (always tried, even if all the above fail)
     *
     * cohere is appended at the very end unconditionally, so a config change
     * can never accidentally remove it from the chain.
     */
    protected function getOrderedCvModels(): array
    {
        $ordered = ['gemini', 'claude', 'openai'];

        // Add optional middle-tier models if they are configured.
        foreach (['grok', 'mistral'] as $model) {
            if ($this->isModelEnabled($model)) {
                $ordered[] = $model;
            }
        }

        // Guaranteed final fallback.
        $ordered[] = 'cohere';

        return array_values(array_unique($ordered));
    }

    /**
     * Check if extracted data has any meaningful content
     */
    protected function hasValidCvData(array $data): bool
    {
        // Check if there's any non-empty field
        foreach ($data as $key => $value) {
            if (is_string($value) && !empty(trim($value))) {
                return true;
            }
            if (is_array($value) && !empty($value)) {
                return true;
            }
            if (is_int($value) && $value > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get empty CV data structure
     */
    protected function getEmptyCvData(): array
    {
        return [
            'first_name' => null,
            'last_name' => null,
            'phone' => null,
            'address' => null,
            'city' => null,
            'country' => null,
            'postal_code' => null,
            'date_of_birth' => null,
            'nationality' => null,
            'professional_summary' => null,
            'professional_title' => null,
            'years_of_experience' => null,
            'linkedin_url' => null,
            'github_url' => null,
            'portfolio_url' => null,
            'skills' => [],
            'languages' => [],
            'certifications' => [],
            'education' => [],
            'work_experience' => [],
            'projects' => [],
        ];
    }

    /**
     * Get API key for a model
     */
    protected function getApiKeyFor(string $model): ?string
    {
        return match ($model) {
            'openai' => env('OPENAI_API_KEY'),
            'claude' => env('ANTHROPIC_API_KEY'),
            'gemini' => env('GEMINI_API_KEY'),
            'grok' => env('GROK_API_KEY'),
            'cohere' => env('COHERE_API_KEY'),
            'mistral' => env('MISTRAL_API_KEY'),
            default => null,
        };
    }

    /**
     * Check if model is usable (has API key and is configured)
     */
    protected function isModelUsable(string $model, ?string $apiKey): bool
    {
        if (empty($apiKey) || str_contains($apiKey, 'xxxx') || str_contains($apiKey, 'your-')) {
            return false;
        }
        return (bool) $this->getModelConfig($model);
    }

    /**
     * Parse JSON response, handling various formats
     */
    protected function parseJsonResponse($raw): ?array
    {
        // If raw is an array, encode it
        if (is_array($raw)) {
            $raw = json_encode($raw);
        }
        
        // If raw is not a string, return null
        if (!is_string($raw)) {
            return null;
        }

        // Remove markdown code fences
        $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($raw));
        $cleaned = trim($cleaned);

        // Tolerates chatter around the object and raw line breaks inside strings
        $data = $this->decodeJsonLenient($cleaned);

        if (is_array($data)) {
            return $data;
        }

        Log::warning('JSON parse error', [
            'error' => json_last_error_msg(),
            'raw_preview' => substr($raw, 0, 500)
        ]);

        return null;
    }

    /**
     * Build CV extraction prompt
     */
    protected function buildCvExtractionPrompt(string $resumeText): string
    {
        return <<<PROMPT
        You are a strict data-extraction engine for a job board. The user has uploaded a file that
        should be their CV (resume). Your first job is to decide whether the file really IS a CV.

        ═══════════════════════════════════════════════════════════════
        STEP 1: IS THIS A CV?
        ═══════════════════════════════════════════════════════════════
        Answer "yes" ONLY if the text contains at least THREE of these signals:
            - A person's name (full name, not a company name)
            - A contact detail (phone number, email, address)
            - Work experience with employer names and/or job titles
            - Education (school, degree, diploma, certificate)
            - Skills, training, or certifications
            - A professional summary or career objective

        Answer "no" if the text is:
            - A job advert / job description (talks about "we are hiring", "the candidate will...")
            - A cover letter (only one job, starts with "Dear Sir/Madam", no employment history)
            - A certificate, transcript, or ID document (no experience, only one institution)
            - A receipt, invoice, chat screenshot, or random document
            - Empty, scanned-as-image with no text, or a password-protected file
            - Too short to be a CV (under 100 words of real content)
            - A company profile, brochure, or marketing material
            - A CV that belongs to MULTIPLE people (multiple names as candidates)
            - Totally unrelated content

        If it is NOT a CV, return ONLY this JSON and stop:
            {"_valid": false, "_reason": "<friendly 1-sentence explanation for the user>"}

        Use these friendly reason templates:
            - "This does not look like a CV. Please upload your CV as a PDF or Word document."
            - "This looks like a job advert, not a CV. Please upload your own CV."
            - "This looks like a cover letter, not a full CV. Please upload your complete CV."
            - "This looks like a certificate or transcript. Please upload your full CV."
            - "This file is empty or unreadable. Please upload a PDF or Word CV with real text."
            - "This content is too short to be a CV. Please upload your full CV."
            - "This does not appear to belong to one person. Please upload your own CV."

        ═══════════════════════════════════════════════════════════════
        STEP 2: EXTRACT THE CV DATA (only if _valid = true)
        ═══════════════════════════════════════════════════════════════
        Return ONLY a single valid JSON object — no markdown, no commentary, no code fences.
        Use null or an empty array for any field you cannot find. Never invent data.

        {
        "_valid": true,
        "first_name": string|null,
        "last_name": string|null,
        "phone": string|null,
        "address": string|null,
        "city": string|null,
        "country": string|null,
        "postal_code": string|null,
        "date_of_birth": "YYYY-MM-DD"|null,
        "nationality": string|null,
        "professional_summary": string|null,
        "professional_title": string|null,
        "years_of_experience": integer|null,
        "linkedin_url": string|null,
        "github_url": string|null,
        "portfolio_url": string|null,
        "skills": string[],
        "languages": string[],
        "certifications": [{"name": string, "issuer": string|null, "year": string|null}],
        "education": [{"institution": string, "degree": string|null, "field": string|null, "grade": string|null, "start_year": string|null, "end_year": string|null}],
        "work_experience": [{"company": string, "title": string, "start_date": string|null, "end_date": string|null, "description": string|null}],
        "projects": [{"name": string, "description": string|null, "url": string|null}]
        }

        For education, include a "grade" field (e.g., "A", "B+", "First Class", "Distinction", "GPA: 3.8/4.0") if mentioned, otherwise null.

        Resume text:
        ---
        {$resumeText}
        ---
        PROMPT;
    }

    /**
     * Call AI API for CV extraction
     */
    protected function callAiApiForCv(string $model, string $apiKey, string $prompt, ?string $imageBase64 = null): array|string
    {
        $config = $this->getModelConfig($model);
        if (!$config) {
            throw new \Exception("Model '{$model}' not configured.");
        }

        $endpoint = $config['endpoint'];
        $modelName = $config['model'];
        $maxTokens = $config['max_tokens'] ?? 4096;

        // Set headers based on model
        $headers = ['Content-Type' => 'application/json'];
        
        if ($model === 'openai' || $model === 'grok' || $model === 'mistral' || $model === 'cohere') {
            $headers['Authorization'] = "Bearer {$apiKey}";
        } elseif ($model === 'claude') {
            $headers['x-api-key'] = $apiKey;
            $headers['anthropic-version'] = '2023-06-01';
        }

        $body = $this->buildCvRequestBody($model, $modelName, $maxTokens, $prompt, $imageBase64);

        if ($model === 'gemini') {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";
        }

        $response = Http::timeout(60)
            ->withHeaders($headers)
            ->post($endpoint, $body);

        if (!$response->successful()) {
            throw new \Exception("API error ({$model}): " . $this->extractCvErrorMessage($response));
        }

        return $this->extractCvResponseText($model, $response->json());
    }

    /**
     * Build request body for CV extraction
     */
    protected function buildCvRequestBody(string $model, string $modelName, int $maxTokens, string $prompt, ?string $imageBase64): array
    {
        switch ($model) {
            case 'openai':
            case 'grok':
            case 'cohere':
            case 'mistral':
                $body = [
                    'model'      => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ];
                if ($json) {
                    $body['temperature'] = 0;   // copy faithfully, do not paraphrase
                }
                if ($json && $model === 'cohere') {
                    $body['response_format'] = ['type' => 'json_object']; // no code fences, no chatter
                }
                return $body;
            case 'claude':
                return [
                    'model' => $modelName,
                    'max_tokens' => $maxTokens,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ];

            case 'gemini':
                return [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                    'generationConfig' => ['maxOutputTokens' => $maxTokens],
                ];

            case 'cohere':
                return [
                    'model' => $modelName,
                    'prompt' => $prompt,
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.1,
                    'return_likelihoods' => 'NONE',
                ];

            default:
                throw new \Exception("Unsupported model: {$model}");
        }
    }

    /**
     * Extract error message from CV API response
     */
    protected function extractCvErrorMessage($response): string
    {
        $body = $response->body();
        $data = json_decode($body, true);

        $errorPaths = [
            'error.message',
            'error.error.message',
            'error.error',
            'message',
            'error'
        ];

        foreach ($errorPaths as $path) {
            $value = $this->getNestedValue($data, $path);
            if ($value && is_string($value)) {
                return $value;
            }
        }

        return $body;
    }

    /**
     * Extract text from CV API response
     */
    protected function extractCvResponseText(string $model, array $data): string
    {
        $text = match ($model) {
            'openai', 'grok', 'mistral' => $data['choices'][0]['message']['content'] ?? '',
            'claude' => $data['content'][0]['text'] ?? '',
            'gemini' => $data['candidates'][0]['content']['parts'][0]['text'] ?? '',
            'cohere' => $data['generations'][0]['text'] ?? '',
            default => '',
        };

        if (empty($text)) {
            Log::warning("Empty response from AI model '{$model}'", ['response' => json_encode($data)]);
        }

        return $text;
    }

    
    
    /**
     * Run the free AI gap review on a CV.
     * Reuses getOrderedCvModels() / isModelUsable() / callAiApi() / parseJsonResponse().
     *
     * @throws \Exception  if all configured models fail
     */
    public function reviewCvGaps(string $resumeText, ?string $jobDescription = null): array
    {
        $prompt = $this->buildGapReviewPrompt($resumeText, $jobDescription);

        $lastError = null;

        foreach ($this->getOrderedCvModels() as $model) {
            $apiKey = $this->getApiKeyFor($model);

            if (!$this->isModelUsable($model, $apiKey)) {
                Log::info("Gap review: skipping {$model} (no API key)");
                continue;
            }

            try {
                $raw = $this->callAiApi($model, $apiKey, $prompt, null, true);
                $data = $this->parseJsonResponse(is_array($raw) ? json_encode($raw) : $raw);

                if ($data !== null && !empty($data)) {
                    // Sanity check: the AI must return an object with at least one of the expected keys
                    if (isset($data['overall_score']) || isset($data['sections']) || isset($data['summary'])) {
                        Log::info("Gap review succeeded with {$model}");
                        return $this->normaliseGapReview($data);
                    }

                    Log::warning("Gap review: {$model} returned JSON but shape looks wrong", [
                        'keys' => array_keys($data),
                    ]);
                } else {
                    Log::warning("Gap review: {$model} returned unparseable/empty response");
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("Gap review failed on {$model}: " . $e->getMessage());
            }
        }

        throw new \Exception(
            'CV gap review failed on all configured models. Last error: ' .
            ($lastError ?? 'no usable response from any model.')
        );
    }

    /**
     * "missing_fields" are the questions shown to the candidate, each written as
     * "Topic: question". Cleans what the model sent. If it returned an old-style key
     * (for example "gpa_scale"), it is swapped for a full, plain question.
     */
    protected function normaliseGapReview(array $data): array
    {
        $out  = [];
        $seen = [];

        foreach ((array) ($data['missing_fields'] ?? []) as $item) {
            $text = trim(preg_replace('/\s+/', ' ', (string) $item));
            $text = preg_replace('/^\d+[.)]\s*/', '', $text);   // the system adds numbers
            if ($text === '') {
                continue;
            }

            if (preg_match('/^[a-z0-9_]+$/i', $text)) {              // a bare key, not a question
                [$group, $question] = $this->fallbackGapQuestion(strtolower($text));
                $text = "{$group}: {$question}";
            } elseif (!str_contains($text, ':')) {
                $text = 'Details: ' . $text;
            }

            $key = mb_strtolower($text);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = mb_substr($text, 0, 600);
        }

        $data['missing_fields'] = array_slice($out, 0, 5);
        unset($data['questions']);

        return $data;
    }

    /** Plain-language backup questions, used only when the model skipped a missing field. */
    protected function fallbackGapQuestion(string $field): array
    {
        $map = [
            'contact_email'                    => ['Contact', 'What is the best professional email address for employers to reach you? (For example firstname.lastname@gmail.com.)'],
            'contact_phone'                    => ['Contact', 'Which phone number should employers call or WhatsApp? Please give one or two numbers.'],
            'location_town_district'           => ['Contact', 'Which town or village and district do you live in now? Are you willing to work in other districts or to relocate?'],
            'linkedin_url'                     => ['Contact', 'Do you have a LinkedIn page? If yes, paste the link. If not, write "none".'],
            'target_job_title'                 => ['Your target job', 'Which job title do you want most, and which type of employer (government, NGO, private company)? Please send one or two adverts you would like to apply to.'],
            'professional_summary'             => ['Your target job', 'In two or three sentences, how would you describe your work to an employer: what you do, how long you have done it, and what you are best at?'],
            'years_of_experience'              => ['Your experience', 'In total, how many years and months of paid or volunteer work do you have in your field? List any breaks of more than three months and what you did during them.'],
            'professional_registration_number' => ['Registration and licence', 'What is your professional registration or enrolment number, and who issued it? When does your practising licence expire (day, month, year)?'],
            'registration_expiry_date'         => ['Registration and licence', 'On what date does your registration or practising licence expire?'],
            'employment_status'                => ['Your work history', 'For each job on your CV, were you paid staff, a volunteer, an intern or on placement? What was your exact job title?'],
            'job_title_exact'                  => ['Your work history', 'What exact job title appears on your appointment letter or contract for each job on your CV?'],
            'employment_start_end_dates'       => ['Your work history', 'For each job on your CV, in what month and year did you start and finish? Write "to date" if you still work there.'],
            'employment_gap_explanation'       => ['Your work history', 'What were you doing in the periods between your jobs (study, casual work, job search, family)? One short line for each gap is enough.'],
            'key_duties_for_role'              => ['Your work history', 'Tell us the real duties you carried out in your most recent job. List everything you actually did, and leave out what you only observed.'],
            'quantified_results'               => ['Your results', 'Give numbers for your best work: how many clients, patients, customers, interviews, households or transactions did you handle per day, month or in total?'],
            'team_size_managed'                => ['Your results', 'Did you supervise anyone? If yes, how many people, and what did you do for them (training, targets, reports)?'],
            'budget_or_volume_handled'         => ['Your results', 'Did you handle money, stock or a budget in any job? Roughly how much per day, month or project (for example UGX 6 million per day)?'],
            'achievements'                     => ['Your results', 'What are you proud of at work? Think of targets you beat, problems you solved or processes you improved, with a number if you have one.'],
            'awards_or_recognition'            => ['Your results', 'Have you received any award, praise, promotion or extra responsibility? Who gave it and when?'],
            'promotions_or_added_responsibility'=> ['Your results', 'Were you ever given extra responsibilities or promoted in a job? What was added and in which month and year?'],
            'education_completion_dates'       => ['Education', 'In what month and year did you complete each qualification on your CV?'],
            'education_grades'                 => ['Education', 'What was your final grade, class or GPA for each qualification (with the scale, for example 4.2 out of 5)? For O-level and A-level, give the division or points.'],
            'gpa_scale'                        => ['Education', 'Your CV shows a GPA without a scale. Is it out of 4.0 or out of 5.0?'],
            'exam_board_results'               => ['Education', 'Did you pass the national or professional exams for your course (for example UNMEB, UBTEB, ICPAU)? Please give the year and your result.'],
            'certifications'                   => ['Training and certificates', 'Which professional certificates do you hold? For each one, say who issued it and in which year, and whether you have the certificate copy.'],
            'training_provider_and_year'       => ['Training and certificates', 'For each short course or training on your CV, who organised it, where was it, what year and how long did it last (days or weeks)?'],
            'clinical_or_field_placements'     => ['Training and certificates', 'Did you do placements or attachments during your course? Where, for how long, and what did you do there?'],
            'projects'                         => ['Your results', 'Tell us about one or two projects you worked on: what the goal was, what you did yourself and what the result was.'],
            'software_and_tools'               => ['Skills and tools', 'Which computer programs or tools do you use for work (for example Word, Excel, QuickBooks, KoboToolbox, DHIS2)? How well: basic, good or advanced?'],
            'technical_skills'                 => ['Skills and tools', 'Which practical skills of your profession can you do without help? List the ones you do most often.'],
            'industry_keywords'                => ['Skills and tools', 'Which procedures, systems or methods are used in the jobs you want? List the ones you have really used.'],
            'languages_with_levels'            => ['Skills and tools', 'Which languages do you speak, and how well in each: fluent, good or basic?'],
            'volunteer_work'                   => ['Your work history', 'Do you have any volunteer or community work? Please give the organisation, dates and what you did.'],
            'referees_full_details'            => ['Referees', 'Please give two or three referees with full name, job title, organisation, phone, email and how they know you. At least one should know your work well.'],
            'referee_consent'                  => ['Referees', 'Have all your referees agreed to be contacted by employers?'],
        ];

        if (isset($map[$field])) {
            return $map[$field];
        }

        $label = trim(str_replace('_', ' ', $field));

        return ['Other details', "Please tell us about your {$label}. Give names, dates and numbers where you can."];
    }

    protected function buildGapReviewPrompt(string $resumeText, ?string $jobDescription = null): string
    {
        $today = Carbon::now()->format('j F Y');

        $jobContext = $jobDescription
            ? "The candidate is targeting this specific job:\n\n{$jobDescription}\n\nEvaluate the CV against that role. Check every duty, skill, qualification and keyword the advert asks for, and flag the ones the CV does not show or cannot prove."
            : "No target job was given. Evaluate this CV for general professional quality and ATS readiness. Infer the career field from the CV and judge it against what recruiters in that field (especially in East Africa) expect to see.";

        return <<<PROMPT
            You are a senior recruiter and ATS (applicant tracking system) specialist with 10+ years of hiring
            experience. Your review is used to decide which questions the candidate must answer so their CV can be
            rebuilt to ATS standards. So do not give a general opinion: dig for the exact facts that are missing,
            unclear, inconsistent or unproven, because each one becomes a question to the candidate.

            TODAY'S DATE: {$today}. Use it to check whether "years of experience" claims and employment dates are
            still correct, and to spot open-ended or stale dates.

            {$jobContext}

            WHAT TO CHECK IN EACH SECTION
            1. Contact Information: full name, one reachable phone number, a professional email (flag joke or
               unprofessional addresses), town/village AND district/country (a bare district is too vague), and a
               LinkedIn or portfolio link where the field expects one. Note personal data that adds nothing and can
               cause bias (date of birth, photo, marital status, religion, hobbies, signed declaration).
            2. Professional Summary: is there one? Does it name the target role, the real years of experience (check
               against the dates), the main field and 2-3 provable strengths? Flag vague lines like "hard-working
               person seeking opportunities" and any claim that the dates do not support.
            3. Work Experience: for EVERY job check employer, exact job title, employment status (paid, volunteer,
               internship, placement, contract), start and end month and year, and unexplained gaps or very short
               stints. Check that the duties actually match the job title and the target role (duties copied from a
               different profession are a red flag). Look for contradictions, such as the same employer and date listed
               twice under different headings. Check that bullets start with action verbs and carry numbers: volume,
               clients, money handled, team size, targets, percentages, time saved, results. If numbers are absent,
               say which ones to ask for.
            4. Education: for each qualification check institution, exact award title, month and year of completion,
               grade, class, division or GPA (with its scale) and the exam board where relevant. Flag missing grades,
               unexplained gaps between levels, and professional registration or licensing that the field legally
               requires (for example nursing and midwifery council enrolment and practising licence, engineering,
               accountancy, law, teaching or medical registration). The registration number and expiry date are among
               the first things an employer checks.
            5. Skills: separate hard skills, tools and software, certifications and soft skills. Flag skill lists that
               are mostly soft skills, are generic, or lack the keywords this field and role normally list. Check for
               short courses and training (provider, year, duration, certificate), languages with levels, and software
               the candidate probably uses but did not name.

            ATS FORMAT PROBLEMS (mention the most serious in the "summary", or in the closest section note)
            - The CV looks like a photograph or scan, or is built from tables, text boxes, columns, headers or
              footers, graphics or rating bars. ATS cannot read these reliably.
            - Dates not in month-and-year form, missing job titles, headings with unusual names.
            - Inconsistent spelling of the candidate's name or employers.

            SCORING (overall_score, 0-100). Be strict and honest.
            - 85-100: complete, consistent, measurable results, and ATS-readable. Rare.
            - 65-84: solid, with some missing numbers, grades or keywords.
            - 40-64: key facts missing or duties generic. Needs a real interview with the candidate.
            - 0-39: major gaps, contradictions or unreadable content.
            Lower the score for each contradiction, missing registration, missing dates, and absence of numbers.

            Return ONLY a single valid JSON object. No markdown fences, no commentary before or after.

            {
            "overall_score": 0-100,
            "summary": "2-3 sentence plain-English overview naming the 2 biggest weaknesses by name and the single strongest point",
            "sections": [
                {
                    "section": "Contact Information",
                    "status": "ok" | "weak" | "missing",
                    "note": "one or two sentences naming exactly what is missing or wrong, quoting the CV where possible"
                },
                {
                    "section": "Professional Summary",
                    "status": "ok" | "weak" | "missing",
                    "note": "..."
                },
                {
                    "section": "Work Experience",
                    "status": "ok" | "weak" | "missing",
                    "note": "..."
                },
                {
                    "section": "Education",
                    "status": "ok" | "weak" | "missing",
                    "note": "..."
                },
                {
                    "section": "Skills",
                    "status": "ok" | "weak" | "missing",
                    "note": "..."
                }
            ],
            "missing_fields": [
                "Topic: one complete question written to this person about their CV"
            ]
            }

            HOW TO WRITE "missing_fields":
            Each item is one question that will be shown to the candidate as a form field, so it must stand on its
            own and be fully understood by someone who has not read any review. A short label such as "Training
            Provider And Year" is useless: even the owner of the CV would not know what to write. Write long,
            direct, specific questions.
            - Format of every item: "Topic: question". The topic is one to three words (a job, a school, "Target",
            "Education", "Referees", "Contact", "Availability"). After the colon comes the question. Do not number
            the items, the system numbers them.
            - Each question is 20 to 60 words and chains the 2 to 4 closely related things the expert needs for
            that topic, so the person can answer in one go.
            - Use the CV's own details: employer, job title, school, project and dates. Name them, so the person
            knows exactly which part of their CV you mean.
            - Say what form each answer should take (month and year, a number, yes or no, a short list) and give a
            short example in brackets where it helps. Estimates are fine ("about how many").
            - Ask about contradictions directly, for example the same job listed twice, a job shown as paid in one
            place and volunteer in another, or duty lines copied word for word across several roles.
            - Do not ask for anything the CV already states. Do not ask for date of birth, marital status,
            religion, a photo or an ID number; if the CV contains them, say in the Contact note that they should
            be removed.
            - Plain, friendly English in the second person ("you", "your"). No jargon, and never the word "ATS".

            RANKING AND CAP — THIS IS STRICT:
            - Return AT MOST 5 items. This is a hard cap. Never more than 5.
            - Rank by impact on the candidate's chances of getting this specific job. Item 1 is the one that, if
            answered well, will improve their CV the most.
            - Prefer topics that unblock the whole rewrite: target job and role focus, the most recent job's real
            duties and numbers, education completion and grade (with professional registration if relevant),
            contact reachability, and referees. Cover the essentials first.
            - If a topic can be answered by looking at the CV, do NOT include it. Only include what the CV cannot
            tell us.
            - Return an empty array [] only if truly nothing important is missing.
            - If you find 10 problems, pick the 5 that matter most to THIS application. The candidate can only
            realistically answer 5; the rest are noise.

            Examples of the right length and style (invented people, for style only):
            - "Target: which job, or type of job, are you applying for, and in which type of organisation (government, NGO or private)? Please send the advert if you have one."
            - "Education: in what month and year did you finish your Diploma in Business Administration at Makerere, and what was your final grade or GPA (and the scale, for example 4.2 out of 5)? Do you also have a degree, and what were your UCE and UACE years and schools?"
            - "Work at Total Energies: what was your exact job title, at which station, and what were your start and end dates (month and year)? Roughly how much cash did you reconcile per day, and how many staff did you supervise?"
            - "Field surveys: your duties are word for word the same under five projects. For each project, about how many households or respondents did you survey, how many days per week did you work, and did you train or lead anyone?"
            - "Referees: which three people should we keep as referees, and what are their job titles, phone numbers and emails? Do they know you have listed them?"

            RULES:
            - Use EXACTLY the 5 sections listed above, in that order. Do not add or remove sections.
            - "status" must be one of: "ok", "weak", "missing" (lowercase). Use "ok" only when nothing important is
              missing. Use "missing" only when the section is absent.
            - Every "note" must be specific to THIS CV: name the job, school, date or line concerned. Never write
              generic advice such as "add more detail" or "improve formatting".
            - Do NOT invent details that are not in the CV, and do not assume a registration, grade or number exists.
            - Do NOT wrap the response in markdown code fences.
            - Do NOT include any text before or after the JSON object.

            CV text:
            ---
            {$resumeText}
            ---
            PROMPT;
    }

    /**
     * Screen a single CV against a job description.
     *
     * @return array{
     *     score: int,
     *     recommendation: string,
     *     summary: string,
     *     strengths: array,
     *     gaps: array,
     *     red_flags: array
     * }
     */
    public function screenApplicant(string $cvText, array $jobContext): array
    {
        $prompt = $this->buildScreeningPrompt($cvText, $jobContext);

        foreach ($this->getOrderedCvModels() as $model) {
            $apiKey = $this->getApiKeyFor($model);

            if (!$this->isModelUsable($model, $apiKey)) {
                continue;
            }

            try {
                $raw  = $this->callAiApi($model, $apiKey, $prompt, null, true);
                $data = $this->parseJsonResponse(is_array($raw) ? json_encode($raw) : $raw);

                if (is_array($data) && isset($data['score'])) {
                    return [
                        'score'          => max(0, min(100, (int) $data['score'])),
                        'recommendation' => in_array($data['recommendation'] ?? '', ['strong_yes', 'maybe', 'no'], true)
                            ? $data['recommendation']
                            : 'maybe',
                        'summary'   => (string) ($data['summary'] ?? ''),
                        'strengths' => is_array($data['strengths'] ?? null) ? array_values(array_filter($data['strengths'])) : [],
                        'gaps'      => is_array($data['gaps'] ?? null) ? array_values(array_filter($data['gaps'])) : [],
                        'red_flags' => is_array($data['red_flags'] ?? null) ? array_values(array_filter($data['red_flags'])) : [],
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("Screening failed on {$model}: " . $e->getMessage());
            }
        }

        throw new \Exception('AI screening failed on all configured models.');
    }

    protected function buildScreeningPrompt(string $cvText, array $jobContext): string
    {
        $jobTitle       = $jobContext['job_title'] ?? 'this role';
        $jobDescription = strip_tags($jobContext['job_description'] ?? '');
        $responsibilities = strip_tags($jobContext['responsibilities'] ?? '');
        $qualifications   = strip_tags($jobContext['qualifications'] ?? '');
        $skills           = $jobContext['skills'] ?? '';

        return <<<PROMPT
            You are an experienced HR screener evaluating a candidate's CV against a specific job opening.

            JOB TITLE: {$jobTitle}

            JOB DESCRIPTION:
            {$jobDescription}

            RESPONSIBILITIES:
            {$responsibilities}

            REQUIRED QUALIFICATIONS:
            {$qualifications}

            REQUIRED SKILLS:
            {$skills}

            CANDIDATE'S CV:
            ---
            {$cvText}
            ---

            Your task: score how well this candidate matches this specific role.

            SCORING GUIDELINES (0-100):
            - 85-100 → Excellent match. Meets all key requirements. Strong evidence in CV.
            - 70-84  → Good match. Meets most requirements. Minor gaps.
            - 55-69  → Reasonable match. Meets some requirements. Notable gaps.
            - 40-54  → Weak match. Meets a few requirements. Significant gaps.
            - 0-39   → Poor match. Doesn't meet core requirements.

            BE FAIR AND OBJECTIVE:
            - Do NOT penalise candidates for name, gender, nationality, age, religion, or other protected characteristics.
            - Focus ONLY on skills, experience, education, and qualifications relevant to the role.
            - If the CV is sparse, judge it as weak but don't invent facts.
            - If the CV is strong but unorganised, still score it fairly.

            RED FLAGS are serious concerns only: e.g., no relevant experience at all for a senior role, missing critical certifications, glaring inconsistencies. Do NOT call minor formatting issues a red flag.

            Return ONLY this JSON object (no markdown, no commentary):

            {
            "score": 0-100,
            "recommendation": "strong_yes" | "maybe" | "no",
            "summary": "2-3 sentence verdict explaining the score",
            "strengths": ["specific strength from the CV", "..."],
            "gaps": ["specific missing skill or qualification", "..."],
            "red_flags": ["serious concern if any", "..."]
            }

            recommendation rules:
            - "strong_yes" → score >= 80
            - "maybe"      → score 50-79
            - "no"         → score < 50

            Keep strengths, gaps, and red_flags arrays to 3-5 items each. Be specific and factual — quote from the CV where useful.
            PROMPT;
    }



    /**
     * Generate a professional cover or application letter.
     *
     * Returns clean HTML (only <p> tags, no attributes).
     *
     * @param  string  $cvText        Raw text extracted from the candidate's CV
     * @param  array   $jobContext    ['job_title', 'company_name', 'job_description']
     * @param  string  $letterType    'cover' | 'application'
     * @throws \Exception             when every configured model fails
     */
    public function generateLetter(string $cvText, array $jobContext, string $letterType = 'cover'): string
    {
        $letterType = in_array($letterType, ['cover', 'application'], true) ? $letterType : 'cover';
        $prompt = $this->buildLetterPrompt($cvText, $jobContext, $letterType);

        $lastError = null;

        foreach ($this->getOrderedCvModels() as $model) {
            $apiKey = $this->getApiKeyFor($model);

            if (!$this->isModelUsable($model, $apiKey)) {
                continue;
            }

            try {
                $raw = $this->callAiApi($model, $apiKey, $prompt);

                if (!is_string($raw) || trim($raw) === '') {
                    Log::warning("Letter generation: empty response from {$model}");
                    continue;
                }

                $clean = $this->cleanLetterHtml($raw);

                if (str_word_count(strip_tags($clean)) < 60) {
                    Log::warning("Letter generation: {$model} returned too short a letter", [
                        'word_count' => str_word_count(strip_tags($clean)),
                    ]);
                    continue;
                }

                return $clean;

            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("Letter generation failed on {$model}: " . $e->getMessage());
            }
        }

        throw new \Exception(
            'Letter generation failed on all configured models. Last error: '
            . ($lastError ?? 'no usable response from any model.')
        );
    }

    protected function buildLetterPrompt(string $cvText, array $jobContext, string $letterType): string
    {
        $jobTitle       = $jobContext['job_title']    ?? 'the advertised post';
        $companyName    = $jobContext['company_name'] ?? 'the organisation';
        $jobDescription = strip_tags($jobContext['job_description'] ?? '');
        $jobDescription = $jobDescription !== '' ? $jobDescription : '(no detailed description provided)';

        // Optional: the caller can pass 'company_address', 'duty_station' or 'location' so the
        // recipient block can carry the company's town/address. Without any of them, the line is omitted.
        $placeRaw = trim((string) ($jobContext['company_address'] ?? $jobContext['duty_station'] ?? $jobContext['location'] ?? ''));
        $jobPlace = $placeRaw !== '' ? "Company address / town: {$placeRaw}\n            " : '';

        $isApplication = $letterType === 'application';

        $subjectLine = $isApplication
            ? 'RE: APPLICATION FOR THE POST OF ' . mb_strtoupper($jobTitle)
            : 'RE: APPLICATION FOR THE POSITION OF ' . mb_strtoupper($jobTitle);

        $openingInstruction = $isApplication
            ? 'Open with a direct application statement: "I am writing to apply for the post of X as advertised in Y." State where the advert was seen.'
            : 'Open with a brief statement of interest in the position and where it was advertised. Keep it formal and direct.';

        $today = \Carbon\Carbon::now()->format('d F Y');

        return <<<PROMPT
            You are a professional career writer in Uganda. You write formal
            application letters in the East African / British Commonwealth
            style that employers in Uganda expect. NOT the American "Dear
            Hiring Manager, I'm excited to apply" style.

            TASK: Write a formal application letter for the candidate below.

            TARGET ROLE:
            Job Title: {$jobTitle}
            Company: {$companyName}
            {$jobPlace}Job Description / Context:
            {$jobDescription}

            TODAY'S DATE (use it in the letter): {$today}

            CANDIDATE CV (raw text extracted from their CV):
            ---
            {$cvText}
            ---

            STRUCTURE — follow this EXACTLY, in this order:

            ── 1. CANDIDATE HEADER (top of the letter, right side or left,
                no label needed) ──
            Line 1: the candidate's full name (from the CV, in bold)
            Line 2: their town/city and country (e.g. "Kampala, Uganda") — from the CV if present, else omit this line
            Line 3: their phone number and email, separated by " | " (from the CV)
            Do NOT invent a postal address or P.O. Box if the CV doesn't have one.

            ── 2. DATE (own line, right-aligned) ──
            "{$today}"

            ── 3. RECIPIENT BLOCK (left-aligned, after the date) ──
            Line 1: "The Human Resource Manager" (default title for all formal applications)
            Line 2: The company name exactly as provided: "{$companyName}"
            Line 3: the "Company address / town" given above, exactly as written — omit this line if none is given
            Line 4: "Uganda" (only if the previous line is present and doesn't already include "Uganda")
            Do NOT invent a P.O. Box, postal code, street address, or HR manager's personal name.
            If the job context names a specific person (e.g. "The Managing Director"), use that instead of "The Human Resource Manager".

            ── 4. SUBJECT LINE (own paragraph, bold, in capitals) ──
            {$subjectLine}

            ── 5. SALUTATION ──
            "Dear Sir/Madam," (unless the job context names a specific person — then use "Dear Mr./Ms. [Surname],")

            ── 6. BODY — four paragraphs, in this order ──

            Paragraph 1 (Opening, 2-3 sentences):
            - {$openingInstruction}
            - Name the exact job title and where it was advertised if the CV or job context mentions it.
            - State that your skills match what {$companyName} is looking for.

            Paragraph 2 (Work experience, 3-5 sentences):
            - Name the most recent relevant employer(s) from the CV.
            - Describe 2-3 concrete duties that match this job.
            - Include numbers (years of experience, clients handled per day,
                records managed, transactions processed) whenever the CV provides them.
            - Do NOT invent numbers.

            Paragraph 3 (Qualifications and skills, 2-4 sentences):
            - Name the highest qualification from the CV (degree, diploma, certificate).
            - Mention 2-3 practical skills that matter for this job (software, languages, tools).
            - State any relevant professional certifications.
            - Add one sentence linking those skills directly to what the job advert asks for.

            Paragraph 4 (Personal qualities and availability, 2-3 sentences):
            - Name 3 personal qualities (e.g. "polite, organised and reliable").
            - End with: "I am available to start immediately and would be glad to discuss how I can support {$companyName} at an interview."
            - Add "Thank you for your time and consideration."

            ── 7. SIGN-OFF ──
            "Yours faithfully," (always, not "Yours sincerely")
            Then two blank lines
            Then the candidate's full name as it appears on the CV, in bold.

            HARD RULES:
            - Length: 300 to 400 words total (excluding the address blocks).
            - Tone: formal, respectful, direct. Plain English — no "I am
            thrilled", no "passionate", no "dynamic", no em-dashes.
            - Do NOT include placeholder brackets like [Your Name],
            [Company Name], [Date], [Job Title], [P.O. Box]. Fill everything
            from the CV and the job context provided, or OMIT the line
            entirely if the data isn't available.
            - Do NOT invent employers, dates, qualifications, salary, P.O. Boxes,
            street addresses, or achievements. Only use what is in the CV.
            - If the CV is thin, write a strong but honest letter that leans
            on transferable skills — never fabricate experience.
            - Do NOT use markdown bold, italics, or headings inside the body.
            - Return clean HTML using only <p>, <strong> and <br> tags. No
            attributes on any tag. No <html>, <head>, or <body> wrapper.
            - Do NOT wrap the response in markdown code fences. No commentary
            before or after the letter.

            Return the letter now.
            PROMPT;
    }

    /**
     * Strip markdown fences, wrapper tags, and any stray attributes from the
     * AI's response so what we store is pure, safe HTML.
     */
    protected function cleanLetterHtml(string $raw): string
    {
        $clean = preg_replace('/^```(?:html|HTML)?\s*/', '', trim($raw));
        $clean = preg_replace('/\s*```\s*$/m', '', $clean);
        $clean = trim($clean);

        // Remove any <html>/<head>/<body> wrappers
        $clean = preg_replace('#</?(html|head|body|meta|title)\b[^>]*>#i', '', $clean);

        // Strip attributes from every tag
        $clean = preg_replace_callback('/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/', function ($m) {
            $tag = strtolower($m[1]);
            $selfClosing = in_array($tag, ['br', 'hr', 'img'], true);
            return $selfClosing ? "<{$tag} />" : "<{$tag}>";
        }, $clean);

        // Drop empty <p>
        $clean = preg_replace('#<p>\s*</p>#i', '', $clean);

        // If the model returned plain text, wrap blocks in <p>
        if (!preg_match('/<p\b/i', $clean)) {
            $blocks = preg_split('/\n\s*\n/', $clean);
            $clean = implode('', array_map(
                fn($b) => '<p>' . nl2br(htmlspecialchars(trim($b), ENT_QUOTES)) . '</p>',
                array_filter($blocks, fn($b) => trim($b) !== '')
            ));
        }

        return trim($clean);
    }


    /**
     * Screen a single uploaded CV against a job description.
     *
     * Returns the match score, verdict, reasons, and extracted candidate
     * identity fields so the shortlist table can show real names.
     */
    public function screenUploadedCv(string $cvText, array $jobContext): array
    {
        $prompt = $this->buildUploadedScreeningPrompt($cvText, $jobContext);

        foreach ($this->getOrderedCvModels() as $model) {
            $apiKey = $this->getApiKeyFor($model);

            if (!$this->isModelUsable($model, $apiKey)) continue;

            try {
                $raw  = $this->callAiApi($model, $apiKey, $prompt, null, true);
                $data = $this->parseJsonResponse(is_array($raw) ? json_encode($raw) : $raw);

                if (is_array($data) && isset($data['score'])) {
                    return [
                        'score'               => max(0, min(100, (int) $data['score'])),
                        'recommendation'      => in_array($data['recommendation'] ?? '', ['strong_yes', 'maybe', 'no'], true)
                                                    ? $data['recommendation']
                                                    : 'maybe',
                        'summary'             => (string) ($data['summary'] ?? ''),
                        'strengths'           => (array) ($data['strengths'] ?? []),
                        'gaps'                => (array) ($data['gaps'] ?? []),
                        'red_flags'           => (array) ($data['red_flags'] ?? []),
                        'matched_skills'      => (array) ($data['matched_skills'] ?? []),
                        'missing_skills'      => (array) ($data['missing_skills'] ?? []),
                        'candidate_name'      => $data['candidate_name']      ?? null,
                        'candidate_email'     => $data['candidate_email']     ?? null,
                        'candidate_phone'     => $data['candidate_phone']     ?? null,
                        'current_title'       => $data['current_title']       ?? null,
                        'years_of_experience' => isset($data['years_of_experience'])
                                                    ? (int) $data['years_of_experience']
                                                    : null,
                        'highest_education'   => $data['highest_education']   ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("Uploaded CV screening failed on {$model}: " . $e->getMessage());
            }
        }

        throw new \Exception('AI screening failed on all configured models.');
    }

    protected function buildUploadedScreeningPrompt(string $cvText, array $jobContext): string
    {
        $title = $jobContext['job_title'] ?? 'this role';
        $jd    = strip_tags($jobContext['job_description'] ?? '');

        return <<<PROMPT
            You are an experienced HR screener evaluating one CV against a job.

            JOB TITLE: {$title}

            JOB DESCRIPTION:
            {$jd}

            CANDIDATE CV:
            ---
            {$cvText}
            ---

            TASK:
            1. Extract the candidate's identity from the CV.
            2. Score how well the CV matches the job (0-100).
            3. Explain the score with concrete strengths, gaps, red flags,
            and a skill-by-skill comparison.

            SCORING:
            - 85-100  Excellent match. Meets all core requirements with strong evidence.
            - 70-84   Good match. Meets most requirements with minor gaps.
            - 55-69   Reasonable match. Meets some requirements, notable gaps.
            - 40-54   Weak match. Meets a few requirements, significant gaps.
            - 0-39    Poor match. Does not meet core requirements.

            RULES:
            - Score only on skills, experience, education, and qualifications.
            - Do NOT score on name, gender, nationality, age, or other protected traits.
            - If the CV is sparse, mark it weak but do not invent facts.
            - Red flags are only serious concerns: wrong domain entirely, missing a required license, or a glaring inconsistency.

            Return ONLY this JSON object (no markdown, no commentary):

            {
            "candidate_name": "Full name as written on the CV, or null",
            "candidate_email": "Email from the CV, or null",
            "candidate_phone": "Phone from the CV, or null",
            "current_title": "Their most recent job title, or null",
            "years_of_experience": <integer or null>,
            "highest_education": "e.g. Bachelor's, Diploma, MSCE, or null",
            "score": <0-100>,
            "recommendation": "strong_yes" | "maybe" | "no",
            "summary": "2-3 sentence verdict explaining the score",
            "strengths": ["specific strength from the CV", "..."],
            "gaps": ["specific missing skill or qualification", "..."],
            "red_flags": ["serious concern if any"],
            "matched_skills": ["skill required by the job and present in the CV"],
            "missing_skills": ["skill required by the job and missing from the CV"]
            }

            Recommendation rule:
            - "strong_yes" -> score >= 80
            - "maybe"      -> score 50-79
            - "no"         -> score < 50

            Keep strengths, gaps, and red_flags to 3-5 items each. Be specific — quote the CV where useful.
            PROMPT;
    }

    /**
     * Tailor a CV to a specific job description.
     *
     * Returns clean HTML (only <p>, <ul>, <li>, <strong> tags, no attributes).
     */
    public function tailorCv(string $cvText, array $jobContext): string
    {
        $prompt = $this->buildTailorCvPrompt($cvText, $jobContext);

        $lastError = null;

        foreach ($this->getOrderedCvModels() as $model) {
            $apiKey = $this->getApiKeyFor($model);
            if (!$this->isModelUsable($model, $apiKey)) continue;

            try {
                $raw = $this->callAiApi($model, $apiKey, $prompt);

                if (!is_string($raw) || trim($raw) === '') {
                    Log::warning("CV tailoring: empty response from {$model}");
                    continue;
                }

                $clean = $this->cleanCvHtml($raw);

                if (str_word_count(strip_tags($clean)) < 120) {
                    Log::warning("CV tailoring: too short from {$model}", [
                        'word_count' => str_word_count(strip_tags($clean)),
                    ]);
                    continue;
                }

                return $clean;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("CV tailoring failed on {$model}: " . $e->getMessage());
            }
        }

        throw new \Exception(
            'CV tailoring failed on all models. Last error: ' . ($lastError ?? 'none')
        );
    }

    protected function buildTailorCvPrompt(string $cvText, array $jobContext): string
    {
        $title       = $jobContext['job_title']       ?? 'this role';
        $company     = $jobContext['company_name']    ?? 'the company';
        $description = strip_tags($jobContext['job_description'] ?? '');
        $skills      = $jobContext['skills']          ?? '';

        return <<<PROMPT
            You are an expert CV writer. Your task is to produce a tailored CV that
            positions the candidate as a strong match for the specific job below.

            TARGET ROLE:
            Job Title: {$title}
            Company: {$company}
            Description: {$description}
            Required Skills: {$skills}

            ORIGINAL CV TEXT:
            ---
            {$cvText}
            ---

            RULES:
            - Never invent employers, dates, degrees, or achievements. Only reframe,
            reorder, and rewrite what is already in the CV.
            - Reorder sections so the most relevant experience is first.
            - Rewrite bullet points to emphasize the skills and outcomes the job needs.
            - Add a short professional summary (3-4 sentences) tailored to this role.
            - Add a "Key Skills" section listing the top 8-12 skills most relevant to
            the job, only from skills already present in the CV.
            - Use clean HTML: <h2>, <h3>, <p>, <ul>, <li>, <strong>.
            No attributes on any tag. No <style>, no <div>, no classes.
            - Do NOT include the candidate's address, date, or a letter.
            - Do NOT include a cover letter. The cover letter is generated separately.
            - Aim for 350-600 words.
            - Return ONLY the HTML. No markdown, no code fences, no commentary.

            Return the tailored CV HTML now.
            PROMPT;
    }

    protected function cleanCvHtml(string $raw): string
    {
        $clean = preg_replace('/^```(?:html|HTML)?\s*/', '', trim($raw));
        $clean = preg_replace('/\s*```\s*$/m', '', $clean);
        $clean = preg_replace('#</?(html|head|body|meta|title)\b[^>]*>#i', '', $clean);

        // Strip attributes from all tags
        $clean = preg_replace_callback('/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/', function ($m) {
            $tag = strtolower($m[1]);
            $selfClosing = in_array($tag, ['br', 'hr'], true);
            return $selfClosing ? "<{$tag} />" : "<{$tag}>";
        }, $clean);

        return trim($clean);
    }

    
}