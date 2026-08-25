<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Get all permissions once for super_admin
        $allPermissions = Permission::all()->pluck('name')->toArray();

        // Define roles and their permissions
        $roles = [
            'super_admin' => [
                'description' => 'Full system access with all permissions',
                'permissions' => $allPermissions // All permissions
            ],
            'admin' => [
                'description' => 'Administrative access without system settings',
                'permissions' => [
                    // User Management
                    'view users', 'create users', 'edit users', 'delete users',
                    
                    // Role Management
                    'view roles', 'create roles', 'edit roles',
                    
                    // Job Management
                    'view jobs', 'create jobs', 'edit jobs', 'delete jobs', 
                    'approve jobs', 'publish jobs', 'archive jobs', 'feature jobs', 'mark jobs urgent',
                    
                    // Job Applications
                    'view applications', 'review applications', 'shortlist candidates', 
                    'reject applications', 'schedule interviews', 'conduct interviews', 'rate candidates',
                    
                    // Candidates
                    'view candidates', 'create candidates', 'edit candidates', 'delete candidates',
                    'view talent pool', 'add to talent pool',
                    
                    // Clients
                    'view clients', 'create clients', 'edit clients', 'delete clients',
                    'manage client contracts',
                    
                    // Dashboard & Reports
                    'view dashboard', 'view reports', 'export reports', 'view analytics',
                    'view revenue dashboard', 'view expense dashboard',
                    
                    // Finance
                    'view revenue', 'create revenue', 'edit revenue', 'delete revenue', 'approve revenue',
                    'view expenses', 'create expenses', 'edit expenses', 'delete expenses', 'approve expenses',
                    'view deposits', 'create deposits', 'edit deposits', 'delete deposits', 'approve deposits',
                    'view financial reports', 'export financial reports',
                    'view salaries', 'view salary structure',
                    'view bonuses', 'view payment methods',
                    
                    // HR
                    'view employees', 'create employees', 'edit employees', 'view employee details',
                    'view departments',
                    'view performance reviews', 'view attendance',
                    
                    // Support
                    'view tickets', 'respond tickets', 'assign tickets',
                    
                    // Recruitment
                    'manage job postings', 'manage interviews', 'send offers', 'manage onboarding',
                ]
            ],
            'supervisor' => [
                'description' => 'Supervisory access to monitor and manage team activities',
                'permissions' => [
                    // User Management
                    'view users',
                    
                    // Job Management
                    'view jobs', 'create jobs', 'edit jobs', 'approve jobs', 'publish jobs', 'archive jobs',
                    
                    // Job Applications
                    'view applications', 'review applications', 'shortlist candidates', 
                    'reject applications', 'schedule interviews', 'conduct interviews',
                    
                    // Candidates
                    'view candidates', 'create candidates', 'edit candidates',
                    'view talent pool',
                    
                    // Clients
                    'view clients', 'create clients', 'edit clients',
                    
                    // Dashboard & Reports
                    'view dashboard', 'view reports', 'view analytics',
                    'view revenue dashboard', 'view expense dashboard',
                    
                    // Finance
                    'view revenue', 'view expenses', 'view deposits',
                    'view financial reports',
                    'view salaries', 'view bonuses',
                    
                    // HR
                    'view employees', 'view employee details',
                    'view performance reviews', 'view attendance',
                    
                    // Support
                    'view tickets', 'respond tickets',
                ]
            ],
            'moderator' => [
                'description' => 'Content moderation and basic management',
                'permissions' => [
                    // Job Management
                    'view jobs', 'edit jobs', 'archive jobs',
                    
                    // Job Applications
                    'view applications', 'review applications', 'shortlist candidates', 'reject applications',
                    
                    // Candidates
                    'view candidates', 'edit candidates',
                    'view talent pool',
                    
                    // Clients
                    'view clients',
                    
                    // Dashboard
                    'view dashboard',
                    
                    // Support
                    'view tickets',
                    
                    // Finance
                    'view revenue', 'view expenses',
                    'view financial reports',
                ]
            ],
            'job_poster' => [
                'description' => 'Can post and manage their own jobs only',
                'permissions' => [
                    // Job Management
                    'view jobs', 'create jobs', 'edit jobs', 'delete jobs',
                    
                    // Job Applications
                    'view applications', 'review applications',
                    
                    // Candidates
                    'view candidates',
                    
                    // Dashboard
                    'view dashboard',
                ]
            ],
            // Additional role for finance team
            'finance_manager' => [
                'description' => 'Full finance access including salary, bonus, and financial reporting',
                'permissions' => [
                    // Revenue
                    'view revenue', 'create revenue', 'edit revenue', 'delete revenue', 
                    'approve revenue', 'reconcile revenue', 'export revenue reports',
                    'view revenue dashboard', 'generate revenue forecasts',
                    
                    // Expenses
                    'view expenses', 'create expenses', 'edit expenses', 'delete expenses', 
                    'approve expenses', 'reconcile expenses', 'export expense reports',
                    'view expense dashboard', 'categorize expenses',
                    
                    // Deposits
                    'view deposits', 'create deposits', 'edit deposits', 'delete deposits', 
                    'approve deposits', 'reconcile deposits', 'track deposits',
                    
                    // Financial Reports
                    'view financial reports', 'export financial reports',
                    'view balance sheet', 'view profit & loss', 'view cash flow',
                    'generate custom reports', 'view trial balance', 'view general ledger',
                    
                    // Salary & Payroll
                    'view salaries', 'create salary', 'edit salary', 'delete salary',
                    'approve salary', 'process payroll', 'view payroll', 'export payroll reports',
                    'view salary structure', 'create salary structure', 'edit salary structure', 'delete salary structure',
                    
                    // Salary Payments
                    'view salary payments', 'create salary payments', 'edit salary payments',
                    'delete salary payments', 'approve salary payments', 'process salary payments',
                    'export salary payment reports', 'reconcile salary payments',
                    
                    // Bonus
                    'view bonuses', 'create bonuses', 'edit bonuses', 'delete bonuses',
                    'approve bonuses', 'calculate bonuses', 'export bonus reports', 'manage bonus schemes',
                    
                    // Phantom Equity
                    'view phantom equity', 'create phantom equity', 'edit phantom equity',
                    'delete phantom equity', 'approve phantom equity', 'vest phantom equity',
                    'calculate phantom equity', 'export phantom equity reports', 'manage phantom equity schemes',
                    
                    // Profit Share
                    'view profit share', 'create profit share', 'edit profit share', 'delete profit share',
                    'approve profit share', 'calculate profit share', 'distribute profit share',
                    'export profit share reports', 'manage profit share schemes',
                    
                    // Profit Share Periods
                    'view profit share periods', 'create profit share periods', 'edit profit share periods',
                    'delete profit share periods', 'open profit share period', 'close profit share period',
                    
                    // Currency
                    'view currencies', 'create currencies', 'edit currencies', 'delete currencies',
                    'set default currency', 'manage exchange rates', 'update exchange rates',
                    
                    // Payment Methods
                    'view payment methods', 'create payment methods', 'edit payment methods',
                    'delete payment methods', 'activate payment methods', 'deactivate payment methods',
                    
                    // Payment Source
                    'view payment sources', 'create payment sources', 'edit payment sources',
                    'delete payment sources', 'activate payment sources', 'deactivate payment sources',
                    
                    // Payment Purpose
                    'view payment purposes', 'create payment purposes', 'edit payment purposes',
                    'delete payment purposes', 'activate payment purposes', 'deactivate payment purposes',
                    
                    // Export
                    'export all reports', 'export financial data', 'schedule exports',
                    
                    // Dashboard
                    'view finance dashboard',
                ]
            ],
            // HR role
            'hr_manager' => [
                'description' => 'Full HR access including employee management, performance reviews, and payroll',
                'permissions' => [
                    // Employees
                    'view employees', 'create employees', 'edit employees', 'delete employees',
                    'view employee details', 'edit employee details', 'activate employees', 'deactivate employees',
                    'export employee list', 'import employees', 'manage employee documents',
                    'view employee contracts', 'create employee contracts', 'edit employee contracts',
                    'terminate employee contracts',
                    
                    // Departments
                    'view departments', 'create departments', 'edit departments', 'delete departments',
                    'view department structure', 'manage department heads',
                    
                    // Performance Reviews
                    'view performance reviews', 'create performance reviews', 'edit performance reviews',
                    'delete performance reviews', 'submit performance reviews', 'approve performance reviews',
                    'schedule performance reviews', 'view performance metrics', 'export performance reports',
                    'set performance goals', 'track performance goals', 'manage performance cycles',
                    
                    // Time & Attendance
                    'view attendance', 'create attendance', 'edit attendance', 'delete attendance',
                    'approve attendance', 'export attendance reports', 'view timesheets', 'approve timesheets',
                    'manage leave requests', 'approve leave requests',
                
                    
                    // Salary (view only)
                    'view salaries', 'view salary structure',
                    
                    // Dashboard
                    'view HR dashboard',
                ]
            ],
        ];

        foreach ($roles as $roleName => $roleData) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web'
            ]);
            
            // Assign permissions to role
            if (!empty($roleData['permissions'])) {
                $role->syncPermissions($roleData['permissions']);
            }
            
            $this->command->info("Role '{$roleName}' created/updated with " . count($roleData['permissions']) . " permissions");
        }

        $this->command->info('Roles seeded successfully!');
    }
}