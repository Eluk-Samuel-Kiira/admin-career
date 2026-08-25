<?php

namespace App\Http\Controllers\Compensation;

use App\Http\Controllers\Controller;
use App\Models\Bonus;
use App\Models\EmployeeSalary;
use App\Models\User;
use App\Models\Department;
use App\Models\PaymentMethod;
use App\Models\Currency;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BonusController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display bonuses list.
     */
    public function index()
    {
        return view('compensation.bonuses.index');
    }

    /**
     * Get bonuses data for datatable.
     */
    public function getBonuses(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        $status = $request->get('status', '');
        $departmentId = $request->get('department_id', '');
        $userId = $request->get('user_id', '');
        $bonusType = $request->get('bonus_type', '');
        $bonusCategory = $request->get('bonus_category', '');

        $query = Bonus::with(['user', 'department', 'paymentMethod', 'approver', 'creator']);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('bonus_number', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('reference', 'like', '%' . $search . '%');
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

        if (!empty($bonusType)) {
            $query->where('bonus_type', $bonusType);
        }

        if (!empty($bonusCategory)) {
            $query->where('bonus_category', $bonusCategory);
        }

        $bonuses = $query->orderBy('bonus_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Get summary statistics
        $summary = [
            'total_bonuses' => Bonus::count(),
            'pending_count' => Bonus::where('status', 'pending')->count(),
            'approved_count' => Bonus::where('status', 'approved')->count(),
            'paid_count' => Bonus::where('status', 'paid')->count(),
            'total_amount' => Bonus::where('status', 'paid')->sum('amount'),
        ];

        $data = [
            'current_page' => $bonuses->currentPage(),
            'data' => collect($bonuses->items())->map(function($bonus) {
                // For UGX, amount is already in base units (no division by 100)
                $amount = $bonus->amount;
                
                return [
                    'id' => $bonus->id,
                    'bonus_number' => $bonus->bonus_number,
                    'user' => $bonus->user ? [
                        'id' => $bonus->user->id,
                        'name' => $bonus->user->full_name ?? $bonus->user->name,
                    ] : null,
                    'department' => $bonus->department?->name ?? 'N/A',
                    'bonus_type' => $bonus->bonus_type,
                    'bonus_type_label' => $bonus->bonus_type_label,
                    'bonus_category' => $bonus->bonus_category,
                    'bonus_category_label' => $bonus->bonus_category_label,
                    'amount' => $amount,
                    'formatted_amount' => 'UGX ' . number_format($amount, 0),
                    'bonus_date' => $bonus->bonus_date,
                    'status' => $bonus->status,
                    'status_badge' => $bonus->status_badge,
                    'is_paid' => (bool) $bonus->is_paid,
                    'description' => $bonus->description,
                    'performance_score' => $bonus->performance_score,
                    'target_achieved' => $bonus->target_achieved,
                    'created_at' => $bonus->created_at,
                ];
            })->toArray(),
            'first_page_url' => $bonuses->url(1),
            'from' => $bonuses->firstItem(),
            'last_page' => $bonuses->lastPage(),
            'last_page_url' => $bonuses->url($bonuses->lastPage()),
            'next_page_url' => $bonuses->nextPageUrl(),
            'prev_page_url' => $bonuses->previousPageUrl(),
            'to' => $bonuses->lastItem(),
            'total' => $bonuses->total(),
            'per_page' => $perPage,
            'summary' => $summary,
        ];

        return response()->json($data);
    }

    /**
     * Get form data for creating/editing bonus.
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

        $employeeSalaries = EmployeeSalary::with(['user', 'department'])
            ->where('is_active', true)
            ->get();

        $paymentMethods = PaymentMethod::with('currency')
            ->where('is_active', true)
            ->get();

        return response()->json([
            'users' => $users,
            'departments' => $departments,
            'employee_salaries' => $employeeSalaries,
            'payment_methods' => $paymentMethods,
            'bonus_types' => [
                ['value' => 'performance', 'label' => 'Performance'],
                ['value' => 'retention', 'label' => 'Retention'],
                ['value' => 'commission', 'label' => 'Commission'],
                ['value' => 'extraordinary', 'label' => 'Extraordinary'],
                ['value' => 'referral', 'label' => 'Referral'],
                ['value' => 'signing', 'label' => 'Signing'],
                ['value' => 'holiday', 'label' => 'Holiday'],
                ['value' => 'project', 'label' => 'Project'],
                ['value' => 'team', 'label' => 'Team'],
            ],
            'bonus_categories' => [
                ['value' => 'monthly', 'label' => 'Monthly'],
                ['value' => 'quarterly', 'label' => 'Quarterly'],
                ['value' => 'annual', 'label' => 'Annual'],
                ['value' => 'one_time', 'label' => 'One Time'],
            ],
            'statuses' => [
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'approved', 'label' => 'Approved'],
                ['value' => 'paid', 'label' => 'Paid'],
                ['value' => 'rejected', 'label' => 'Rejected'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    /**
     * Store a new bonus.
     */
    public function store(Request $request)
    {
        
        if (!auth()->user()->can('create bonuses')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create bonuses.'
            ]);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'employee_salary_id' => 'nullable|exists:employee_salaries,id',
            'department_id' => 'nullable|exists:departments,id',
            'bonus_type' => 'required|in:performance,retention,commission,extraordinary,referral,signing,holiday,project,team',
            'bonus_category' => 'required|in:monthly,quarterly,annual,one_time',
            'amount' => 'required|numeric|min:0',
            'bonus_date' => 'required|date',
            'status' => 'required|in:pending,approved,paid,rejected,cancelled',
            'description' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:255',
            'performance_score' => 'nullable|numeric|min:0|max:100',
            'target_achieved' => 'nullable|numeric|min:0|max:100',
            'target_metric' => 'nullable|string|max:255',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'notes' => 'nullable|string',
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

            // Store amount as-is for UGX (no multiplication by 100)
            $amount = (int) round($request->amount);

            $bonus = Bonus::create([
                'bonus_number' => 'BONUS-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'employee_salary_id' => $employeeSalaryId,
                'user_id' => $request->user_id,
                'department_id' => $departmentId,
                'payment_method_id' => $request->payment_method_id,
                'bonus_type' => $request->bonus_type,
                'bonus_category' => $request->bonus_category,
                'amount' => $amount, // No multiplication
                'percentage_of_salary' => $request->percentage_of_salary,
                'performance_score' => $request->performance_score,
                'target_achieved' => $request->target_achieved,
                'target_metric' => $request->target_metric,
                'description' => $request->description,
                'reference' => $request->reference,
                'bonus_date' => $request->bonus_date,
                'status' => $request->status,
                'notes' => $request->notes,
                'metadata' => $request->metadata,
                'created_by' => auth()->id(),
            ]);

            // If status is paid, mark as paid
            if ($request->status === 'paid' && $request->payment_method_id) {
                $this->processBonusPayment($bonus, $request->payment_method_id);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bonus created successfully',
                'bonus' => $bonus
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bonus creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bonus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show bonus details.
     */
    public function show($id)
    {
        try {
            $bonus = Bonus::with(['user', 'department', 'paymentMethod', 'employeeSalary', 'approver', 'creator'])
                ->findOrFail($id);

            // No division by 100 for UGX
            $amount = $bonus->amount;

            return response()->json([
                'id' => $bonus->id,
                'bonus_number' => $bonus->bonus_number,
                'user' => $bonus->user ? [
                    'id' => $bonus->user->id,
                    'name' => $bonus->user->full_name ?? $bonus->user->name,
                    'email' => $bonus->user->email,
                ] : null,
                'department' => $bonus->department ? [
                    'id' => $bonus->department->id,
                    'name' => $bonus->department->name,
                ] : null,
                'payment_method' => $bonus->paymentMethod ? [
                    'id' => $bonus->paymentMethod->id,
                    'name' => $bonus->paymentMethod->name,
                ] : null,
                'employee_salary' => $bonus->employeeSalary ? [
                    'id' => $bonus->employeeSalary->id,
                    'base_salary' => $bonus->employeeSalary->base_salary,
                ] : null,
                'bonus_type' => $bonus->bonus_type,
                'bonus_type_label' => $bonus->bonus_type_label,
                'bonus_category' => $bonus->bonus_category,
                'bonus_category_label' => $bonus->bonus_category_label,
                'amount' => $amount,
                'formatted_amount' => 'UGX ' . number_format($amount, 0),
                'percentage_of_salary' => $bonus->percentage_of_salary,
                'performance_score' => $bonus->performance_score,
                'target_achieved' => $bonus->target_achieved,
                'target_metric' => $bonus->target_metric,
                'description' => $bonus->description,
                'reference' => $bonus->reference,
                'bonus_date' => $bonus->bonus_date,
                'paid_date' => $bonus->paid_date,
                'is_paid' => (bool) $bonus->is_paid,
                'status' => $bonus->status,
                'status_badge' => $bonus->status_badge,
                'approval_notes' => $bonus->approval_notes,
                'approved_at' => $bonus->approved_at,
                'approved_by' => $bonus->approver ? [
                    'id' => $bonus->approver->id,
                    'name' => $bonus->approver->full_name ?? $bonus->approver->name,
                ] : null,
                'notes' => $bonus->notes,
                'created_at' => $bonus->created_at,
                'created_by' => $bonus->creator?->name ?? 'N/A',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bonus not found'
            ], 404);
        }
    }

    /**
     * Update bonus.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit bonuses')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit bonuses.'
            ]);
        }

        $request->validate([
            'bonus_type' => 'required|in:performance,retention,commission,extraordinary,referral,signing,holiday,project,team',
            'bonus_category' => 'required|in:monthly,quarterly,annual,one_time',
            'amount' => 'required|numeric|min:0',
            'bonus_date' => 'required|date',
            'status' => 'required|in:pending,approved,paid,rejected,cancelled',
            'description' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:255',
            'performance_score' => 'nullable|numeric|min:0|max:100',
            'target_achieved' => 'nullable|numeric|min:0|max:100',
            'target_metric' => 'nullable|string|max:255',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $bonus = Bonus::findOrFail($id);

            // Store amount as-is for UGX (no multiplication by 100)
            $amount = (int) round($request->amount);

            // If status is being changed to paid, process payment
            if ($request->status === 'paid' && $bonus->status !== 'paid' && $request->payment_method_id) {
                $bonus->update([
                    'bonus_type' => $request->bonus_type,
                    'bonus_category' => $request->bonus_category,
                    'amount' => $amount,
                    'percentage_of_salary' => $request->percentage_of_salary,
                    'performance_score' => $request->performance_score,
                    'target_achieved' => $request->target_achieved,
                    'target_metric' => $request->target_metric,
                    'description' => $request->description,
                    'reference' => $request->reference,
                    'bonus_date' => $request->bonus_date,
                    'status' => $request->status,
                    'notes' => $request->notes,
                    'metadata' => $request->metadata,
                    'payment_method_id' => $request->payment_method_id,
                ]);
                $this->processBonusPayment($bonus, $request->payment_method_id);
            } else {
                $bonus->update([
                    'bonus_type' => $request->bonus_type,
                    'bonus_category' => $request->bonus_category,
                    'amount' => $amount,
                    'percentage_of_salary' => $request->percentage_of_salary,
                    'performance_score' => $request->performance_score,
                    'target_achieved' => $request->target_achieved,
                    'target_metric' => $request->target_metric,
                    'description' => $request->description,
                    'reference' => $request->reference,
                    'bonus_date' => $request->bonus_date,
                    'status' => $request->status,
                    'notes' => $request->notes,
                    'metadata' => $request->metadata,
                    'payment_method_id' => $request->payment_method_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bonus updated successfully',
                'bonus' => $bonus
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bonus update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update bonus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve bonus.
     */
    public function approve(Request $request, $id)
    {
        if (!auth()->user()->can('approve bonuses')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve bonuses.'
            ]);
        }

        try {
            DB::beginTransaction();

            $bonus = Bonus::findOrFail($id);
            
            if ($bonus->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Bonus cannot be approved. Current status: ' . $bonus->status
                ], 400);
            }

            $bonus->approve(auth()->id(), $request->approval_notes);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bonus approved successfully',
                'bonus' => $bonus
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve bonus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pay bonus.
     */
    public function pay(Request $request, $id)
    {
        if (!auth()->user()->can('pay bonuses')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to pay bonuses.'
            ]);
        }

        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        try {
            DB::beginTransaction();

            $bonus = Bonus::findOrFail($id);
            
            if (!in_array($bonus->status, ['pending', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bonus cannot be paid. Current status: ' . $bonus->status
                ], 400);
            }

            $this->processBonusPayment($bonus, $request->payment_method_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bonus paid successfully',
                'bonus' => $bonus
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to pay bonus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process bonus payment through PaymentService.
     */
    private function processBonusPayment(Bonus $bonus, $paymentMethodId)
    {
        $paymentMethod = PaymentMethod::with('currency')->find($paymentMethodId);
        if (!$paymentMethod) {
            throw new \Exception('Payment method not found.');
        }

        $currency = $paymentMethod->currency ?? Currency::getDefault();
        
        // For UGX, amount is already in base units (no conversion needed)
        $amountToDeduct = $bonus->amount;

        // Check balance
        if ($paymentMethod->current_balance < $amountToDeduct) {
            throw new \Exception('Insufficient balance in selected payment method. Available: ' . 
                $paymentMethod->formatted_current_balance . ', Required: UGX ' . number_format($amountToDeduct, 0));
        }

        $transaction = $this->paymentService->withdraw([
            'payment_method_id' => $paymentMethodId,
            'amount' => $amountToDeduct,
            'currency_id' => $currency->id,
            'user_id' => $bonus->user_id,
            'department_id' => $bonus->department_id,
            'description' => 'Bonus payment - ' . $bonus->bonus_number . ' - ' . $bonus->bonus_type_label,
            'reference_table' => 'bonuses',
            'reference_id' => $bonus->id,
            'external_reference' => $bonus->reference,
            'metadata' => [
                'bonus_number' => $bonus->bonus_number,
                'bonus_type' => $bonus->bonus_type_label,
                'bonus_category' => $bonus->bonus_category_label,
                'employee' => $bonus->user?->full_name ?? $bonus->user?->name,
                'department' => $bonus->department?->name,
            ],
        ]);

        $bonus->markAsPaid($paymentMethodId);

        return $transaction;
    }

    /**
     * Delete bonus.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete bonuses')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete bonuses.'
            ]);
        }
        try {
            $bonus = Bonus::findOrFail($id);
            
            if ($bonus->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a paid bonus.'
                ], 400);
            }
            
            $bonus->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bonus deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete bonus: ' . $e->getMessage()
            ], 500);
        }
    }
}