<?php

namespace App\Http\Controllers\Job\Setting;

use App\Http\Controllers\Controller;
use App\Models\Job\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('view countries')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view countries.'
            ]);
        }

        return view('job.settings.countries');
    }

    /**
     * Get data for DataTable.
     */
    public function getData(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $query = Country::with('creator');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%')
                ->orWhere('region', 'like', '%' . $search . '%')
                ->orWhere('capital', 'like', '%' . $search . '%');
            });
        }

        if (!empty($status)) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $countries = $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        $countries->getCollection()->transform(function ($item) {
            $item->flag_emoji = $item->flag_emoji;
            $item->status_badge = $item->status_badge;
            return $item;
        });

        return response()->json($countries);
    }

    /**
     * Get all active countries for dropdown.
     */
    public function getActiveCountries()
    {
        $countries = Country::active()
            ->ordered()
            ->get(['id', 'code', 'name', 'flag', 'currency', 'phone_code', 'timezone']);

        return response()->json([
            'success' => true,
            'countries' => $countries->map(function($country) {
                return [
                    'id' => $country->id,
                    'code' => $country->code,
                    'name' => $country->name,
                    'flag' => $country->flag_emoji,
                    'currency' => $country->currency,
                    'phone_code' => $country->phone_code,
                    'timezone' => $country->timezone,
                ];
            })
        ]);
    }

    /**
     * Get all feature flags for the edit form
     */
    private function getFeatureFlags()
    {
        return [
            // Job Seeker & Employer Services
            'can_view_casual_workers' => 'View Casual Workers',
            'can_view_blue_collar_workers' => 'View Blue Collar Workers',
            'can_accept_cv_services' => 'CV Services',
            'can_offer_exam_services' => 'Exam Services',
            'can_view_salary_insights' => 'Salary Insights',
            'can_view_cost_of_living_tools' => 'Cost of Living Tools',
            'can_use_social_media_services' => 'Social Media Services',
            'can_view_employer_services' => 'Employer Services',
            'can_view_jobseeker_services' => 'Jobseeker Services',
            'can_access_subscription' => 'Subscription Access',
            
            // Additional Traffic & Engagement Features
            'can_view_company_profiles' => 'Company Profiles',
            'can_view_industry_insights' => 'Industry Insights',
            'can_access_career_advice' => 'Career Advice',
            'can_view_job_alerts' => 'Job Alerts',
            'can_use_resume_builder' => 'Resume Builder',
            'can_view_employer_reviews' => 'Employer Reviews',
            'can_access_skill_assessment' => 'Skill Assessment',
            'can_view_market_trends' => 'Market Trends',
            'can_use_job_comparison_tools' => 'Job Comparison Tools',
            'can_access_networking_events' => 'Networking Events',
            'can_view_training_courses' => 'Training Courses',
            'can_use_chat_support' => 'Chat Support',
            
            // Premium/Paid Features
            'can_access_premium_content' => 'Premium Content',
            'can_view_verified_employers' => 'Verified Employers',
            'can_use_priority_application' => 'Priority Application',
            'can_view_exclusive_jobs' => 'Exclusive Jobs',
            'can_access_interview_coaching' => 'Interview Coaching',
            'can_view_salary_negotiation_tips' => 'Salary Negotiation Tips',
            
            // Job Posting Features
            'can_post_jobs' => 'Post Jobs',
            'can_post_featured_jobs' => 'Post Featured Jobs',
            'can_post_urgent_jobs' => 'Post Urgent Jobs',
            'can_use_job_analytics' => 'Job Analytics',
            'can_manage_applications' => 'Manage Applications',
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create countries')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create countries.'
            ]);
        }

        try {
            // Get all feature flags
            $featureFlags = $this->getFeatureFlags();
            
            // Build validation rules
            $rules = [
                'code' => 'required|string|size:2|unique:countries,code',
                'name' => 'required|string|max:100',
                'frontend_url' => 'required|string|max:100',
                'domain' => 'required|string|max:100',
                'region' => 'nullable|string|max:50',
                'timezone' => 'nullable|string|max:50',
                'currency' => 'nullable|string|max:10',
                'currency_symbol' => 'nullable|string|max:5',
                'default_lat' => 'nullable|numeric|between:-90,90',
                'default_lng' => 'nullable|numeric|between:-180,180',
                'flag' => 'nullable|string|max:5',
                'capital' => 'nullable|string|max:100',
                'capital_lat' => 'nullable|numeric|between:-90,90',
                'capital_lng' => 'nullable|numeric|between:-180,180',
                'phone_code' => 'nullable|string|max:10',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
            ];
            
            // Add feature flags to rules (all are nullable boolean)
            foreach ($featureFlags as $field => $label) {
                $rules[$field] = 'nullable|boolean';
            }
            
            $validated = $request->validate($rules);

            $data = $validated;
            $data['created_by'] = auth()->id();

            // Handle boolean fields - set to false if not present
            foreach ($featureFlags as $field => $label) {
                $data[$field] = $request->has($field) ? filter_var($request->$field, FILTER_VALIDATE_BOOLEAN) : false;
            }

            // Handle is_active
            $data['is_active'] = $request->has('is_active') ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) : true;

            $country = Country::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Country created successfully!',
                'data' => $country
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create country: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create country: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            if (!auth()->user()->can('view countries')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view countries.'
                ], 403);
            }

            $country = Country::with('creator')->findOrFail($id);
            
            // Log the data being returned for debugging
            // \Log::info('Country data being returned:', $country->toArray());
            
            return response()->json($country);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Country not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Country not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to load country: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load country: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // \Log::info($request->all());
        if (!auth()->user()->can('edit countries')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit countries.'
            ]);
        }

        try {
            $country = Country::findOrFail($id);

            // Get all feature flags
            $featureFlags = $this->getFeatureFlags();

            // Build validation rules
            $rules = [
                'code' => 'required|string|size:2|unique:countries,code,' . $id,
                'name' => 'required|string|max:100',
                'frontend_url' => 'required|string|max:100',
                'domain' => 'required|string|max:100',
                'region' => 'nullable|string|max:50',
                'timezone' => 'nullable|string|max:50',
                'currency' => 'nullable|string|max:10',
                'currency_symbol' => 'nullable|string|max:5',
                'default_lat' => 'nullable|numeric|between:-90,90',
                'default_lng' => 'nullable|numeric|between:-180,180',
                'flag' => 'nullable|string|max:5',
                'capital' => 'nullable|string|max:100',
                'capital_lat' => 'nullable|numeric|between:-90,90',
                'capital_lng' => 'nullable|numeric|between:-180,180',
                'phone_code' => 'nullable|string|max:10',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
            ];

            // Add feature flags to rules (all are nullable boolean)
            foreach ($featureFlags as $field => $label) {
                $rules[$field] = 'nullable|boolean';
            }

            $validated = $request->validate($rules);

            $data = $validated;

            // Handle boolean fields - set to false if not present
            foreach ($featureFlags as $field => $label) {
                $data[$field] = $request->has($field) ? filter_var($request->$field, FILTER_VALIDATE_BOOLEAN) : false;
            }

            // Handle is_active
            $data['is_active'] = $request->has('is_active') ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) : false;

            $country->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Country updated successfully!',
                'data' => $country->fresh()
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update country: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update country: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete countries')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete countries.'
            ]);
        }

        try {
            $country = Country::findOrFail($id);

            // Check if it has related job locations
            if ($country->jobLocations()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this country because it has associated job locations.'
                ], 400);
            }

            // Check if it has related job posts
            if ($country->jobPosts()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this country because it has associated job posts.'
                ], 400);
            }

            $country->delete();

            return response()->json([
                'success' => true,
                'message' => 'Country deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete country: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete country: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle status of the specified resource.
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('edit countries')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit countries.'
            ]);
        }

        try {
            $country = Country::findOrFail($id);
            $country->is_active = !$country->is_active;
            $country->save();

            return response()->json([
                'success' => true,
                'message' => $country->is_active ? 'Country activated successfully!' : 'Country deactivated successfully!',
                'is_active' => $country->is_active
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