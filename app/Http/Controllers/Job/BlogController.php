<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Models\Job\Blog;
use App\Models\Job\Country;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, DB, Storage, Validator };
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException; // <-- was missing, caused fatal errors on validation failure

class BlogController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('view blogs')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to view blogs.']);
        }
        return view('job.blog.index');
    }

    public function create()
    {
        if (!auth()->user()->can('create blogs')) {
            abort(403, 'You do not have permission to create blogs.');
        }

        $countries = Country::where('is_active', true)->orderBy('name')->get();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('job.blog.create', compact('countries', 'users'));
    }

    public function edit($id)
    {
        if (!auth()->user()->can('edit blogs')) {
            abort(403, 'You do not have permission to edit blogs.');
        }

        $blog = Blog::with(['author', 'country'])->findOrFail($id);
        $countries = Country::where('is_active', true)->orderBy('name')->get();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('job.blog.edit', compact('blog', 'countries', 'users'));
    }

    public function getData(Request $request)
    {
        $search = $request->get('search', '');
        $country = $request->get('country', '');
        $category = $request->get('category', '');
        $status = $request->get('status', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $query = Blog::with(['author', 'country']);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                ->orWhere('excerpt', 'like', '%' . $search . '%')
                ->orWhere('content', 'like', '%' . $search . '%')
                ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        if (!empty($country)) $query->where('country_code', $country);
        if (!empty($category)) $query->where('category', $category);

        if (!empty($status)) {
            if ($status === 'published') {
                $query->where('is_published', true)->where('is_active', true);
            } elseif ($status === 'draft') {
                $query->where('is_published', false);
            } elseif ($status === 'scheduled') {
                $query->where('is_published', true)->where('published_at', '>', now());
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $blogs = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        $blogs->getCollection()->transform(function ($item) {
            $item->country_flag = $item->country ? $item->country->flag : '🌍';
            $item->country_name = $item->country ? $item->country->name : 'N/A';
            $item->status_badge = $this->getStatusBadge($item);
            $item->featured_badge = $item->is_featured
                ? '<span class="badge badge-light-warning">⭐ Featured</span>'
                : '<span class="badge badge-light-secondary">-</span>';
            $item->author_name_display = $item->author ? $item->author->name : ($item->author_name ?? 'Unknown');
            return $item;
        });

        return response()->json($blogs);
    }

    public function getFilters(Request $request)
    {
        $country = $request->get('country', '');

        $query = Blog::query();
        if (!empty($country)) $query->where('country_code', $country);

        $categories = $query->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->values();

        return response()->json(['success' => true, 'categories' => $categories]);
    }

    /**
     * Distinct tags across all blogs, used for the tag autocomplete/datalist.
     */
    public function getTags()
    {
        $tags = Blog::whereNotNull('tags')
            ->pluck('tags')
            ->flatMap(function ($t) {
                if (is_string($t)) {
                    $decoded = json_decode($t, true);
                    return is_array($decoded) ? $decoded : [];
                }
                return is_array($t) ? $t : [];
            })
            ->filter()
            ->unique()
            ->values();

        return response()->json(['success' => true, 'tags' => $tags]);
    }

    public function getCountries()
    {
        $countries = Country::where('is_active', true)->orderBy('name')->get(['code', 'name', 'flag']);

        return response()->json([
            'success' => true,
            'countries' => $countries->map(fn($c) => [
                'code' => $c->code, 'name' => $c->name, 'flag' => $c->flag,
            ]),
        ]);
    }

    /**
     * Cover image upload used by both the create and edit pages.
     */
    public function uploadCover(Request $request)
    {
        if (!auth()->user()->can('create blogs') && !auth()->user()->can('edit blogs')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to upload images.'], 403);
        }

        $request->validate([
            'cover_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        try {
            $path = $request->file('cover_image')->store('blogs/covers', 'public');
            return response()->json(['success' => true, 'url' => Storage::url($path)]);
        } catch (\Exception $e) {
            \Log::error('Cover image upload failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to upload image.'], 500);
        }
    }

    /**
     * Normalize a comma-separated tags string into an array before validation.
     */
    private function normalizeTagsInput(Request $request): void
    {
        if ($request->has('tags') && is_string($request->input('tags'))) {
            $raw = $request->input('tags');
            $decoded = json_decode($raw, true);
            $tags = is_array($decoded)
                ? $decoded
                : array_values(array_filter(array_map('trim', explode(',', $raw))));
            $request->merge(['tags' => $tags]);
        }
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('create blogs')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to create blogs.']);
        }

        $this->normalizeTagsInput($request);

        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'excerpt' => 'nullable|string|max:500',
                'category' => 'nullable|string|max:100',
                'tags' => 'nullable|array',
                'cover_image' => 'nullable|string|max:500',
                'cover_image_alt' => 'nullable|string|max:255',
                'cover_image_caption' => 'nullable|string|max:255',
                'author_id' => 'nullable|exists:users,id',
                'author_name' => 'nullable|string|max:255',
                'author_title' => 'nullable|string|max:255',
                'author_avatar' => 'nullable|url|max:500',
                'country_code' => 'required|string|size:2|exists:countries,code',
                'is_active' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean',
                'is_published' => 'nullable|boolean',
                'published_at' => 'nullable|date',
                'sort_order' => 'nullable|integer|min:0',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'keywords' => 'nullable|string|max:500',
                'canonical_url' => 'nullable|url|max:500',
                'og_image' => 'nullable|url|max:500',
                'og_title' => 'nullable|string|max:255',
                'og_description' => 'nullable|string|max:500',
                // 'robots' => 'nullable|string|in:index,follow,noindex,nofollow,index,follow,noindex,nofollow',
            ]);

            $data = $validated;

            $booleanFields = ['is_active', 'is_featured', 'is_published'];
            foreach ($booleanFields as $field) {
                if ($request->has($field)) {
                    $value = $request->input($field);
                    $data[$field] = is_string($value)
                        ? in_array(strtolower($value), ['on', '1', 'true', 'yes'])
                        : (bool) $value;
                } else {
                    $data[$field] = false;
                }
            }

            if (empty($data['author_id']) && auth()->check()) {
                $data['author_id'] = auth()->id();
                $data['author_name'] = $data['author_name'] ?? auth()->user()->name;
            }

            if (empty($data['slug'])) {
                $data['slug'] = Blog::generateUniqueSlug($data['title']);
            }

            if (empty($data['meta_title']) && !empty($data['title'])) {
                $data['meta_title'] = $this->generateMetaTitle($data['title']);
            }

            if (empty($data['meta_description'])) {
                $data['meta_description'] = $this->generateMetaDescription($data);
            }

            if (!empty($data['is_published']) && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $data['created_by'] = auth()->id();

            $blog = Blog::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Blog created successfully!',
                'data' => $blog,
            ]);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create blog: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create blog: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        if (!auth()->user()->can('view blogs')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to view blogs.']);
        }

        try {
            $blog = Blog::with(['author', 'country'])->findOrFail($id);
            return response()->json($blog);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Blog not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit blogs')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to edit blogs.']);
        }

        $this->normalizeTagsInput($request);

        try {
            $blog = Blog::findOrFail($id);

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'excerpt' => 'nullable|string|max:500',
                'category' => 'nullable|string|max:100',
                'tags' => 'nullable|array',
                'cover_image' => 'nullable|string|max:500',
                'cover_image_alt' => 'nullable|string|max:255',
                'cover_image_caption' => 'nullable|string|max:255',
                'author_id' => 'nullable|exists:users,id',
                'author_name' => 'nullable|string|max:255',
                'author_title' => 'nullable|string|max:255',
                'author_avatar' => 'nullable|url|max:500',
                'country_code' => 'required|string|size:2|exists:countries,code',
                'is_active' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean',
                'is_published' => 'nullable|boolean',
                'published_at' => 'nullable|date',
                'sort_order' => 'nullable|integer|min:0',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'keywords' => 'nullable|string|max:500',
                'canonical_url' => 'nullable|url|max:500',
                'og_image' => 'nullable|url|max:500',
                'og_title' => 'nullable|string|max:255',
                'og_description' => 'nullable|string|max:500',
                // 'robots' => 'nullable|string|in:index,follow,noindex,nofollow,index,nofollow,noindex,follow',
            ]);

            $data = $validated;

            $booleanFields = ['is_active', 'is_featured', 'is_published'];
            foreach ($booleanFields as $field) {
                if ($request->has($field)) {
                    $value = $request->input($field);
                    $data[$field] = is_string($value)
                        ? in_array(strtolower($value), ['on', '1', 'true', 'yes'])
                        : (bool) $value;
                } else {
                    $data[$field] = $blog->$field;
                }
            }

            if (empty($data['meta_title']) && !empty($data['title'])) {
                $data['meta_title'] = $this->generateMetaTitle($data['title']);
            }

            if (empty($data['meta_description'])) {
                $metaData = [
                    'title' => $data['title'] ?? $blog->title,
                    'excerpt' => $data['excerpt'] ?? $blog->excerpt,
                    'content' => $data['content'] ?? $blog->content,
                ];
                $data['meta_description'] = $this->generateMetaDescription($metaData);
            }

            if (!empty($data['is_published']) && empty($blog->published_at) && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $data['updated_by'] = auth()->id();

            $blog->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Blog updated successfully!',
                'data' => $blog->fresh(),
            ]);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update blog: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update blog: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('delete blogs')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to delete blogs.']);
        }

        try {
            $blog = Blog::findOrFail($id);

            if ($blog->cover_image && !filter_var($blog->cover_image, FILTER_VALIDATE_URL)) {
                $coverPath = str_replace('/storage/', '', $blog->cover_image);
                if (Storage::disk('public')->exists($coverPath)) {
                    Storage::disk('public')->delete($coverPath);
                }
            }

            $blog->delete();

            return response()->json(['success' => true, 'message' => 'Blog deleted successfully!']);
        } catch (\Exception $e) {
            \Log::error('Failed to delete blog: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete blog: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('edit blogs')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to edit blogs.']);
        }

        try {
            $blog = Blog::findOrFail($id);
            $blog->is_active = !$blog->is_active;
            
            // If deactivating, also unpublish
            if (!$blog->is_active) {
                $blog->is_published = false;
            }
            
            $blog->save();

            return response()->json([
                'success' => true,
                'message' => $blog->is_active ? 'Blog activated successfully!' : 'Blog deactivated and unpublished!',
                'is_active' => $blog->is_active,
                'is_published' => $blog->is_published,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle status: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to toggle status: ' . $e->getMessage()], 500);
        }
    }

    public function toggleFeatured($id)
    {
        if (!auth()->user()->can('edit blogs')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to edit blogs.']);
        }

        try {
            $blog = Blog::findOrFail($id);
            $blog->is_featured = !$blog->is_featured;
            $blog->save();

            return response()->json([
                'success' => true,
                'message' => $blog->is_featured ? 'Blog featured successfully!' : 'Blog unfeatured successfully!',
                'is_featured' => $blog->is_featured,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to toggle featured: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to toggle featured: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Publish a blog post
     */
    public function publish($id)
    {
        if (!auth()->user()->can('edit blogs')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to publish blogs.']);
        }

        try {
            $blog = Blog::findOrFail($id);
            $blog->is_published = true;
            $blog->is_active = true;
            
            // If published_at is null or in the future, set it to now
            if (empty($blog->published_at) || $blog->published_at->isFuture()) {
                $blog->published_at = now();
            }
            
            $blog->save();

            return response()->json([
                'success' => true,
                'message' => 'Blog published successfully!',
                'data' => [
                    'is_published' => $blog->is_published,
                    'is_active' => $blog->is_active,
                    'published_at' => $blog->published_at ? $blog->published_at->format('Y-m-d H:i:s') : null,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to publish blog: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to publish blog: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Unpublish a blog post
     */
    public function unpublish($id)
    {
        if (!auth()->user()->can('edit blogs')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to unpublish blogs.']);
        }

        try {
            $blog = Blog::findOrFail($id);
            $blog->is_published = false;
            $blog->save();

            return response()->json([
                'success' => true,
                'message' => 'Blog unpublished successfully!',
                'data' => [
                    'is_published' => $blog->is_published,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to unpublish blog: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to unpublish blog: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get status badge for blog
     */
    private function getStatusBadge($blog)
    {
        if (!$blog->is_active) {
            return '<span class="badge badge-light-secondary">Inactive</span>';
        }
        if (!$blog->is_published) {
            return '<span class="badge badge-light-warning">Draft</span>';
        }
        // Check if published_at is set and is in the future
        if ($blog->published_at && $blog->published_at->isFuture()) {
            return '<span class="badge badge-light-info">Scheduled</span>';
        }
        return '<span class="badge badge-light-success">Published</span>';
    }

    private function generateMetaTitle(string $title): string
    {
        $title = trim($title);
        if (mb_strlen($title) > 60) $title = mb_substr($title, 0, 57) . '...';
        return $title;
    }

    private function generateMetaDescription(array $data): string
    {
        $text = '';

        if (!empty($data['excerpt'])) {
            $text = strip_tags($data['excerpt']);
        } elseif (!empty($data['content'])) {
            $text = substr(strip_tags($data['content']), 0, 300);
        } elseif (!empty($data['title'])) {
            $text = "Read our latest blog post: " . $data['title'];
        }

        if (empty($text)) {
            return 'Latest blog post from Great Jobs. Get career tips, job search advice, and industry insights.';
        }

        if (mb_strlen($text) > 160) $text = mb_substr($text, 0, 157) . '...';

        return $text;
    }
}