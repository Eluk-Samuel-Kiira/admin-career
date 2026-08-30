<?php

namespace App\Models\Job;

use App\Models\SeekerProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobSeekerJob extends Model
{
    use HasFactory;

    protected $table = 'job_seeker_jobs';

    protected $fillable = [
        'seeker_profile_id',
        'job_post_id',
        'is_saved',
        'is_applied',
        'is_called_for_interview',
        'is_got_job',
        'is_rejected',
        'is_viewed',
        'application_letter',
        'cover_letter',
        'cv_used_path',
        'applied_at',
        'saved_at',
        'interview_date',
        'interview_notes',
        'notes',
    ];

    protected $casts = [
        'is_saved' => 'boolean',
        'is_applied' => 'boolean',
        'is_called_for_interview' => 'boolean',
        'is_got_job' => 'boolean',
        'is_rejected' => 'boolean',
        'is_viewed' => 'boolean',
        'applied_at' => 'datetime',
        'saved_at' => 'datetime',
        'interview_date' => 'datetime',
    ];

    public function seekerProfile()
    {
        return $this->belongsTo(SeekerProfile::class);
    }

    public function jobPost()
    {
        return $this->belongsTo(JobPost::class);
    }
}