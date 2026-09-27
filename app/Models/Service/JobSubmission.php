<?php

namespace App\Models\Service;

use App\Models\Currency;
use App\Models\Job\Company;
use App\Models\Job\JobPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class JobSubmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'job_submissions';

    protected $fillable = [
        'uuid',
        'user_id',
        'company_id',

        'job_title',
        'content',
        'target_country',

        'service_key',
        'amount_cents',
        'currency_id',
        'package_meta',

        'payment_status',
        'payment_reference',
        'paid_at',

        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'admin_notes',

        'job_post_id',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'package_meta' => 'array',
        'paid_at'      => 'datetime',
        'reviewed_at'  => 'datetime',
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
    // Relationships — just the essentials
    // =================================================================
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobSubmission()
    {
        return $this->belongsTo(\App\Models\Service\JobSubmission::class, 'job_submission_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_key', 'key');
    }

    public function jobPost()
    {
        return $this->belongsTo(JobPost::class, 'job_post_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // =================================================================
    // Scopes
    // =================================================================
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePendingPayment($query)
    {
        return $query->where('status', 'pending_payment');
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // =================================================================
    // Accessors
    // =================================================================
    public function getFormattedAmountAttribute(): string
    {
        if (!$this->currency) return '—';
        return $this->currency->formatAmount($this->amount_cents);
    }

    public function getStatusBadgeAttribute(): string
    {
        $map = [
            'draft'           => ['light-secondary', 'Draft'],
            'pending_payment' => ['light-warning',   'Pending Payment'],
            'pending_review'  => ['light-info',      'Under Review'],
            'approved'        => ['light-primary',   'Approved'],
            'rejected'        => ['light-danger',    'Rejected'],
            'published'       => ['light-success',   'Published'],
            'cancelled'       => ['light-secondary', 'Cancelled'],
        ];

        [$color, $label] = $map[$this->status] ?? ['light-secondary', ucfirst($this->status)];
        return "<span class=\"badge badge-{$color}\">{$label}</span>";
    }

    public function getPaymentBadgeAttribute(): string
    {
        return match ($this->payment_status) {
            'paid'         => '<span class="badge badge-light-success">✅ Paid</span>',
            'pending'      => '<span class="badge badge-light-warning">⏳ Pending</span>',
            'refunded'     => '<span class="badge badge-light-danger">↩️ Refunded</span>',
            'not_required' => '<span class="badge badge-light-secondary">Free</span>',
            default        => '<span class="badge badge-light-secondary">—</span>',
        };
    }

    public function getServiceNameAttribute(): string
    {
        return $this->service->name ?? $this->service_key;
    }

    public function getIsFreeAttribute(): bool
    {
        return $this->amount_cents === 0;
    }

    // =================================================================
    // Helpers
    // =================================================================
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'pending_payment', 'rejected'], true);
    }

    public function canBeDeleted(): bool
    {
        return in_array($this->status, ['draft', 'pending_payment', 'rejected', 'cancelled'], true);
    }

    public function allowedNextStatuses(): array
    {
        return match ($this->status) {
            'draft'           => ['pending_payment', 'pending_review', 'cancelled'],
            'pending_payment' => ['pending_review', 'cancelled'],
            'pending_review'  => ['approved', 'rejected'],
            'approved'        => ['published', 'rejected'],
            'rejected'        => ['pending_review'],
            'published'       => ['cancelled'],
            'cancelled'       => [],
            default           => [],
        };
    }
}