<?php

namespace App\Http\Controllers\Job\JobIndex;

use App\Http\Controllers\Controller;
use App\Models\Job\JobLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JobLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('view job locations')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view job locations.'
            ]);
        }

        return view('job.job-index.location');
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

        $query = JobLocation::with('creator');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('district', 'like', '%' . $search . '%')
                  ->orWhere('city', 'like', '%' . $search . '%')
                  ->orWhere('country', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%')
                  ->orWhere('region', 'like', '%' . $search . '%');
            });
        }

        if (!empty($country)) {
            $query->where('country', $country);
        }

        $locations = $query->orderBy('sort_order', 'asc')
            ->orderBy('district', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Format the data for display
        $locations->getCollection()->transform(function ($item) {
            $item->country_name = $item->country_name;
            $item->flag = $item->flag;
            $item->status_badge = $item->is_active 
                ? '<span class="badge badge-light-success">Active</span>' 
                : '<span class="badge badge-light-danger">Inactive</span>';
            $item->capital_badge = $item->is_capital 
                ? '<span class="badge badge-light-warning">Capital</span>' 
                : '<span class="badge badge-light-secondary">-</span>';
            return $item;
        });

        return response()->json($locations);
    }

    /**
     * Get countries for dropdown.
     */
    public function getCountries()
    {
        $countries = [];
        foreach (JobLocation::getAvailableCountries() as $code => $data) {
            $countries[] = [
                'code' => $code,
                'name' => $data['name'],
                'flag' => $this->getFlag($code),
            ];
        }
        return response()->json([
            'success' => true,
            'countries' => $countries
        ]);
    }

    /**
     * Get flag for country.
     */
    private function getFlag($code)
    {
        $flags = [
            'AU' => '🇦🇺',
            'UG' => '🇺🇬',
            'KE' => '🇰🇪',
            'TZ' => '🇹🇿',
            'RW' => '🇷🇼',
            'ZA' => '🇿🇦',
            'SG' => '🇸🇬',
        ];
        return $flags[$code] ?? '🌍';
    }

    /**
     * Get country name from code.
     */
    private function getCountryName($code)
    {
        $countries = [
            'AU' => 'Australia',
            'UG' => 'Uganda',
            'KE' => 'Kenya',
            'TZ' => 'Tanzania',
            'RW' => 'Rwanda',
            'ZA' => 'South Africa',
            'SG' => 'Singapore',
        ];
        return $countries[$code] ?? $code;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create job locations')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create job locations.'
            ]);
        }
        try {
            // \Log::info('Store job location request: ', $request->all());
            
            $validated = $request->validate([
                'country' => 'required|string|size:2',
                'district' => 'required|string|max:255',
                'city' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'featured_image' => 'nullable|string|max:255',
                'is_active' => 'nullable|boolean',
                'is_capital' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $data = $validated;
            
            // Handle is_active
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

            // Handle is_capital
            if ($request->has('is_capital')) {
                $isCapital = $request->input('is_capital');
                if (is_string($isCapital)) {
                    $data['is_capital'] = in_array(strtolower($isCapital), ['on', '1', 'true', 'yes']);
                } else {
                    $data['is_capital'] = (bool) $isCapital;
                }
            } else {
                $data['is_capital'] = false;
            }

            // Set country_code
            $data['country_code'] = $data['country'];
            
            // Generate slug
            $countryCode = strtolower($data['country']);
            $districtSlug = Str::slug($data['district']);
            $data['slug'] = "{$districtSlug}-jobs-in-{$countryCode}";
            $slug = $data['slug'];
            $counter = 1;
            while (JobLocation::where('slug', $slug)->exists()) {
                $slug = $data['slug'] . '-' . $counter++;
            }
            $data['slug'] = $slug;

            // Set region from country data
            $countryData = JobLocation::getAvailableCountries();
            if (isset($countryData[$data['country']])) {
                $data['region'] = $countryData[$data['country']]['region'];
                $data['timezone'] = $countryData[$data['country']]['timezone'];
            }

            // Set meta title if not provided
            if (empty($data['meta_title'])) {
                $countryName = $this->getCountryName($data['country']);
                $locationName = $data['district'] ?? $data['city'] ?? 'Jobs';
                $data['meta_title'] = "Jobs in {$locationName}, {$countryName} - Latest Career Opportunities";
            }

            // Set meta description if not provided
            if (empty($data['meta_description'])) {
                $countryName = $this->getCountryName($data['country']);
                $locationName = $data['district'] ?? $data['city'] ?? 'Jobs';
                $data['meta_description'] = "Find latest jobs in {$locationName}, {$countryName}. Browse career opportunities, vacancies, and employment in {$locationName}, {$countryName}. Apply today!";
            }

            $data['created_by'] = auth()->id();

            // \Log::info('Final job location data: ', $data);

            $location = JobLocation::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Job location created successfully!',
                'data' => $location
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create job location: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create job location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (!auth()->user()->can('view job locations')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view job locations.'
            ]);
        }
        try {
            $location = JobLocation::with('creator')->findOrFail($id);
            $location->country_name = $location->country_name;
            $location->flag = $location->flag;
            return response()->json($location);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Job location not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit job locations')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit job locations.'
            ]);
        }
        try {
            // \Log::info('Update job location request for ID ' . $id . ': ', $request->all());
            
            $validated = $request->validate([
                'country' => 'required|string|size:2',
                'district' => 'required|string|max:255',
                'city' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'featured_image' => 'nullable|string|max:255',
                'is_active' => 'nullable|boolean',
                'is_capital' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $location = JobLocation::findOrFail($id);
            
            $data = $validated;
            
            // Handle is_active
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

            // Handle is_capital
            if ($request->has('is_capital')) {
                $isCapital = $request->input('is_capital');
                if (is_string($isCapital)) {
                    $data['is_capital'] = in_array(strtolower($isCapital), ['on', '1', 'true', 'yes']);
                } else {
                    $data['is_capital'] = (bool) $isCapital;
                }
            } else {
                $data['is_capital'] = false;
            }

            // Set country_code
            $data['country_code'] = $data['country'];

            // Update slug if district or country changed
            if (($location->district !== $data['district']) || ($location->country !== $data['country'])) {
                $countryCode = strtolower($data['country']);
                $districtSlug = Str::slug($data['district']);
                $data['slug'] = "{$districtSlug}-jobs-in-{$countryCode}";
                $slug = $data['slug'];
                $counter = 1;
                while (JobLocation::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $data['slug'] . '-' . $counter++;
                }
                $data['slug'] = $slug;
            }

            // Update region and timezone if country changed
            if ($location->country !== $data['country']) {
                $countryData = JobLocation::getAvailableCountries();
                if (isset($countryData[$data['country']])) {
                    $data['region'] = $countryData[$data['country']]['region'];
                    $data['timezone'] = $countryData[$data['country']]['timezone'];
                }
            }

            // Set meta title if not provided
            if (empty($data['meta_title'])) {
                $countryName = $this->getCountryName($data['country']);
                $locationName = $data['district'] ?? $data['city'] ?? 'Jobs';
                $data['meta_title'] = "Jobs in {$locationName}, {$countryName} - Latest Career Opportunities";
            }

            // Set meta description if not provided
            if (empty($data['meta_description'])) {
                $countryName = $this->getCountryName($data['country']);
                $locationName = $data['district'] ?? $data['city'] ?? 'Jobs';
                $data['meta_description'] = "Find latest jobs in {$locationName}, {$countryName}. Browse career opportunities, vacancies, and employment in {$locationName}, {$countryName}. Apply today!";
            }

            // \Log::info('Final job location data for update: ', $data);

            $location->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Job location updated successfully!',
                'data' => $location->fresh()
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Job location not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Job location not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to update job location: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update job location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete job locations')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete job locations.'
            ]);
        }
        try {
            $location = JobLocation::findOrFail($id);
            
            // Check if it has related job posts
            if ($location->jobPosts()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this location because it is being used by job posts.'
                ], 400);
            }
            
            // Check if it has related companies
            if ($location->companies()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this location because it is being used by companies.'
                ], 400);
            }
            
            $location->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job location deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete job location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status of the specified resource.
     */
    public function toggleStatus($id)
    {
        
        if (!auth()->user()->can('edit job locations')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit job locations.'
            ]);
        }
        try {
            $location = JobLocation::findOrFail($id);
            $location->is_active = !$location->is_active;
            $location->save();

            return response()->json([
                'success' => true,
                'message' => $location->is_active ? 'Location activated successfully!' : 'Location deactivated successfully!',
                'is_active' => $location->is_active
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