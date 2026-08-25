<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EmployeePayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_number',
        'employee_salary_id',
        'user_id',
        'department_id',
        'payment_method_id',
        'payment_date',
        'payment_type',
        'description',
        'gross_amount',
        'tax_amount',
        'net_amount',
        'total_amount',
        'deductions',
        'allowances',
        'payment_status',
        'approved_by',
        'approved_at',
        'paid_date',
        'pay_period_start',
        'pay_period_end',
        'hours_worked',
        'hourly_rate',
        'breakdown',
        'reference_number',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'paid_date' => 'datetime',
        'approved_at' => 'datetime',
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'gross_amount' => 'integer',
        'tax_amount' => 'integer',
        'net_amount' => 'integer',
        'total_amount' => 'integer',
        'hours_worked' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'deductions' => 'array',
        'allowances' => 'array',
        'breakdown' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = [
        'total_amount_display',
        'formatted_total',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->payment_number)) {
                $model->payment_number = 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }
        });
    }

    // Relationships
    public function employeeSalary()
    {
        return $this->belongsTo(EmployeeSalary::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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

    // Accessors - Simple display formatting
    public function getTotalAmountDisplayAttribute()
    {
        return $this->total_amount;
    }

    public function getFormattedTotalAttribute()
    {
        return 'UGX ' . number_format($this->total_amount, 0);
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

    public function getPaymentTypeLabelAttribute()
    {
        $labels = [
            'salary' => 'Salary',
            'bonus' => 'Bonus',
            'commission' => 'Commission',
            'advance' => 'Advance',
            'reimbursement' => 'Reimbursement',
        ];
        return $labels[$this->payment_type] ?? $this->payment_type;
    }

    // Methods
    public function approve($userId, $notes = null)
    {
        $this->payment_status = 'approved';
        $this->approved_by = $userId;
        $this->approved_at = now();
        if ($notes) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Approved: {$notes}";
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
}