<?php

namespace App\Services\AutoApply\Channels;

use App\Mail\AutoApply\EmployerApplicationMail;
use App\Models\Job\JobPost;
use App\Models\Job\JobSeekerJob;
use App\Models\SeekerProfile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class EmailChannel
{
    public function submit(
        SeekerProfile $seeker,
        JobPost $job,
        array $cv,
        ?string $coverHtml,
        array $jobContext,
    ): void {
        $coverPath = null;
        if ($coverHtml) {
            $coverPath = $this->renderCoverPdf($seeker, $coverHtml, $jobContext);
        }

        Mail::to($job->email)->send(new EmployerApplicationMail(
            seeker:      $seeker,
            job:         $job,
            cvPath:      $cv['path'],
            coverPath:   $coverPath,
            coverHtml:   $coverHtml,
            jobContext:  $jobContext,
        ));

        // Write the application row *after* the mail send succeeds
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
            'notes'        => 'Auto-applied via email on ' . now()->toDateTimeString(),
        ]);
        $row->save();

        JobPost::where('id', $job->id)->increment('application_count');
    }

    protected function renderCoverPdf(SeekerProfile $seeker, string $html, array $jobContext): string
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.auto-apply.tailored-cover-letter', [
            'content' => $html,
            'seeker'  => $seeker,
            'job'     => $jobContext,
        ])->setPaper('a4');

        $path = 'auto-apply/' . \Illuminate\Support\Str::uuid() . '/cover-letter.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}