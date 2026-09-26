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

        'ats_status',
        'employer_rating',
        'employer_notes',
        'reviewed_by',
        'reviewed_at',

        'ai_score',
        'ai_recommendation',
        'ai_summary',
        'ai_strengths',
        'ai_gaps',
        'ai_red_flags',
        'ai_screened_at',
        'ai_job_fingerprint',

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

        'employer_rating' => 'integer',
        'reviewed_at'     => 'datetime',

        'ai_score'         => 'integer',
        'ai_strengths'     => 'array',
        'ai_gaps'          => 'array',
        'ai_red_flags'     => 'array',
        'ai_screened_at'   => 'datetime',
    ];

    public function seekerProfile()
    {
        return $this->belongsTo(SeekerProfile::class);
    }

    public function jobPost()
    {
        return $this->belongsTo(JobPost::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    // Add accessor for badge
    public function getAtsStatusBadgeAttribute(): string
    {
        $map = [
            'new'         => ['light-primary',  'New'],
            'screening'   => ['light-info',     'Screening'],
            'shortlisted' => ['light-success',  'Shortlisted'],
            'interview'   => ['light-warning',  'Interview'],
            'offer'       => ['light-warning',  'Offer'],
            'hired'       => ['light-success',  'Hired'],
            'rejected'    => ['light-danger',   'Rejected'],
            'withdrawn'   => ['light-secondary','Withdrawn'],
        ];

        [$color, $label] = $map[$this->ats_status] ?? ['light-secondary', ucfirst($this->ats_status)];
        return "<span class=\"badge badge-{$color}\">{$label}</span>";
    }

    public function getAiRecommendationBadgeAttribute(): string
    {
        return match ($this->ai_recommendation) {
            'strong_yes' => '<span class="badge badge-light-success">🟢 Strong Yes</span>',
            'maybe'      => '<span class="badge badge-light-warning">🟡 Maybe</span>',
            'no'         => '<span class="badge badge-light-danger">🔴 No</span>',
            default      => '<span class="badge badge-light-secondary">Not screened</span>',
        };
    }

    public function getAiScoreColorAttribute(): string
    {
        $s = $this->ai_score;
        if ($s === null) return 'secondary';
        if ($s >= 80) return 'success';
        if ($s >= 60) return 'primary';
        if ($s >= 40) return 'warning';
        return 'danger';
    }

    /**
     * Is the screening stale? (Job description changed since last screening)
     */
    public function getAiScreeningStaleAttribute(): bool
    {
        if (!$this->ai_screened_at || !$this->ai_job_fingerprint) return true;

        $currentFingerprint = $this->computeJobFingerprint();
        return $currentFingerprint !== $this->ai_job_fingerprint;
    }

    public function computeJobFingerprint(): string
    {
        $job = $this->jobPost;
        if (!$job) return '';

        return hash('sha256', implode('|', [
            $job->job_title ?? '',
            strip_tags($job->job_description ?? ''),
            strip_tags($job->responsibilities ?? ''),
            strip_tags($job->qualifications ?? ''),
            $job->skills ?? '',
        ]));
    }

}