<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;
use App\Models\Expense;
use App\Models\Department;
use App\Models\User;
use App\Models\PaymentMethod;
use Illuminate\Support\Str;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        // First, create the categories
        $this->command->info('Creating expense categories...');
        $this->createExpenseCategories();
        
        // Then create the expenses
        $this->command->info('Creating expenses...');
        $this->createExpenses();
    }

    private function createExpenseCategories(): void
    {
        $categories = [
            [
                'name' => 'Office Supplies',
                'code' => 'OFS',
                'description' => 'Office stationery, printer ink, paper, etc.',
                'requires_receipt' => true,
                'requires_approval' => false,
                'budget_monthly' => 500000,
                'budget_annual' => 6000000,
                'is_active' => true,
            ],
            [
                'name' => 'Utilities',
                'code' => 'UTL',
                'description' => 'Electricity, water, internet, phone bills',
                'requires_receipt' => true,
                'requires_approval' => false,
                'budget_monthly' => 2000000,
                'budget_annual' => 24000000,
                'is_active' => true,
            ],
            [
                'name' => 'Rent',
                'code' => 'RNT',
                'description' => 'Office rent and related costs',
                'requires_receipt' => true,
                'requires_approval' => false,
                'budget_monthly' => 5000000,
                'budget_annual' => 60000000,
                'is_active' => true,
            ],
            [
                'name' => 'Salaries & Wages',
                'code' => 'SAL',
                'description' => 'Employee salaries, wages, and benefits',
                'requires_receipt' => false,
                'requires_approval' => true,
                'budget_monthly' => 50000000,
                'budget_annual' => 600000000,
                'is_active' => true,
            ],
            [
                'name' => 'Marketing & Advertising',
                'code' => 'MKT',
                'description' => 'Marketing campaigns, ads, promotions',
                'requires_receipt' => true,
                'requires_approval' => true,
                'budget_monthly' => 3000000,
                'budget_annual' => 36000000,
                'is_active' => true,
            ],
            [
                'name' => 'Travel & Entertainment',
                'code' => 'TRV',
                'description' => 'Business travel, meals, client entertainment',
                'requires_receipt' => true,
                'requires_approval' => true,
                'budget_monthly' => 1500000,
                'budget_annual' => 18000000,
                'is_active' => true,
            ],
            [
                'name' => 'IT Equipment & Software',
                'code' => 'ITSW',
                'description' => 'Computers, software licenses, IT equipment',
                'requires_receipt' => true,
                'requires_approval' => true,
                'budget_monthly' => 2000000,
                'budget_annual' => 24000000,
                'is_active' => true,
            ],
            [
                'name' => 'Training & Development',
                'code' => 'TRN',
                'description' => 'Employee training, workshops, certifications',
                'requires_receipt' => true,
                'requires_approval' => true,
                'budget_monthly' => 1000000,
                'budget_annual' => 12000000,
                'is_active' => true,
            ],
            [
                'name' => 'Maintenance & Repairs',
                'code' => 'MNT',
                'description' => 'Office maintenance, repairs, equipment servicing',
                'requires_receipt' => true,
                'requires_approval' => false,
                'budget_monthly' => 500000,
                'budget_annual' => 6000000,
                'is_active' => true,
            ],
            [
                'name' => 'Insurance',
                'code' => 'INS',
                'description' => 'Business insurance policies',
                'requires_receipt' => true,
                'requires_approval' => false,
                'budget_monthly' => 1000000,
                'budget_annual' => 12000000,
                'is_active' => true,
            ],
            [
                'name' => 'Recruitment',
                'code' => 'REC',
                'description' => 'Recruitment agency fees, job postings, background checks',
                'requires_receipt' => true,
                'requires_approval' => true,
                'budget_monthly' => 500000,
                'budget_annual' => 6000000,
                'is_active' => true,
            ],
            [
                'name' => 'Staff Welfare',
                'code' => 'WLF',
                'description' => 'Staff meals, team building, staff gifts',
                'requires_receipt' => true,
                'requires_approval' => false,
                'budget_monthly' => 300000,
                'budget_annual' => 3600000,
                'is_active' => true,
            ],
            [
                'name' => 'Bank Charges',
                'code' => 'BNK',
                'description' => 'Bank fees, transaction charges, interest',
                'requires_receipt' => false,
                'requires_approval' => false,
                'budget_monthly' => 100000,
                'budget_annual' => 1200000,
                'is_active' => true,
            ],
            [
                'name' => 'Professional Services',
                'code' => 'PRF',
                'description' => 'Legal fees, accounting, consultancy services',
                'requires_receipt' => true,
                'requires_approval' => true,
                'budget_monthly' => 1000000,
                'budget_annual' => 12000000,
                'is_active' => true,
            ],
            [
                'name' => 'Other Expenses',
                'code' => 'OTH',
                'description' => 'Miscellaneous business expenses',
                'requires_receipt' => true,
                'requires_approval' => true,
                'budget_monthly' => 500000,
                'budget_annual' => 6000000,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $catData) {
            // Check if category already exists
            $existing = ExpenseCategory::where('code', $catData['code'])->first();
            if (!$existing) {
                ExpenseCategory::create($catData);
                $this->command->info("Created category: {$catData['name']} ({$catData['code']})");
            } else {
                $this->command->info("Category already exists: {$catData['name']} ({$catData['code']})");
            }
        }

        $this->command->info('Expense categories seeded successfully!');
    }

    private function createExpenses(): void
    {
        // Get all departments
        $itDept = Department::where('code', 'IT')->first();
        $hrDept = Department::where('code', 'HR')->first();
        $opsDept = Department::where('code', 'OPS')->first();
        $bdDept = Department::where('code', 'BD')->first();
        $finDept = Department::where('code', 'FIN')->first();
        
        // Get users
        $adminUser = User::where('email', 'superadmin@lafab.com')->first() ?? User::first();
        $john = User::where('email', 'john.doe@lafab.com')->first();
        $jane = User::where('email', 'jane.smith@lafab.com')->first();
        
        // Get payment methods
        $bankMethod = PaymentMethod::where('code', 'STANBIC_UGX')->first();
        $cashMethod = PaymentMethod::where('code', 'CASH_HO')->first();

        // Get all categories - ensure we have them
        $categories = ExpenseCategory::all();
        
        if ($categories->isEmpty()) {
            $this->command->error('No expense categories found! Please run the category seeder first.');
            return;
        }

        // Helper function to get category ID
        $getCategoryId = function($code) use ($categories) {
            $category = $categories->where('code', $code)->first();
            if (!$category) {
                $this->command->warn("Category with code '{$code}' not found! Skipping expense.");
                return null;
            }
            return $category->id;
        };

        // Sample Expenses
        $expenses = [
            [
                'expense_number' => 'EXP-2024-001',
                'category_code' => 'OFS',
                'department_id' => $itDept?->id,
                'employee_id' => $john?->id,
                'created_by' => $adminUser?->id,
                'vendor_name' => 'Office Supplies Ltd',
                'vendor_contact' => '+256712345678',
                'vendor_email' => 'info@officesupplies.com',
                'date' => now()->subDays(5),
                'description' => 'Monthly office stationery order',
                'gross_amount' => 250000,
                'tax_amount' => 45000,
                'net_amount' => 205000,
                'total_amount' => 250000,
                'payment_status' => 'paid',
                'payment_method_id' => $bankMethod?->id,
                'paid_date' => now()->subDays(4),
                'receipt_number' => 'INV-2024-001',
                'is_recurring' => true,
                'recurring_frequency' => 'monthly',
                'next_recurring_date' => now()->addMonth(),
            ],
            [
                'expense_number' => 'EXP-2024-002',
                'category_code' => 'UTL',
                'department_id' => $opsDept?->id,
                'employee_id' => $jane?->id,
                'created_by' => $adminUser?->id,
                'vendor_name' => 'Uganda Electricity Board',
                'date' => now()->subDays(10),
                'description' => 'Monthly electricity bill',
                'gross_amount' => 800000,
                'tax_amount' => 0,
                'net_amount' => 800000,
                'total_amount' => 800000,
                'payment_status' => 'pending',
                'payment_method_id' => null,
                'receipt_number' => 'ELEC-2024-005',
                'is_recurring' => true,
                'recurring_frequency' => 'monthly',
                'next_recurring_date' => now()->addMonth(),
            ],
            [
                'expense_number' => 'EXP-2024-003',
                'category_code' => 'MKT',
                'department_id' => $bdDept?->id,
                'employee_id' => $adminUser?->id,
                'created_by' => $adminUser?->id,
                'vendor_name' => 'Google Ads',
                'date' => now()->subDays(3),
                'description' => 'Google Ads campaign - Q2 2024',
                'gross_amount' => 1500000,
                'tax_amount' => 270000,
                'net_amount' => 1230000,
                'total_amount' => 1500000,
                'payment_status' => 'approved',
                'payment_method_id' => $cashMethod?->id,
                'receipt_number' => 'GA-2024-042',
                'is_recurring' => false,
            ],
            [
                'expense_number' => 'EXP-2024-004',
                'category_code' => 'ITSW',
                'department_id' => $itDept?->id,
                'employee_id' => $john?->id,
                'created_by' => $adminUser?->id,
                'vendor_name' => 'Tech Store Uganda',
                'date' => now()->subDays(7),
                'description' => 'New laptop for IT team member',
                'gross_amount' => 3500000,
                'tax_amount' => 630000,
                'net_amount' => 2870000,
                'total_amount' => 3500000,
                'payment_status' => 'paid',
                'payment_method_id' => $bankMethod?->id,
                'paid_date' => now()->subDays(6),
                'receipt_number' => 'TS-2024-089',
                'is_recurring' => false,
            ],
            [
                'expense_number' => 'EXP-2024-005',
                'category_code' => 'SAL',
                'department_id' => $hrDept?->id,
                'employee_id' => $adminUser?->id,
                'created_by' => $adminUser?->id,
                'vendor_name' => 'Payroll Services',
                'date' => now()->subDays(15),
                'description' => 'Staff salaries - June 2024',
                'gross_amount' => 25000000,
                'tax_amount' => 4500000,
                'net_amount' => 20500000,
                'total_amount' => 25000000,
                'payment_status' => 'paid',
                'payment_method_id' => $bankMethod?->id,
                'paid_date' => now()->subDays(14),
                'receipt_number' => 'PAY-2024-006',
                'is_recurring' => true,
                'recurring_frequency' => 'monthly',
                'next_recurring_date' => now()->addMonth(),
            ],
            [
                'expense_number' => 'EXP-2024-006',
                'category_code' => 'RNT',
                'department_id' => $opsDept?->id,
                'employee_id' => $adminUser?->id,
                'created_by' => $adminUser?->id,
                'vendor_name' => 'Premier Properties',
                'date' => now()->subDays(8),
                'description' => 'Monthly office rent',
                'gross_amount' => 5000000,
                'tax_amount' => 0,
                'net_amount' => 5000000,
                'total_amount' => 5000000,
                'payment_status' => 'pending',
                'payment_method_id' => null,
                'receipt_number' => 'RENT-2024-006',
                'is_recurring' => true,
                'recurring_frequency' => 'monthly',
                'next_recurring_date' => now()->addMonth(),
            ],
            [
                'expense_number' => 'EXP-2024-007',
                'category_code' => 'TRN',
                'department_id' => $hrDept?->id,
                'employee_id' => $jane?->id,
                'created_by' => $adminUser?->id,
                'vendor_name' => 'Lagos Training Institute',
                'date' => now()->subDays(12),
                'description' => 'Staff training on new HR software',
                'gross_amount' => 1200000,
                'tax_amount' => 216000,
                'net_amount' => 984000,
                'total_amount' => 1200000,
                'payment_status' => 'approved',
                'payment_method_id' => $bankMethod?->id,
                'receipt_number' => 'TRN-2024-023',
                'is_recurring' => false,
            ],
            [
                'expense_number' => 'EXP-2024-008',
                'category_code' => 'WLF',
                'department_id' => $hrDept?->id,
                'employee_id' => $jane?->id,
                'created_by' => $adminUser?->id,
                'vendor_name' => 'Catering Services',
                'date' => now()->subDays(2),
                'description' => 'Staff team building lunch',
                'gross_amount' => 300000,
                'tax_amount' => 54000,
                'net_amount' => 246000,
                'total_amount' => 300000,
                'payment_status' => 'pending',
                'payment_method_id' => null,
                'receipt_number' => 'CATER-2024-012',
                'is_recurring' => false,
            ],
            [
                'expense_number' => 'EXP-2024-009',
                'category_code' => 'REC',
                'department_id' => $hrDept?->id,
                'employee_id' => $john?->id,
                'created_by' => $adminUser?->id,
                'vendor_name' => 'Recruitment Agency Ltd',
                'date' => now()->subDays(20),
                'description' => 'Recruitment fee for Senior Developer',
                'gross_amount' => 2500000,
                'tax_amount' => 450000,
                'net_amount' => 2050000,
                'total_amount' => 2500000,
                'payment_status' => 'paid',
                'payment_method_id' => $bankMethod?->id,
                'paid_date' => now()->subDays(19),
                'receipt_number' => 'REC-2024-015',
                'is_recurring' => false,
            ],
            [
                'expense_number' => 'EXP-2024-010',
                'category_code' => 'PRF',
                'department_id' => $finDept?->id,
                'employee_id' => $adminUser?->id,
                'created_by' => $adminUser?->id,
                'vendor_name' => 'Accounting Solutions Ltd',
                'date' => now()->subDays(6),
                'description' => 'Quarterly accounting services',
                'gross_amount' => 2000000,
                'tax_amount' => 360000,
                'net_amount' => 1640000,
                'total_amount' => 2000000,
                'payment_status' => 'approved',
                'payment_method_id' => null,
                'receipt_number' => 'ACC-2024-008',
                'is_recurring' => true,
                'recurring_frequency' => 'quarterly',
                'next_recurring_date' => now()->addMonths(3),
            ],
        ];

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($expenses as $expData) {
            // Get category ID from code
            $categoryId = $getCategoryId($expData['category_code']);
            
            // Skip if category not found
            if (!$categoryId) {
                $skippedCount++;
                continue;
            }

            // Remove category_code and add category_id
            unset($expData['category_code']);
            $expData['category_id'] = $categoryId;

            try {
                Expense::create($expData);
                $createdCount++;
                $this->command->info("Created expense: {$expData['expense_number']}");
            } catch (\Exception $e) {
                $skippedCount++;
                $this->command->error("Failed to create expense {$expData['expense_number']}: " . $e->getMessage());
            }
        }

        $this->command->info("Expenses seeded successfully! Created: {$createdCount}, Skipped: {$skippedCount}");
    }
}