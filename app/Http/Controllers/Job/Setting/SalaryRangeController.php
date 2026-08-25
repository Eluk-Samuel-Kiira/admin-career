<?php

namespace App\Http\Controllers\Job\Setting;

use App\Http\Controllers\Controller;
use App\Models\Job\SalaryRange;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SalaryRangeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('job.settings.salary-ranges');
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

        $query = SalaryRange::with('creator');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%')
                  ->orWhere('currency', 'like', '%' . $search . '%')
                  ->orWhere('country_code', 'like', '%' . $search . '%');
            });
        }

        if (!empty($country)) {
            $query->where('country_code', $country);
        }

        $salaryRanges = $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Format the data for display
        $salaryRanges->getCollection()->transform(function ($item) {
            $item->formatted_range = $item->formatted_range;
            $item->display_name = $item->display_name;
            $item->country_flag = $item->country_flag;
            $item->country_name = $item->country_name;
            return $item;
        });

        return response()->json($salaryRanges);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            
            if (!auth()->user()->can('create salary ranges')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to create salary ranges.'
                ]);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'min_salary' => 'nullable|numeric|min:0',
                'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
                'currency' => 'nullable|string|max:10',
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
            
            // Generate slug
            $data['slug'] = Str::slug($data['name']);
            $slug = $data['slug'];
            $counter = 1;
            while (SalaryRange::where('slug', $slug)->exists()) {
                $slug = $data['slug'] . '-' . $counter++;
            }
            $data['slug'] = $slug;

            // Set currency from country if not provided
            if (empty($data['currency']) && !empty($data['country_code'])) {
                $data['currency'] = SalaryRange::getCurrencyForCountry($data['country_code']);
            }

            $data['created_by'] = auth()->id();

            // \Log::info('Final data for store: ', $data);

            $salaryRange = SalaryRange::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Salary range created successfully!',
                'data' => $salaryRange
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create salary range: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create salary range: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (!auth()->user()->can('view salary ranges')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view salary ranges.'
            ]);
        }

        try {
            $salaryRange = SalaryRange::with('creator')->findOrFail($id);
            return response()->json($salaryRange);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Salary range not found'
            ], 404);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        
        if (!auth()->user()->can('edit salary ranges')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit salary ranges.'
            ]);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
            'currency' => 'nullable|string|max:10',
            'country_code' => 'required|string|size:2',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_active' => 'nullable|boolean', // Changed from sometimes|boolean
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $salaryRange = SalaryRange::findOrFail($id);
            
            $data = $request->except(['_token', '_method']);
            
            // Handle is_active properly
            if ($request->has('is_active')) {
                $isActive = $request->input('is_active');
                // Convert 'on', '1', 'true', true to boolean
                if (is_string($isActive)) {
                    $data['is_active'] = in_array(strtolower($isActive), ['on', '1', 'true', 'yes']);
                } else {
                    $data['is_active'] = (bool) $isActive;
                }
            } else {
                $data['is_active'] = false;
            }
            
            // Update slug if name changed
            if (isset($data['name']) && $salaryRange->name !== $data['name']) {
                $data['slug'] = Str::slug($data['name']);
                $slug = $data['slug'];
                $counter = 1;
                while (SalaryRange::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $data['slug'] . '-' . $counter++;
                }
                $data['slug'] = $slug;
            }

            // Set currency from country if not provided
            if (empty($data['currency']) && !empty($data['country_code'])) {
                $data['currency'] = SalaryRange::getCurrencyForCountry($data['country_code']);
            }

            // \Log::info('Final data for update: ', $data);
            
            $salaryRange->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Salary range updated successfully!',
                'data' => $salaryRange->fresh()
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to update salary range: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update salary range: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete salary ranges')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete salary ranges.'
            ]);
        }

        try {
            $salaryRange = SalaryRange::findOrFail($id);
            
            // Check if it has related job posts
            if ($salaryRange->jobPosts()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this salary range because it is being used by job posts.'
                ], 400);
            }
            
            $salaryRange->delete();

            return response()->json([
                'success' => true,
                'message' => 'Salary range deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete salary range: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status of the specified resource.
     */
    public function toggleStatus($id)
    {
        
        if (!auth()->user()->can('edit salary ranges')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit salary ranges.'
            ]);
        }

        try {
            $salaryRange = SalaryRange::findOrFail($id);
            $salaryRange->is_active = !$salaryRange->is_active;
            $salaryRange->save();

            return response()->json([
                'success' => true,
                'message' => $salaryRange->is_active ? 'Salary range activated successfully!' : 'Salary range deactivated successfully!',
                'is_active' => $salaryRange->is_active
            ]);

        } catch (\Exception $e) {
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
            'countries' => SalaryRange::getAvailableCountries()
        ]);
    }

    /**
     * Get currencies for dropdown.
     */
    public function getCurrencies()
    {
        $currencies = [];
        foreach (SalaryRange::getAvailableCountries() as $code => $country) {
            $currencies[] = [
                'code' => $country['currency'],
                'name' => $country['currency'],
                'country' => $code,
            ];
        }
        // Add USD as default
        $currencies[] = ['code' => 'USD', 'name' => 'USD', 'country' => 'US'];
        return response()->json([
            'success' => true,
            'currencies' => $currencies
        ]);
    }
}