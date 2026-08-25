<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentProfitShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'financial_year',
        'total_profit',
        'profit_share_percentage',
        'profit_share_amount',
        'total_units',
        'unit_value',
        'distribution_date',
        'status', // pending, calculated, distributed, closed
        'created_by',
    ];

    protected $casts = [
        'total_profit' => 'integer',
        'profit_share_percentage' => 'float',
        'profit_share_amount' => 'integer',
        'total_units' => 'integer',
        'unit_value' => 'integer',
        'distribution_date' => 'date',
    ];

    // Relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function distributions()
    {
        return $this->hasMany(ProfitShareDistribution::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDistributed($query)
    {
        return $query->where('status', 'distributed');
    }
}