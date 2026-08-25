<?php

namespace App\Models\Job;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Job\JobPost;
use App\Models\Job\Company;

class Industry extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'icon',
        'is_active',
        'sort_order',
        'estimated_salary',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'estimated_salary' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($industry) {
            if (empty($industry->slug)) {
                $industry->slug = Str::slug($industry->name);
            }
            if (empty($industry->meta_title)) {
                $industry->meta_title = "{$industry->name} Industry Jobs in Uganda - Career Opportunities";
            }
            if (empty($industry->meta_description)) {
                $industry->meta_description = "Find {$industry->name} industry jobs in Uganda. Browse career opportunities in {$industry->name} sector and apply for positions.";
            }
            if (empty($industry->created_by) && auth()->check()) {
                $industry->created_by = auth()->id();
            }
        });

        static::updating(function ($industry) {
            if ($industry->isDirty('name') && empty($industry->slug)) {
                $industry->slug = Str::slug($industry->name);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('sort_order', '<=', 15);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSeoAttributes()
    {
        return [
            'title' => $this->meta_title ?? "{$this->name} Industry Jobs in Uganda",
            'description' => $this->meta_description,
            'keywords' => "{$this->name} industry Uganda, {$this->name} sector jobs, {$this->name} careers Uganda"
        ];
    }

    public function getUrlAttribute()
    {
        return url("/industry/{$this->slug}");
    }

    public function jobPosts()
    {
        return $this->hasMany(JobPost::class, 'industry_id');
    }

    public function companies()
    {
        return $this->hasMany(Company::class, 'industry_id');
    }
}
