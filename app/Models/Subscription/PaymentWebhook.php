<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    protected $table = 'payment_webhooks';

    protected $fillable = [
        'gateway', 'event_type', 'gateway_reference', 'payload',
        'signature', 'signature_valid', 'status', 'error_message', 'processed_at',
    ];

    protected $casts = [
        'payload'         => 'array',
        'signature_valid' => 'boolean',
        'processed_at'    => 'datetime',
    ];
}