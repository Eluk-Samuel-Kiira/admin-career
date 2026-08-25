<?php

namespace App\Http\Controllers\Compensation;

use App\Http\Controllers\Controller;
use App\Models\PerformanceReview;
use App\Models\EmployeeSalary;
use App\Models\{ User, Employee };
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceReviewController extends Controller
{
    /**
     * Display performance reviews list.
     */
    public function index()
    {
        return view('compensation.performance-reviews.index');
    }

    /**
     * Get performance reviews data for datatable.
     */
    public function getReviews(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        $status = $request->get('status', '');
        $departmentId = $request->get('department_id', '');
        $userId = $request->get('user_id', '');
        $reviewPeriod = $request->get('review_period', '');

        $query = PerformanceReview::with(['user', 'department', 'reviewer', 'approver', 'employeeSalary']);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('overall_rating', 'like', '%' . $search . '%')
                  ->orWhere('recommendations', 'like', '%' . $search . '%');
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        if (!empty($userId)) {
            $query->where('user_id', $userId);
        }

        if (!empty($reviewPeriod)) {
            $query->where('review_period', $reviewPeriod);
        }

        $reviews = $query->orderBy('review_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Get summary statistics
        $summary = [
            'total_reviews' => PerformanceReview::count(),
            'pending_count' => PerformanceReview::where('status', 'pending')->count(),
            'completed_count' => PerformanceReview::where('status', 'completed')->count(),
            'approved_count' => PerformanceReview::where('status', 'approved')->count(),
            'avg_score' => PerformanceReview::avg('score'),
            'bonus_eligible' => PerformanceReview::where('bonus_eligible', true)->count(),
        ];

        $data = [
            'current_page' => $reviews->currentPage(),
            'data' => collect($reviews->items())->map(function($review) {
                return [
                    'id' => $review->id,
                    'user' => $review->user ? [
                        'id' => $review->user->id,
                        'name' => $review->user->full_name ?? $review->user->name,
                    ] : null,
                    'department' => $review->department?->name ?? 'N/A',
                    'review_period' => $review->review_period,
                    'review_period_label' => ucfirst($review->review_period),
                    'review_date' => $review->review_date,
                    'score' => $review->score,
                    'overall_rating' => $review->overall_rating,
                    'overall_rating_badge' => $this->getRatingBadge($review->overall_rating),
                    'bonus_eligible' => (bool) $review->bonus_eligible,
                    'bonus_badge' => $review->bonus_eligible ? 
                        '<span class="badge badge-light-success">Yes</span>' : 
                        '<span class="badge badge-light-danger">No</span>',
                    'promotion_recommended' => (bool) $review->promotion_recommended,
                    'status' => $review->status,
                    'status_badge' => $this->getStatusBadge($review->status),
                    'reviewer' => $review->reviewer ? [
                        'id' => $review->reviewer->id,
                        'name' => $review->reviewer->full_name ?? $review->reviewer->name,
                    ] : null,
                    'created_at' => $review->created_at,
                ];
            })->toArray(),
            'first_page_url' => $reviews->url(1),
            'from' => $reviews->firstItem(),
            'last_page' => $reviews->lastPage(),
            'last_page_url' => $reviews->url($reviews->lastPage()),
            'next_page_url' => $reviews->nextPageUrl(),
            'prev_page_url' => $reviews->previousPageUrl(),
            'to' => $reviews->lastItem(),
            'total' => $reviews->total(),
            'per_page' => $perPage,
            'summary' => $summary,
        ];

        return response()->json($data);
    }

    /**
     * Get form data.
     */
    public function getFormData()
    {
        $users = User::whereHas('employee', function($query) {
                $query->where('is_active', true);
            })
            ->with('employee')
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'last_name', 'email']);

        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $reviewers = User::whereHas('employee', function($query) {
                $query->where('is_active', true);
            })
            ->with('employee')
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'last_name', 'email']);

        $employeeSalaries = EmployeeSalary::with(['user', 'department'])
            ->where('is_active', true)
            ->get();

        return response()->json([
            'users' => $users,
            'departments' => $departments,
            'reviewers' => $reviewers,
            'employee_salaries' => $employeeSalaries,
            'review_periods' => [
                ['value' => 'monthly', 'label' => 'Monthly'],
                ['value' => 'quarterly', 'label' => 'Quarterly'],
                ['value' => 'annual', 'label' => 'Annual'],
            ],
            'ratings' => [
                ['value' => 'excellent', 'label' => 'Excellent'],
                ['value' => 'good', 'label' => 'Good'],
                ['value' => 'average', 'label' => 'Average'],
                ['value' => 'below_average', 'label' => 'Below Average'],
                ['value' => 'poor', 'label' => 'Poor'],
            ],
            'statuses' => [
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'approved', 'label' => 'Approved'],
            ],
        ]);
    }

    /**
     * Store a new performance review.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create performance reviews')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create performance reviews.'
            ]);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'employee_salary_id' => 'nullable|exists:employee_salaries,id',
            'department_id' => 'nullable|exists:departments,id',
            'review_period' => 'required|in:monthly,quarterly,annual',
            'review_date' => 'required|date',
            'score' => 'required|numeric|min:0|max:100',
            'overall_rating' => 'required|in:excellent,good,average,below_average,poor',
            'bonus_eligible' => 'boolean',
            'promotion_recommended' => 'boolean',
            'reviewer_id' => 'nullable|exists:users,id',
            'status' => 'required|in:pending,completed,approved',
            'recommendations' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Get department if not provided
            $departmentId = $request->department_id;
            if (!$departmentId && $request->user_id) {
                $user = User::with('employee')->find($request->user_id);
                $departmentId = $user?->employee?->department_id;
            }

            // Get employee salary if not provided
            $employeeSalaryId = $request->employee_salary_id;
            if (!$employeeSalaryId && $request->user_id) {
                $employeeSalary = EmployeeSalary::where('user_id', $request->user_id)
                    ->where('is_active', true)
                    ->first();
                $employeeSalaryId = $employeeSalary?->id;
            }

            $review = PerformanceReview::create([
                'employee_salary_id' => $employeeSalaryId,
                'user_id' => $request->user_id,
                'department_id' => $departmentId,
                'review_period' => $request->review_period,
                'review_date' => $request->review_date,
                'score' => $request->score,
                'revenue_contribution' => $request->revenue_contribution,
                'client_satisfaction' => $request->client_satisfaction,
                'reporting_discipline' => $request->reporting_discipline,
                'innovation_score' => $request->innovation_score,
                'teamwork_score' => $request->teamwork_score,
                'quality_score' => $request->quality_score,
                'attendance_score' => $request->attendance_score,
                'kpi_achievements' => $request->kpi_achievements,
                'overall_rating' => $request->overall_rating,
                'recommendations' => $request->recommendations,
                'bonus_eligible' => $request->has('bonus_eligible'),
                'promotion_recommended' => $request->has('promotion_recommended'),
                'reviewer_id' => $request->reviewer_id,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Performance review created successfully',
                'review' => $review
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Performance review creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create performance review: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show review details.
     */
    public function show($id)
    {
        try {
            $review = PerformanceReview::with(['user', 'department', 'reviewer', 'approver', 'employeeSalary'])
                ->findOrFail($id);

            return response()->json($review);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found'
            ], 404);
        }
    }

    /**
     * Update review.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit performance reviews')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit performance reviews.'
            ]);
        }

        $request->validate([
            'review_period' => 'required|in:monthly,quarterly,annual',
            'review_date' => 'required|date',
            'score' => 'required|numeric|min:0|max:100',
            'overall_rating' => 'required|in:excellent,good,average,below_average,poor',
            'bonus_eligible' => 'boolean',
            'promotion_recommended' => 'boolean',
            'reviewer_id' => 'nullable|exists:users,id',
            'status' => 'required|in:pending,completed,approved',
            'recommendations' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $review = PerformanceReview::findOrFail($id);

            $review->update([
                'review_period' => $request->review_period,
                'review_date' => $request->review_date,
                'score' => $request->score,
                'revenue_contribution' => $request->revenue_contribution,
                'client_satisfaction' => $request->client_satisfaction,
                'reporting_discipline' => $request->reporting_discipline,
                'innovation_score' => $request->innovation_score,
                'teamwork_score' => $request->teamwork_score,
                'quality_score' => $request->quality_score,
                'attendance_score' => $request->attendance_score,
                'kpi_achievements' => $request->kpi_achievements,
                'overall_rating' => $request->overall_rating,
                'recommendations' => $request->recommendations,
                'bonus_eligible' => $request->has('bonus_eligible'),
                'promotion_recommended' => $request->has('promotion_recommended'),
                'reviewer_id' => $request->reviewer_id,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Performance review updated successfully',
                'review' => $review
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Performance review update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update performance review: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve review.
     */
    public function approve($id)
    {
        if (!auth()->user()->can('approve performance reviews')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve performance reviews.'
            ]);
        }

        try {
            DB::beginTransaction();

            $review = PerformanceReview::findOrFail($id);
            $review->status = 'approved';
            $review->approved_by = auth()->id();
            $review->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Performance review approved successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve review: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete review.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete performance reviews')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete performance reviews.'
            ]);
        }

        try {
            $review = PerformanceReview::findOrFail($id);
            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Performance review deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete performance review: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge badge-light-warning">Pending</span>',
            'completed' => '<span class="badge badge-light-info">Completed</span>',
            'approved' => '<span class="badge badge-light-success">Approved</span>',
        ];
        return $badges[$status] ?? '<span class="badge badge-light-secondary">' . $status . '</span>';
    }

    private function getRatingBadge($rating)
    {
        $badges = [
            'excellent' => '<span class="badge badge-light-success">Excellent</span>',
            'good' => '<span class="badge badge-light-primary">Good</span>',
            'average' => '<span class="badge badge-light-warning">Average</span>',
            'below_average' => '<span class="badge badge-light-danger">Below Average</span>',
            'poor' => '<span class="badge badge-light-danger">Poor</span>',
        ];
        return $badges[$rating] ?? '<span class="badge badge-light-secondary">' . $rating . '</span>';
    }
}