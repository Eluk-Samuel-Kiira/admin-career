<?php

namespace App\Jobs;

use App\Models\AlertDispatch;
use App\Models\SeekerProfile;
use App\Services\Alerts\JobMatcher;
use App\Services\Alerts\AlertResolver;
use App\Services\Alerts\Channels\EmailChannel;
use App\Services\Alerts\Channels\WhatsAppChannel;
use App\Services\Alerts\Channels\TelegramChannel;
use App\Services\Subscription\SubscriptionGate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSeekerAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 2;

    public function __construct(
        public int $seekerProfileId,
        public array $channels = ['email'],
        public string $mode = 'real',
    ) {}

    public function handle(
        JobMatcher $matcher,
        AlertResolver $resolver,
        SubscriptionGate $gate,
        EmailChannel $email,
        WhatsAppChannel $whatsapp,
        TelegramChannel $telegram,
    ): void {
        // \Log::info('SendSeekerAlertJob BEGIN', [
        //     'seeker_profile_id' => $this->seekerProfileId,
        //     'channels'          => $this->channels,
        //     'mode'              => $this->mode,
        // ]);

        // ── 1. Load seeker ────────────────────────────────────────
        $seeker = SeekerProfile::with('user')->find($this->seekerProfileId);

        if (!$seeker) {
            \Log::warning('SendSeekerAlertJob: seeker not found', [
                'seeker_profile_id' => $this->seekerProfileId,
            ]);
            return;
        }

        if (!$seeker->user) {
            \Log::warning('SendSeekerAlertJob: user not found for seeker', [
                'seeker_profile_id' => $this->seekerProfileId,
                'user_id'           => $seeker->user_id,
            ]);
            return;
        }

        $user  = $seeker->user;
        $prefs = $seeker->job_preferences_array;

        // \Log::info('SendSeekerAlertJob: loaded', [
        //     'seeker_profile_id' => $seeker->id,
        //     'user_id'           => $user->id,
        //     'user_email'        => $user->email,
        //     'is_active'         => $user->is_active,
        //     'prefs'             => $prefs,
        // ]);

        // ── 2. Decide what kind of alert ──────────────────────────
        $decision = $resolver->decide($seeker, $prefs);

        // \Log::info('SendSeekerAlertJob: decision', [
        //     'seeker_profile_id' => $seeker->id,
        //     'kind'              => $decision['kind'],
        //     'reason'            => $decision['reason'] ?? null,
        // ]);

        if ($decision['kind'] === 'skip') {
            \Log::warning('SendSeekerAlertJob: skipping', [
                'seeker_profile_id' => $seeker->id,
                'reason'            => $decision['reason'] ?? null,
            ]);
            return;
        }

        // ── 3. Resolve matching jobs ──────────────────────────────
        $jobs = $decision['kind'] === 'jobs'
            ? $matcher->match($seeker, $prefs, JobMatcher::MAX_JOBS)
            : collect();

        // \Log::info('SendSeekerAlertJob: jobs resolved', [
        //     'seeker_profile_id' => $seeker->id,
        //     'jobs_count'        => $jobs->count(),
        //     'job_ids'           => $jobs->pluck('id')->toArray(),
        // ]);

        if ($decision['kind'] === 'jobs' && $jobs->isEmpty()) {
            \Log::warning('SendSeekerAlertJob: no matching jobs, exiting', [
                'seeker_profile_id' => $seeker->id,
            ]);
            return;
        }

        // ── 4. Loop channels ─────────────────────────────────────
        foreach ($this->channels as $channel) {
            $allowed = match ($channel) {
                'email'    => $prefs['send_alert_by_email'] ?? true,
                'whatsapp' => ($prefs['send_alert_by_whatsapp'] ?? false) && $gate->canWhatsApp($user),
                'telegram' => ($prefs['send_alert_by_telegram'] ?? false) && $gate->canTelegram($user),
                default    => false,
            };

            // \Log::info('SendSeekerAlertJob: channel check', [
            //     'seeker_profile_id' => $seeker->id,
            //     'channel'           => $channel,
            //     'allowed'           => $allowed,
            // ]);

            if (!$allowed) continue;

            // Create the log row first, so even a failed send is recorded
            $dispatch = AlertDispatch::create([
                'user_id'   => $user->id,
                'channel'   => $channel,
                'kind'      => $decision['kind'],
                'jobs_sent' => $jobs->count(),
                'job_ids'   => $jobs->pluck('id')->all(),
                'status'    => 'queued',
            ]);

            // \Log::info('SendSeekerAlertJob: dispatch row created', [
            //     'dispatch_id' => $dispatch->id,
            //     'channel'     => $channel,
            // ]);

            if ($this->mode === 'dry_run') {
                $dispatch->update(['status' => 'sent', 'sent_at' => now()]);
                continue;
            }

            try {
                // \Log::info('SendSeekerAlertJob: about to send', [
                //     'dispatch_id' => $dispatch->id,
                //     'channel'     => $channel,
                // ]);

                match ($channel) {
                    'email'    => $email->send($seeker, $decision, $jobs),
                    'whatsapp' => $whatsapp->send($seeker, $decision, $jobs),
                    'telegram' => $telegram->send($seeker, $decision, $jobs),
                };

                // \Log::info('SendSeekerAlertJob: send succeeded', [
                //     'dispatch_id' => $dispatch->id,
                //     'channel'     => $channel,
                // ]);

                $dispatch->update(['status' => 'sent', 'sent_at' => now()]);

            } catch (\Throwable $e) {
                \Log::error("Alert dispatch failed on {$channel}", [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);

                $dispatch->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }

}