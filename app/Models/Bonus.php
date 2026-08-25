<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Bonus extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bonus_number',
        'employee_salary_id',
        'user_id',
        'department_id',
        'bonus_type', // performance, retention, commission, extraordinary, referral, signing, holiday, project, team
        'bonus_category', // monthly, quarterly, annual, one_time
        'amount',
        'percentage_of_salary',
        'performance_score',
        'target_achieved',
        'target_metric',
        'description',
        'reference',
        'bonus_date',
        'paid_date',
        'is_paid',
        'payment_method_id',
        'approved_by',
        'approved_at',
        'approval_notes',
        'status', // pending, approved, paid, rejected, cancelled
        'created_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'percentage_of_salary' => 'float',
        'performance_score' => 'float',
        'target_achieved' => 'float',
        'is_paid' => 'boolean',
        'bonus_date' => 'date',
        'paid_date' => 'datetime',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'formatted_amount',
        'status_badge',
        'bonus_type_label',
        'bonus_category_label',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->bonus_number)) {
                $model->bonus_number = 'BONUS-' . date('Ymd') . '-' . strtoupper(Str::random(6));
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
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('bonus_type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('bonus_category', $category);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('bonus_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        $currency = $this->paymentMethod?->currency ?? Currency::getDefault();
        return $currency->formatAmount($this->amount);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => '<span class="badge badge-light-warning">Pending</span>',
            'approved' => '<span class="badge badge-light-info">Approved</span>',
            'paid' => '<span class="badge badge-light-success">Paid</span>',
            'rejected' => '<span class="badge badge-light-danger">Rejected</span>',
            'cancelled' => '<span class="badge badge-light-secondary">Cancelled</span>',
        ];
        return $badges[$this->status] ?? '<span class="badge badge-light-secondary">' . $this->status . '</span>';
    }

    public function getBonusTypeLabelAttribute()
    {
        $labels = [
            'performance' => 'Performance',
            'retention' => 'Retention',
            'commission' => 'Commission',
            'extraordinary' => 'Extraordinary',
            'referral' => 'Referral',
            'signing' => 'Signing',
            'holiday' => 'Holiday',
            'project' => 'Project',
            'team' => 'Team',
        ];
        return $labels[$this->bonus_type] ?? $this->bonus_type;
    }

    public function getBonusCategoryLabelAttribute()
    {
        $labels = [
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'annual' => 'Annual',
            'one_time' => 'One Time',
        ];
        return $labels[$this->bonus_category] ?? $this->bonus_category;
    }

    // Methods
    public function approve($userId, $notes = null)
    {
        $this->status = 'approved';
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
        $this->status = 'paid';
        $this->is_paid = true;
        $this->paid_date = now();
        if ($paymentMethodId) {
            $this->payment_method_id = $paymentMethodId;
        }
        $this->save();
        return $this;
    }

    public function reject($reason = null)
    {
        $this->status = 'rejected';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Rejected: {$reason}";
        }
        $this->save();
        return $this;
    }

    public function cancel($reason = null)
    {
        $this->status = 'cancelled';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Cancelled: {$reason}";
        }
        $this->save();
        return $this;
    }

    /**
     * Get currency for this bonus
     */
    public function getCurrencyAttribute()
    {
        return $this->paymentMethod?->currency ?? Currency::getDefault();
    }
}