<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualEmailDispatch extends Model
{
    protected $table = 'manual_email_dispatches';

    protected $fillable = [
        'sent_by', 'purpose', 'audience', 'subject', 'body',
        'attachments', 'cc', 'bcc', 'recipients',
        'recipient_count', 'status', 'sent_count', 'failed_count',
        'error_message', 'deposit_id', 'sent_at',
        'employer_email', 'employer_name', 'job_title', 'company_name',
        'seeker_name', 'seeker_email',
    ];


    protected $casts = [
        'attachments' => 'array',
        'recipients'  => 'array',
        'sent_at'     => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function getPurposeLabelAttribute(): string
    {
        return match ($this->purpose) {
            'product_advert'        => 'Product Advertisement',
            'job_application_help'  => 'Job Application Help',
            'hiring_appointment'    => 'Hiring Appointment',
            'manual_outreach'       => 'Manual Outreach',
            'newsletter'            => 'Newsletter',
            default                 => ucfirst(str_replace('_', ' ', $this->purpose)),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'queued'   => '<span class="badge badge-light-secondary">Queued</span>',
            'sending'  => '<span class="badge badge-light-info">Sending…</span>',
            'sent'     => '<span class="badge badge-light-success">Sent</span>',
            'partial'  => '<span class="badge badge-light-warning">Partial</span>',
            'failed'   => '<span class="badge badge-light-danger">Failed</span>',
            default    => '<span class="badge badge-light-secondary">' . e($this->status) . '</span>',
        };
    }
}