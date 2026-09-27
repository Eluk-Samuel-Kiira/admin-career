<?php

namespace App\Models\Service;

use App\Models\Job\JobPost;
use App\Models\SeekerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LetterRequest extends Model
{
    use HasFactory;

    protected $table = 'letter_requests';

    protected $fillable = [
        'uuid',
        'user_id',
        'seeker_profile_id',
        'cv_source',
        'cv_path',
        'job_post_id',
        'job_title',
        'company_name',
        'job_description',
        'letter_type',
        'content',
        'price',
        'currency',
        'status',
        'payment_reference',
        'paid_at',
        'error_message',
        'generated_at',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'paid_at'      => 'datetime',
        'generated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seekerProfile()
    {
        return $this->belongsTo(SeekerProfile::class);
    }

    public function jobPost()
    {
        return $this->belongsTo(JobPost::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────
    public function getIsPaidAttribute(): bool
    {
        return in_array($this->status, ['paid', 'processing', 'generated'], true);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending_payment' => '<span class="badge badge-light-warning">Pending Payment</span>',
            'paid'            => '<span class="badge badge-light-info">Paid</span>',
            'processing'      => '<span class="badge badge-light-primary">Generating</span>',
            'generated'       => '<span class="badge badge-light-success">Ready</span>',
            'failed'          => '<span class="badge badge-light-danger">Failed</span>',
            default           => '<span class="badge badge-light-secondary">' . $this->status . '</span>',
        };
    }

    public function getLetterTypeLabelAttribute(): string
    {
        return $this->letter_type === 'application'
            ? 'Application Letter'
            : 'Cover Letter';
    }

    public function getPriceLabelAttribute(): string
    {
        return $this->currency . ' ' . number_format((float) $this->price);
    }
}