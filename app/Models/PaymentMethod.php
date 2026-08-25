<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'type', 'code', 'description', 'provider',
        'account_name', 'account_number', 'iban', 'swift_bic', 'routing_number',
        'card_last_four', 'card_type', 'card_expiry_date',
        'wallet_id', 'wallet_email', 'phone_number',
        'transaction_fee_percentage', 'transaction_fee_fixed',
        'min_transaction_amount', 'max_transaction_amount',
        'daily_limit', 'monthly_limit',
        'current_balance', 'available_balance', 'pending_balance',
        'min_balance_limit', 'max_balance_limit', 'allow_negative_balance',
        'is_active', 'is_default', 'is_online',
        'requires_verification', 'is_verified', 'verified_at',
        'token', 'api_key', 'secret_key', 'webhook_url',
        'settings', 'supported_currencies', 'currency_id', 'extra_data',
        'cash_handler_id', 'cash_location',
        'last_reconciled_at', 'last_transaction_at',
        'last_transaction_amount', 'last_transaction_type',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_online' => 'boolean',
        'requires_verification' => 'boolean',
        'is_verified' => 'boolean',
        'allow_negative_balance' => 'boolean',
        'settings' => 'array',
        'supported_currencies' => 'array',
        'extra_data' => 'array',
        'card_expiry_date' => 'date',
        'verified_at' => 'datetime',
        'last_reconciled_at' => 'datetime',
        'last_transaction_at' => 'datetime',
        'transaction_fee_percentage' => 'integer',
        'transaction_fee_fixed' => 'integer',
        'min_transaction_amount' => 'integer',
        'max_transaction_amount' => 'integer',
        'daily_limit' => 'integer',
        'monthly_limit' => 'integer',
        'current_balance' => 'integer',
        'available_balance' => 'integer',
        'pending_balance' => 'integer',
        'min_balance_limit' => 'integer',
        'max_balance_limit' => 'integer',
        'last_transaction_amount' => 'integer',
    ];

    protected $appends = ['formatted_current_balance'];

    // Relationships
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function cashHandler()
    {
        return $this->belongsTo(User::class, 'cash_handler_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function transactionLogs()
    {
        return $this->hasMany(PaymentTransactionLog::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getFormattedCurrentBalanceAttribute()
    {
        if (!$this->currency) {
            return '$' . number_format($this->current_balance / 100, 2);
        }
        
        $balance = $this->current_balance ?? 0;
        return $this->currency->formatAmount($balance);
    }

    public function getBalanceStatusAttribute()
    {
        if ($this->current_balance <= $this->min_balance_limit) {
            return 'critical';
        } elseif ($this->current_balance <= $this->min_balance_limit * 1.2) {
            return 'warning';
        }
        return 'healthy';
    }

    // Methods
    public function updateBalance(int $amount, string $type, int $fee = 0)
    {
        $oldBalance = $this->current_balance ?? 0;
        $netAmount = $type === 'deposit' ? $amount - $fee : -($amount + $fee);
        
        $this->current_balance = $oldBalance + $netAmount;
        $this->available_balance = ($this->available_balance ?? 0) + $netAmount;
        $this->last_transaction_at = now();
        $this->last_transaction_amount = $amount;
        $this->last_transaction_type = $type;
        $this->save();

        return $oldBalance;
    }

    public function canProcessTransaction(int $amountInCents): bool
    {
        if (!$this->is_active) return false;
        
        if ($amountInCents < $this->min_transaction_amount) return false;
        
        if ($this->max_transaction_amount && $amountInCents > $this->max_transaction_amount) return false;
        
        if (!$this->allow_negative_balance && ($this->current_balance ?? 0) - $amountInCents < 0) return false;
        
        if ($this->min_balance_limit && ($this->current_balance ?? 0) - $amountInCents < $this->min_balance_limit) return false;
        
        return true;
    }

    public function calculateFee(int $amountInCents): int
    {
        $percentageFee = (int) round($amountInCents * ($this->transaction_fee_percentage / 10000));
        return $percentageFee + ($this->transaction_fee_fixed ?? 0);
    }
}