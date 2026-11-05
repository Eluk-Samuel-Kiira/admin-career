<?php

namespace App\Services\Jobs;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiLetterRotator
{
    // Cooldown durations (seconds) per failure type
    protected const COOLDOWN_RATE_LIMIT = 60;   // 429
    protected const COOLDOWN_AUTH       = 3600; // 401/403 — key is dead, rest of the hour
    protected const COOLDOWN_SERVER     = 120;  // 5xx

    /**
     * Generate a letter. Tries each Gemini key in order, then falls back
     * to OpenAI, then Claude. Returns the first non-empty response.
     *
     * @throws \Exception when every provider and every key fails
     */
    public function generate(string $prompt, int $maxTokens = 4096): string
    {
        $attempts = [];
        $lastErr  = null;

        // ── 1. Try each Gemini key ────────────────────────────────
        foreach ($this->geminiKeys() as $index => $apiKey) {
            $slot = 'gemini#' . ($index + 1)
                  . ' (' . substr($apiKey, 0, 6) . '…' . substr($apiKey, -4) . ')';

            if ($this->isCoolingDown('gemini', $apiKey)) {
                $attempts[] = "{$slot}: cooling down";
                continue;
            }

            try {
                $text = $this->callGemini($apiKey, $prompt, $maxTokens);
                if (is_string($text) && trim($text) !== '') {
                    $this->recordSuccess('gemini', $apiKey);
                    Log::info('GeminiLetterRotator: gemini success', [
                        'slot'  => $slot,
                        'chars' => strlen($text),
                    ]);
                    return $text;
                }
                $attempts[] = "{$slot}: empty response";
                $lastErr    = 'Gemini returned empty response.';
            } catch (ProviderHttpException $e) {
                $cooldown = $this->cooldownFor($e->status);
                if ($cooldown > 0) {
                    $this->startCoolDown('gemini', $apiKey, $cooldown);
                }
                $attempts[] = "{$slot}: HTTP {$e->status}" . ($cooldown ? " ({$cooldown}s cooldown)" : '');
                $lastErr    = $e->getMessage();
                Log::warning('GeminiLetterRotator: gemini key failed', [
                    'slot'   => $slot,
                    'status' => $e->status,
                    'body'   => substr($e->body, 0, 200),
                ]);
            } catch (\Throwable $e) {
                $attempts[] = "{$slot}: " . $e->getMessage();
                $lastErr    = $e->getMessage();
                Log::warning('GeminiLetterRotator: gemini exception', [
                    'slot'  => $slot,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ── 2. Try OpenAI ─────────────────────────────────────────
        $openaiKey = config('ai.models.openai.api_key');
        if (is_string($openaiKey) && strlen(trim($openaiKey)) >= 20) {
            $slot = 'openai (' . substr($openaiKey, 0, 6) . '…' . substr($openaiKey, -4) . ')';

            if ($this->isCoolingDown('openai', $openaiKey)) {
                $attempts[] = "{$slot}: cooling down";
            } else {
                try {
                    $text = $this->callOpenAI($openaiKey, $prompt, $maxTokens);
                    if (is_string($text) && trim($text) !== '') {
                        $this->recordSuccess('openai', $openaiKey);
                        Log::info('GeminiLetterRotator: openai success', [
                            'slot'  => $slot,
                            'chars' => strlen($text),
                        ]);
                        return $text;
                    }
                    $attempts[] = "{$slot}: empty response";
                    $lastErr    = 'OpenAI returned empty response.';
                } catch (ProviderHttpException $e) {
                    $cooldown = $this->cooldownFor($e->status);
                    if ($cooldown > 0) {
                        $this->startCoolDown('openai', $openaiKey, $cooldown);
                    }
                    $attempts[] = "{$slot}: HTTP {$e->status}";
                    $lastErr    = $e->getMessage();
                    Log::warning('GeminiLetterRotator: openai failed', [
                        'slot'   => $slot,
                        'status' => $e->status,
                    ]);
                } catch (\Throwable $e) {
                    $attempts[] = "{$slot}: " . $e->getMessage();
                    $lastErr    = $e->getMessage();
                }
            }
        } else {
            $attempts[] = 'openai: no key configured';
        }

        // ── 3. Try Claude ─────────────────────────────────────────
        $claudeKey = config('ai.models.claude.api_key');
        if (is_string($claudeKey) && strlen(trim($claudeKey)) >= 20) {
            $slot = 'claude (' . substr($claudeKey, 0, 6) . '…' . substr($claudeKey, -4) . ')';

            if ($this->isCoolingDown('claude', $claudeKey)) {
                $attempts[] = "{$slot}: cooling down";
            } else {
                try {
                    $text = $this->callClaude($claudeKey, $prompt, $maxTokens);
                    if (is_string($text) && trim($text) !== '') {
                        $this->recordSuccess('claude', $claudeKey);
                        Log::info('GeminiLetterRotator: claude success', [
                            'slot'  => $slot,
                            'chars' => strlen($text),
                        ]);
                        return $text;
                    }
                    $attempts[] = "{$slot}: empty response";
                    $lastErr    = 'Claude returned empty response.';
                } catch (ProviderHttpException $e) {
                    $cooldown = $this->cooldownFor($e->status);
                    if ($cooldown > 0) {
                        $this->startCoolDown('claude', $claudeKey, $cooldown);
                    }
                    $attempts[] = "{$slot}: HTTP {$e->status}";
                    $lastErr    = $e->getMessage();
                    Log::warning('GeminiLetterRotator: claude failed', [
                        'slot'   => $slot,
                        'status' => $e->status,
                    ]);
                } catch (\Throwable $e) {
                    $attempts[] = "{$slot}: " . $e->getMessage();
                    $lastErr    = $e->getMessage();
                }
            }
        } else {
            $attempts[] = 'claude: no key configured';
        }

        // ── All providers exhausted ───────────────────────────────
        throw new \Exception(
            'All letter providers failed. Tried: ' . implode(' | ', $attempts)
            . ' — last error: ' . ($lastErr ?? 'unknown')
        );
    }

    // ═════════════════════════════════════════════════════════════
    // Provider-specific callers
    // ═════════════════════════════════════════════════════════════

    /**
     * Google Gemini — same endpoint shape as before.
     */
    protected function callGemini(string $apiKey, string $prompt, int $maxTokens): ?string
    {
        $endpoint = rtrim((string) config('ai.letter_gemini_endpoint'), '/');
        $model    = (string) config('ai.letter_gemini_model', 'gemini-3.5-flash');

        $body = [
            'contents' => [[
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'maxOutputTokens' => $maxTokens,
                'temperature'     => 0.8,
            ],
        ];

        $url = "{$endpoint}/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(90)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $body);

        if (!$response->successful()) {
            throw new ProviderHttpException($response->status(), $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text');
    }

    /**
     * OpenAI Chat Completions.
     */
    protected function callOpenAI(string $apiKey, string $prompt, int $maxTokens): ?string
    {
        $config   = config('ai.models.openai');
        $endpoint = $config['endpoint'] ?? 'https://api.openai.com/v1/chat/completions';
        $model    = $config['model']    ?? 'gpt-4o';

        $body = [
            'model'       => $model,
            'max_tokens'  => $maxTokens,
            'temperature' => 0.8,
            'messages'    => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        $response = Http::timeout(90)
            ->withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => "Bearer {$apiKey}",
            ])
            ->post($endpoint, $body);

        if (!$response->successful()) {
            throw new ProviderHttpException($response->status(), $response->body());
        }

        return $response->json('choices.0.message.content');
    }

    /**
     * Anthropic Claude Messages API.
     */
    protected function callClaude(string $apiKey, string $prompt, int $maxTokens): ?string
    {
        $config   = config('ai.models.claude');
        $endpoint = $config['endpoint'] ?? 'https://api.anthropic.com/v1/messages';
        $model    = $config['model']    ?? 'claude-sonnet-4-20250514';

        $body = [
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        $response = Http::timeout(90)
            ->withHeaders([
                'Content-Type'      => 'application/json',
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post($endpoint, $body);

        if (!$response->successful()) {
            throw new ProviderHttpException($response->status(), $response->body());
        }

        return $response->json('content.0.text');
    }

    // ═════════════════════════════════════════════════════════════
    // Gemini key resolution
    // ═════════════════════════════════════════════════════════════

    /**
     * Returns all usable Gemini keys. Index 0 is always tried first if it's
     * not cooling down. On success, that key is "preferred" for an hour.
     */
    protected function geminiKeys(): array
    {
        // Support both config shapes — new `letter_providers.gemini_keys`
        // and legacy `letter_gemini_keys` (primary + fallbacks).
        $keys = config('ai.letter_providers.gemini_keys');

        if (empty($keys)) {
            $primary   = config('ai.letter_gemini_keys.primary');
            $fallbacks = (array) config('ai.letter_gemini_keys.fallbacks', []);
            $keys      = array_merge([$primary], $fallbacks);
        }

        $keys = array_values(array_filter(
            $keys,
            fn ($k) => is_string($k) && strlen(trim($k)) >= 20
        ));

        if (empty($keys)) {
            return [];
        }

        $preferred = Cache::get('letter_rotator:preferred:gemini');
        if ($preferred && in_array($preferred, $keys, true)) {
            return array_values(array_merge(
                [$preferred],
                array_diff($keys, [$preferred])
            ));
        }

        return $keys;
    }

    // ═════════════════════════════════════════════════════════════
    // Cooldown / lock helpers (per-provider, key-scoped)
    // ═════════════════════════════════════════════════════════════

    protected function isCoolingDown(string $provider, string $apiKey): bool
    {
        return Cache::has($this->cacheKey($provider, $apiKey));
    }

    protected function startCoolDown(string $provider, string $apiKey, int $seconds): void
    {
        if ($seconds > 0) {
            Cache::put($this->cacheKey($provider, $apiKey), true, $seconds);
        }
    }

    protected function recordSuccess(string $provider, string $apiKey): void
    {
        Cache::put("letter_rotator:preferred:{$provider}", $apiKey, 3600);
        Cache::forget($this->cacheKey($provider, $apiKey));
    }

    protected function cacheKey(string $provider, string $apiKey): string
    {
        return "letter_rotator:cooldown:{$provider}:" . md5($apiKey);
    }

    protected function cooldownFor(int $status): int
    {
        return match (true) {
            $status === 429               => self::COOLDOWN_RATE_LIMIT,
            in_array($status, [401, 403]) => self::COOLDOWN_AUTH,
            $status >= 500                => self::COOLDOWN_SERVER,
            default                       => 0,
        };
    }
}

/**
 * Tiny internal exception that carries the HTTP status so we can decide
 * the cooldown length without parsing the message.
 */
class ProviderHttpException extends \Exception
{
    public function __construct(
        public readonly int $status,
        public readonly string $body,
    ) {
        parent::__construct("HTTP {$status} from provider: " . substr($body, 0, 200));
    }
}