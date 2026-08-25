<?php

namespace App\Http\Controllers\Job\JobIndex;

use App\Http\Controllers\Controller;
use App\Models\Job\Company;
use App\Models\Job\Industry;
use App\Models\Job\JobLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('view company')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view company.'
            ]);
        }
        return view('job.job-index.companies');
    }

    /**
     * Get data for DataTable.
     */
    public function getData(Request $request)
    {
        $search = $request->get('search', '');
        $country = $request->get('country', '');
        $status = $request->get('status', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $query = Company::with(['industry', 'location', 'creator']);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%')
                  ->orWhere('contact_name', 'like', '%' . $search . '%')
                  ->orWhere('contact_email', 'like', '%' . $search . '%')
                  ->orWhere('legacy_alias', 'like', '%' . $search . '%')
                  ->orWhere('legacy_id', $search);
            });
        }

        if (!empty($country)) {
            $query->where('country_code', $country);
        }

        if (!empty($status)) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($status === 'verified') {
                $query->where('is_verified', true);
            } elseif ($status === 'gold') {
                $query->where('is_gold', true);
            } elseif ($status === 'featured') {
                $query->where('is_featured', true);
            } elseif ($status === 'migrated') {
                $query->whereNotNull('migrated_at');
            } elseif ($status === 'pending') {
                $query->whereNull('migrated_at');
            }
        }

        $companies = $query->orderBy('name', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Format the data for display
        $companies->getCollection()->transform(function ($item) {
            $item->logo_url = $item->logo_url;
            $item->logo_html = $item->logo_html;
            $item->status_badge = $item->status_badge;
            $item->verified_badge = $item->verified_badge;
            $item->migration_badge = $item->migration_badge;
            $item->gold_badge = $item->gold_badge;
            $item->featured_badge = $item->featured_badge;
            $item->company_size_label = $item->company_size_label;
            return $item;
        });

        return response()->json($companies);
    }

    /**
     * Get form data (industries, locations, etc.)
     */
    public function getFormData(Request $request)
    {
        $countryCode = $request->get('country', 'AU');
        
        $industries = Industry::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
            
        $locations = JobLocation::where('is_active', true)
            ->where('country', $countryCode)
            ->orderBy('district')
            ->get(['id', 'district', 'city', 'country']);
            
        return response()->json([
            'success' => true,
            'industries' => $industries,
            'locations' => $locations,
        ]);
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create company')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create company.'
            ]);
        }

        try {
            // \Log::info('Store company request: ', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:companies,slug',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'description' => 'nullable|string',
                'website' => 'nullable|string|max:255',
                'contact_name' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|max:255',
                'address1' => 'nullable|string',
                'company_size' => 'nullable|string|max:50',
                'industry_id' => 'required|exists:industries,id',
                'location_id' => 'required|exists:job_locations,id',
                'country_code' => 'required|string|size:2',
                'gold_start_date' => 'nullable|date',
                'gold_end_date' => 'nullable|date|after_or_equal:gold_start_date',
                'featured_start_date' => 'nullable|date',
                'featured_end_date' => 'nullable|date|after_or_equal:featured_start_date',
                'hits' => 'nullable|integer|min:0',
            ]);

            $data = $validated;
            
            // Handle boolean fields
            $booleanFields = ['is_active', 'is_verified', 'is_gold', 'is_featured'];
            foreach ($booleanFields as $field) {
                if ($request->has($field)) {
                    $value = $request->input($field);
                    if (is_string($value)) {
                        $data[$field] = in_array(strtolower($value), ['on', '1', 'true', 'yes']);
                    } else {
                        $data[$field] = (bool) $value;
                    }
                } else {
                    $data[$field] = false;
                }
            }
            
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
                $slug = $data['slug'];
                $counter = 1;
                while (Company::where('slug', $slug)->exists()) {
                    $slug = $data['slug'] . '-' . $counter++;
                }
                $data['slug'] = $slug;
            }

            // Create the company first to get the ID
            $data['created_by'] = auth()->id();
            $company = Company::create($data);

            // Handle logo upload after company is created
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $countryCode = strtolower($data['country_code'] ?? 'au');
                $timestamp = time();
                $extension = $file->getClientOriginalExtension();
                $filename = Str::slug($data['name']) . '_' . $timestamp . '.' . $extension;
                
                $path = $file->storeAs(
                    "{$countryCode}-companies/{$company->id}/logo",
                    $filename,
                    'public'
                );
                
                $company->update([
                    'logo' => $filename,
                    'logo_path' => $path
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Company created successfully!',
                'data' => $company->fresh()
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create company: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create company: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (!auth()->user()->can('view company')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view company.'
            ]);
        }
        try {
            $company = Company::with(['industry', 'location', 'creator'])->findOrFail($id);
            $company->logo_url = $company->logo_url;
            return response()->json($company);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit company')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit company.'
            ]);
        }
        try {
            // \Log::info('Update company request for ID ' . $id . ': ', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:companies,slug,' . $id,
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'description' => 'nullable|string',
                'website' => 'nullable|string|max:255',
                'contact_name' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|max:255',
                'address1' => 'nullable|string',
                'company_size' => 'nullable|string|max:50',
                'industry_id' => 'required|exists:industries,id',
                'location_id' => 'required|exists:job_locations,id',
                'country_code' => 'required|string|size:2',
                'gold_start_date' => 'nullable|date',
                'gold_end_date' => 'nullable|date|after_or_equal:gold_start_date',
                'featured_start_date' => 'nullable|date',
                'featured_end_date' => 'nullable|date|after_or_equal:featured_start_date',
                'hits' => 'nullable|integer|min:0',
            ]);

            $company = Company::findOrFail($id);
            
            $data = $validated;
            
            // Handle boolean fields
            $booleanFields = ['is_active', 'is_verified', 'is_gold', 'is_featured'];
            foreach ($booleanFields as $field) {
                if ($request->has($field)) {
                    $value = $request->input($field);
                    if (is_string($value)) {
                        $data[$field] = in_array(strtolower($value), ['on', '1', 'true', 'yes']);
                    } else {
                        $data[$field] = (bool) $value;
                    }
                } else {
                    $data[$field] = false;
                }
            }
            
            // Handle logo upload
            if ($request->hasFile('logo')) {
                if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                    Storage::disk('public')->delete($company->logo_path);
                }
                
                $file = $request->file('logo');
                $countryCode = strtolower($data['country_code'] ?? $company->country_code ?? 'au');
                $timestamp = time();
                $extension = $file->getClientOriginalExtension();
                $filename = Str::slug($data['name']) . '_' . $timestamp . '.' . $extension;
                
                $path = $file->storeAs(
                    "{$countryCode}-companies/{$company->id}/logo",
                    $filename,
                    'public'
                );
                
                $data['logo'] = $filename;
                $data['logo_path'] = $path;
            }
            
            if (empty($data['slug']) && $company->name !== $data['name']) {
                $data['slug'] = Str::slug($data['name']);
                $slug = $data['slug'];
                $counter = 1;
                while (Company::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $data['slug'] . '-' . $counter++;
                }
                $data['slug'] = $slug;
            }

            // \Log::info('Final company data for update: ', $data);

            $company->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Company updated successfully!',
                'data' => $company->fresh()
            ]);

        } catch (ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Company not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to update company: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete company')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete company.'
            ]);
        }
        try {
            $company = Company::findOrFail($id);
            
            if ($company->jobs()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this company because it has associated jobs.'
                ], 400);
            }
            
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            
            $company->delete();

            return response()->json([
                'success' => true,
                'message' => 'Company deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete company: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete company: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status of the specified resource.
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('edit company')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit company.'
            ]);
        }
        try {
            $company = Company::findOrFail($id);
            $company->is_active = !$company->is_active;
            $company->save();

            return response()->json([
                'success' => true,
                'message' => $company->is_active ? 'Company activated successfully!' : 'Company deactivated successfully!',
                'is_active' => $company->is_active
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
     * Toggle verification status.
     */
    public function toggleVerified($id)
    {
        try {
            $company = Company::findOrFail($id);
            $company->is_verified = !$company->is_verified;
            $company->save();

            return response()->json([
                'success' => true,
                'message' => $company->is_verified ? 'Company verified successfully!' : 'Company unverified!',
                'is_verified' => $company->is_verified
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to toggle verification: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle verification: ' . $e->getMessage()
            ], 500);
        }
    }
}