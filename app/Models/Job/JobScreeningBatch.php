<?php

namespace App\Models\Job;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JobScreeningBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'job_post_id',
        'user_id',
        'total',
        'processed',
        'failed',
        'status',
        'applicant_ids',
        'failed_ids',
        'job_fingerprint',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'applicant_ids' => 'array',
        'failed_ids'    => 'array',
        'total'         => 'integer',
        'processed'     => 'integer',
        'failed'        => 'integer',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // =================================================================
    // Relationships
    // =================================================================
    public function jobPost()
    {
        return $this->belongsTo(JobPost::class, 'job_post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // =================================================================
    // Progress helpers
    // =================================================================
    public function getProgressPercentAttribute(): int
    {
        if ($this->total === 0) return 0;
        return (int) round(($this->processed / $this->total) * 100);
    }

    public function getIsCompleteAttribute(): bool
    {
        return in_array($this->status, ['completed', 'failed'], true);
    }

    public function markStarted(): void
    {
        if ($this->status === 'queued') {
            $this->update([
                'status'     => 'processing',
                'started_at' => now(),
            ]);
        }
    }

    /**
     * Called by each job after it finishes (success OR failure).
     * Marks the batch completed when processed + failed === total.
     */
    public function recordCompletion(bool $succeeded): void
    {
        $field = $succeeded ? 'processed' : 'failed';
        $this->increment($field);

        $this->refresh();

        if (($this->processed + $this->failed) >= $this->total) {
            $this->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        }
    }

    public function recordFailure(int $applicantId): void
    {
        $failed = $this->failed_ids ?? [];
        $failed[] = $applicantId;
        $this->failed_ids = array_values(array_unique($failed));
        $this->save();
    }
}