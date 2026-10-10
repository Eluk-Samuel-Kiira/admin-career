<?php

namespace App\Models\Hiring;

use App\Models\User;
use App\Models\Job\Company;
use App\Models\Job\JobPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HiringRun extends Model
{
    protected $table = 'hiring_runs';

    protected $fillable = [
        'uuid', 'user_id', 'company_id', 'title',
        'job_post_id', 'job_description', 'job_description_source',
        'total', 'processed', 'failed', 'status',
        'started_at', 'completed_at', 'job_fingerprint',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'total'        => 'integer',
        'processed'    => 'integer',
        'failed'       => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->uuid)) $m->uuid = (string) Str::uuid();
        });
    }

    public function user()      { return $this->belongsTo(User::class); }
    public function company()   { return $this->belongsTo(Company::class); }
    public function jobPost()   { return $this->belongsTo(JobPost::class); }
    public function candidates(){ return $this->hasMany(HiringRunCandidate::class); }

    public function getProgressPercentAttribute(): int
    {
        if ($this->total === 0) return 0;
        return (int) round((($this->processed + $this->failed) / $this->total) * 100);
    }
}