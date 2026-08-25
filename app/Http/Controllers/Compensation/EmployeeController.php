<?php

namespace App\Http\Controllers\Compensation;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\EmployeeSalary;
use App\Models\SalaryStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    public function index()
    {
        return view('compensation.employees.index');
    }

    public function getEmployees(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        $status = $request->get('status', '');
        $departmentId = $request->get('department_id', '');
        $employeeType = $request->get('employee_type', '');

        $query = Employee::with(['user', 'department', 'employeeSalary']);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('job_title', 'like', '%' . $search . '%');
            });
        }

        if (!empty($status)) {
            $query->where('is_active', $status === 'active');
        }

        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        if (!empty($employeeType)) {
            $query->where('employee_type', $employeeType);
        }

        $query->whereNotIn('employee_type', ['job_seeker', 'employer']);

        $employees = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $summary = [
            'total' => Employee::whereNotIn('employee_type', ['job_seeker', 'employer'])->count(),
            'active' => Employee::whereNotIn('employee_type', ['job_seeker', 'employer'])->where('is_active', true)->count(),
            'inactive' => Employee::whereNotIn('employee_type', ['job_seeker', 'employer'])->where('is_active', false)->count(),
            'total_salary' => Employee::whereNotIn('employee_type', ['job_seeker', 'employer'])->sum('salary'),
        ];

        $data = [
            'current_page' => $employees->currentPage(),
            'data' => collect($employees->items())->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'user_id' => $employee->user_id,
                    'name' => $employee->full_name,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'job_title' => $employee->job_title,
                    'employee_type' => $employee->employee_type,
                    'employee_type_label' => $employee->employee_type_label,
                    'department' => $employee->department?->name ?? 'N/A',
                    'department_id' => $employee->department_id,
                    'hire_date' => $employee->hire_date,
                    'salary' => $employee->salary,
                    'formatted_salary' => 'UGX ' . number_format($employee->salary, 0),
                    'salary_type' => $employee->salary_type,
                    'is_active' => $employee->is_active,
                    'status_badge' => $employee->is_active ? 
                        '<span class="badge badge-light-success">Active</span>' : 
                        '<span class="badge badge-light-danger">Inactive</span>',
                    'has_salary' => $employee->employeeSalary?->exists() ?? false,
                    'phantom_equity_units' => $employee->employeeSalary?->phantom_equity_units ?? 0,
                    'vested_units' => $employee->employeeSalary?->vested_units ?? 0,
                    'created_at' => $employee->created_at?->format('Y-m-d H:i:s'),
                ];
            })->toArray(),
            'first_page_url' => $employees->url(1),
            'from' => $employees->firstItem(),
            'last_page' => $employees->lastPage(),
            'last_page_url' => $employees->url($employees->lastPage()),
            'next_page_url' => $employees->nextPageUrl(),
            'prev_page_url' => $employees->previousPageUrl(),
            'to' => $employees->lastItem(),
            'total' => $employees->total(),
            'per_page' => $perPage,
            'summary' => $summary,
        ];

        return response()->json($data);
    }

    public function getFormData()
    {
        $users = User::whereDoesntHave('employee')
            ->where('is_active', true)
            ->select('id', 'name', 'first_name', 'last_name', 'email')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->full_name ?? $user->name,
                    'email' => $user->email,
                ];
            });

        $departments = Department::where('is_active', true)
            ->select('id', 'name', 'code')
            ->get();

        $salaryStructures = SalaryStructure::where('is_active', true)
            ->select('id', 'job_title', 'role_code', 'base_salary')
            ->get();

        return response()->json([
            'users' => $users,
            'departments' => $departments,
            'salary_structures' => $salaryStructures,
            'employee_types' => [
                ['value' => 'full_time', 'label' => 'Full Time'],
                ['value' => 'part_time', 'label' => 'Part Time'],
                ['value' => 'contract', 'label' => 'Contract'],
                ['value' => 'intern', 'label' => 'Intern'],
            ],
            'salary_types' => [
                ['value' => 'fixed', 'label' => 'Fixed Salary'],
                ['value' => 'hourly', 'label' => 'Hourly Rate'],
                ['value' => 'commission', 'label' => 'Commission Based'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('create employees')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create employees.'
            ]);
        }

        $isRecurring = $request->has('is_salary_recurring') && 
                    ($request->input('is_salary_recurring') == '1' || 
                        $request->input('is_salary_recurring') == 'on' || 
                        $request->input('is_salary_recurring') === true);

        $request->merge([
            'is_salary_recurring' => $isRecurring
        ]);

        $request->validate([
            'user_id' => 'required|exists:users,id|unique:employees,user_id',
            'department_id' => 'nullable|exists:departments,id',
            'job_title' => 'required|string|max:255',
            'employee_type' => 'required|in:full_time,part_time,contract,intern',
            'hire_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'salary_type' => 'required|in:fixed,hourly,commission',
            'is_salary_recurring' => 'sometimes|boolean',
            'recurring_day' => 'nullable|integer|min:1|max:31',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $user = User::findOrFail($request->user_id);

                $firstName = $user->first_name ?? $user->name ?? 'Unknown';
                $lastName = $user->last_name ?? '';

                // Store exactly what comes from the form
                $salary = (int) round($request->salary);

                $employee = Employee::create([
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'phone' => $user->phone ?? null,
                    'department_id' => $request->department_id,
                    'job_title' => $request->job_title,
                    'employee_type' => $request->employee_type,
                    'hire_date' => $request->hire_date,
                    'salary' => $salary,
                    'salary_type' => $request->salary_type,
                    'is_salary_recurring' => $request->is_salary_recurring,
                    'recurring_day' => $request->recurring_day,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);

                $salaryStructure = SalaryStructure::where('role_code', $request->job_title)
                    ->orWhere('job_title', $request->job_title)
                    ->first();

                $phantomUnits = $salaryStructure?->phantom_equity_units ?? 0;

                EmployeeSalary::create([
                    'employee_id' => $employee->id,
                    'user_id' => $user->id,
                    'department_id' => $request->department_id,
                    'salary_structure_id' => $salaryStructure?->id,
                    'base_salary' => $salary,
                    'salary_type' => $request->salary_type,
                    'is_recurring' => $request->is_salary_recurring,
                    'recurring_day' => $request->recurring_day,
                    'hire_date' => $request->hire_date,
                    'phantom_equity_units' => $phantomUnits,
                    'vested_units' => 0,
                    'units_vested_percentage' => 0,
                    'current_balance' => 0,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Employee creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create employee: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        
        if (!auth()->user()->can('edit employees')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update employees.'
            ]);
        }

        $isRecurring = $request->has('is_salary_recurring') && 
                    ($request->input('is_salary_recurring') == '1' || 
                        $request->input('is_salary_recurring') == 'on' || 
                        $request->input('is_salary_recurring') === true);

        $request->merge([
            'is_salary_recurring' => $isRecurring
        ]);

        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'job_title' => 'required|string|max:255',
            'employee_type' => 'required|in:full_time,part_time,contract,intern',
            'hire_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'salary_type' => 'required|in:fixed,hourly,commission',
            'is_salary_recurring' => 'sometimes|boolean',
            'recurring_day' => 'nullable|integer|min:1|max:31',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $employee = Employee::findOrFail($id);

                // Store exactly what comes from the form
                $salary = (int) round($request->salary);

                $employee->update([
                    'department_id' => $request->department_id,
                    'job_title' => $request->job_title,
                    'employee_type' => $request->employee_type,
                    'hire_date' => $request->hire_date,
                    'salary' => $salary,
                    'salary_type' => $request->salary_type,
                    'is_salary_recurring' => $request->is_salary_recurring,
                    'recurring_day' => $request->recurring_day,
                    'updated_by' => auth()->id(),
                ]);

                $salaryStructure = SalaryStructure::where('role_code', $request->job_title)
                    ->orWhere('job_title', $request->job_title)
                    ->first();

                $employeeSalary = $employee->employeeSalary;
                if ($employeeSalary) {
                    $employeeSalary->update([
                        'department_id' => $request->department_id,
                        'salary_structure_id' => $salaryStructure?->id,
                        'base_salary' => $salary,
                        'salary_type' => $request->salary_type,
                        'is_recurring' => $request->is_salary_recurring,
                        'recurring_day' => $request->recurring_day,
                        'hire_date' => $request->hire_date,
                        'updated_by' => auth()->id(),
                    ]);
                } else {
                    EmployeeSalary::create([
                        'employee_id' => $employee->id,
                        'user_id' => $employee->user_id,
                        'department_id' => $request->department_id,
                        'salary_structure_id' => $salaryStructure?->id,
                        'base_salary' => $salary,
                        'salary_type' => $request->salary_type,
                        'is_recurring' => $request->is_salary_recurring,
                        'recurring_day' => $request->recurring_day,
                        'hire_date' => $request->hire_date,
                        'phantom_equity_units' => $salaryStructure?->phantom_equity_units ?? 0,
                        'vested_units' => 0,
                        'units_vested_percentage' => 0,
                        'current_balance' => 0,
                        'is_active' => true,
                        'created_by' => auth()->id(),
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Employee update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update employee: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $employee = Employee::with(['user', 'department', 'employeeSalary', 'employeeSalary.salaryStructure'])
                ->findOrFail($id);

            return response()->json([
                'id' => $employee->id,
                'user_id' => $employee->user_id,
                'name' => $employee->full_name,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'department_id' => $employee->department_id,
                'department' => $employee->department?->name ?? 'N/A',
                'job_title' => $employee->job_title,
                'employee_type' => $employee->employee_type,
                'employee_type_label' => $employee->employee_type_label,
                'hire_date' => $employee->hire_date ? $employee->hire_date->format('Y-m-d') : null,
                'termination_date' => $employee->termination_date ? $employee->termination_date->format('Y-m-d') : null,
                'salary' => $employee->salary, // Return exactly what's in the database
                'formatted_salary' => 'UGX ' . number_format($employee->salary, 0),
                'salary_type' => $employee->salary_type,
                'is_salary_recurring' => (bool) $employee->is_salary_recurring,
                'recurring_day' => $employee->recurring_day,
                'is_active' => (bool) $employee->is_active,
                'employee_salary' => $employee->employeeSalary ? [
                    'base_salary' => $employee->employeeSalary->base_salary,
                    'phantom_equity_units' => $employee->employeeSalary->phantom_equity_units,
                    'vested_units' => $employee->employeeSalary->vested_units,
                    'units_vested_percentage' => $employee->employeeSalary->units_vested_percentage,
                    'performance_rating' => $employee->employeeSalary->performance_rating,
                    'performance_multiplier' => $employee->employeeSalary->performance_multiplier,
                    'salary_structure' => $employee->employeeSalary->salaryStructure ? [
                        'job_title' => $employee->employeeSalary->salaryStructure->job_title,
                        'role_code' => $employee->employeeSalary->salaryStructure->role_code,
                        'phantom_equity_units' => $employee->employeeSalary->salaryStructure->phantom_equity_units,
                    ] : null,
                ] : null,
                'created_at' => $employee->created_at?->format('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            Log::error('Employee show failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Employee not found'
            ], 404);
        }
    }

    public function toggleStatus($id)
    {
        
        // 1. Check if user has permission to create employees
        if (!auth()->user()->can('edit employees')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update employees.'
            ]);
        }
        try {
            $employee = Employee::findOrFail($id);
            $employee->is_active = !$employee->is_active;
            $employee->save();

            if ($employee->employeeSalary) {
                $employee->employeeSalary->is_active = $employee->is_active;
                $employee->employeeSalary->save();
            }

            return response()->json([
                'success' => true,
                'message' => $employee->is_active ? 'Employee activated successfully' : 'Employee deactivated successfully',
                'is_active' => $employee->is_active
            ]);

        } catch (\Exception $e) {
            \Log::error('Toggle status failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle employee status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        
        if (!auth()->user()->can('delete employees')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete employees.'
            ]);
        }
        try {
            DB::transaction(function () use ($id) {
                $employee = Employee::withTrashed()->findOrFail($id);
                
                if ($employee->employeeSalary) {
                    $employee->employeeSalary->forceDelete();
                }
                
                $employee->forceDelete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Employee permanently deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Force delete employee failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete employee: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncWithUsers()
    {
        try {
            DB::transaction(function () {
                $users = User::whereDoesntHave('employee')
                    ->where('is_active', true)
                    ->get();

                $created = 0;
                foreach ($users as $user) {
                    if ($user->hasRole(['admin', 'hr', 'manager', 'staff'])) {
                        Employee::create([
                            'user_id' => $user->id,
                            'first_name' => $user->first_name ?? $user->name,
                            'last_name' => $user->last_name ?? '',
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'job_title' => 'Staff',
                            'employee_type' => 'full_time',
                            'hire_date' => now(),
                            'salary' => 0,
                            'salary_type' => 'fixed',
                            'is_salary_recurring' => true,
                            'is_active' => true,
                            'created_by' => auth()->id(),
                        ]);
                        $created++;
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => "Synced {$created} users to employees",
                    'created' => $created
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Employee sync failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync employees: ' . $e->getMessage()
            ], 500);
        }
    }
}