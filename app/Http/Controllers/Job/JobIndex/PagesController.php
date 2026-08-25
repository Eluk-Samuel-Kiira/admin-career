<?php

namespace App\Http\Controllers\Job\JobIndex;

use App\Http\Controllers\Controller;
use App\Models\Job\Page;
use App\Models\Job\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PagesController extends Controller
{
    /**
     * Display a listing of pages.
     */
    public function index()
    {
        if (!auth()->user()->can('view pages')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view pages.'
            ]);
        }

        return view('job.job-index.pages');
    }

    /**
     * Get data for DataTable.
     */
    public function getData(Request $request)
    {
        $search = $request->get('search', '');
        $country = $request->get('country', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $query = Page::with('creator');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%')
                  ->orWhere('meta_title', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        if (!empty($country)) {
            $query->where('country_code', $country);
        }

        $pages = $query->orderBy('sort_order', 'asc')
            ->orderBy('title', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        $pages->getCollection()->transform(function ($item) {
            $item->status_badge = $item->is_active 
                ? '<span class="badge badge-light-success">Active</span>' 
                : '<span class="badge badge-light-danger">Inactive</span>';
            $item->template_badge = $this->getTemplateBadge($item->template);
            $item->country_flag = $this->getFlag($item->country_code);
            $item->country_name = $this->getCountryName($item->country_code);
            return $item;
        });

        return response()->json($pages);
    }

    /**
     * Get countries for dropdown.
     */
    public function getCountries()
    {
        $countries = Country::where('is_active', true)
            ->orderBy('name')
            ->get(['code', 'name', 'flag']);

        return response()->json([
            'success' => true,
            'countries' => $countries->map(function($country) {
                return [
                    'code' => $country->code,
                    'name' => $country->name,
                    'flag' => $country->flag,
                ];
            })
        ]);
    }

    /**
     * Get flag for country.
     */
    private function getFlag($code)
    {
        $country = Country::where('code', $code)->first();
        return $country ? $country->flag : '🌍';
    }

    /**
     * Get country name from code.
     */
    private function getCountryName($code)
    {
        $country = Country::where('code', $code)->first();
        return $country ? $country->name : $code;
    }

    /**
     * Get template badge.
     */
    private function getTemplateBadge($template)
    {
        $badges = [
            'default' => '<span class="badge badge-light-primary">Default</span>',
            'contact' => '<span class="badge badge-light-info">Contact</span>',
            'legal' => '<span class="badge badge-light-warning">Legal</span>',
        ];
        return $badges[$template] ?? '<span class="badge badge-light-secondary">Default</span>';
    }

    /**
     * Store a newly created page.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create pages')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create pages.'
            ]);
        }

        try {
            $validated = $request->validate([
                'slug' => 'required|string|max:255',
                'title' => 'required|string|max:255',
                'content' => 'nullable|string',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
                'template' => 'nullable|string|in:default,contact,legal',
                'country_code' => 'required|string|size:2|exists:countries,code',
                'featured_image' => 'nullable|string|max:255',
                'published_at' => 'nullable|date',
            ]);

            $data = $validated;

            // Check if slug exists for this country
            $exists = Page::where('slug', $data['slug'])
                ->where('country_code', $data['country_code'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'A page with this slug already exists for this country.'
                ], 422);
            }

            // Set default template if not provided
            if (empty($data['template'])) {
                $data['template'] = 'default';
            }

            // Handle booleans
            $data['is_active'] = $request->has('is_active') ? (bool) $request->is_active : true;
            $data['is_featured'] = $request->has('is_featured') ? (bool) $request->is_featured : false;

            $data['created_by'] = auth()->id();

            $page = Page::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Page created successfully!',
                'data' => $page
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create page: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create page: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified page.
     */
    public function show($id)
    {
        if (!auth()->user()->can('view pages')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view pages.'
            ]);
        }

        try {
            $page = Page::with('creator')->findOrFail($id);
            return response()->json($page);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found'
            ], 404);
        }
    }

    /**
     * Update the specified page.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit pages')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit pages.'
            ]);
        }

        try {
            $page = Page::findOrFail($id);

            $validated = $request->validate([
                'slug' => 'required|string|max:255',
                'title' => 'required|string|max:255',
                'content' => 'nullable|string',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
                'template' => 'nullable|string|in:default,contact,legal',
                'country_code' => 'required|string|size:2|exists:countries,code',
                'featured_image' => 'nullable|string|max:255',
                'published_at' => 'nullable|date',
            ]);

            $data = $validated;

            // Check if slug exists for this country (excluding current page)
            $exists = Page::where('slug', $data['slug'])
                ->where('country_code', $data['country_code'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'A page with this slug already exists for this country.'
                ], 422);
            }

            // Handle booleans
            $data['is_active'] = $request->has('is_active') ? (bool) $request->is_active : $page->is_active;
            $data['is_featured'] = $request->has('is_featured') ? (bool) $request->is_featured : $page->is_featured;

            $page->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Page updated successfully!',
                'data' => $page->fresh()
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update page: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update page: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified page.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete pages')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete pages.'
            ]);
        }

        try {
            $page = Page::findOrFail($id);
            $page->delete();

            return response()->json([
                'success' => true,
                'message' => 'Page deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete page: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete page: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status of the specified page.
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('edit pages')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit pages.'
            ]);
        }

        try {
            $page = Page::findOrFail($id);
            $page->is_active = !$page->is_active;
            $page->save();

            return response()->json([
                'success' => true,
                'message' => $page->is_active ? 'Page activated successfully!' : 'Page deactivated successfully!',
                'is_active' => $page->is_active
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to toggle status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage()
            ], 500);
        }
    }
}