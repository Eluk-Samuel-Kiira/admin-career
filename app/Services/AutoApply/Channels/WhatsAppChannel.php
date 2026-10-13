<?php

namespace App\Services\AutoApply\Channels;

use App\Models\Job\JobPost;
use App\Models\Job\JobSeekerJob;
use App\Models\SeekerProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

class WhatsAppChannel
{
    public function submit(
        SeekerProfile $seeker,
        JobPost $job,
        array $cv,
        ?string $coverHtml,
        array $jobContext,
    ): void {
        $token   = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_id');

        if (!$token || !$phoneId) {
            throw new \Exception('WhatsApp channel not configured.');
        }

        $to = $this->normalizePhone($job->telephone);
        if (!$to) {
            throw new \Exception('No valid telephone on job.');
        }

        // Signed, expiring link to the tailored CV.
        $cvUrl = URL::temporarySignedRoute(
            'auto-apply.cv.download',
            now()->addDays(7),
            ['path' => base64_encode($cv['path'])]
        );

        $name = $seeker->full_name;
        $text = "*Application for {$job->job_title}*\n\n"
              . "Hello, my name is {$name} and I am applying for this role.\n\n"
              . "You can view my tailored CV here:\n{$cvUrl}\n\n"
              . "I am available for interview at your earliest convenience.";

        Http::withToken($token)
            ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $text],
            ])
            ->throw();

        $row = JobSeekerJob::firstOrNew([
            'seeker_profile_id' => $seeker->id,
            'job_post_id'       => $job->id,
        ]);

        if ($row->is_applied) return;

        $row->fill([
            'is_applied'   => true,
            'applied_at'   => now(),
            'ats_status'   => 'new',
            'cv_used_path' => $cv['path'],
            'cover_letter' => $coverHtml,
            'notes'        => 'Auto-applied via WhatsApp on ' . now()->toDateTimeString(),
        ]);
        $row->save();

        JobPost::where('id', $job->id)->increment('application_count');
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $digits = preg_replace('/[^0-9]/', '', $phone);
        return $digits ?: null;
    }
}