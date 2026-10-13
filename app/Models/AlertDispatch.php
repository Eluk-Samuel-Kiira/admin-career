<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertDispatch extends Model
{
    use HasFactory;

    protected $table = 'alert_dispatches';

    protected $fillable = [
        'user_id',
        'channel',
        'kind',
        'jobs_sent',
        'job_ids',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'job_ids'   => 'array',
        'jobs_sent' => 'integer',
        'sent_at'   => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // ─────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'sent'   => '<span class="badge badge-light-success">Sent</span>',
            'queued' => '<span class="badge badge-light-info">Queued</span>',
            'failed' => '<span class="badge badge-light-danger">Failed</span>',
            default  => '<span class="badge badge-light-secondary">' . e($this->status) . '</span>',
        };
    }

    public function getChannelBadgeAttribute(): string
    {
        return match ($this->channel) {
            'email'    => '<span class="badge badge-light-primary">Email</span>',
            'whatsapp' => '<span class="badge badge-light-success">WhatsApp</span>',
            'telegram' => '<span class="badge badge-light-info">Telegram</span>',
            default    => '<span class="badge badge-light-secondary">' . e($this->channel) . '</span>',
        };
    }

    public function getKindLabelAttribute(): string
    {
        return match ($this->kind) {
            'jobs'               => 'Job Alert',
            'preferences_prompt' => 'Preferences Prompt',
            'profile_prompt'     => 'Profile Prompt',
            'cv_prompt'          => 'CV Prompt',
            default              => ucfirst(str_replace('_', ' ', $this->kind)),
        };
    }
}