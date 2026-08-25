<?php

namespace App\Http\Controllers\Compensation;

use App\Http\Controllers\Controller;
use App\Models\SalaryStructure;
use App\Models\Department;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SalaryStructureController extends Controller
{
    /**
     * Display salary structures list
     */
    public function index()
    {
        return view('compensation.salary-structures.index');
    }

    /**
     * Get salary structures data for datatable
     */
    public function getSalaryStructures(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        $departmentId = $request->get('department_id', '');
        $isActive = $request->get('is_active', '');

        $query = SalaryStructure::with(['department', 'currency', 'creator']);

        // Apply filters
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('job_title', 'like', '%' . $search . '%')
                  ->orWhere('role_code', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        if ($isActive !== '') {
            $query->where('is_active', $isActive === 'true');
        }

        $structures = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Get summary statistics
        $summary = [
            'total' => SalaryStructure::count(),
            'active' => SalaryStructure::where('is_active', true)->count(),
            'inactive' => SalaryStructure::where('is_active', false)->count(),
            'total_budget' => SalaryStructure::sum('base_salary'),
        ];

        $data = [
            'current_page' => $structures->currentPage(),
            'data' => collect($structures->items())->map(function($structure) {
                $currency = $structure->currency;
                $decimalPlaces = $currency ? $currency->decimal_places : 0;
                
                // Format based on currency decimal places
                if ($currency && $currency->decimal_places === 0) {
                    // UGX, KES - no decimals
                    $formattedSalary = $currency->symbol . ' ' . number_format($structure->base_salary, 0);
                } else {
                    // USD, EUR - with decimals
                    $formattedSalary = $currency ? $currency->formatAmount($structure->base_salary) : '$ ' . number_format($structure->base_salary / 100, 2);
                }

                return [
                    'id' => $structure->id,
                    'job_title' => $structure->job_title,
                    'role_code' => $structure->role_code,
                    'department' => $structure->department?->name ?? 'N/A',
                    'department_id' => $structure->department_id,
                    'base_salary' => $structure->base_salary,
                    'formatted_salary' => $formattedSalary,
                    'salary_type' => $structure->salary_type,
                    'salary_type_label' => ucfirst($structure->salary_type),
                    'phantom_equity_units' => $structure->phantom_equity_units,
                    'profit_share_percentage' => $structure->profit_share_percentage,
                    'commission_rate' => $structure->commission_rate,
                    'performance_bonus_percentage' => $structure->performance_bonus_percentage,
                    'retention_bonus' => $structure->retention_bonus,
                    'formatted_retention_bonus' => $currency ? $currency->formatAmount($structure->retention_bonus ?? 0) : 'UGX ' . number_format(($structure->retention_bonus ?? 0), 0),
                    'min_salary' => $structure->min_salary,
                    'max_salary' => $structure->max_salary,
                    'is_active' => (bool) $structure->is_active,
                    'status_badge' => $structure->is_active ? 
                        '<span class="badge badge-light-success">Active</span>' : 
                        '<span class="badge badge-light-danger">Inactive</span>',
                    'created_at' => $structure->created_at?->format('Y-m-d H:i:s'),
                    'currency_code' => $currency?->code ?? 'UGX',
                    'decimal_places' => $decimalPlaces,
                ];
            })->toArray(),
            'first_page_url' => $structures->url(1),
            'from' => $structures->firstItem(),
            'last_page' => $structures->lastPage(),
            'last_page_url' => $structures->url($structures->lastPage()),
            'next_page_url' => $structures->nextPageUrl(),
            'prev_page_url' => $structures->previousPageUrl(),
            'to' => $structures->lastItem(),
            'total' => $structures->total(),
            'per_page' => $perPage,
            'summary' => $summary,
        ];

        return response()->json($data);
    }

    /**
     * Get form data for salary structure creation/editing
     */
    public function getFormData()
    {
        $departments = Department::where('is_active', true)
            ->select('id', 'name', 'code')
            ->get();

        $currencies = Currency::where('is_active', true)
            ->select('id', 'code', 'name', 'symbol', 'decimal_places')
            ->get();

        return response()->json([
            'departments' => $departments,
            'currencies' => $currencies,
            'salary_types' => [
                ['value' => 'fixed', 'label' => 'Fixed Salary'],
                ['value' => 'hourly', 'label' => 'Hourly Rate'],
                ['value' => 'commission', 'label' => 'Commission Based'],
            ],
        ]);
    }

    /**
     * Store a new salary structure
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create salary structure')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create salary structure.'
            ]);
        }

        $request->validate([
            'job_title' => 'required|string|max:255',
            'role_code' => 'required|string|max:50|unique:salary_structures,role_code',
            'department_id' => 'nullable|exists:departments,id',
            'base_salary' => 'required|numeric|min:0',
            'salary_type' => 'required|in:fixed,hourly,commission',
            'currency_id' => 'required|exists:currencies,id',
            'phantom_equity_units' => 'nullable|integer|min:0',
            'profit_share_percentage' => 'nullable|numeric|min:0|max:100',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'performance_bonus_percentage' => 'nullable|numeric|min:0|max:100',
            'performance_bonus_max' => 'nullable|numeric|min:0',
            'retention_bonus' => 'nullable|numeric|min:0',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
            'description' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $currency = Currency::findOrFail($request->currency_id);
                
                // Convert based on currency decimal places
                if ($currency->decimal_places === 0) {
                    // For UGX, KES, etc. - store as-is
                    $baseSalary = (int) round($request->base_salary);
                    $minSalary = $request->min_salary ? (int) round($request->min_salary) : null;
                    $maxSalary = $request->max_salary ? (int) round($request->max_salary) : null;
                    $performanceBonusMax = $request->performance_bonus_max ? (int) round($request->performance_bonus_max) : null;
                    $retentionBonus = $request->retention_bonus ? (int) round($request->retention_bonus) : null;
                } else {
                    // For USD, EUR, etc. - multiply by 100 (cents)
                    $baseSalary = (int) round($request->base_salary * 100);
                    $minSalary = $request->min_salary ? (int) round($request->min_salary * 100) : null;
                    $maxSalary = $request->max_salary ? (int) round($request->max_salary * 100) : null;
                    $performanceBonusMax = $request->performance_bonus_max ? (int) round($request->performance_bonus_max * 100) : null;
                    $retentionBonus = $request->retention_bonus ? (int) round($request->retention_bonus * 100) : null;
                }

                SalaryStructure::create([
                    'job_title' => $request->job_title,
                    'role_code' => strtoupper($request->role_code),
                    'department_id' => $request->department_id,
                    'base_salary' => $baseSalary,
                    'salary_type' => $request->salary_type,
                    'currency_id' => $request->currency_id,
                    'phantom_equity_units' => (int) ($request->phantom_equity_units ?? 0),
                    'profit_share_percentage' => (float) ($request->profit_share_percentage ?? 0),
                    'commission_rate' => (float) ($request->commission_rate ?? 0),
                    'performance_bonus_percentage' => (float) ($request->performance_bonus_percentage ?? 0),
                    'performance_bonus_max' => $performanceBonusMax,
                    'retention_bonus' => $retentionBonus,
                    'min_salary' => $minSalary,
                    'max_salary' => $maxSalary,
                    'description' => $request->description,
                    'is_active' => $request->has('is_active'),
                    'created_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Salary structure created successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Salary structure creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create salary structure: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show salary structure details
     */
    public function show($id)
    {
        try {
            $structure = SalaryStructure::with(['department', 'currency', 'creator'])
                ->findOrFail($id);

            $currency = $structure->currency;
            $decimalPlaces = $currency ? $currency->decimal_places : 0;

            // Format values based on currency
            if ($decimalPlaces === 0) {
                // For UGX, KES - value is already in base units
                $baseSalary = (string) $structure->base_salary;
                $minSalary = $structure->min_salary !== null ? (string) $structure->min_salary : null;
                $maxSalary = $structure->max_salary !== null ? (string) $structure->max_salary : null;
                $performanceBonusMax = $structure->performance_bonus_max !== null ? (string) $structure->performance_bonus_max : null;
                $retentionBonus = $structure->retention_bonus !== null ? (string) $structure->retention_bonus : null;
                $formattedSalary = $currency->symbol . ' ' . number_format($structure->base_salary, 0);
            } else {
                // For USD, EUR - divide by 100 (cents to dollars)
                $baseSalary = number_format($structure->base_salary / 100, $decimalPlaces, '.', '');
                $minSalary = $structure->min_salary !== null ? number_format($structure->min_salary / 100, $decimalPlaces, '.', '') : null;
                $maxSalary = $structure->max_salary !== null ? number_format($structure->max_salary / 100, $decimalPlaces, '.', '') : null;
                $performanceBonusMax = $structure->performance_bonus_max !== null ? number_format($structure->performance_bonus_max / 100, $decimalPlaces, '.', '') : null;
                $retentionBonus = $structure->retention_bonus !== null ? number_format($structure->retention_bonus / 100, $decimalPlaces, '.', '') : null;
                $formattedSalary = $currency->formatAmount($structure->base_salary);
            }

            return response()->json([
                'id' => $structure->id,
                'job_title' => $structure->job_title,
                'role_code' => $structure->role_code,
                'department_id' => $structure->department_id,
                'department' => $structure->department?->name ?? 'N/A',
                'base_salary' => $baseSalary,
                'formatted_salary' => $formattedSalary,
                'salary_type' => $structure->salary_type,
                'salary_type_label' => ucfirst($structure->salary_type),
                'currency_id' => $structure->currency_id,
                'currency' => $currency?->code ?? 'UGX',
                'currency_decimal_places' => $decimalPlaces,
                'phantom_equity_units' => $structure->phantom_equity_units,
                'profit_share_percentage' => $structure->profit_share_percentage,
                'commission_rate' => $structure->commission_rate,
                'performance_bonus_percentage' => $structure->performance_bonus_percentage,
                'performance_bonus_max' => $performanceBonusMax,
                'retention_bonus' => $retentionBonus,
                'min_salary' => $minSalary,
                'max_salary' => $maxSalary,
                'description' => $structure->description,
                'is_active' => (bool) $structure->is_active,
                'created_at' => $structure->created_at?->format('Y-m-d H:i:s'),
                'created_by' => $structure->creator?->name ?? 'N/A',
            ]);

        } catch (\Exception $e) {
            Log::error('Salary structure show failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Salary structure not found'
            ], 404);
        }
    }

    /**
     * Update salary structure
     */
    public function update(Request $request, $id)
    {
        
        if (!auth()->user()->can('edit salary structure')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit salary structure.'
            ]);
        }

        $request->validate([
            'job_title' => 'required|string|max:255',
            'role_code' => 'required|string|max:50|unique:salary_structures,role_code,' . $id,
            'department_id' => 'nullable|exists:departments,id',
            'base_salary' => 'required|numeric|min:0',
            'salary_type' => 'required|in:fixed,hourly,commission',
            'currency_id' => 'required|exists:currencies,id',
            'phantom_equity_units' => 'nullable|integer|min:0',
            'profit_share_percentage' => 'nullable|numeric|min:0|max:100',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'performance_bonus_percentage' => 'nullable|numeric|min:0|max:100',
            'performance_bonus_max' => 'nullable|numeric|min:0',
            'retention_bonus' => 'nullable|numeric|min:0',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
            'description' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $structure = SalaryStructure::findOrFail($id);
                $currency = Currency::findOrFail($request->currency_id);
                
                // Convert based on currency decimal places
                if ($currency->decimal_places === 0) {
                    // For UGX, KES, etc. - store as-is
                    $baseSalary = (int) round($request->base_salary);
                    $minSalary = $request->min_salary ? (int) round($request->min_salary) : null;
                    $maxSalary = $request->max_salary ? (int) round($request->max_salary) : null;
                    $performanceBonusMax = $request->performance_bonus_max ? (int) round($request->performance_bonus_max) : null;
                    $retentionBonus = $request->retention_bonus ? (int) round($request->retention_bonus) : null;
                } else {
                    // For USD, EUR, etc. - multiply by 100 (cents)
                    $baseSalary = (int) round($request->base_salary * 100);
                    $minSalary = $request->min_salary ? (int) round($request->min_salary * 100) : null;
                    $maxSalary = $request->max_salary ? (int) round($request->max_salary * 100) : null;
                    $performanceBonusMax = $request->performance_bonus_max ? (int) round($request->performance_bonus_max * 100) : null;
                    $retentionBonus = $request->retention_bonus ? (int) round($request->retention_bonus * 100) : null;
                }

                $structure->update([
                    'job_title' => $request->job_title,
                    'role_code' => strtoupper($request->role_code),
                    'department_id' => $request->department_id,
                    'base_salary' => $baseSalary,
                    'salary_type' => $request->salary_type,
                    'currency_id' => $request->currency_id,
                    'phantom_equity_units' => (int) ($request->phantom_equity_units ?? 0),
                    'profit_share_percentage' => (float) ($request->profit_share_percentage ?? 0),
                    'commission_rate' => (float) ($request->commission_rate ?? 0),
                    'performance_bonus_percentage' => (float) ($request->performance_bonus_percentage ?? 0),
                    'performance_bonus_max' => $performanceBonusMax,
                    'retention_bonus' => $retentionBonus,
                    'min_salary' => $minSalary,
                    'max_salary' => $maxSalary,
                    'description' => $request->description,
                    'is_active' => $request->has('is_active'),
                    'updated_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Salary structure updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Salary structure update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update salary structure: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle salary structure status
     */
    public function toggleStatus($id)
    {
        
        if (!auth()->user()->can('edit salary structure')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit salary structure.'
            ]);
        }

        try {
            $structure = SalaryStructure::findOrFail($id);
            $structure->is_active = !$structure->is_active;
            $structure->save();

            return response()->json([
                'success' => true,
                'message' => $structure->is_active ? 'Salary structure activated successfully' : 'Salary structure deactivated successfully',
                'is_active' => $structure->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Toggle status failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle salary structure status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete salary structure
     */
    public function destroy($id)
    {
        
        if (!auth()->user()->can('delete salary structure')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete salary structure.'
            ]);
        }

        try {
            $structure = SalaryStructure::findOrFail($id);
            
            // Check if structure is being used by employees
            $hasEmployees = \App\Models\EmployeeSalary::where('salary_structure_id', $id)->exists();
            if ($hasEmployees) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this salary structure as it is assigned to employees'
                ], 400);
            }
            
            $structure->delete();

            return response()->json([
                'success' => true,
                'message' => 'Salary structure deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete salary structure failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete salary structure: ' . $e->getMessage()
            ], 500);
        }
    }
}