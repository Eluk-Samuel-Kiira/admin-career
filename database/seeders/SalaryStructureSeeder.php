<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalaryStructure;
use App\Models\Department;
use App\Models\Currency;
use App\Models\User;

class SalaryStructureSeeder extends Seeder
{
    public function run(): void
    {
        $currency = Currency::where('code', 'UGX')->first() ?? Currency::first();
        $adminUser = User::where('email', 'superadmin@lafab.com')->first() ?? User::first();

        $structures = [
            // Leadership Layer
            [
                'job_title' => 'Chief Executive Officer',
                'role_code' => 'CEO',
                'base_salary' => 3000000,
                'salary_type' => 'fixed',
                'performance_bonus_percentage' => 20,
                'performance_bonus_max' => 600000,
                'phantom_equity_units' => 500,
                'profit_share_percentage' => 15,
                'commission_rate' => 0,
                'retention_bonus' => 1000000,
                'min_salary' => 3000000,
                'max_salary' => 5000000,
            ],
            [
                'job_title' => 'Operations Officer',
                'role_code' => 'OO',
                'base_salary' => 600000,
                'salary_type' => 'fixed',
                'performance_bonus_percentage' => 15,
                'performance_bonus_max' => 400000,
                'phantom_equity_units' => 100,
                'profit_share_percentage' => 10,
                'commission_rate' => 0,
                'retention_bonus' => 500000,
                'min_salary' => 600000,
                'max_salary' => 1000000,
            ],
            [
                'job_title' => 'Systems Administrator',
                'role_code' => 'SA',
                'base_salary' => 650000,
                'salary_type' => 'fixed',
                'performance_bonus_percentage' => 15,
                'performance_bonus_max' => 500000,
                'phantom_equity_units' => 100,
                'profit_share_percentage' => 10,
                'commission_rate' => 0,
                'retention_bonus' => 500000,
                'min_salary' => 650000,
                'max_salary' => 1200000,
            ],
            [
                'job_title' => 'Human Resource Officer',
                'role_code' => 'HRO',
                'base_salary' => 700000,
                'salary_type' => 'fixed',
                'performance_bonus_percentage' => 15,
                'performance_bonus_max' => 400000,
                'phantom_equity_units' => 100,
                'profit_share_percentage' => 10,
                'commission_rate' => 0,
                'retention_bonus' => 500000,
                'min_salary' => 700000,
                'max_salary' => 1200000,
            ],
            // Revenue & Operations Layer
            [
                'job_title' => 'Business Development Officer',
                'role_code' => 'BDO',
                'base_salary' => 500000,
                'salary_type' => 'commission',
                'performance_bonus_percentage' => 20,
                'performance_bonus_max' => 500000,
                'phantom_equity_units' => 100,
                'profit_share_percentage' => 10,
                'commission_rate' => 5,
                'retention_bonus' => 300000,
                'min_salary' => 500000,
                'max_salary' => 2000000,
            ],
            [
                'job_title' => 'Recruitment Officer',
                'role_code' => 'RO',
                'base_salary' => 500000,
                'salary_type' => 'commission',
                'performance_bonus_percentage' => 15,
                'performance_bonus_max' => 500000,
                'phantom_equity_units' => 80,
                'profit_share_percentage' => 8,
                'commission_rate' => 10,
                'retention_bonus' => 200000,
                'min_salary' => 500000,
                'max_salary' => 1500000,
            ],
            [
                'job_title' => 'Casual Management Officer',
                'role_code' => 'CMO',
                'base_salary' => 450000,
                'salary_type' => 'fixed',
                'performance_bonus_percentage' => 15,
                'performance_bonus_max' => 400000,
                'phantom_equity_units' => 80,
                'profit_share_percentage' => 8,
                'commission_rate' => 0,
                'retention_bonus' => 200000,
                'min_salary' => 450000,
                'max_salary' => 1000000,
            ],
            [
                'job_title' => 'Maids Officer',
                'role_code' => 'MO',
                'base_salary' => 400000,
                'salary_type' => 'fixed',
                'performance_bonus_percentage' => 15,
                'performance_bonus_max' => 300000,
                'phantom_equity_units' => 80,
                'profit_share_percentage' => 8,
                'commission_rate' => 0,
                'retention_bonus' => 150000,
                'min_salary' => 400000,
                'max_salary' => 800000,
            ],
        ];

        foreach ($structures as $structure) {
            // Get department if applicable
            $department = null;
            $departmentMap = [
                'CEO' => null,
                'OO' => Department::where('code', 'OPS')->first(),
                'SA' => Department::where('code', 'IT')->first(),
                'HRO' => Department::where('code', 'HR')->first(),
                'BDO' => Department::where('code', 'BD')->first(),
                'RO' => Department::where('code', 'BD')->first(),
                'CMO' => Department::where('code', 'OPS')->first(),
                'MO' => Department::where('code', 'OPS')->first(),
            ];

            $department = $departmentMap[$structure['role_code']] ?? null;

            SalaryStructure::create([
                'department_id' => $department?->id,
                'job_title' => $structure['job_title'],
                'role_code' => $structure['role_code'],
                'base_salary' => $structure['base_salary'],
                'salary_type' => $structure['salary_type'],
                'performance_bonus_percentage' => $structure['performance_bonus_percentage'],
                'performance_bonus_max' => $structure['performance_bonus_max'],
                'phantom_equity_units' => $structure['phantom_equity_units'],
                'profit_share_percentage' => $structure['profit_share_percentage'],
                'commission_rate' => $structure['commission_rate'],
                'retention_bonus' => $structure['retention_bonus'],
                'min_salary' => $structure['min_salary'],
                'max_salary' => $structure['max_salary'],
                'currency_id' => $currency->id,
                'is_active' => true,
                'created_by' => $adminUser->id,
            ]);
        }

        $this->command->info('Salary structures seeded successfully!');
    }
}