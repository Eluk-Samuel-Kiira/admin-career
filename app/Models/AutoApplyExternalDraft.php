<?php

namespace App\Models;

use App\Models\Job\JobPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoApplyExternalDraft extends Model
{
    protected $table = 'auto_apply_external_drafts';

    protected $fillable = [
        'auto_apply_run_id',
        'user_id',
        'job_post_id',
        'tailored_cv_html',
        'cover_letter_html',
        'external_url',
        'status',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AutoApplyRun::class, 'auto_apply_run_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JobPost::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        $map = [
            'drafted' => ['light-info', 'Drafted'],
            'applied' => ['light-success', 'Applied'],
        ];

        [$color, $label] = $map[$this->status] ?? ['light-secondary', ucfirst($this->status)];
        return "<span class=\"badge badge-{$color}\">{$label}</span>";
    }
}