<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutoApplyRun extends Model
{
    protected $table = 'auto_apply_runs';

    protected $fillable = [
        'user_id', 'seeker_profile_id', 'run_date',
        'quota_total', 'quota_used_before', 'quota_remaining',
        'jobs_scanned', 'dispatched', 'applied_count',
        'relayed_count', 'skipped_count', 'failed_count',
        'status', 'error_message', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'run_date'     => 'date',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seekerProfile(): BelongsTo
    {
        return $this->belongsTo(SeekerProfile::class);
    }

    public function relays(): HasMany
    {
        return $this->hasMany(AutoApplyRelay::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        $map = [
            'queued'            => ['light-secondary', 'Queued'],
            'processing'        => ['light-info',      'Processing'],
            'completed'         => ['light-success',   'Completed'],
            'failed'            => ['light-danger',    'Failed'],
            'quota_exhausted'   => ['light-warning',   'Quota Exhausted'],
            'no_cv'             => ['light-warning',   'No CV'],
            'no_preferences'    => ['light-warning',   'No Preferences'],
        ];

        [$color, $label] = $map[$this->status] ?? ['light-secondary', ucfirst($this->status)];
        return "<span class=\"badge badge-{$color}\">{$label}</span>";
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'quota_exhausted', 'no_cv', 'no_preferences'], true);
    }
}