<?php

namespace App\Models\Service;

use App\Models\Currency;
use App\Models\Job\Country;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CvReviewRequest extends Model
{
    use HasFactory;

    // ---------------------------------------------------------------
    // Mass assignment
    // ---------------------------------------------------------------
    protected $fillable = [
        'uuid',
        'user_id',
        'service_id',
        'country_code',
        'currency_id',
        'amount_cents',

        'target_job_title',
        'target_job_description',

        'original_cv_path',
        'original_cv_name',
        'seeker_cv_path',

        'ai_gap_review',
        'admin_edited_review',
        'review_delivered_at',
        'review_delivery_channel',
        'delivery_log',

        'seeker_gap_answers',
        'revision_history',

        'status',
        'payment_reference',
        'assigned_admin_id',
        'sla_due_at',
        'delivered_cv_path',
        'delivered_at',
        'admin_notes',
    ];

    // ---------------------------------------------------------------
    // Casts
    // ---------------------------------------------------------------
    protected $casts = [
        'ai_gap_review'        => 'array',
        'admin_edited_review'  => 'array',
        'delivery_log'         => 'array',
        'seeker_gap_answers'   => 'array',
        'revision_history'     => 'array',
        'amount_cents'         => 'integer',
        'sla_due_at'           => 'datetime',
        'delivered_at'         => 'datetime',
        'review_delivered_at'  => 'datetime',
    ];

    // ---------------------------------------------------------------
    // Boot — auto-generate UUID
    // ---------------------------------------------------------------
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    /**
     * Country resolved by its 2-letter code (not id).
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------
    public function scopeAwaitingPayment($query)
    {
        return $query->where('status', 'awaiting_payment');
    }

    public function scopePaid($query)
    {
        return $query->whereIn('status', [
            'paid', 'in_progress', 'delivered', 'revision_requested', 'completed',
        ]);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['submitted', 'ai_reviewed', 'awaiting_payment']);
    }

    public function scopeActiveWork($query)
    {
        return $query->whereIn('status', ['paid', 'in_progress', 'revision_requested']);
    }

    public function scopeSlaBreached($query)
    {
        return $query->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->whereNotIn('status', ['delivered', 'completed', 'cancelled']);
    }

    // ---------------------------------------------------------------
    // Accessors
    // ---------------------------------------------------------------

    public function getStatusBadgeAttribute(): string
    {
        $map = [
            'submitted'          => 'light-info',
            'ai_reviewed'        => 'light-info',
            'awaiting_payment'   => 'light-warning',
            'paid'               => 'light-primary',
            'in_progress'        => 'light-primary',
            'delivered'          => 'light-success',
            'revision_requested' => 'light-warning',
            'completed'          => 'light-success',
            'cancelled'          => 'light-danger',
        ];
        $color = $map[$this->status] ?? 'light-secondary';
        $label = ucwords(str_replace('_', ' ', (string) $this->status));
        return "<span class=\"badge badge-{$color}\">{$label}</span>";
    }

    public function getPaymentBadgeAttribute(): string
    {
        $paidStatuses = ['paid', 'in_progress', 'delivered', 'revision_requested', 'completed'];

        if (in_array($this->status, $paidStatuses, true)) {
            return '<span class="badge badge-light-success">✅ Paid</span>';
        }
        if ($this->status === 'awaiting_payment') {
            return '<span class="badge badge-light-warning">⏳ Awaiting</span>';
        }
        if ($this->status === 'cancelled') {
            return '<span class="badge badge-light-danger">❌ Cancelled</span>';
        }
        return '<span class="badge badge-light-secondary">Not Required</span>';
    }

    public function getFormattedAmountAttribute(): string
    {
        if (!$this->currency) {
            return '—';
        }
        return $this->currency->formatAmount($this->amount_cents);
    }

    public function getCountryLabelAttribute(): string
    {
        if ($this->country_code === 'XX') {
            return '<span class="badge badge-light-info">🌍 Global</span>';
        }
        $flag = $this->country->flag ?? '🌍';
        $name = $this->country->name ?? $this->country_code;
        return "{$flag} " . e($name);
    }

    public function getSlaBadgeAttribute(): string
    {
        if (!$this->sla_due_at) {
            return '<span class="text-muted">—</span>';
        }

        if (in_array($this->status, ['delivered', 'completed'], true)) {
            return '<span class="badge badge-light-success">Delivered</span>';
        }

        if ($this->status === 'cancelled') {
            return '<span class="text-muted">—</span>';
        }

        $now = now();
        $due = $this->sla_due_at;

        if ($due->isPast()) {
            $hours = $due->diffInHours($now);
            return "<span class=\"badge badge-light-danger\">Overdue {$hours}h</span>";
        }

        $hours = $now->diffInHours($due);
        if ($hours <= 6) {
            return "<span class=\"badge badge-light-warning\">Due in {$hours}h</span>";
        }
        return "<span class=\"badge badge-light-info\">{$hours}h left</span>";
    }


    /**
     * Full URL to the CV the seeker submitted.
     * Either their fresh upload (original_cv_path) or a reference to
     * one of their existing CVs (seeker_cv_path).
     */
    public function getCvDownloadUrlAttribute(): ?string
    {
        $path = $this->original_cv_path ?: $this->seeker_cv_path;

        if (!$path) {
            return null;
        }

        if (!\Storage::disk('public')->exists($path)) {
            return null;
        }

        return \Storage::disk('public')->url($path);
    }

    /**
     * The filename shown to admin.
     */
    public function getCvDisplayNameAttribute(): string
    {
        return $this->original_cv_name
            ?: ($this->seeker_cv_path ? basename($this->seeker_cv_path) : 'CV file');
    }

    /**
     * Whether a CV file is actually available for download.
     */
    public function getHasCvFileAttribute(): bool
    {
        return $this->cv_download_url !== null;
    }

    // ---------------------------------------------------------------
    // Review accessors (delivery workflow)
    // ---------------------------------------------------------------

    /**
     * The review the seeker sees. Admin edits win over the raw AI output.
     */
    public function getEffectiveReviewAttribute(): ?array
    {
        return $this->admin_edited_review ?: $this->ai_gap_review;
    }

    public function getHasReviewAttribute(): bool
    {
        return !empty($this->effective_review);
    }

    public function getWasReviewEditedAttribute(): bool
    {
        return !empty($this->admin_edited_review);
    }

    public function getReviewDeliveredAttribute(): bool
    {
        return $this->review_delivered_at !== null;
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    

    /**
     * Whether this request should count as "revenue" in reports.
     */
    public function isPaid(): bool
    {
        return in_array($this->status, [
            'paid', 'in_progress', 'delivered', 'revision_requested', 'completed',
        ], true);
    }

    /**
     * Append an entry to the delivery log (safe, doesn't overwrite history).
     */
    public function logDelivery(array $entry): void
    {
        $log = $this->delivery_log ?? [];
        $log[] = array_merge(['at' => now()->toISOString()], $entry);
        $this->delivery_log = $log;
    }

    /**
     * Statuses the ADMIN can set — any valid status. Admins need to
     * correct mistakes, record off-platform payments, undo an accidental
     * cancel, etc., so they are not bound by the forward-only rules.
     */
    public static function allStatuses(): array
    {
        return array_keys(self::statuses());
    }

    public static function statuses(): array
    {
        return [
            'submitted'           => 'Submitted',
            'ai_reviewed'         => 'AI Reviewed',
            'awaiting_payment'    => 'Awaiting Payment',
            'paid'                => 'Paid',
            'in_progress'         => 'In Progress',
            'delivered'           => 'Delivered',
            'revision_requested'  => 'Revision Requested',
            'completed'           => 'Completed',
            'cancelled'           => 'Cancelled',
        ];
    }

    /**
     * Legality map used ONLY for seeker-initiated actions
     * (answers, revision requests). Admin transitions are unrestricted.
     */
    public function allowedNextStatuses(): array
    {
        return match ($this->status) {
            'submitted'           => ['ai_reviewed', 'awaiting_payment', 'cancelled'],
            'ai_reviewed'         => ['awaiting_payment', 'cancelled'],
            'awaiting_payment'    => ['paid', 'cancelled'],
            'paid'                => ['in_progress', 'cancelled'],
            'in_progress'         => ['delivered', 'cancelled'],
            'delivered'           => ['revision_requested', 'completed'],
            'revision_requested'  => ['in_progress', 'delivered'],
            'completed'           => [],
            'cancelled'           => [],
            default               => [],
        };
    }

    /**
     * Convenience: is this request allowed to move to a specific status
     * when triggered by an admin? (Always true — kept as a hook for future
     * policy rules if you ever want to restrict a specific case.)
     */
    public function adminCanMoveTo(string $newStatus): bool
    {
        return in_array($newStatus, self::allStatuses(), true);
    }
}