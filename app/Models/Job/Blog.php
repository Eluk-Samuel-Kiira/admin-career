<?php

namespace App\Models\Job;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ Log, Storage };

class Blog extends Model
{
    use SoftDeletes;

    protected $table = 'blogs';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image',
        'cover_image_alt',
        'cover_image_caption',
        'category',
        'tags',
        'reading_time',
        'author_id',
        'author_name',
        'author_title',
        'author_avatar',
        'country_code',
        'is_active',
        'is_featured',
        'is_published',
        'published_at',
        'meta_title',
        'meta_description',
        'keywords',
        'canonical_url',
        'og_image',
        'og_title',
        'og_description',
        // 'robots',
        'is_pinged',
        'last_pinged_at',
        'submitted_to_indexing',
        'indexing_submitted_at',
        'indexing_status',
        'indexing_response',
        'is_indexed',
        'view_count',
        'share_count',
        'like_count',
        'comment_count',
        'seo_score',
        'content_quality_score',
        'sort_order',
        'featured_until',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'indexing_response' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'is_pinged' => 'boolean',
        'submitted_to_indexing' => 'boolean',
        'is_indexed' => 'boolean',
        'published_at' => 'datetime',
        'last_pinged_at' => 'datetime',
        'indexing_submitted_at' => 'datetime',
        'featured_until' => 'datetime',
    ];

    // Relationships
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_active', true)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeByCountry($query, $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
            ->where(function($q) {
                $q->whereNull('featured_until')
                    ->orWhere('featured_until', '>=', now());
            });
    }

    // Accessors
    public function getFormattedReadingTimeAttribute()
    {
        if ($this->reading_time) return $this->reading_time;
        $words = str_word_count(strip_tags($this->content ?? ''));
        $mins = max(1, (int) ceil($words / 200));
        return "{$mins} min read";
    }

    public function getStatusBadgeAttribute()
    {
        if (!$this->is_active) {
            return '<span class="badge badge-light-secondary">Inactive</span>';
        }
        if (!$this->is_published) {
            return '<span class="badge badge-light-warning">Draft</span>';
        }
        if ($this->published_at && $this->published_at > now()) {
            return '<span class="badge badge-light-info">Scheduled</span>';
        }
        return '<span class="badge badge-light-success">Published</span>';
    }

    public function getFeaturedBadgeAttribute()
    {
        if ($this->is_featured) {
            return '<span class="badge badge-light-warning">⭐ Featured</span>';
        }
        return '<span class="badge badge-light-secondary">-</span>';
    }

    // Boot
    protected static function booted()
    {
        static::creating(function ($blog) {
            if (empty($blog->slug)) {
                $blog->slug = static::generateUniqueSlug($blog->title);
            }
            if (empty($blog->reading_time)) {
                $words = str_word_count(strip_tags($blog->content ?? ''));
                $mins = max(1, (int) ceil($words / 200));
                $blog->reading_time = "{$mins} min read";
            }
        });

        static::updating(function ($blog) {
            if ($blog->isDirty('content')) {
                $words = str_word_count(strip_tags($blog->content ?? ''));
                $mins = max(1, (int) ceil($words / 200));
                $blog->reading_time = "{$mins} min read";
            }
        });
    }

    public static function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;
        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    /**
     * Get the cover image URL - Handles both legacy and new blog cover images
     */
    public function getCoverImageUrlAttribute()
    {
        if (!$this->cover_image) {
            return asset('assets/media/books/img-72.jpg');
        }

        $countryCode = strtolower($this->country_code ?? 'ug');
        $coverName = $this->cover_image;

        // If it's already a full URL, return it
        if (filter_var($this->cover_image, FILTER_VALIDATE_URL)) {
            return $this->cover_image;
        }

        // Remove /storage/ prefix if present in the database value
        $cleanPath = str_replace('/storage/', '', $this->cover_image);
        $cleanPath = str_replace('storage/', '', $cleanPath);

        // Try different possible paths
        $possiblePaths = [
            $cleanPath, // Direct path from database
            "blog/covers/{$cleanPath}",
            "blog/covers/" . basename($cleanPath),
            "blogs/{$countryCode}/covers/" . basename($cleanPath),
            "blogs/covers/" . basename($cleanPath),
            $this->cover_image,
        ];

        foreach ($possiblePaths as $path) {
            if (Storage::disk('public')->exists($path)) {
                return asset('storage/' . $path);
            }
        }

        // If the path starts with 'storage/' in the database, try that
        if (str_starts_with($this->cover_image, 'storage/')) {
            $path = str_replace('storage/', '', $this->cover_image);
            if (Storage::disk('public')->exists($path)) {
                return asset('storage/' . $path);
            }
        }

        // If the path starts with '/storage/' in the database, try that
        if (str_starts_with($this->cover_image, '/storage/')) {
            $path = str_replace('/storage/', '', $this->cover_image);
            if (Storage::disk('public')->exists($path)) {
                return asset('storage/' . $path);
            }
        }

        // Check if it's just a filename
        $filename = basename($this->cover_image);
        $possibleFilenames = [
            "blog/covers/{$filename}",
            "blogs/{$countryCode}/covers/{$filename}",
            "blogs/covers/{$filename}",
            "covers/{$filename}",
        ];

        foreach ($possibleFilenames as $path) {
            if (Storage::disk('public')->exists($path)) {
                return asset('storage/' . $path);
            }
        }

        // Return default image if no image found
        return asset('assets/media/books/img-72.jpg');
    }

}