<?php

namespace App\Services\Jobs;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiLetterRotator
{
    protected const COOLDOWN_RATE_LIMIT = 60;
    protected const COOLDOWN_AUTH       = 3600;
    protected const COOLDOWN_SERVER     = 120;

    public function generate(string $prompt, int $maxTokens = 4096): string
    {
        $keys = $this->resolveKeys();

        if (empty($keys)) {
            throw new \Exception('No Gemini API keys are configured for letter generation.');
        }

        $endpoint  = $this->endpoint();
        $lastError = null;
        $attempted = [];

        foreach ($keys as $index => $apiKey) {
            $slot = 'key#' . ($index + 1) . ' (' . substr($apiKey, 0, 6) . '…' . substr($apiKey, -4) . ')';

            if ($this->isCoolingDown($apiKey)) {
                $attempted[] = "{$slot}: cooling down";
                continue;
            }

            try {
                $body = [
                    'contents' => [[
                        'parts' => [['text' => $prompt]],
                    ]],
                    'generationConfig' => [
                        'maxOutputTokens' => $maxTokens,
                        'temperature'     => 0.8,
                    ],
                ];

                $url = "{$endpoint}/{$this->model()}:generateContent?key={$apiKey}";

                $response = Http::timeout(90)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $body);

                if ($response->successful()) {
                    $text = $this->extractText($response->json());

                    if (is_string($text) && trim($text) !== '') {
                        $this->recordSuccess($apiKey);
                        Log::info('GeminiLetterRotator: success', [
                            'slot'  => $slot,
                            'chars' => strlen($text),
                        ]);
                        return $text;
                    }

                    Log::warning('GeminiLetterRotator: empty response', [
                        'slot' => $slot,
                        'body' => substr($response->body(), 0, 300),
                    ]);
                    $attempted[] = "{$slot}: empty response";
                    $lastError   = 'Model returned an empty response.';
                    continue;
                }

                $status   = $response->status();
                $cooldown = $this->cooldownFor($status);
                $this->startCoolDown($apiKey, $cooldown);

                $bodyPreview = substr($response->body(), 0, 200);
                $attempted[] = "{$slot}: HTTP {$status} ({$cooldown}s cooldown)";
                $lastError   = "HTTP {$status} from Gemini: {$bodyPreview}";

                Log::warning('GeminiLetterRotator: key failed', [
                    'slot'     => $slot,
                    'status'   => $status,
                    'cooldown' => $cooldown,
                    'body'     => $bodyPreview,
                ]);

            } catch (\Throwable $e) {
                $attempted[] = "{$slot}: " . $e->getMessage();
                $lastError   = $e->getMessage();

                Log::warning('GeminiLetterRotator: exception', [
                    'slot'  => $slot,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw new \Exception(
            'All Gemini letter keys failed. Tried: ' . implode(' | ', $attempted)
            . ' — last error: ' . ($lastError ?? 'unknown')
        );
    }

    protected function resolveKeys(): array
    {
        $primary   = config('ai.letter_gemini_keys.primary');
        $fallbacks = (array) config('ai.letter_gemini_keys.fallbacks', []);

        $keys = array_values(array_filter(
            array_merge([$primary], $fallbacks),
            fn($k) => is_string($k) && strlen(trim($k)) >= 20
        ));

        if (empty($keys)) {
            return [];
        }

        $preferred = Cache::get('letter_gemini:preferred');
        if ($preferred && in_array($preferred, $keys, true)) {
            $keys = array_values(array_merge(
                [$preferred],
                array_diff($keys, [$preferred])
            ));
        }

        return $keys;
    }

    protected function endpoint(): string
    {
        return rtrim((string) config('ai.letter_gemini_endpoint'), '/');
    }

    protected function model(): string
    {
        return (string) config('ai.letter_gemini_model', 'gemini-3.5-flash');
    }

    protected function extractText(array $data): ?string
    {
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    protected function isCoolingDown(string $apiKey): bool
    {
        return Cache::has($this->cacheKey($apiKey));
    }

    protected function startCoolDown(string $apiKey, int $seconds): void
    {
        if ($seconds > 0) {
            Cache::put($this->cacheKey($apiKey), true, $seconds);
        }
    }

    protected function recordSuccess(string $apiKey): void
    {
        Cache::put('letter_gemini:preferred', $apiKey, 3600);
        Cache::forget($this->cacheKey($apiKey));
    }

    protected function cacheKey(string $apiKey): string
    {
        return 'gemini_letter_cooldown:' . md5($apiKey);
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