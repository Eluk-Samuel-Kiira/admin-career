<?php

namespace App\Jobs;

use App\Models\AlertDispatch;
use App\Models\SeekerProfile;
use App\Services\Alerts\AlertResolver;
use App\Services\Alerts\Channels\EmailChannel;
use App\Services\Alerts\Channels\TelegramChannel;
use App\Services\Alerts\Channels\WhatsAppChannel;
use App\Services\Alerts\JobMatcher;
use App\Services\Subscription\SubscriptionGate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class SendSeekerAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 90;
    public int $tries = 50;
    public int $maxExceptions = 3;

    public function __construct(
        public int $seekerProfileId,
        public array $channels = ['email'],
        public string $mode = 'real',
    ) {}

    // ─────────────────────────────────────────────────────────
    // THROTTLE: applies only to email channel
    // ─────────────────────────────────────────────────────────
    public function middleware(): array
    {
        $limiter = $this->emailLimiterName();

        return [
            (new RateLimited($limiter))->releaseAfter(60),
        ];
    }

    protected function emailLimiterName(): string
    {
        $seeker = SeekerProfile::with('user')->find($this->seekerProfileId);

        $candidates = [
            $seeker?->preferred_country_code,
            $seeker?->user?->country_code,
            $seeker?->country,
        ];

        $code = null;
        foreach ($candidates as $c) {
            if (!$c) continue;
            $c = strtoupper(trim((string) $c));
            if (preg_match('/^[A-Z]{2}$/', $c)) { $code = $c; break; }
            $resolved = \App\Models\Job\Country::whereRaw('LOWER(name) = ?', [strtolower(trim((string) $c))])
                ->value('code');
            if ($resolved) { $code = strtoupper($resolved); break; }
        }

        return match ($code) {
            'AU' => 'alert-emails-au',
            'UG' => 'alert-emails-ug',
            default => 'alert-emails-default',
        };
    }

    public function handle(
        JobMatcher $matcher,
        AlertResolver $resolver,
        SubscriptionGate $gate,
        EmailChannel $email,
        WhatsAppChannel $whatsapp,
        TelegramChannel $telegram,
    ): void {
        $seeker = SeekerProfile::with('user')->find($this->seekerProfileId);
        if (!$seeker || !$seeker->user) {
            \Log::warning('SendSeekerAlertJob: seeker/user missing', [
                'seeker_profile_id' => $this->seekerProfileId,
            ]);
            return;
        }

        $user  = $seeker->user;
        $prefs = $seeker->job_preferences_array;

        $decision = $resolver->decide($seeker, $prefs);
        if ($decision['kind'] === 'skip') return;

        $jobs = $decision['kind'] === 'jobs'
            ? $matcher->match($seeker, $prefs, JobMatcher::MAX_JOBS)
            : collect();

        if ($decision['kind'] === 'jobs' && $jobs->isEmpty()) return;

        foreach ($this->channels as $channel) {
            $allowed = match ($channel) {
                'email'    => $prefs['send_alert_by_email'] ?? true,
                'whatsapp' => ($prefs['send_alert_by_whatsapp'] ?? false) && $gate->canWhatsApp($user),
                'telegram' => ($prefs['send_alert_by_telegram'] ?? false) && $gate->canTelegram($user),
                default    => false,
            };
            if (!$allowed) continue;

            $dispatch = AlertDispatch::create([
                'user_id'   => $user->id,
                'channel'   => $channel,
                'kind'      => $decision['kind'],
                'jobs_sent' => $jobs->count(),
                'job_ids'   => $jobs->pluck('id')->all(),
                'status'    => 'queued',
            ]);

            if ($this->mode === 'dry_run') {
                $dispatch->update(['status' => 'sent', 'sent_at' => now()]);
                continue;
            }

            try {
                match ($channel) {
                    'email'    => $email->send($seeker, $decision, $jobs),
                    'whatsapp' => $whatsapp->send($seeker, $decision, $jobs),
                    'telegram' => $telegram->send($seeker, $decision, $jobs),
                };

                $dispatch->update(['status' => 'sent', 'sent_at' => now()]);

            } catch (\Throwable $e) {
                if ($this->isRateLimitError($e)) {
                    \Log::notice('SendSeekerAlertJob: rate limited, releasing', [
                        'dispatch_id' => $dispatch->id,
                        'channel'     => $channel,
                        'error'       => $e->getMessage(),
                    ]);

                    $dispatch->update([
                        'status'        => 'queued',
                        'error_message' => 'Rate limited, retrying: ' . $e->getMessage(),
                    ]);

                    $this->release(120);
                    return;
                }

                \Log::error("Alert dispatch failed on {$channel}", [
                    'dispatch_id' => $dispatch->id,
                    'error'       => $e->getMessage(),
                    'trace'       => $e->getTraceAsString(),
                ]);

                $dispatch->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function isRateLimitError(\Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());

        return str_contains($msg, 'too many')
            || str_contains($msg, 'rate limit')
            || str_contains($msg, 'rate-limit')
            || str_contains($msg, 'throttl')
            || str_contains($msg, '421')
            || str_contains($msg, '450')
            || str_contains($msg, '451')
            || str_contains($msg, '452')
            || str_contains($msg, '454')
            || str_contains($msg, '554');
    }
}