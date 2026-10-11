<?php

namespace App\Services\Alerts\Channels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    /**
     * Send via WhatsApp Cloud API. Configure these in .env:
     *   WHATSAPP_TOKEN=...
     *   WHATSAPP_PHONE_ID=...
     *   WHATSAPP_TEMPLATE=...
     */
    public function send($seeker, array $decision, $jobs): void
    {
        $token     = config('services.whatsapp.token');
        $phoneId   = config('services.whatsapp.phone_id');

        if (!$token || !$phoneId) {
            throw new \Exception('WhatsApp channel is not configured.');
        }

        $to = $this->normalizePhone($seeker->phone ?? $seeker->user->phone);
        if (!$to) {
            throw new \Exception('No phone number on file.');
        }

        $message = $this->compose($seeker, $decision, $jobs);

        Http::withToken($token)
            ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $message],
            ])
            ->throw();
    }

    protected function compose($seeker, array $decision, $jobs): string
    {
        $name = $seeker->first_name ?: $seeker->user->first_name ?: 'there';

        if ($decision['kind'] === 'cv_prompt') {
            return "*Hi {$name},* you're missing out!\n\n"
                 . "Upload your CV so employers can find you and so you can receive job alerts tailored to you.\n\n"
                 . "Upload your CV: " . $this->proxyUrl('/cv/edit');
        }

        if ($decision['kind'] === 'profile_prompt') {
            return "*Hi {$name},* your profile is incomplete.\n\n"
                 . "Complete it so employers can notice you and so you receive the right jobs.\n\n"
                 . "Complete profile: " . $this->proxyUrl('/profile');
        }

        if ($decision['kind'] === 'preferences_prompt') {
            return "*Hi {$name},* you haven't set your job preferences yet.\n\n"
                 . "Set them so we only send you jobs you actually want.\n\n"
                 . "Set preferences: " . $this->proxyUrl('/alert/preferences');
        }

        // Normal job alert
        $lines = ["*Hi {$name}, here are some open positions for you:*", ''];

        foreach ($jobs->take(5) as $job) {
            $lines[] = '• *' . $job->job_title . '*';
            $lines[] = $this->jobUrl($job);
            $lines[] = '';
        }

        $lines[] = 'More jobs: ' . $this->proxyUrl('/jobs');

        return implode("\n", $lines);
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $digits = preg_replace('/[^0-9]/', '', $phone);
        return $digits ?: null;
    }

    protected function proxyUrl(string $path): string
    {
        return rtrim(config('app.proxy_url', config('app.url')), '/') . $path;
    }

    protected function jobUrl($job): string
    {
        return rtrim(config('app.proxy_url', config('app.url')), '/')
             . '/job/' . $job->slug;
    }
}