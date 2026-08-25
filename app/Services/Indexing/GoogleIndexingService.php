<?php

namespace App\Services\Indexing;

use App\Models\Job\JobPost;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GoogleIndexingService
 * ─────────────────────────────────────────────────────────────
 * Submits selected JobPost URLs to Google's Indexing API.
 * - Uses JobPost::generateUrl() so every job gets its correct
 *   country-domain URL automatically (no manual slug suffixing).
 * - Only flips is_indexed / last_indexed_at when Google confirms
 *   success (HTTP 200). Failures are left untouched so they can
 *   be retried later.
 * - Enforces the 200 URLs/day Google quota via cache.
 * - No email notifications — silent, dashboard-only.
 * ─────────────────────────────────────────────────────────────
 */
class GoogleIndexingService
{
    private const DAILY_LIMIT     = 200;
    private const QUOTA_CACHE_KEY = 'google_indexing_daily_quota';
    private const TOKEN_CACHE_KEY = 'google_indexing_access_token';
    private const API_ENDPOINT    = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    /**
     * Submit a batch of job IDs to Google. Respects remaining daily quota
     * (silently truncates the list if it exceeds what's left).
     */
    public function submitBatch(array $jobIds): array
    {
        $remaining = $this->getRemainingQuota();

        if ($remaining <= 0) {
            return [
                'success'         => false,
                'submitted'       => 0,
                'failed'          => 0,
                'skipped'         => count($jobIds),
                'message'         => 'Daily Google quota of ' . self::DAILY_LIMIT . ' reached. Resets at midnight UTC.',
                'quota_used'      => $this->getQuotaUsed(),
                'quota_remaining' => 0,
                'results'         => [],
            ];
        }

        $skipped = max(0, count($jobIds) - $remaining);
        $jobIds  = array_slice($jobIds, 0, $remaining);

        $jobs = JobPost::whereIn('id', $jobIds)
            ->where('is_active', true)
            ->get();

        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success'   => false,
                'submitted' => 0,
                'failed'    => 0,
                'message'   => 'Google Indexing API not configured. Upload storage/app/google-service-account.json',
                'results'   => [],
            ];
        }

        $results   = [];
        $submitted = 0;
        $failed    = 0;

        foreach ($jobs as $job) {
            $url = $job->generateUrl(); // already country-correct

            $result = $this->callGoogleApi($url, $token);

            if ($result['success']) {
                $submitted++;
                $this->incrementQuota();

                // Only update on confirmed success
                $job->is_indexed      = true;
                $job->last_indexed_at = now();
                $job->save();
            } else {
                $failed++;
                // Leave is_indexed / last_indexed_at untouched — retryable later
            }

            $results[] = [
                'job_id'  => $job->id,
                'title'   => $job->job_title,
                'url'     => $url,
                'success' => $result['success'],
                'status'  => $result['status'],
                'message' => $result['message'],
            ];

            usleep(200000); // 200ms between requests, stay within rate limits
        }

        return [
            'success'         => $submitted > 0,
            'submitted'       => $submitted,
            'failed'          => $failed,
            'skipped'         => $skipped,
            'total_jobs'      => $jobs->count(),
            'quota_used'      => $this->getQuotaUsed(),
            'quota_remaining' => $this->getRemainingQuota(),
            'results'         => $results,
        ];
    }

    private function callGoogleApi(string $url, string $token): array
    {
        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->post(self::API_ENDPOINT, [
                    'url'  => $url,
                    'type' => 'URL_UPDATED',
                ]);

            $status  = $response->status();
            $body    = $response->json();
            $success = $response->successful();

            $message = match ($status) {
                200     => 'URL submitted to Google index queue',
                400     => 'Bad request — ' . ($body['error']['message'] ?? 'invalid format'),
                401     => 'Unauthorized — check service account credentials',
                403     => 'Forbidden — add the service account as Owner in Search Console for this domain',
                429     => 'Quota exceeded — too many requests',
                default => "HTTP {$status}: " . ($body['error']['message'] ?? 'Unknown'),
            };

            // Log::info("GOOGLE INDEXING: HTTP {$status} — {$url}");

            return [
                'success' => $success,
                'status'  => $status,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error("GOOGLE INDEXING exception for {$url}: " . $e->getMessage());
            return [
                'success' => false,
                'status'  => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Stats for the dashboard — quota only. Indexed/unindexed counts
     * already come from JobPost via SitemapController::getStatistics().
     */
    public function getStats(): array
    {
        return [
            'quota_used'      => $this->getQuotaUsed(),
            'quota_remaining' => $this->getRemainingQuota(),
            'quota_limit'     => self::DAILY_LIMIT,
            'api_configured'  => file_exists(storage_path('app/google-service-account.json')),
        ];
    }

    // ─── Google JWT auth ──────────────────────────────────────
    private function getAccessToken(): ?string
    {
        if ($cached = Cache::get(self::TOKEN_CACHE_KEY)) {
            return $cached;
        }

        $keyPath = storage_path('app/google-service-account.json');
        if (!file_exists($keyPath)) {
            Log::warning('GOOGLE INDEXING: Service account file not found.');
            return null;
        }

        try {
            $key = json_decode(file_get_contents($keyPath), true);
            $now = time();

            $header  = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $payload = $this->base64UrlEncode(json_encode([
                'iss'   => $key['client_email'],
                'scope' => 'https://www.googleapis.com/auth/indexing',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'exp'   => $now + 3600,
                'iat'   => $now,
            ]));

            $signingInput = "{$header}.{$payload}";
            $signature    = '';
            openssl_sign($signingInput, $signature, $key['private_key'], 'SHA256');
            $jwt = $signingInput . '.' . $this->base64UrlEncode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if (!$response->successful()) {
                Log::error('GOOGLE INDEXING: OAuth token request failed — ' . $response->body());
                return null;
            }

            $token     = $response->json('access_token');
            $expiresIn = $response->json('expires_in', 3500);

            Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addSeconds($expiresIn - 60));
            return $token;
        } catch (\Exception $e) {
            Log::error('GOOGLE INDEXING: JWT auth failed — ' . $e->getMessage());
            return null;
        }
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // ─── Quota management ─────────────────────────────────────
    private function getQuotaUsed(): int
    {
        return (int) Cache::get(self::QUOTA_CACHE_KEY, 0);
    }

    private function getRemainingQuota(): int
    {
        return max(0, self::DAILY_LIMIT - $this->getQuotaUsed());
    }

    private function incrementQuota(): void
    {
        $secondsUntilMidnight = strtotime('tomorrow midnight UTC') - time();

        if (Cache::has(self::QUOTA_CACHE_KEY)) {
            Cache::increment(self::QUOTA_CACHE_KEY);
        } else {
            Cache::put(self::QUOTA_CACHE_KEY, 1, $secondsUntilMidnight);
        }
    }
}