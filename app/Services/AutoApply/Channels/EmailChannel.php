<?php

namespace App\Services\AutoApply\Channels;

use App\Mail\AutoApply\EmployerApplicationMail;
use App\Models\Job\JobPost;
use App\Models\Job\JobSeekerJob;
use App\Models\SeekerProfile;
use Illuminate\Support\Facades\Mail;

class EmailChannel
{
    public function submit(
        SeekerProfile $seeker,
        JobPost $job,
        array $cv,
        ?string $coverHtml,
        array $jobContext,
    ): void {
        // NO PDF generation here — we pass the HTML straight to the Mailable.
        // The Mailable will render → PDF → attach in memory during send.

        Mail::to($job->email)->send(new EmployerApplicationMail(
            seeker:     $seeker,
            job:        $job,
            cvHtml:     $cv['html'],
            coverHtml:  $coverHtml,
            jobContext: $jobContext,
        ));

        // Log the application
        $row = JobSeekerJob::firstOrNew([
            'seeker_profile_id' => $seeker->id,
            'job_post_id'       => $job->id,
        ]);

        if ($row->is_applied) return;

        $row->fill([
            'is_applied'   => true,
            'applied_at'   => now(),
            'ats_status'   => 'new',
            'cover_letter' => $coverHtml,
            'notes'        => 'Auto-applied via email on ' . now()->toDateTimeString(),
        ]);
        $row->save();

        JobPost::where('id', $job->id)->increment('application_count');
    }
}