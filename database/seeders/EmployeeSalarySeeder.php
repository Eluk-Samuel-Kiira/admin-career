<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmployeeSalary;
use App\Models\Employee;
use App\Models\User;
use App\Models\Department;
use App\Models\SalaryStructure;

class EmployeeSalarySeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', 'superadmin@lafab.com')->first() ?? User::first();

        // Get all employees (non-job_seeker, non-employer)
        $employees = Employee::where('employee_type', '!=', 'job_seeker')
            ->where('employee_type', '!=', 'employer')
            ->get();

        foreach ($employees as $employee) {
            // Determine salary structure based on role
            $salaryStructure = SalaryStructure::where('role_code', $employee->job_title)
                ->orWhere('job_title', $employee->job_title)
                ->first();

            if (!$salaryStructure) {
                // Try to find by department
                $salaryStructure = SalaryStructure::where('department_id', $employee->department_id)
                    ->first();
            }

            EmployeeSalary::create([
                'employee_id' => $employee->id,
                'user_id' => $employee->user_id,
                'department_id' => $employee->department_id,
                'salary_structure_id' => $salaryStructure?->id,
                'base_salary' => $employee->salary ?? $salaryStructure?->base_salary ?? 0,
                'salary_type' => $salaryStructure?->salary_type ?? 'fixed',
                'is_recurring' => $employee->is_salary_recurring ?? true,
                'recurring_day' => $employee->recurring_day ?? 1,
                'hire_date' => $employee->hire_date ?? now(),
                'termination_date' => $employee->termination_date,
                'phantom_equity_units' => $salaryStructure?->phantom_equity_units ?? 0,
                'vested_units' => 0,
                'units_vested_percentage' => 0,
                'current_balance' => 0,
                'is_active' => $employee->is_active ?? true,
                'created_by' => $adminUser->id,
            ]);
        }

        $this->command->info('Employee salaries seeded successfully!');
    }
}