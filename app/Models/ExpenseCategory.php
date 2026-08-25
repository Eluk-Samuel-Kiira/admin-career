<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ExpenseCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'expense_categories';

    protected $fillable = [
        'name',
        'code',
        'description',
        'requires_receipt',
        'requires_approval',
        'budget_monthly',
        'budget_annual',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'requires_receipt' => 'boolean',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'budget_monthly' => 'decimal:2',
        'budget_annual' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = strtoupper(substr(Str::slug($model->name), 0, 10));
            }
        });
    }

    // Relationships - Explicitly specify the foreign key
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    public function scopeRequiresReceipt($query)
    {
        return $query->where('requires_receipt', true);
    }

    // Accessors
    public function getFormattedMonthlyBudgetAttribute()
    {
        return '$' . number_format($this->budget_monthly, 2);
    }

    public function getFormattedAnnualBudgetAttribute()
    {
        return '$' . number_format($this->budget_annual, 2);
    }
}