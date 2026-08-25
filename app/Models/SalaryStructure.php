<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryStructure extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department_id',
        'job_title',
        'role_code',
        'base_salary',
        'salary_type', // fixed, hourly, commission
        'performance_bonus_percentage',
        'performance_bonus_max',
        'phantom_equity_units',
        'profit_share_percentage',
        'commission_rate',
        'retention_bonus',
        'min_salary',
        'max_salary',
        'currency_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'base_salary' => 'integer',
        'performance_bonus_percentage' => 'float',
        'performance_bonus_max' => 'integer',
        'phantom_equity_units' => 'integer',
        'profit_share_percentage' => 'float',
        'commission_rate' => 'float',
        'retention_bonus' => 'integer',
        'min_salary' => 'integer',
        'max_salary' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employeeSalaries()
    {
        return $this->hasMany(EmployeeSalary::class);
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

    public function scopeByRole($query, $roleCode)
    {
        return $query->where('role_code', $roleCode);
    }
}