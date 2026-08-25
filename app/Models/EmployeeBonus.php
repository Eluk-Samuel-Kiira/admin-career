<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeBonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_salary_id',
        'user_id',
        'department_id',
        'bonus_type', // performance, retention, commission, extraordinary, recruitment, training, automation, client_acquisition
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
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'percentage_of_salary' => 'float',
        'performance_score' => 'float',
        'target_achieved' => 'float',
        'is_paid' => 'boolean',
        'bonus_date' => 'datetime',
        'paid_date' => 'datetime',
    ];

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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('bonus_type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('bonus_category', $category);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        $currency = $this->department?->currency ?? Currency::getDefault();
        return $currency->formatAmount($this->amount);
    }
}