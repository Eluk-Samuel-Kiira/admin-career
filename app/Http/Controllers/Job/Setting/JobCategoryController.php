<?php

namespace App\Http\Controllers\Job\Setting;

use App\Http\Controllers\Controller;
use App\Models\Job\JobCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JobCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('view job categories')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view job categories.'
            ]);
        }
        return view('job.settings.job-categories');
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

        $query = JobCategory::with('migratedBy');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('legacy_alias', 'like', '%' . $search . '%')
                  ->orWhere('legacy_id', $search);
            });
        }

        if (!empty($country)) {
            $query->where('country_code', $country);
        }

        $categories = $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Format the data for display
        $categories->getCollection()->transform(function ($item) {
            $item->country_name = $item->country_name;
            $item->status_badge = $item->status_badge;
            $item->migration_status_badge = $item->migration_status_badge;
            return $item;
        });

        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create job categories')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create job categories.'
            ]);
        }
        try {
            // \Log::info('Store job category request: ', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'country_code' => 'required|string|size:2',
                'icon' => 'nullable|string|max:50',
                'color' => 'nullable|string|max:20',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'is_default' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            $data = $validated;
            
            // Handle is_active - convert from 'on' to boolean
            if ($request->has('is_active')) {
                $isActive = $request->input('is_active');
                if (is_string($isActive)) {
                    $data['is_active'] = in_array(strtolower($isActive), ['on', '1', 'true', 'yes']);
                } else {
                    $data['is_active'] = (bool) $isActive;
                }
            } else {
                $data['is_active'] = false;
            }

            // Handle is_default
            if ($request->has('is_default')) {
                $isDefault = $request->input('is_default');
                if (is_string($isDefault)) {
                    $data['is_default'] = in_array(strtolower($isDefault), ['on', '1', 'true', 'yes']);
                } else {
                    $data['is_default'] = (bool) $isDefault;
                }
            } else {
                $data['is_default'] = false;
            }
            
            // Generate slug
            $data['slug'] = Str::slug($data['name'] . '-' . $data['country_code']);
            $slug = $data['slug'];
            $counter = 1;
            while (JobCategory::where('slug', $slug)->exists()) {
                $slug = $data['slug'] . '-' . $counter++;
            }
            $data['slug'] = $slug;

            // Set meta title if not provided
            if (empty($data['meta_title'])) {
                $countryName = $this->getCountryName($data['country_code']);
                $data['meta_title'] = "{$data['name']} Jobs in {$countryName} - Career Opportunities";
            }

            // Set meta description if not provided
            if (empty($data['meta_description'])) {
                $countryName = $this->getCountryName($data['country_code']);
                $data['meta_description'] = "Find {$data['name']} jobs in {$countryName}. Browse career opportunities and vacancies in the {$data['name']} industry.";
            }

            // Set default color if not provided
            if (empty($data['color'])) {
                $colors = ['primary', 'success', 'info', 'warning', 'danger', 'dark', 'purple', 'teal'];
                $data['color'] = $colors[array_rand($colors)];
            }

            // \Log::info('Final job category data: ', $data);

            $category = JobCategory::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Job category created successfully!',
                'data' => $category
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create job category: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create job category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        
        if (!auth()->user()->can('view job categories')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view job categories.'
            ]);
        }

        try {
            $category = JobCategory::with('migratedBy')->findOrFail($id);
            return response()->json($category);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Job category not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit job categories')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit job categories.'
            ]);
        }

        try {
            // \Log::info('Update job category request for ID ' . $id . ': ', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'country_code' => 'required|string|size:2',
                'icon' => 'nullable|string|max:50',
                'color' => 'nullable|string|max:20',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'is_default' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            $category = JobCategory::findOrFail($id);
            
            $data = $validated;
            
            // Handle is_active - convert from 'on' to boolean
            if ($request->has('is_active')) {
                $isActive = $request->input('is_active');
                if (is_string($isActive)) {
                    $data['is_active'] = in_array(strtolower($isActive), ['on', '1', 'true', 'yes']);
                } else {
                    $data['is_active'] = (bool) $isActive;
                }
            } else {
                $data['is_active'] = false;
            }

            // Handle is_default
            if ($request->has('is_default')) {
                $isDefault = $request->input('is_default');
                if (is_string($isDefault)) {
                    $data['is_default'] = in_array(strtolower($isDefault), ['on', '1', 'true', 'yes']);
                } else {
                    $data['is_default'] = (bool) $isDefault;
                }
            } else {
                $data['is_default'] = false;
            }
            
            // Update slug if name or country changed
            if (isset($data['name']) && $category->name !== $data['name'] || 
                isset($data['country_code']) && $category->country_code !== $data['country_code']) {
                $data['slug'] = Str::slug($data['name'] . '-' . $data['country_code']);
                $slug = $data['slug'];
                $counter = 1;
                while (JobCategory::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $data['slug'] . '-' . $counter++;
                }
                $data['slug'] = $slug;
            }

            // Set meta title if not provided
            if (empty($data['meta_title'])) {
                $countryName = $this->getCountryName($data['country_code']);
                $data['meta_title'] = "{$data['name']} Jobs in {$countryName} - Career Opportunities";
            }

            // Set meta description if not provided
            if (empty($data['meta_description'])) {
                $countryName = $this->getCountryName($data['country_code']);
                $data['meta_description'] = "Find {$data['name']} jobs in {$countryName}. Browse career opportunities and vacancies in the {$data['name']} industry.";
            }

            // \Log::info('Final job category data for update: ', $data);

            $category->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Job category updated successfully!',
                'data' => $category->fresh()
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Job category not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Job category not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to update job category: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update job category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete job categories')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete job categories.'
            ]);
        }
        try {
            $category = JobCategory::findOrFail($id);
            
            // Check if it has related jobs
            if ($category->jobs()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this category because it is being used by jobs.'
                ], 400);
            }
            
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job category deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete job category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status of the specified resource.
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('edit job categories')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit job categories.'
            ]);
        }
        
        try {
            $category = JobCategory::findOrFail($id);
            $category->is_active = !$category->is_active;
            $category->save();

            return response()->json([
                'success' => true,
                'message' => $category->is_active ? 'Category activated successfully!' : 'Category deactivated successfully!',
                'is_active' => $category->is_active
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to toggle status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available icons.
     */
    public function getIcons()
    {
        $icons = [
            'ki-briefcase' => 'Briefcase',
            'ki-code' => 'Code',
            'ki-design' => 'Design',
            'ki-rocket' => 'Rocket',
            'ki-chart' => 'Chart',
            'ki-people' => 'People',
            'ki-user' => 'User',
            'ki-users' => 'Users',
            'ki-building' => 'Building',
            'ki-home' => 'Home',
            'ki-laptop' => 'Laptop',
            'ki-phone' => 'Phone',
            'ki-email' => 'Email',
            'ki-file' => 'File',
            'ki-folder' => 'Folder',
            'ki-document' => 'Document',
            'ki-book' => 'Book',
            'ki-education' => 'Education',
            'ki-graduation' => 'Graduation',
            'ki-medical' => 'Medical',
            'ki-cash' => 'Cash',
            'ki-coin' => 'Coin',
            'ki-crown' => 'Crown',
            'ki-star' => 'Star',
            'ki-heart' => 'Heart',
            'ki-shield' => 'Shield',
            'ki-tag' => 'Tag',
            'ki-globe' => 'Globe',
            'ki-earth' => 'Earth',
            'ki-database' => 'Database',
            'ki-cloud' => 'Cloud',
            'ki-server' => 'Server',
        ];

        return response()->json([
            'success' => true,
            'icons' => $icons
        ]);
    }

    /**
     * Get available colors.
     */
    public function getColors()
    {
        $colors = [
            'primary' => 'Blue',
            'success' => 'Green',
            'info' => 'Cyan',
            'warning' => 'Orange',
            'danger' => 'Red',
            'dark' => 'Dark',
            'purple' => 'Purple',
            'teal' => 'Teal',
            'pink' => 'Pink',
            'indigo' => 'Indigo',
        ];

        return response()->json([
            'success' => true,
            'colors' => $colors
        ]);
    }

    /**
     * Get country name.
     */
    private function getCountryName($code)
    {
        $countries = [
            'AU' => 'Australia',
            'KE' => 'Kenya',
            'UG' => 'Uganda',
            'RW' => 'Rwanda',
            'TZ' => 'Tanzania',
            'US' => 'United States',
            'UK' => 'United Kingdom',
            'CA' => 'Canada',
            'NG' => 'Nigeria',
            'ZA' => 'South Africa',
            'GH' => 'Ghana',
            'ZM' => 'Zambia',
            'MW' => 'Malawi',
            'SG' => 'Singapore',
        ];
        return $countries[$code] ?? $code;
    }
}