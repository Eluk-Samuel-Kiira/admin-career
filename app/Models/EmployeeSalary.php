<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSalary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'user_id',
        'department_id',
        'salary_structure_id',
        'base_salary',
        'salary_type',
        'is_recurring',
        'recurring_day',
        'hire_date',
        'termination_date',
        'performance_rating',
        'performance_multiplier',
        'phantom_equity_units',
        'vested_units',
        'units_vested_percentage',
        'current_balance',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'base_salary' => 'integer',
        'performance_multiplier' => 'float',
        'phantom_equity_units' => 'integer',
        'vested_units' => 'integer',
        'units_vested_percentage' => 'float',
        'current_balance' => 'integer',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'recurring_day' => 'integer',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function salaryStructure()
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function payments()
    {
        return $this->hasMany(EmployeePayment::class);
    }

    public function phantomEquityTransactions()
    {
        return $this->hasMany(PhantomEquityTransaction::class);
    }

    public function performanceReviews()
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function bonuses()
    {
        return $this->hasMany(EmployeeBonus::class);
    }

    public function profitShareDistributions()
    {
        return $this->hasMany(ProfitShareDistribution::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // Methods
    public function calculateVestedUnits()
    {
        $serviceMonths = $this->hire_date->diffInMonths(now());
        
        if ($serviceMonths < 12) {
            $percentage = 0;
        } elseif ($serviceMonths >= 12 && $serviceMonths < 24) {
            $percentage = 25;
        } elseif ($serviceMonths >= 24 && $serviceMonths < 36) {
            $percentage = 50;
        } elseif ($serviceMonths >= 36 && $serviceMonths < 48) {
            $percentage = 75;
        } else {
            $percentage = 100;
        }

        $this->units_vested_percentage = $percentage;
        $this->vested_units = round(($this->phantom_equity_units * $percentage) / 100);
        $this->save();

        return $this->vested_units;
    }

    public function calculatePerformanceMultiplier($performanceScore)
    {
        if ($performanceScore >= 95) {
            return 1.5;
        } elseif ($performanceScore >= 90) {
            return 1.25;
        } elseif ($performanceScore >= 80) {
            return 1.0;
        } elseif ($performanceScore >= 70) {
            return 0.75;
        }
        return 0;
    }
}