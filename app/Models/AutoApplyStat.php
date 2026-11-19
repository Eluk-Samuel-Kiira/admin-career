<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoApplyStat extends Model
{
    protected $table = 'auto_apply_stats';

    protected $fillable = [
        'user_id',
        // Application counters
        'applied_total',
        'applied_via_email',
        'applied_via_whatsapp',
        'applied_via_external',
        'last_applied_at',
        // CV delivery counters
        'cv_reviews_delivered',
        'cv_delivered_via_email',
        'cv_delivered_via_whatsapp',
        'cv_delivered_via_external',
        'last_cv_delivered_at',
    ];

    protected $casts = [
        'last_applied_at'      => 'datetime',
        'last_cv_delivered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}