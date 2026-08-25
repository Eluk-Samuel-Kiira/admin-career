<?php

namespace App\Http\Controllers\Job\Setting;

use App\Http\Controllers\Controller;
use App\Models\Job\Industry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IndustryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('view job industries')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view job industries.'
            ]);
        }
        return view('job.settings.industries');
    }

    /**
     * Get data for DataTable.
     */
    public function getData(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $query = Industry::with('creator');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $industries = $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Format the data for display
        $industries->getCollection()->transform(function ($item) {
            $item->status_badge = $item->status_badge;
            return $item;
        });

        return response()->json($industries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create job industries')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create job industries.'
            ]);
        }
        try {
            // \Log::info('Store industry request: ', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:50',
                'estimated_salary' => 'nullable|numeric|min:0',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
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
            
            // Generate slug
            $data['slug'] = Str::slug($data['name']);
            $slug = $data['slug'];
            $counter = 1;
            while (Industry::where('slug', $slug)->exists()) {
                $slug = $data['slug'] . '-' . $counter++;
            }
            $data['slug'] = $slug;

            // Set meta title if not provided
            if (empty($data['meta_title'])) {
                $data['meta_title'] = "{$data['name']} Industry Jobs - Career Opportunities";
            }

            // Set meta description if not provided
            if (empty($data['meta_description'])) {
                $data['meta_description'] = "Find {$data['name']} industry jobs and career opportunities. Browse positions in the {$data['name']} sector and apply today.";
            }

            $data['created_by'] = auth()->id();

            // \Log::info('Final industry data: ', $data);

            $industry = Industry::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Industry created successfully!',
                'data' => $industry
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create industry: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create industry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (!auth()->user()->can('view job industries')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view job industries.'
            ]);
        }

        try {
            $industry = Industry::with('creator')->findOrFail($id);
            return response()->json($industry);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Industry not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit job industries')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit job industries.'
            ]);
        }
        try {
            // \Log::info('Update industry request for ID ' . $id . ': ', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:50',
                'estimated_salary' => 'nullable|numeric|min:0',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            $industry = Industry::findOrFail($id);
            
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
            
            // Update slug if name changed
            if (isset($data['name']) && $industry->name !== $data['name']) {
                $data['slug'] = Str::slug($data['name']);
                $slug = $data['slug'];
                $counter = 1;
                while (Industry::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $data['slug'] . '-' . $counter++;
                }
                $data['slug'] = $slug;
            }

            // Set meta title if not provided
            if (empty($data['meta_title'])) {
                $data['meta_title'] = "{$data['name']} Industry Jobs - Career Opportunities";
            }

            // Set meta description if not provided
            if (empty($data['meta_description'])) {
                $data['meta_description'] = "Find {$data['name']} industry jobs and career opportunities. Browse positions in the {$data['name']} sector and apply today.";
            }

            // \Log::info('Final industry data for update: ', $data);

            $industry->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Industry updated successfully!',
                'data' => $industry->fresh()
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Industry not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Industry not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to update industry: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update industry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete job industries')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete job industries.'
            ]);
        }
        try {
            $industry = Industry::findOrFail($id);
            
            // Check if it has related job posts
            if ($industry->jobPosts()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this industry because it is being used by job posts.'
                ], 400);
            }
            
            // Check if it has related companies
            if ($industry->companies()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this industry because it is being used by companies.'
                ], 400);
            }
            
            $industry->delete();

            return response()->json([
                'success' => true,
                'message' => 'Industry deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete industry: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete industry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status of the specified resource.
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('edit job industries')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit job industries.'
            ]);
        }
        try {
            $industry = Industry::findOrFail($id);
            $industry->is_active = !$industry->is_active;
            $industry->save();

            return response()->json([
                'success' => true,
                'message' => $industry->is_active ? 'Industry activated successfully!' : 'Industry deactivated successfully!',
                'is_active' => $industry->is_active
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
            'ki-factory' => 'Factory',
            'ki-truck' => 'Truck',
            'ki-hotel' => 'Hotel',
            'ki-restaurant' => 'Restaurant',
            'ki-shopping' => 'Shopping',
            'ki-bank' => 'Bank',
            'ki-insurance' => 'Insurance',
            'ki-accounting' => 'Accounting',
            'ki-law' => 'Law',
            'ki-marketing' => 'Marketing',
            'ki-media' => 'Media',
            'ki-music' => 'Music',
            'ki-sport' => 'Sport',
        ];

        return response()->json([
            'success' => true,
            'icons' => $icons
        ]);
    }
}