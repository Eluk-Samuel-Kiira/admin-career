<?php

namespace App\Services\AutoApply\Channels;

use App\Mail\AutoApply\TailoredApplicationMail;
use App\Models\AutoApplyRelay;
use App\Models\AutoApplyRun;
use App\Models\Job\JobPost;
use App\Models\SeekerProfile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class RelayChannel
{
    public function submit(
        AutoApplyRun $run,
        SeekerProfile $seeker,
        JobPost $job,
        array $cv,
        ?string $coverHtml,
        array $jobContext,
    ): void {
        $prefs    = $seeker->job_preferences_array;
        $channels = [];

        if (!empty($prefs['send_alert_by_email']) || !isset($prefs['send_alert_by_email'])) {
            $channels[] = 'email';
        }
        if (!empty($prefs['send_alert_by_whatsapp'])) {
            $channels[] = 'whatsapp';
        }
        if (!empty($prefs['send_alert_by_telegram'])) {
            $channels[] = 'telegram';
        }

        if (empty($channels)) {
            $channels = ['email'];
        }

        $relay = AutoApplyRelay::create([
            'auto_apply_run_id'  => $run->id,
            'user_id'            => $seeker->user_id,
            'job_post_id'        => $job->id,
            'reason'             => 'no_direct_channel',
            'channels_attempted' => $channels,
            'channels_sent'      => [],
            'cv_path'            => $cv['path'],
            'cover_letter'       => $coverHtml,
            'status'             => 'queued',
        ]);

        $sent = [];

        foreach ($channels as $channel) {
            try {
                match ($channel) {
                    'email'    => $this->sendEmail($seeker, $job, $cv, $coverHtml),
                    'whatsapp' => $this->sendWhatsApp($seeker, $job, $cv),
                    'telegram' => $this->sendTelegram($seeker, $job, $cv),
                    default    => null,
                };
                $sent[] = $channel;
            } catch (\Throwable $e) {
                Log::warning("Relay failed on {$channel}", [
                    'user_id' => $seeker->user_id,
                    'job_id'  => $job->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $relay->update([
            'channels_sent' => $sent,
            'status'        => count($sent) === count($channels) ? 'sent'
                             : (count($sent) > 0 ? 'partial' : 'failed'),
            'sent_at'       => count($sent) > 0 ? now() : null,
        ]);
    }

    protected function sendEmail(SeekerProfile $seeker, JobPost $job, array $cv, ?string $coverHtml): void
    {
        $coverPath = null;
        if ($coverHtml) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.auto-apply.tailored-cover-letter', [
                'content' => $coverHtml,
                'seeker'  => $seeker,
                'job'     => ['job_title' => $job->job_title],
            ])->setPaper('a4');

            $coverPath = 'auto-apply/' . \Illuminate\Support\Str::uuid() . '/cover-letter.pdf';
            Storage::disk('public')->put($coverPath, $pdf->output());
        }

        Mail::to($seeker->user->email)->send(new TailoredApplicationMail(
            seeker:      $seeker,
            job:         $job,
            cvPath:      $cv['path'],
            coverPath:   $coverPath,
            coverHtml:   $coverHtml,
        ));
    }

    protected function sendWhatsApp(SeekerProfile $seeker, JobPost $job, array $cv): void
    {
        $token   = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_id');
        if (!$token || !$phoneId) throw new \Exception('WhatsApp not configured.');

        $to = $this->normalizePhone($seeker->user->phone ?? $seeker->phone);
        if (!$to) throw new \Exception('No phone for seeker.');

        $text = "*Hi {$seeker->full_name},*\n\n"
              . "Apply today: *{$job->job_title}*\n"
              . "Company: " . (optional($job->company)->name ?? '—') . "\n\n"
              . "I have prepared your CV and cover letter for this role. Open the job and submit:\n"
              . url('/job/' . $job->slug);

        \Illuminate\Support\Facades\Http::withToken($token)
            ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $text],
            ])
            ->throw();
    }

    protected function sendTelegram(SeekerProfile $seeker, JobPost $job, array $cv): void
    {
        $bot = config('services.telegram.bot_token');
        if (!$bot) throw new \Exception('Telegram not configured.');

        $chatId = $seeker->user->telegram_chat_id ?? null;
        if (!$chatId) throw new \Exception('No Telegram chat for seeker.');

        $text = "*Hi {$seeker->full_name},*\n"
              . "Apply today: *{$job->job_title}*\n"
              . "Company: " . (optional($job->company)->name ?? '—') . "\n"
              . "[Open the job](" . url('/job/' . $job->slug) . ")";

        \Illuminate\Support\Facades\Http::post(
            "https://api.telegram.org/bot{$bot}/sendMessage",
            [
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => 'Markdown',
            ]
        )->throw();
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $digits = preg_replace('/[^0-9]/', '', $phone);
        return $digits ?: null;
    }
}