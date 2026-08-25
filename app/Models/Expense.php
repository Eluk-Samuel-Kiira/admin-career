<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'expense_number',
        'date',
        'description',
        'category_id',  
        'department_id',
        'payment_method_id',
        'employee_id',
        'created_by',
        'approved_by',
        'vendor_name',
        'vendor_contact',
        'vendor_email',
        'gross_amount',
        'tax_amount',
        'net_amount',
        'total_amount',
        'tax_breakdown',
        'payment_status',
        'paid_date',
        'is_recurring',
        'recurring_frequency',
        'next_recurring_date',
        'receipt_url',
        'receipt_number',
        'approved_at',
        'approval_notes',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'paid_date' => 'datetime',
        'approved_at' => 'datetime',
        'next_recurring_date' => 'date',
        'gross_amount' => 'integer',
        'tax_amount' => 'integer',
        'net_amount' => 'integer',
        'total_amount' => 'integer',
        'is_recurring' => 'boolean',
        'tax_breakdown' => 'array',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->expense_number)) {
                $model->expense_number = 'EXP-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }
        });
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('payment_status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Get the currency for this expense
     */
    public function getCurrencyAttribute()
    {
        return $this->paymentMethod?->currency ?? Currency::getDefault();
    }

    /**
     * Get formatted amount in a specific currency
     */
    public function getFormattedAmountInCurrency(string $currencyCode): string
    {
        $targetCurrency = Currency::getByCode($currencyCode);
        if (!$targetCurrency) {
            $targetCurrency = Currency::getDefault();
        }
        
        $sourceCurrency = $this->currency;
        $convertedAmount = Currency::convertAmount(
            $this->total_amount,
            $sourceCurrency,
            $targetCurrency
        );
        
        return $targetCurrency->formatAmount(
            $targetCurrency->toCents($convertedAmount)
        );
    }

    /**
     * Get formatted amount in a specific currency with symbol
     */
    public function getFormattedAmountWithSymbol(string $currencyCode): string
    {
        $targetCurrency = Currency::getByCode($currencyCode);
        if (!$targetCurrency) {
            $targetCurrency = Currency::getDefault();
        }
        
        $sourceCurrency = $this->currency;
        $convertedAmount = Currency::convertAmount(
            $this->total_amount,
            $sourceCurrency,
            $targetCurrency
        );
        
        $amount = $targetCurrency->fromCents(
            $targetCurrency->toCents($convertedAmount)
        );
        
        return $targetCurrency->symbol . ' ' . number_format($amount, $targetCurrency->decimal_places);
    }

    // Accessors - formatted in the expense's native currency
    public function getFormattedGrossAmountAttribute()
    {
        $currency = $this->currency;
        return $currency->formatAmount($this->gross_amount);
    }

    public function getFormattedTaxAmountAttribute()
    {
        $currency = $this->currency;
        return $currency->formatAmount($this->tax_amount);
    }

    public function getFormattedNetAmountAttribute()
    {
        $currency = $this->currency;
        return $currency->formatAmount($this->net_amount);
    }

    public function getFormattedTotalAmountAttribute()
    {
        $currency = $this->currency;
        return $currency->formatAmount($this->total_amount);
    }

    /**
     * Get total amount formatted in USD
     */
    public function getFormattedTotalAmountUsdAttribute()
    {
        $usd = Currency::getByCode('USD');
        if (!$usd) {
            $usd = Currency::getDefault();
        }
        return $this->getFormattedAmountWithSymbol('USD');
    }

    /**
     * Get total amount formatted in the base currency (USD by default)
     */
    public function getFormattedTotalAmountBaseAttribute()
    {
        $baseCurrency = Currency::getDefault();
        return $this->getFormattedAmountWithSymbol($baseCurrency->code);
    }

    public function getPaymentStatusBadgeAttribute()
    {
        $badges = [
            'pending' => '<span class="badge badge-light-warning">Pending</span>',
            'approved' => '<span class="badge badge-light-info">Approved</span>',
            'paid' => '<span class="badge badge-light-success">Paid</span>',
            'cancelled' => '<span class="badge badge-light-secondary">Cancelled</span>',
            'rejected' => '<span class="badge badge-light-danger">Rejected</span>',
        ];
        return $badges[$this->payment_status] ?? '<span class="badge badge-light-secondary">' . $this->payment_status . '</span>';
    }

    public function getRecurringFrequencyLabelAttribute()
    {
        $labels = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ];
        return $labels[$this->recurring_frequency] ?? $this->recurring_frequency;
    }

    // Methods
    public function approve($userId, $notes = null)
    {
        $this->payment_status = 'approved';
        $this->approved_by = $userId;
        $this->approved_at = now();
        if ($notes) {
            $this->approval_notes = $notes;
        }
        $this->save();
        
        return $this;
    }

    public function markAsPaid($paymentMethodId = null)
    {
        $this->payment_status = 'paid';
        $this->paid_date = now();
        if ($paymentMethodId) {
            $this->payment_method_id = $paymentMethodId;
        }
        $this->save();
        
        return $this;
    }

    public function cancel($reason = null)
    {
        $this->payment_status = 'cancelled';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Cancelled: {$reason}";
        }
        $this->save();
        
        return $this;
    }

    public function reject($reason = null)
    {
        $this->payment_status = 'rejected';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Rejected: {$reason}";
        }
        $this->save();
        
        return $this;
    }

    /**
     * Convert expense total to target currency
     */
    public function convertToCurrency(string $targetCurrencyCode): float
    {
        $targetCurrency = Currency::getByCode($targetCurrencyCode);
        if (!$targetCurrency) {
            $targetCurrency = Currency::getDefault();
        }
        
        $sourceCurrency = $this->currency;
        return Currency::convertAmount(
            $this->total_amount,
            $sourceCurrency,
            $targetCurrency
        );
    }

    /**
     * Get display amount in target currency
     */
    public function getDisplayAmountInCurrency(string $targetCurrencyCode): string
    {
        $targetCurrency = Currency::getByCode($targetCurrencyCode);
        if (!$targetCurrency) {
            $targetCurrency = Currency::getDefault();
        }
        
        $amount = $this->convertToCurrency($targetCurrencyCode);
        return $targetCurrency->formatAmount($targetCurrency->toCents($amount));
    }
}