<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitShareDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_profit_share_id',
        'employee_salary_id', // This can be null
        'user_id',
        'department_id',
        'units_held',
        'vested_units',
        'unit_value',
        'total_amount',
        'distribution_date',
        'status',
        'reference',
        'notes',
        'paid_by',
    ];

    protected $casts = [
        'units_held' => 'integer',
        'vested_units' => 'integer',
        'unit_value' => 'integer',
        'total_amount' => 'integer',
        'distribution_date' => 'date',
    ];

    // Relationships
    public function departmentProfitShare()
    {
        return $this->belongsTo(DepartmentProfitShare::class);
    }

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

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}