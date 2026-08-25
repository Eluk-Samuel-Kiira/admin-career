<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
        'icon',
        'color',
        'head_of_department_id',
        'email',
        'phone',
        'sort_order',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
            if (empty($model->code)) {
                $model->code = strtoupper(substr($model->slug, 0, 10));
            }
        });
    }

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Head of Department relationship - only users with admin/supervisor/manager roles
     * Excludes job_seeker, employer, and job_poster roles
     */
    public function headOfDepartment()
    {
        return $this->belongsTo(User::class, 'head_of_department_id')
            ->whereHas('roles', function($query) {
                $query->whereNotIn('name', ['job_seeker', 'employer', 'job_poster']);
            });
    }

    /**
     * Get all potential heads of department (users with leadership roles)
     */
    public static function getPotentialHeads()
    {
        return User::whereHas('roles', function($query) {
                $query->whereIn('name', ['super_admin', 'admin', 'supervisor', 'manager', 'director']);
            })
            ->orderBy('name')
            ->get();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Accessors
    public function getUserCountAttribute()
    {
        return $this->users()->count();
    }

    public function getFormattedNameAttribute()
    {
        return $this->name . ' (' . $this->code . ')';
    }

    public function getHeadOfDepartmentNameAttribute()
    {
        if ($this->headOfDepartment) {
            return $this->headOfDepartment->name;
        }
        return 'Not Assigned';
    }
}