<?php

namespace App\Http\Controllers\Job\Setting;

use App\Http\Controllers\Controller;
use App\Models\Job\ExperienceLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExperienceLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('view experience levels')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view experience levels.'
            ]);
        }
        return view('job.settings.experience-levels');
    }

    /**
     * Get data for DataTable.
     */
    public function getData(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $query = ExperienceLevel::with('creator');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $experienceLevels = $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Format the data for display
        $experienceLevels->getCollection()->transform(function ($item) {
            $item->display_name = $item->display_name;
            $item->years_range = $item->years_range;
            return $item;
        });

        return response()->json($experienceLevels);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create experience levels')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create experience levels.'
            ]);
        }
        try {
            // \Log::info('Store experience level request: ', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'min_years' => 'nullable|integer|min:0',
                'max_years' => 'nullable|integer|min:0|gte:min_years',
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
            while (ExperienceLevel::where('slug', $slug)->exists()) {
                $slug = $data['slug'] . '-' . $counter++;
            }
            $data['slug'] = $slug;

            // Set meta title if not provided
            if (empty($data['meta_title'])) {
                $data['meta_title'] = "{$data['name']} Jobs - Experience Level";
            }

            // Set meta description if not provided
            if (empty($data['meta_description'])) {
                $yearsRange = isset($data['min_years']) && isset($data['max_years']) 
                    ? "{$data['min_years']}-{$data['max_years']} years" 
                    : (isset($data['min_years']) ? "{$data['min_years']}+ years" : "various years");
                $data['meta_description'] = "Find {$data['name']} positions requiring {$yearsRange} of experience. Browse career opportunities and job vacancies.";
            }

            $data['created_by'] = auth()->id();

            // \Log::info('Final experience level data: ', $data);

            $experienceLevel = ExperienceLevel::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Experience level created successfully!',
                'data' => $experienceLevel
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create experience level: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create experience level: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (!auth()->user()->can('view experience levels')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view experience levels.'
            ]);
        }

        try {
            $experienceLevel = ExperienceLevel::with('creator')->findOrFail($id);
            return response()->json($experienceLevel);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Experience level not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit experience levels')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit experience levels.'
            ]);
        }
        try {
            // \Log::info('Update experience level request for ID ' . $id . ': ', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'min_years' => 'nullable|integer|min:0',
                'max_years' => 'nullable|integer|min:0|gte:min_years',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            $experienceLevel = ExperienceLevel::findOrFail($id);
            
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
            if (isset($data['name']) && $experienceLevel->name !== $data['name']) {
                $data['slug'] = Str::slug($data['name']);
                $slug = $data['slug'];
                $counter = 1;
                while (ExperienceLevel::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $data['slug'] . '-' . $counter++;
                }
                $data['slug'] = $slug;
            }

            // Set meta title if not provided
            if (empty($data['meta_title'])) {
                $data['meta_title'] = "{$data['name']} Jobs - Experience Level";
            }

            // Set meta description if not provided
            if (empty($data['meta_description'])) {
                $yearsRange = isset($data['min_years']) && isset($data['max_years']) 
                    ? "{$data['min_years']}-{$data['max_years']} years" 
                    : (isset($data['min_years']) ? "{$data['min_years']}+ years" : "various years");
                $data['meta_description'] = "Find {$data['name']} positions requiring {$yearsRange} of experience. Browse career opportunities and job vacancies.";
            }

            // \Log::info('Final experience level data for update: ', $data);

            $experienceLevel->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Experience level updated successfully!',
                'data' => $experienceLevel->fresh()
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Experience level not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Experience level not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to update experience level: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update experience level: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete experience levels')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete experience levels.'
            ]);
        }
        try {
            $experienceLevel = ExperienceLevel::findOrFail($id);
            
            // Check if it has related job posts
            if ($experienceLevel->jobPosts()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this experience level because it is being used by job posts.'
                ], 400);
            }
            
            $experienceLevel->delete();

            return response()->json([
                'success' => true,
                'message' => 'Experience level deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete experience level: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete experience level: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status of the specified resource.
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('edit experience levels')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit experience levels.'
            ]);
        }
        try {
            $experienceLevel = ExperienceLevel::findOrFail($id);
            $experienceLevel->is_active = !$experienceLevel->is_active;
            $experienceLevel->save();

            return response()->json([
                'success' => true,
                'message' => $experienceLevel->is_active ? 'Experience level activated successfully!' : 'Experience level deactivated successfully!',
                'is_active' => $experienceLevel->is_active
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