<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DepartmentProfitShare;
use App\Models\Department;

class DepartmentProfitShareSeeder extends Seeder
{
    public function run()
    {
        $departments = Department::where('is_active', true)->get();

        foreach ($departments as $department) {
            DepartmentProfitShare::create([
                'department_id' => $department->id,
                'financial_year' => '2024',
                'total_profit' => 200000000,
                'profit_share_percentage' => 10,
                'profit_share_amount' => 20000000,
                'total_units' => 1000,
                'unit_value' => 20000,
                'status' => 'calculated',
                'created_by' => 1,
            ]);
        }
    }
}