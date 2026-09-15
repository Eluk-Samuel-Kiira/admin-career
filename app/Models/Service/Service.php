<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'default_turnaround_hours',
        'billing_type',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_turnaround_hours' => 'integer',
        'sort_order' => 'integer',
    ];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function prices()
    {
        return $this->hasMany(ServicePrice::class);
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    // ---------------------------------------------------------------
    // Accessors
    // ---------------------------------------------------------------
    public function getBillingTypeLabelAttribute(): string
    {
        return match ($this->billing_type) {
            'one_time'     => 'One-Time',
            'subscription' => 'Subscription',
            'free'         => 'Free',
            default        => ucfirst($this->billing_type),
        };
    }

    public function getBillingBadgeAttribute(): string
    {
        return match ($this->billing_type) {
            'one_time'     => '<span class="badge badge-light-primary">One-Time</span>',
            'subscription' => '<span class="badge badge-light-warning">Subscription</span>',
            'free'         => '<span class="badge badge-light-success">Free</span>',
            default        => '<span class="badge badge-light-secondary">' . e($this->billing_type) . '</span>',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active
            ? '<span class="badge badge-light-success">Active</span>'
            : '<span class="badge badge-light-danger">Inactive</span>';
    }

    public function getTurnaroundLabelAttribute(): string
    {
        $h = $this->default_turnaround_hours;
        if ($h < 24) {
            return $h . ' hour' . ($h === 1 ? '' : 's');
        }
        $days = intdiv($h, 24);
        $rem  = $h % 24;
        $label = $days . ' day' . ($days === 1 ? '' : 's');
        if ($rem > 0) {
            $label .= ' ' . $rem . 'h';
        }
        return $label;
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------
    public static function generateKey(string $name): string
    {
        return Str::slug($name, '_');
    }

    public static function getBillingTypes(): array
    {
        return [
            'one_time'     => 'One-Time',
            'subscription' => 'Subscription',
            'free'         => 'Free',
        ];
    }



}