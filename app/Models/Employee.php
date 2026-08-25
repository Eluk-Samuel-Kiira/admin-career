<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'department_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'gender',
        'date_of_birth',
        'residence',
        'hire_date',
        'termination_date',
        'job_title',
        'employee_type',
        'salary',
        'salary_type',
        'is_salary_recurring',
        'recurring_day',
        'nssf_number',
        'tin_number',
        'bank_name',
        'bank_account_number',
        'bank_branch',
        'id_type',
        'id_number',
        'qualification',
        'skills',
        'next_of_kin_name',
        'next_of_kin_contact',
        'next_of_kin_relationship',
        'documents',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'salary' => 'integer',
        'is_salary_recurring' => 'boolean',
        'is_active' => 'boolean',
        'skills' => 'array',
        'documents' => 'array',
        'recurring_day' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function employeeSalary()
    {
        return $this->hasOne(EmployeeSalary::class, 'employee_id');
    }

    public function employeeSalaries()
    {
        return $this->hasMany(EmployeeSalary::class, 'employee_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('employee_type', $type);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        if ($this->first_name || $this->last_name) {
            return trim($this->first_name . ' ' . $this->last_name);
        }
        
        if ($this->user) {
            return $this->user->full_name ?? $this->user->name ?? 'Unknown';
        }
        
        return 'Unknown';
    }

    public function getNameAttribute()
    {
        return $this->full_name;
    }

    public function getEmployeeTypeLabelAttribute()
    {
        $types = [
            'full_time' => 'Full Time',
            'part_time' => 'Part Time',
            'contract' => 'Contract',
            'intern' => 'Intern',
            'job_seeker' => 'Job Seeker',
            'employer' => 'Employer',
        ];
        return $types[$this->employee_type] ?? $this->employee_type;
    }

    public function getStatusBadgeAttribute()
    {
        if ($this->is_active) {
            return '<span class="badge badge-light-success">Active</span>';
        }
        return '<span class="badge badge-light-danger">Inactive</span>';
    }
}