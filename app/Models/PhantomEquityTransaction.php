<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhantomEquityTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_salary_id',
        'user_id',
        'department_id',
        'transaction_type', // allocation, award, vesting, forfeiture, payout
        'units',
        'vested_units',
        'unit_value',
        'total_value',
        'performance_score',
        'performance_multiplier',
        'description',
        'reference',
        'transaction_date',
        'is_vested',
        'created_by',
    ];

    protected $casts = [
        'units' => 'integer',
        'vested_units' => 'integer',
        'unit_value' => 'integer',
        'total_value' => 'integer',
        'performance_score' => 'float',
        'performance_multiplier' => 'float',
        'is_vested' => 'boolean',
        'transaction_date' => 'datetime',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeAllocation($query)
    {
        return $query->where('transaction_type', 'allocation');
    }

    public function scopeAward($query)
    {
        return $query->where('transaction_type', 'award');
    }

    public function scopeVested($query)
    {
        return $query->where('is_vested', true);
    }

    public function scopePayout($query)
    {
        return $query->where('transaction_type', 'payout');
    }
}