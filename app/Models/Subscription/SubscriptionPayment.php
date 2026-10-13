<?php

namespace App\Models\Subscription;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubscriptionPayment extends Model
{
    protected $table = 'subscription_payments';

    protected $fillable = [
        'uuid', 'subscription_id', 'user_id',
        'gateway', 'gateway_reference', 'gateway_transaction_id',
        'currency', 'amount_cents', 'status', 'failure_reason',
        'paid_at', 'gateway_payload',
    ];

    protected $casts = [
        'gateway_payload' => 'array',
        'paid_at'         => 'datetime',
        'amount_cents'    => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) $model->uuid = (string) Str::uuid();
        });
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}