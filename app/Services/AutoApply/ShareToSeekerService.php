<?php

namespace App\Services\AutoApply;

use App\Models\Job\JobPost;
use App\Models\SeekerProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ShareToSeekerService
{
    public function send(SeekerProfile $seeker, JobPost $job, string $cvUrl, string $coverUrl): bool
    {
        $token   = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_id');
        if (!$token || !$phoneId) return false;

        $to = $this->normalizePhone($seeker->user->phone ?? $seeker->phone);
        if (!$to) return false;

        $name = $seeker->first_name ?: 'there';
        $text = "*Hi {$name},*\n\n"
              . "We just submitted your application for *{$job->job_title}* at " . (optional($job->company)->name ?? 'the company') . ".\n\n"
              . "Here are the documents we sent:\n"
              . "📄 View CV: {$cvUrl}\n"
              . "✉️ View Cover Letter: {$coverUrl}\n\n"
              . "Good luck!";

        try {
            Http::withToken($token)
                ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'text',
                    'text'              => ['body' => $text],
                ])
                ->throw();

            return true;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp Share Failed: ' . $e->getMessage());
            return false;
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $digits = preg_replace('/[^0-9]/', '', $phone);
        return $digits ?: null;
    }
}