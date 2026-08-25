<?php

namespace App\Http\Controllers\Job\Setting;

use App\Http\Controllers\Controller;
use App\Models\Job\EducationLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EducationLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('view education levels')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view education levels.'
            ]);
        }
        return view('job.settings.education-levels');
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

        $query = EducationLevel::with('creator');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('country_code', 'like', '%' . $search . '%');
            });
        }

        if (!empty($country)) {
            $query->where('country_code', $country);
        }

        $educationLevels = $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Format the data for display
        $educationLevels->getCollection()->transform(function ($item) {
            $item->display_name = $item->display_name;
            $item->country_flag = $item->country_flag;
            $item->country_name = $item->country_name;
            return $item;
        });

        return response()->json($educationLevels);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create education levels')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create education levels.'
            ]);
        }

        try {
            // \Log::info('Store education level request: ', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'country_code' => 'required|string|size:2',
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
            
            // Generate slug with country code
            $data['slug'] = Str::slug($data['name'] . '-' . $data['country_code']);
            $slug = $data['slug'];
            $counter = 1;
            while (EducationLevel::where('slug', $slug)->exists()) {
                $slug = $data['slug'] . '-' . $counter++;
            }
            $data['slug'] = $slug;

            // Set meta title if not provided
            if (empty($data['meta_title'])) {
                $countryName = EducationLevel::getCountryName($data['country_code']);
                $data['meta_title'] = "{$data['name']} Jobs in {$countryName} - Education Requirements";
            }

            // Set meta description if not provided
            if (empty($data['meta_description'])) {
                $countryName = EducationLevel::getCountryName($data['country_code']);
                $data['meta_description'] = "Find {$data['name']} level jobs in {$countryName}. Browse career opportunities requiring {$data['name']} education level.";
            }

            $data['created_by'] = auth()->id();

            // \Log::info('Final education level data: ', $data);

            $educationLevel = EducationLevel::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Education level created successfully!',
                'data' => $educationLevel
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create education level: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create education level: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (!auth()->user()->can('view education levels')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view education levels.'
            ]);
        }

        try {
            $educationLevel = EducationLevel::with('creator')->findOrFail($id);
            return response()->json($educationLevel);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Education level not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit education levels')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit education levels.'
            ]);
        }

        try {
            // \Log::info('Update education level request for ID ' . $id . ': ', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'country_code' => 'required|string|size:2',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            $educationLevel = EducationLevel::findOrFail($id);
            
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
            
            // Update slug if name or country changed
            if (isset($data['name']) && $educationLevel->name !== $data['name'] || 
                isset($data['country_code']) && $educationLevel->country_code !== $data['country_code']) {
                $data['slug'] = Str::slug($data['name'] . '-' . $data['country_code']);
                $slug = $data['slug'];
                $counter = 1;
                while (EducationLevel::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $data['slug'] . '-' . $counter++;
                }
                $data['slug'] = $slug;
            }

            // Set meta title if not provided
            if (empty($data['meta_title'])) {
                $countryName = EducationLevel::getCountryName($data['country_code']);
                $data['meta_title'] = "{$data['name']} Jobs in {$countryName} - Education Requirements";
            }

            // Set meta description if not provided
            if (empty($data['meta_description'])) {
                $countryName = EducationLevel::getCountryName($data['country_code']);
                $data['meta_description'] = "Find {$data['name']} level jobs in {$countryName}. Browse career opportunities requiring {$data['name']} education level.";
            }

            // \Log::info('Final education level data for update: ', $data);

            $educationLevel->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Education level updated successfully!',
                'data' => $educationLevel->fresh()
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Education level not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Education level not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to update education level: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update education level: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete education levels')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete education levels.'
            ]);
        }

        try {
            $educationLevel = EducationLevel::findOrFail($id);
            
            // Check if it has related job posts
            if ($educationLevel->jobPosts()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this education level because it is being used by job posts.'
                ], 400);
            }
            
            $educationLevel->delete();

            return response()->json([
                'success' => true,
                'message' => 'Education level deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete education level: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete education level: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status of the specified resource.
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('edit education levels')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit education levels.'
            ]);
        }
        try {
            $educationLevel = EducationLevel::findOrFail($id);
            $educationLevel->is_active = !$educationLevel->is_active;
            $educationLevel->save();

            return response()->json([
                'success' => true,
                'message' => $educationLevel->is_active ? 'Education level activated successfully!' : 'Education level deactivated successfully!',
                'is_active' => $educationLevel->is_active
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
     * Get countries for dropdown.
     */
    public function getCountries()
    {
        return response()->json([
            'success' => true,
            'countries' => EducationLevel::getAvailableCountries()
        ]);
    }
}