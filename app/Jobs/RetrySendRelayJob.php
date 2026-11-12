<?php

namespace App\Jobs;

use App\Models\AutoApplyRelay;
use App\Services\AutoApply\Channels\EmailChannel;
use App\Services\AutoApply\Channels\WhatsAppChannel;
use App\Models\SeekerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetrySendRelayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const MAX_ATTEMPTS = 3;
    public const RETRY_DELAYS = [300, 1800, 7200]; // 5min, 30min, 2hrs

    public function __construct(public int $relayId) {}

    public function handle(): void
    {
        $relay = AutoApplyRelay::find($this->relayId);
        if (!$relay) return;

        // Skip if already sent or explicitly marked failed
        if (in_array($relay->status, ['sent', 'failed'], true)) {
            return;
        }

        // Skip if we've exceeded max attempts
        if ($relay->send_attempts >= self::MAX_ATTEMPTS) {
            $relay->update([
                'status'        => 'failed',
                'error_message' => 'Max retry attempts reached (' . self::MAX_ATTEMPTS . ').',
                'next_retry_at' => null,
            ]);
            Log::error("AutoApply: Relay #{$relay->id} permanently failed after " . self::MAX_ATTEMPTS . " attempts.");
            return;
        }

        $seeker = SeekerProfile::where('user_id', $relay->user_id)->first();
        if (!$seeker) return;

        $channel = $relay->channels_attempted[0] ?? 'email';

        // ── ATTEMPT THE SEND ──
        try {
            $cv = [
                'html' => $relay->tailored_cv_html,
                'path' => $relay->cv_path ?? 'auto-apply/temp-cv.pdf',
            ];

            if ($channel === 'email') {
                app(EmailChannel::class)->submit(
                    $seeker,
                    $relay->jobPost,
                    $cv,
                    $relay->cover_letter_html,
                    []
                );
            } elseif ($channel === 'whatsapp') {
                app(WhatsAppChannel::class)->submit(
                    $seeker,
                    $relay->jobPost,
                    $cv,
                    $relay->cover_letter_html,
                    []
                );
            }

            // ── SUCCESS ──
            $relay->update([
                'status'          => 'sent',
                'sent_at'         => now(),
                'last_attempt_at' => now(),
                'next_retry_at'   => null,
                'error_message'   => null,
            ]);

            Log::info("AutoApply: Relay #{$relay->id} sent successfully on attempt " . ($relay->send_attempts + 1));

        } catch (\Throwable $e) {
            // ── FAILURE — schedule retry ──
            $attempt = $relay->send_attempts + 1;
            $delay = self::RETRY_DELAYS[$attempt - 1] ?? end(self::RETRY_DELAYS);

            $relay->update([
                'status'          => 'queued',  // stays queued, just delayed
                'send_attempts'   => $attempt,
                'last_attempt_at' => now(),
                'next_retry_at'   => now()->addSeconds($delay),
                'error_message'   => mb_substr($e->getMessage(), 0, 500),
            ]);

            Log::warning("AutoApply: Relay #{$relay->id} failed on attempt {$attempt}, retrying in {$delay}s", [
                'error' => $e->getMessage(),
            ]);

            // Re-dispatch this job for the retry (Laravel will delay it)
            if ($attempt < self::MAX_ATTEMPTS) {
                self::dispatch($relay->id)->delay(now()->addSeconds($delay));
            } else {
                $relay->update(['status' => 'failed', 'next_retry_at' => null]);
                Log::error("AutoApply: Relay #{$relay->id} permanently failed after {$attempt} attempts.");
            }
        }
    }
}