<?php

namespace App\Services\Compensation;

use App\Models\EmployeeSalary;
use App\Models\EmployeeBonus;
use App\Models\PhantomEquityTransaction;
use App\Models\PerformanceReview;
use App\Models\DepartmentProfitShare;
use App\Models\ProfitShareDistribution;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompensationService
{
    /**
     * Calculate performance bonus for an employee
     */
    public function calculatePerformanceBonus(EmployeeSalary $employeeSalary, PerformanceReview $review): array
    {
        $salaryStructure = $employeeSalary->salaryStructure;
        
        if (!$salaryStructure || !$review->bonus_eligible) {
            return ['eligible' => false, 'amount' => 0, 'message' => 'Not eligible for bonus'];
        }

        $maxBonus = $salaryStructure->performance_bonus_max ?? ($employeeSalary->base_salary * 0.20);
        $bonusPercentage = $salaryStructure->performance_bonus_percentage / 100;
        
        // Calculate bonus based on performance score
        $score = $review->score;
        $bonusAmount = 0;
        
        if ($score >= 95) {
            $bonusAmount = $maxBonus * 1.5;
        } elseif ($score >= 90) {
            $bonusAmount = $maxBonus * 1.25;
        } elseif ($score >= 80) {
            $bonusAmount = $maxBonus * 1.0;
        } elseif ($score >= 70) {
            $bonusAmount = $maxBonus * 0.75;
        } elseif ($score >= 60) {
            $bonusAmount = $maxBonus * 0.5;
        }

        // Ensure bonus doesn't exceed max
        $bonusAmount = min($bonusAmount, $maxBonus * 1.5);

        return [
            'eligible' => true,
            'amount' => (int) $bonusAmount,
            'percentage' => $bonusPercentage * 100,
            'score' => $score,
            'max_bonus' => $maxBonus,
        ];
    }

    /**
     * Calculate retention bonus
     */
    public function calculateRetentionBonus(EmployeeSalary $employeeSalary): array
    {
        $salaryStructure = $employeeSalary->salaryStructure;
        
        if (!$salaryStructure || !$salaryStructure->retention_bonus) {
            return ['eligible' => false, 'amount' => 0];
        }

        $hireDate = $employeeSalary->hire_date;
        $serviceMonths = $hireDate->diffInMonths(now());
        
        // Retention bonus eligibility: 6 months, 1 year, 2 years
        if ($serviceMonths >= 24) {
            $bonus = $salaryStructure->retention_bonus * 1.5;
        } elseif ($serviceMonths >= 12) {
            $bonus = $salaryStructure->retention_bonus;
        } elseif ($serviceMonths >= 6) {
            $bonus = $salaryStructure->retention_bonus * 0.5;
        } else {
            $bonus = 0;
        }

        return [
            'eligible' => $bonus > 0,
            'amount' => (int) $bonus,
            'service_months' => $serviceMonths,
        ];
    }

    /**
     * Calculate phantom equity vesting
     */
    public function calculateVesting(EmployeeSalary $employeeSalary): void
    {
        $serviceMonths = $employeeSalary->hire_date->diffInMonths(now());
        
        if ($serviceMonths < 12) {
            $percentage = 0;
        } elseif ($serviceMonths >= 12 && $serviceMonths < 24) {
            $percentage = 25;
        } elseif ($serviceMonths >= 24 && $serviceMonths < 36) {
            $percentage = 50;
        } elseif ($serviceMonths >= 36 && $serviceMonths < 48) {
            $percentage = 75;
        } else {
            $percentage = 100;
        }

        $previousPercentage = $employeeSalary->units_vested_percentage ?? 0;
        
        if ($percentage > $previousPercentage) {
            // New units vested
            $newVestedUnits = round(($employeeSalary->phantom_equity_units * $percentage) / 100);
            $previousVestedUnits = $employeeSalary->vested_units ?? 0;
            $vestedDifference = $newVestedUnits - $previousVestedUnits;

            if ($vestedDifference > 0) {
                // Create vesting transaction
                PhantomEquityTransaction::create([
                    'employee_salary_id' => $employeeSalary->id,
                    'user_id' => $employeeSalary->user_id,
                    'department_id' => $employeeSalary->department_id,
                    'transaction_type' => 'vesting',
                    'units' => $vestedDifference,
                    'vested_units' => $newVestedUnits,
                    'unit_value' => 0,
                    'total_value' => 0,
                    'performance_score' => null,
                    'performance_multiplier' => 1.0,
                    'description' => "Vesting at {$percentage}% after {$serviceMonths} months of service",
                    'transaction_date' => now(),
                    'is_vested' => true,
                    'created_by' => $employeeSalary->created_by,
                ]);

                // Update employee salary
                $employeeSalary->vested_units = $newVestedUnits;
                $employeeSalary->units_vested_percentage = $percentage;
                $employeeSalary->save();
            }
        }
    }

    /**
     * Calculate phantom equity allocation for a department
     */
    public function calculateDepartmentProfitShare($departmentId, $financialYear): array
    {
        // This would typically get total profit from accounting system
        // For now, we'll use a placeholder
        $totalProfit = 200000000; // UGX 200M - In production, get from accounting
        
        $department = Department::find($departmentId);
        if (!$department) {
            return ['success' => false, 'message' => 'Department not found'];
        }

        // Get all active employees in department
        $employeeSalaries = EmployeeSalary::where('department_id', $departmentId)
            ->where('is_active', true)
            ->get();

        $totalUnits = $employeeSalaries->sum('phantom_equity_units');
        
        // Calculate profit share
        $profitSharePercentage = 10; // 10% of profits
        $profitShareAmount = (int) ($totalProfit * ($profitSharePercentage / 100));
        
        // Calculate unit value
        $unitValue = $totalUnits > 0 ? (int) ($profitShareAmount / $totalUnits) : 0;

        // Create department profit share record
        $departmentProfitShare = DepartmentProfitShare::create([
            'department_id' => $departmentId,
            'financial_year' => $financialYear,
            'total_profit' => $totalProfit,
            'profit_share_percentage' => $profitSharePercentage,
            'profit_share_amount' => $profitShareAmount,
            'total_units' => $totalUnits,
            'unit_value' => $unitValue,
            'distribution_date' => now()->endOfYear(),
            'status' => 'calculated',
            'created_by' => auth()->id() ?? 1,
        ]);

        // Create distributions for each employee
        foreach ($employeeSalaries as $employeeSalary) {
            $this->createProfitShareDistribution($departmentProfitShare, $employeeSalary);
        }

        return [
            'success' => true,
            'department_profit_share' => $departmentProfitShare,
            'total_profit' => $totalProfit,
            'profit_share_amount' => $profitShareAmount,
            'total_units' => $totalUnits,
            'unit_value' => $unitValue,
        ];
    }

    /**
     * Create profit share distribution for an employee
     */
    private function createProfitShareDistribution($departmentProfitShare, $employeeSalary): void
    {
        $unitsHeld = $employeeSalary->phantom_equity_units;
        $vestedUnits = $employeeSalary->vested_units;
        $unitValue = $departmentProfitShare->unit_value;
        
        // Only distribute based on vested units
        $totalAmount = $vestedUnits * $unitValue;

        if ($totalAmount > 0) {
            ProfitShareDistribution::create([
                'department_profit_share_id' => $departmentProfitShare->id,
                'employee_salary_id' => $employeeSalary->id,
                'user_id' => $employeeSalary->user_id,
                'department_id' => $employeeSalary->department_id,
                'units_held' => $unitsHeld,
                'vested_units' => $vestedUnits,
                'unit_value' => $unitValue,
                'total_amount' => $totalAmount,
                'distribution_date' => now(),
                'status' => 'pending',
                'reference' => 'PFS-' . date('Ymd') . '-' . $employeeSalary->user_id,
                'notes' => "Profit share distribution for {$departmentProfitShare->financial_year}",
            ]);
        }
    }

    /**
     * Process extraordinary contribution award
     */
    public function awardExtraordinaryContribution(
        int $employeeId, 
        string $description, 
        int $units = 0, 
        float $amount = 0
    ): array {
        $employeeSalary = EmployeeSalary::where('employee_id', $employeeId)->first();
        
        if (!$employeeSalary) {
            return ['success' => false, 'message' => 'Employee salary record not found'];
        }

        $maxUnits = 200; // Maximum extraordinary award per year
        $totalAwardedThisYear = PhantomEquityTransaction::where('employee_salary_id', $employeeSalary->id)
            ->where('transaction_type', 'award')
            ->whereYear('created_at', date('Y'))
            ->sum('units');

        if ($totalAwardedThisYear + $units > $maxUnits) {
            return [
                'success' => false, 
                'message' => "Cannot award more than {$maxUnits} units per year. Already awarded: {$totalAwardedThisYear}"
            ];
        }

        // Create award transaction
        $transaction = PhantomEquityTransaction::create([
            'employee_salary_id' => $employeeSalary->id,
            'user_id' => $employeeSalary->user_id,
            'department_id' => $employeeSalary->department_id,
            'transaction_type' => 'award',
            'units' => $units,
            'vested_units' => $units, // Extraordinary awards vest immediately
            'unit_value' => 0, // Will be calculated at payout time
            'total_value' => 0,
            'performance_score' => null,
            'performance_multiplier' => 1.0,
            'description' => $description,
            'reference' => 'EXT-' . date('Ymd') . '-' . uniqid(),
            'transaction_date' => now(),
            'is_vested' => true,
            'created_by' => auth()->id() ?? 1,
        ]);

        // Update employee's phantom equity units
        $employeeSalary->phantom_equity_units += $units;
        $employeeSalary->vested_units += $units;
        $employeeSalary->save();

        return [
            'success' => true,
            'transaction' => $transaction,
            'message' => 'Extraordinary award granted successfully',
        ];
    }

    /**
     * Calculate bonus for Business Development Officer (commission)
     */
    public function calculateBDOCommission($employeeId, float $revenueAmount, float $percentage = 5): array
    {
        $employeeSalary = EmployeeSalary::where('employee_id', $employeeId)->first();
        
        if (!$employeeSalary) {
            return ['success' => false, 'message' => 'Employee salary record not found'];
        }

        $salaryStructure = $employeeSalary->salaryStructure;
        $commissionRate = $salaryStructure?->commission_rate ?? $percentage;
        
        $commissionAmount = $revenueAmount * ($commissionRate / 100);

        // Create bonus record
        EmployeeBonus::create([
            'employee_salary_id' => $employeeSalary->id,
            'user_id' => $employeeSalary->user_id,
            'department_id' => $employeeSalary->department_id,
            'bonus_type' => 'commission',
            'bonus_category' => 'one_time',
            'amount' => (int) round($commissionAmount),
            'percentage_of_salary' => $commissionRate,
            'target_achieved' => $revenueAmount,
            'target_metric' => 'revenue_closed',
            'description' => "Commission on revenue of UGX " . number_format($revenueAmount),
            'reference' => 'COM-' . date('Ymd') . '-' . uniqid(),
            'bonus_date' => now(),
            'is_paid' => false,
            'created_by' => auth()->id() ?? 1,
        ]);

        return [
            'success' => true,
            'commission_amount' => (int) round($commissionAmount),
            'rate' => $commissionRate,
            'revenue' => $revenueAmount,
        ];
    }

    /**
     * Calculate recruitment bonus
     */
    public function calculateRecruitmentBonus($employeeId, $placementAmount, $percentage = 10): array
    {
        $employeeSalary = EmployeeSalary::where('employee_id', $employeeId)->first();
        
        if (!$employeeSalary) {
            return ['success' => false, 'message' => 'Employee salary record not found'];
        }

        $bonusAmount = $placementAmount * ($percentage / 100);

        EmployeeBonus::create([
            'employee_salary_id' => $employeeSalary->id,
            'user_id' => $employeeSalary->user_id,
            'department_id' => $employeeSalary->department_id,
            'bonus_type' => 'placement',
            'bonus_category' => 'one_time',
            'amount' => (int) round($bonusAmount),
            'percentage_of_salary' => $percentage,
            'target_achieved' => $placementAmount,
            'target_metric' => 'placement_revenue',
            'description' => "Recruitment placement bonus",
            'reference' => 'REC-' . date('Ymd') . '-' . uniqid(),
            'bonus_date' => now(),
            'is_paid' => false,
            'created_by' => auth()->id() ?? 1,
        ]);

        return [
            'success' => true,
            'bonus_amount' => (int) round($bonusAmount),
            'percentage' => $percentage,
        ];
    }

    /**
     * Calculate HR recruitment bonus
     */
    public function calculateHRRecruitmentBonus($employeeId, $staffLevel, $salary): array
    {
        $employeeSalary = EmployeeSalary::where('employee_id', $employeeId)->first();
        
        if (!$employeeSalary) {
            return ['success' => false, 'message' => 'Employee salary record not found'];
        }

        $bonusAmount = 0;
        $bonusMap = [
            'junior' => 50000,
            'officer' => 100000,
            'senior' => 200000,
        ];

        if (isset($bonusMap[$staffLevel])) {
            $bonusAmount = $bonusMap[$staffLevel];
        }

        if ($bonusAmount > 0) {
            EmployeeBonus::create([
                'employee_salary_id' => $employeeSalary->id,
                'user_id' => $employeeSalary->user_id,
                'department_id' => $employeeSalary->department_id,
                'bonus_type' => 'recruitment',
                'bonus_category' => 'one_time',
                'amount' => $bonusAmount,
                'percentage_of_salary' => null,
                'target_achieved' => $salary,
                'target_metric' => 'recruitment_salary',
                'description' => "HR recruitment bonus for {$staffLevel} level staff",
                'reference' => 'HRREC-' . date('Ymd') . '-' . uniqid(),
                'bonus_date' => now(),
                'is_paid' => false,
                'created_by' => auth()->id() ?? 1,
            ]);
        }

        return [
            'success' => true,
            'bonus_amount' => $bonusAmount,
            'level' => $staffLevel,
        ];
    }

    /**
     * Calculate automation bonus for Systems Administrator
     */
    public function calculateAutomationBonus($employeeId, $monthlySavings, $percentage = 10): array
    {
        $employeeSalary = EmployeeSalary::where('employee_id', $employeeId)->first();
        
        if (!$employeeSalary) {
            return ['success' => false, 'message' => 'Employee salary record not found'];
        }

        $bonusAmount = $monthlySavings * ($percentage / 100);

        EmployeeBonus::create([
            'employee_salary_id' => $employeeSalary->id,
            'user_id' => $employeeSalary->user_id,
            'department_id' => $employeeSalary->department_id,
            'bonus_type' => 'automation',
            'bonus_category' => 'monthly',
            'amount' => (int) round($bonusAmount),
            'percentage_of_salary' => $percentage,
            'target_achieved' => $monthlySavings,
            'target_metric' => 'cost_savings',
            'description' => "Automation savings bonus",
            'reference' => 'AUTO-' . date('Ymd') . '-' . uniqid(),
            'bonus_date' => now(),
            'is_paid' => false,
            'created_by' => auth()->id() ?? 1,
        ]);

        return [
            'success' => true,
            'bonus_amount' => (int) round($bonusAmount),
            'percentage' => $percentage,
            'monthly_savings' => $monthlySavings,
        ];
    }

    /**
     * Calculate casual workers management bonus
     */
    public function calculateCasualManagementBonus($employeeId, $workersCount): array
    {
        $employeeSalary = EmployeeSalary::where('employee_id', $employeeId)->first();
        
        if (!$employeeSalary) {
            return ['success' => false, 'message' => 'Employee salary record not found'];
        }

        $bonusAmount = 0;
        if ($workersCount >= 200) {
            $bonusAmount = 700000;
        } elseif ($workersCount >= 100) {
            $bonusAmount = 350000;
        } elseif ($workersCount >= 50) {
            $bonusAmount = 150000;
        }

        if ($bonusAmount > 0) {
            EmployeeBonus::create([
                'employee_salary_id' => $employeeSalary->id,
                'user_id' => $employeeSalary->user_id,
                'department_id' => $employeeSalary->department_id,
                'bonus_type' => 'management',
                'bonus_category' => 'monthly',
                'amount' => $bonusAmount,
                'percentage_of_salary' => null,
                'target_achieved' => $workersCount,
                'target_metric' => 'workers_managed',
                'description' => "Casual workers management bonus for {$workersCount} workers",
                'reference' => 'CMW-' . date('Ymd') . '-' . uniqid(),
                'bonus_date' => now(),
                'is_paid' => false,
                'created_by' => auth()->id() ?? 1,
            ]);
        }

        return [
            'success' => true,
            'bonus_amount' => $bonusAmount,
            'workers_count' => $workersCount,
        ];
    }
}