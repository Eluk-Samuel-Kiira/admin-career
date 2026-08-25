<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_salary_id',
        'user_id',
        'department_id',
        'review_period', // monthly, quarterly, annual
        'review_date',
        'score', // 0-100
        'revenue_contribution',
        'client_satisfaction',
        'reporting_discipline',
        'innovation_score',
        'teamwork_score',
        'quality_score',
        'attendance_score',
        'kpi_achievements',
        'overall_rating', // excellent, good, average, below_average, poor
        'recommendations',
        'bonus_eligible',
        'promotion_recommended',
        'reviewer_id',
        'approved_by',
        'status', // pending, completed, approved
    ];

    protected $casts = [
        'score' => 'float',
        'revenue_contribution' => 'float',
        'client_satisfaction' => 'float',
        'reporting_discipline' => 'float',
        'innovation_score' => 'float',
        'teamwork_score' => 'float',
        'quality_score' => 'float',
        'attendance_score' => 'float',
        'kpi_achievements' => 'array',
        'bonus_eligible' => 'boolean',
        'promotion_recommended' => 'boolean',
        'review_date' => 'datetime',
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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeBonusEligible($query)
    {
        return $query->where('bonus_eligible', true);
    }
}