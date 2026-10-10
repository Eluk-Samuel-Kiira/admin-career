<?php

namespace App\Models\Hiring;

use Illuminate\Database\Eloquent\Model;

class HiringRunCandidate extends Model
{
    protected $table = 'hiring_run_candidates';

    protected $fillable = [
        'hiring_run_id', 'original_name', 'stored_path', 'size', 'mime_type',
        'candidate_name', 'candidate_email', 'candidate_phone',
        'score', 'recommendation', 'summary',
        'strengths', 'gaps', 'red_flags', 'matched_skills', 'missing_skills',
        'years_of_experience', 'highest_education', 'current_title',
        'status', 'error_message', 'screened_at',
    ];

    protected $casts = [
        'strengths'      => 'array',
        'gaps'           => 'array',
        'red_flags'      => 'array',
        'matched_skills' => 'array',
        'missing_skills' => 'array',
        'screened_at'    => 'datetime',
        'score'          => 'integer',
    ];

    public function run() { return $this->belongsTo(HiringRun::class, 'hiring_run_id'); }

    public function getRecommendationBadgeAttribute(): string
    {
        return match ($this->recommendation) {
            'strong_yes' => '<span class="badge badge-light-success">Strong Yes</span>',
            'maybe'      => '<span class="badge badge-light-warning">Maybe</span>',
            'no'         => '<span class="badge badge-light-danger">No</span>',
            default      => '<span class="badge badge-light-secondary">Pending</span>',
        };
    }

    public function getScoreColorAttribute(): string
    {
        $s = $this->score;
        if ($s === null) return 'secondary';
        if ($s >= 80) return 'success';
        if ($s >= 60) return 'primary';
        if ($s >= 40) return 'warning';
        return 'danger';
    }
}