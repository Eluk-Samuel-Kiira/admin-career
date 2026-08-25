<?php

namespace App\Http\Controllers\Job\Setting;

use App\Http\Controllers\Controller;
use App\Models\Job\JobType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JobTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {        
        if (!auth()->user()->can('view job types')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view job types.'
            ]);
        }
        return view('job.settings.job-types');
    }

    /**
     * Get data for DataTable.
     */
    public function getData(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $query = JobType::with('creator');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $jobTypes = $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Format the data for display
        $jobTypes->getCollection()->transform(function ($item) {
            $item->display_name = $item->display_name;
            $item->icon_html = $item->icon_html;
            $item->icon_class = $item->icon_class;
            return $item;
        });

        return response()->json($jobTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create job types')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create job types.'
            ]);
        }
        try {
            // \Log::info('Store job type request: ', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:50',
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
            while (JobType::where('slug', $slug)->exists()) {
                $slug = $data['slug'] . '-' . $counter++;
            }
            $data['slug'] = $slug;

            // Set meta title if not provided
            if (empty($data['meta_title'])) {
                $data['meta_title'] = "{$data['name']} Jobs - Employment Opportunities";
            }

            // Set meta description if not provided
            if (empty($data['meta_description'])) {
                $data['meta_description'] = "Find {$data['name']} jobs and employment opportunities. Browse career positions across various industries and companies.";
            }

            $data['created_by'] = auth()->id();

            // \Log::info('Final job type data: ', $data);

            $jobType = JobType::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Job type created successfully!',
                'data' => $jobType
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create job type: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create job type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        
        if (!auth()->user()->can('view job types')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view job types.'
            ]);
        }

        try {
            $jobType = JobType::with('creator')->findOrFail($id);
            return response()->json($jobType);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Job type not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit job types')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit job types.'
            ]);
        }

        try {
            // \Log::info('Update job type request for ID ' . $id . ': ', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:50',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            $jobType = JobType::findOrFail($id);
            
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
            if (isset($data['name']) && $jobType->name !== $data['name']) {
                $data['slug'] = Str::slug($data['name']);
                $slug = $data['slug'];
                $counter = 1;
                while (JobType::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $data['slug'] . '-' . $counter++;
                }
                $data['slug'] = $slug;
            }

            // Set meta title if not provided
            if (empty($data['meta_title'])) {
                $data['meta_title'] = "{$data['name']} Jobs - Employment Opportunities";
            }

            // Set meta description if not provided
            if (empty($data['meta_description'])) {
                $data['meta_description'] = "Find {$data['name']} jobs and employment opportunities. Browse career positions across various industries and companies.";
            }

            // \Log::info('Final job type data for update: ', $data);

            $jobType->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Job type updated successfully!',
                'data' => $jobType->fresh()
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Job type not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Job type not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to update job type: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update job type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete job types')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete job types.'
            ]);
        }

        try {
            $jobType = JobType::findOrFail($id);
            
            // Check if it has related job posts
            if ($jobType->jobPosts()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this job type because it is being used by job posts.'
                ], 400);
            }
            
            $jobType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job type deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete job type: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status of the specified resource.
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('edit job types')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit job types.'
            ]);
        }

        try {
            $jobType = JobType::findOrFail($id);
            $jobType->is_active = !$jobType->is_active;
            $jobType->save();

            return response()->json([
                'success' => true,
                'message' => $jobType->is_active ? 'Job type activated successfully!' : 'Job type deactivated successfully!',
                'is_active' => $jobType->is_active
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
     * Get available icons
     */
    public function getIcons()
    {
        return response()->json([
            'success' => true,
            'icons' => JobType::getAvailableIcons()
        ]);
    }
}