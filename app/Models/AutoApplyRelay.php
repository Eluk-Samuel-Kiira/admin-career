<?php

namespace App\Models;

use App\Models\Job\JobPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoApplyRelay extends Model
{
    protected $table = 'auto_apply_relays';

    protected $fillable = [
        'auto_apply_run_id',
        'user_id',
        'job_post_id',
        'reason',
        'channels_attempted',
        'channels_sent',
        'cv_path',
        'tailored_cv_html',     // ← ADDED
        'cover_letter_html',    // ← ADDED
        'status',
        'error_message',
        'sent_at',
        'send_attempts',
        'last_attempt_at',
        'next_retry_at',
    ];

    protected $casts = [
        'channels_attempted' => 'array',
        'channels_sent'      => 'array',
        'sent_at'            => 'datetime',
        'last_attempt_at' => 'datetime',
        'next_retry_at'   => 'datetime',

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

    public function externalDrafts(): HasMany
    {
        return $this->hasMany(AutoApplyExternalDraft::class, 'auto_apply_run_id');
    }
}