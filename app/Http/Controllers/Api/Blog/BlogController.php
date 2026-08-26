<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\Job\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Log, Storage };
use Illuminate\Support\Str;

class BlogController extends Controller
{
    /**
     * Get blogs with filters and pagination - FRONTEND API
     */
    public function index(Request $request)
    {
        try {
            // \Log::info($request->all());
            $countryCode = $request->input('country_code', 'UG');
            $perPage = $request->input('per_page', 12);

            $query = Blog::with(['author'])
                ->where('country_code', $countryCode)
                ->where('is_active', true)
                ->where('is_published', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now());

            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('content', 'LIKE', "%{$search}%")
                      ->orWhere('excerpt', 'LIKE', "%{$search}%")
                      ->orWhere('category', 'LIKE', "%{$search}%")
                      ->orWhere('tags', 'LIKE', "%{$search}%");
                });
            }

            // Category filter
            if ($request->has('category') && !empty($request->category)) {
                $query->where('category', $request->category);
            }

            // Featured filter
            if ($request->has('featured') && $request->featured) {
                $query->where('is_featured', true);
            }

            // Exclude specific blog
            if ($request->has('exclude') && !empty($request->exclude)) {
                $query->where('id', '!=', $request->exclude);
            }

            // Sort
            $sort = $request->get('sort', 'newest');
            switch ($sort) {
                case 'oldest':
                    $query->orderBy('published_at', 'asc');
                    break;
                case 'popular':
                    $query->orderBy('view_count', 'desc');
                    break;
                default:
                    $query->orderBy('published_at', 'desc');
            }

            $blogs = $query->paginate($perPage);

            $formattedBlogs = $blogs->getCollection()->map(function($blog) {
                return $this->formatBlogData($blog);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedBlogs,
                'pagination' => [
                    'current_page' => $blogs->currentPage(),
                    'last_page' => $blogs->lastPage(),
                    'per_page' => $blogs->perPage(),
                    'total' => $blogs->total(),
                    'prev_page_url' => $blogs->previousPageUrl(),
                    'next_page_url' => $blogs->nextPageUrl(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching blogs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch blogs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single blog by slug or ID
     */
    public function show(Request $request, $identifier)
    {
        try {
            $countryCode = $request->input('country_code', 'UG');

            $blog = Blog::with(['author'])
                ->where('country_code', $countryCode)
                ->where(function($q) use ($identifier) {
                    $q->where('slug', $identifier)
                      ->orWhere('id', $identifier);
                })
                ->where('is_active', true)
                ->where('is_published', true)
                ->first();

            if (!$blog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blog not found'
                ], 404);
            }

            // Increment view count
            $blog->increment('view_count');

            return response()->json([
                'success' => true,
                'data' => $this->formatBlogData($blog, true)
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching blog: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch blog'
            ], 500);
        }
    }

    /**
     * Get blog categories (distinct from blog table)
     */
    public function categories(Request $request)
    {
        try {
            $countryCode = $request->input('country_code', 'UG');

            $categories = Blog::where('country_code', $countryCode)
                ->where('is_active', true)
                ->where('is_published', true)
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct()
                ->pluck('category')
                ->map(function($cat) {
                    return [
                        'id' => null,
                        'name' => $cat,
                        'slug' => Str::slug($cat),
                        'count' => Blog::where('category', $cat)
                            ->where('country_code', request()->input('country_code', 'UG'))
                            ->where('is_active', true)
                            ->where('is_published', true)
                            ->count(),
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching blog categories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => []
            ]);
        }
    }

    /**
     * Track blog view count
     */
    public function trackView(Request $request, $id)
    {
        try {
            $blog = Blog::find($id);

            if (!$blog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blog not found',
                ], 404);
            }

            $blog->increment('view_count');

            return response()->json([
                'success' => true,
                'view_count' => $blog->view_count,
            ]);

        } catch (\Exception $e) {
            Log::error('Error tracking blog view: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to track view',
            ], 500);
        }
    }

    /**
     * Format blog data for API response
     */
    private function formatBlogData($blog, $detailed = false)
    {
        try {
            $baseData = [
                'id' => $blog->id,
                'title' => $blog->title ?? '',
                'slug' => $blog->slug ?? '',
                'content' => $blog->content ?? '',
                'excerpt' => $blog->excerpt ?? $this->generateExcerpt($blog->content ?? ''),
                'category' => $blog->category ?? null,
                'tags' => $this->parseTags($blog->tags),
                // Use the accessor - this will return the full URL
                'cover_image' => $blog->cover_image_url,
                'cover_image_alt' => $blog->cover_image_alt ?? '',
                'cover_image_caption' => $blog->cover_image_caption ?? '',
                
                'is_featured' => (bool) ($blog->is_featured ?? false),
                'is_active' => (bool) ($blog->is_active ?? true),
                'is_published' => (bool) ($blog->is_published ?? false),
                
                'view_count' => (int) ($blog->view_count ?? 0),
                'share_count' => (int) ($blog->share_count ?? 0),
                
                'published_at' => $blog->published_at ? $blog->published_at->format('Y-m-d H:i:s') : null,
                'created_at' => $blog->created_at ? $blog->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $blog->updated_at ? $blog->updated_at->format('Y-m-d H:i:s') : null,
                
                'author_name' => $blog->author_name ?? ($blog->author ? $blog->author->name : null),
                'author_title' => $blog->author_title ?? null,
                'author_avatar' => $blog->author_avatar ?? null,
            ];

            if ($detailed) {
                $baseData['meta_title'] = $blog->meta_title ?? $blog->title ?? '';
                $baseData['meta_description'] = $blog->meta_description ?? $this->generateExcerpt($blog->content ?? '', 160);
                $baseData['keywords'] = $blog->keywords ?? '';
                $baseData['canonical_url'] = $blog->canonical_url ?? null;
                // Use the accessor for OG image as well
                $baseData['og_image'] = $blog->og_image ?? $blog->cover_image_url ?? null;
                $baseData['og_title'] = $blog->og_title ?? $blog->title ?? '';
                $baseData['og_description'] = $blog->og_description ?? $this->generateExcerpt($blog->content ?? '', 160);
                
                $baseData['author_id'] = $blog->author_id ?? null;
                $baseData['author'] = $blog->author ? [
                    'id' => $blog->author->id,
                    'name' => $blog->author->name,
                    'email' => $blog->author->email,
                ] : null;
            }

            // Read time
            $baseData['read_time'] = $this->calculateReadTime($blog->content ?? '');

            return $baseData;

        } catch (\Exception $e) {
            Log::error('Error formatting blog data: ' . $e->getMessage());
            return [
                'id' => $blog->id ?? null,
                'title' => $blog->title ?? 'Unknown Blog',
                'slug' => $blog->slug ?? '',
            ];
        }
    }

    /**
     * Parse tags from string or array
     */
    private function parseTags($tags)
    {
        if (empty($tags)) {
            return [];
        }

        if (is_array($tags)) {
            return $tags;
        }

        if (is_string($tags)) {
            // Try to decode JSON
            $decoded = json_decode($tags, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            
            // Split by comma
            return array_values(array_filter(array_map('trim', explode(',', $tags))));
        }

        return [];
    }

    /**
     * Generate excerpt from content
     */
    private function generateExcerpt($content, $length = 150)
    {
        if (empty($content)) {
            return '';
        }
        
        $text = strip_tags($content);
        return Str::limit($text, $length);
    }

    /**
     * Calculate read time in minutes
     */
    private function calculateReadTime($content)
    {
        if (empty($content)) {
            return '1 min read';
        }
        
        $words = str_word_count(strip_tags($content));
        $minutes = max(1, ceil($words / 200));
        return $minutes . ' min read';
    }
}