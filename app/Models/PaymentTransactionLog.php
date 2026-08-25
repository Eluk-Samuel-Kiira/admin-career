<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaymentTransactionLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payment_transaction_logs';

    protected $fillable = [
        'transaction_ref',
        'payment_method_id',
        'department_id',           // NEW
        'depositor_id',            // NEW
        'transaction_type',
        'transaction_category',
        'reference_table',
        'reference_id',
        'amount',
        'transaction_fee',
        'net_amount',
        'balance_before',
        'balance_after',
        'currency_id',
        'exchange_rate',
        'original_currency',
        'original_amount',
        'status',
        'transaction_date',
        'effective_date',
        'settlement_date',
        'description',
        'metadata',
        'notes',
        'external_reference',
        'bank_reference',
        'receipt_number',
        'user_id',
        'counterparty_id',
        'counterparty_name',
        'counterparty_account',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'transaction_fee' => 'integer',
        'net_amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'exchange_rate' => 'decimal:6',
        'original_amount' => 'integer',
        'metadata' => 'array',
        'transaction_date' => 'datetime',
        'effective_date' => 'datetime',
        'settlement_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->transaction_ref)) {
                $model->transaction_ref = (string) Str::uuid();
            }
            if (empty($model->transaction_date)) {
                $model->transaction_date = now();
            }
        });
    }

    // Relationships
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function depositor()
    {
        return $this->belongsTo(User::class, 'depositor_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByDepositor($query, $depositorId)
    {
        return $query->where('depositor_id', $depositorId);
    }

    // Accessors
    public function getIsSuccessfulAttribute(): bool
    {
        return $this->status === 'completed';
    }

    

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->status === 'failed';
    }

    public function getAmountSignAttribute(): string
    {
        return in_array($this->transaction_type, ['deposit', 'refund']) ? '+' : '-';
    }

    public function getFormattedAmountAttribute()
    {
        if (!$this->currency) {
            $amount = $this->amount / 100;
            return $this->getAmountSignAttribute() . ' $' . number_format(abs($amount), 2);
        }
        
        $amount = $this->currency->fromCents(abs($this->amount));
        return $this->getAmountSignAttribute() . ' ' . $this->currency->symbol . ' ' . number_format($amount, $this->currency->decimal_places);
    }

    // Methods
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'settlement_date' => now(),
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason ? ($this->notes ? $this->notes . "\n" . $reason : $reason) : $this->notes,
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCancelled(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason ? ($this->notes ? $this->notes . "\n" . $reason : $reason) : $this->notes,
        ]);
    }
}