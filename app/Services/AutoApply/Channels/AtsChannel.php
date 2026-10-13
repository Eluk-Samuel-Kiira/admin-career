<?php

namespace App\Services\AutoApply\Channels;

use App\Models\Job\JobPost;
use App\Models\Job\JobSeekerJob;
use App\Models\SeekerProfile;

class AtsChannel
{
    public function submit(SeekerProfile $seeker, JobPost $job, array $cv, ?string $coverHtml): void
    {
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
            'notes'        => 'Auto-applied by system on ' . now()->toDateTimeString(),
        ]);
        $row->save();

        JobPost::where('id', $job->id)->increment('application_count');
    }
}