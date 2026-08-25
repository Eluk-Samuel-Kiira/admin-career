<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Deposit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'deposit_ref',
        'payment_method_id',
        'currency_id',
        'amount',
        'fee',
        'net_amount',
        'deposit_method',
        'reference_number',
        'cheque_number',
        'card_last_four',
        'department_id',        // NEW
        'depositor_id',         // NEW
        'source_id',
        'source_name_manual',
        'source_reference',
        'source_contact',
        'customer_id',
        'invoice_number',
        'po_number',
        'contract_number',
        'project_code',
        'purpose_id',
        'purpose_description',
        'status',
        'deposit_date',
        'cleared_date',
        'depositor_name',
        'depositor_phone',
        'depositor_email',
        'description',
        'notes',
        'receipt_image',
        'attachments',
        'approved_by',
        'approved_at',
        'verified_by',
        'verified_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'fee' => 'integer',
        'net_amount' => 'integer',
        'attachments' => 'array',
        'deposit_date' => 'datetime',
        'cleared_date' => 'datetime',
        'approved_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected $appends = ['formatted_amount', 'formatted_fee', 'formatted_net_amount', 'source_display', 'purpose_display'];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->deposit_ref)) {
                $model->deposit_ref = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function depositor()
    {
        return $this->belongsTo(User::class, 'depositor_id');
    }

    public function source()
    {
        return $this->belongsTo(PaymentSource::class, 'source_id');
    }

    public function purpose()
    {
        return $this->belongsTo(PaymentPurpose::class, 'purpose_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        if (!$this->currency) {
            return '$' . number_format($this->amount / 100, 2);
        }
        return $this->currency->formatAmount($this->amount);
    }

    public function getFormattedFeeAttribute()
    {
        if (!$this->currency) {
            return '$' . number_format($this->fee / 100, 2);
        }
        return $this->currency->formatAmount($this->fee);
    }

    public function getFormattedNetAmountAttribute()
    {
        if (!$this->currency) {
            return '$' . number_format($this->net_amount / 100, 2);
        }
        return $this->currency->formatAmount($this->net_amount);
    }

    public function getSourceDisplayAttribute()
    {
        if ($this->source) {
            return $this->source->name;
        }
        if ($this->source_name_manual) {
            return $this->source_name_manual . ' (Manual)';
        }
        return 'N/A';
    }

    public function getPurposeDisplayAttribute()
    {
        if ($this->purpose) {
            return $this->purpose->name;
        }
        return 'N/A';
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByDepositor($query, $depositorId)
    {
        return $query->where('depositor_id', $depositorId);
    }

    // Methods
    public function approve(int $userId)
    {
        $this->status = 'approved';
        $this->approved_by = $userId;
        $this->approved_at = now();
        $this->save();
        
        return $this;
    }

    public function complete(int $userId)
    {
        $this->status = 'completed';
        $this->verified_by = $userId;
        $this->verified_at = now();
        $this->cleared_date = now();
        $this->save();
        
        return $this;
    }

    public function fail(string $reason = null)
    {
        $this->status = 'failed';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Failed: {$reason}";
        }
        $this->save();
        
        return $this;
    }

    public function cancel(string $reason = null)
    {
        $this->status = 'cancelled';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Cancelled: {$reason}";
        }
        $this->save();
        
        return $this;
    }

    public function receipts()
    {
        return $this->hasMany(DepositReceipt::class);
    }

    public function primaryReceipt()
    {
        return $this->hasOne(DepositReceipt::class)->where('is_primary', true);
    }
    
}