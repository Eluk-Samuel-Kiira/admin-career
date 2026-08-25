<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions grouped by modules - NO DUPLICATES
        $permissions = [
            // ============================================================
            // USER MANAGEMENT
            // ============================================================
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',
            'activate users',
            'deactivate users',
            'reset user passwords',

            // ============================================================
            // ROLE & PERMISSION MANAGEMENT
            // ============================================================
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
            'assign permissions',
            'revoke permissions',

            // ============================================================
            // JOB MANAGEMENT
            // ============================================================
            'view jobs',
            'create jobs',
            'edit jobs',
            'delete jobs',
            'approve jobs',
            'publish jobs',
            'archive jobs',
            'feature jobs',
            'mark jobs urgent',
            'view job analytics',
            'export jobs',
            'import jobs',
            'bulk delete jobs',
            'restore jobs',
            'view job applications',
            'manage job categories',
            'manage job types',
            'manage job locations',
            'manage job industries',
            'manage experience levels',
            'manage education levels',
            'manage salary ranges',

            // ============================================================
            // JOB APPLICATIONS
            // ============================================================
            'view applications',
            'review applications',
            'shortlist candidates',
            'reject applications',
            'schedule interviews',
            'conduct interviews',
            'rate candidates',
            'send interview feedback',
            'export applications',
            'bulk process applications',

            // ============================================================
            // JOBS REPORTS PERMISSIONS
            // ============================================================
            'view jobs reports',
            'view job summary',
            'view job timeline',
            'view job category report',
            'view job company report',
            'view job location report',
            'view job source report',
            'view job performance',
            'view job seo',
            'view job trends',
            'view job country report',
            'view job poster report',
            'view job poster report',
            'view countries',
            'create countries',
            'edit countries',
            'delete countries',
            'view pages',
            'create pages',
            'edit pages',
            'delete pages',

            'view social media platforms',
            'create social media platforms',
            'edit social media platforms',
            'delete social media platforms',

            // ============================================================
            // CANDIDATES / JOB SEEKERS
            // ============================================================
            'view candidates',
            'create candidates',
            'edit candidates',
            'delete candidates',
            'export candidates',
            'import candidates',
            'view talent pool',
            'add to talent pool',
            'search candidates',
            'contact candidates',
            'track candidate status',
            'view candidate profiles',
            'manage candidate resumes',
            'view candidate history',

            // ============================================================
            // CLIENTS 
            // ============================================================
            'view clients',
            'create clients',
            'edit clients',
            'delete clients',
            'manage client contracts',
            'view client history',
            'deactivate clients',
            'view client jobs',
            'manage client relationships',
            'view client analytics',

            // ============================================================
            // COMPANIES
            // ============================================================
            'view company',
            'create company',
            'edit company',
            'delete company',
            'deactivate company',

            // ============================================================
            // RECRUITMENT PROCESS
            // ============================================================
            'manage job postings',
            'manage interviews',
            'send offers',
            'manage onboarding',
            'view onboarding tasks',
            'complete onboarding tasks',
            'manage offboarding',
            'view offboarding tasks',
            'manage recruitment pipeline',
            'view recruitment analytics',
            'post job openings',
            'view applicants',
            'shortlist applicants',
            'rate applicants',

            // ============================================================
            // FINANCE: REVENUE
            // ============================================================
            'view revenue',
            'create revenue',
            'edit revenue',
            'delete revenue',
            'approve revenue',
            'reconcile revenue',
            'export revenue reports',
            'view revenue dashboard',
            'generate revenue forecasts',

            // ============================================================
            // FINANCE: EXPENSES
            // ============================================================
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',
            'approve expenses',
            'pay expenses',
            'reject expenses',
            'cancel expenses',
            'reconcile expenses',
            'export expense reports',
            'view expense dashboard',
            'categorize expenses',
            'view expense reports',
            'view expense report dashboard',
            'view expense by category',
            'view expense by vendor',
            'view expense by employee',
            'view expense by payment method',
            'view recurring expenses',
            'view tax reports',
            'view budget vs actual',
            'view audit trail',
            'view expense summary',
            'export financial reports',

            // ============================================================
            // FINANCE: DEPOSITS
            // ============================================================
            'view deposits',
            'create deposits',
            'edit deposits',
            'delete deposits',
            'approve deposits',
            'reconcile deposits',
            'track deposits',
            'cancel deposits',
            
            // ============================================================
            // EXPENSE: EXPENSE CATEGORY
            // ============================================================
            'view expense categories',
            'create expense categories',
            'edit expense categories',
            'delete expense categories',
            'approve expense categories',

            // ============================================================
            // FINANCE: FINANCIAL REPORTS
            // ============================================================
            'view account balances',
            'view transaction ledger',
            'view income statement',
            'view flexible reports',
            'view balance sheet',
            'view profit & loss',
            'view cash flow',
            'generate custom reports',
            'view trial balance',
            'view general ledger',

            // ============================================================
            // FINANCE: SALARY & PAYROLL
            // ============================================================
            'view salaries',
            'create salary',
            'edit salary',
            'delete salary',
            'approve salary',
            'process payroll',
            'view payroll',
            'export payroll reports',
            'view salary structure',
            'create salary structure',
            'edit salary structure',
            'delete salary structure',
            'manage employee salaries',
            'view employee reports',

            // ============================================================
            // FINANCE: SALARY PAYMENTS
            // ============================================================
            'view salary payments',
            'create salary payments',
            'edit salary payments',
            'delete salary payments',
            'approve salary payments',
            'process salary payments',
            'export salary payment reports',
            'reconcile salary payments',
            'cancel salary payments',
            'reject salary payments',

            // ============================================================
            // FINANCE: BONUS
            // ============================================================
            'view bonuses',
            'create bonuses',
            'edit bonuses',
            'delete bonuses',
            'approve bonuses',
            'pay bonuses',
            'calculate bonuses',
            'export bonus reports',
            'manage bonus schemes',

            // ============================================================
            // FINANCE: PHANTOM EQUITY
            // ============================================================
            'view phantom equity',
            'create phantom equity',
            'edit phantom equity',
            'delete phantom equity',
            'approve phantom equity',
            'vest phantom equity',
            'calculate phantom equity',
            'export phantom equity reports',
            'manage phantom equity schemes',

            // ============================================================
            // FINANCE: PROFIT SHARE
            // ============================================================
            'view profit share',
            'create profit share',
            'edit profit share',
            'delete profit share',
            'approve profit share',
            'pay profit share',
            'calculate profit share',
            'distribute profit share',
            'export profit share reports',
            'manage profit share schemes',

            // ============================================================
            // FINANCE: PROFIT SHARE PERIODS
            // ============================================================
            'view profit share periods',
            'create profit share periods',
            'edit profit share periods',
            'delete profit share periods',
            'open profit share period',
            'close profit share period',
            'review profit share period',

            // ============================================================
            // FINANCE: CURRENCY
            // ============================================================
            'view currencies',
            'create currencies',
            'edit currencies',
            'delete currencies',
            'set default currency',
            'manage exchange rates',
            'update exchange rates',

            // ============================================================
            // FINANCE: PAYMENT METHODS
            // ============================================================
            'view payment methods',
            'create payment methods',
            'edit payment methods',
            'delete payment methods',
            'activate payment methods',
            'deactivate payment methods',
            'assign payment methods',
            'transfer payment methods',

            // ============================================================
            // FINANCE: PAYMENT SOURCE
            // ============================================================
            'view payment sources',
            'create payment sources',
            'edit payment sources',
            'delete payment sources',
            'activate payment sources',
            'deactivate payment sources',
            'track payment sources',

            // ============================================================
            // FINANCE: PAYMENT PURPOSE
            // ============================================================
            'view payment purposes',
            'create payment purposes',
            'edit payment purposes',
            'delete payment purposes',
            'activate payment purposes',
            'deactivate payment purposes',

            // ============================================================
            // FINANCE: PAYMENTS
            // ============================================================
            'view payments',
            'create payments',
            'edit payments',
            'delete payments',
            'process payments',
            'approve payments',
            'cancel payments',
            'view payment reports',

            // ============================================================
            // HR: EMPLOYEES
            // ============================================================
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'view employee details',
            'edit employee details',
            'activate employees',
            'deactivate employees',
            'export employee list',
            'import employees',
            'manage employee documents',
            'view employee contracts',
            'create employee contracts',
            'edit employee contracts',
            'terminate employee contracts',

            // ============================================================
            // HR: DEPARTMENTS
            // ============================================================
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',
            'view department structure',
            'manage department heads',
            'manage department budgets',

            // ============================================================
            // HR: PERFORMANCE REVIEWS
            // ============================================================
            'view performance reviews',
            'create performance reviews',
            'edit performance reviews',
            'delete performance reviews',
            'submit performance reviews',
            'approve performance reviews',
            'schedule performance reviews',
            'view performance metrics',
            'export performance reports',
            'set performance goals',
            'track performance goals',
            'manage performance cycles',

            // ============================================================
            // HR: TIME & ATTENDANCE
            // ============================================================
            'view attendance',
            'create attendance',
            'edit attendance',
            'delete attendance',
            'approve attendance',
            'export attendance reports',
            'view timesheets',
            'approve timesheets',
            'manage leave requests',
            'approve leave requests',

            // ============================================================
            // COMPENSATION
            // ============================================================
            'view compensation',
            'manage salary structures',
            'process payroll',
            'view payroll reports',
            'manage bonuses',
            'manage phantom equity',
            'view compensation reports',

            // ============================================================
            // ACCOUNTING / FINANCE
            // ============================================================
            'view accounting',
            'manage payment methods',
            'view account balances',
            'view transaction ledger',
            'view income statement',
            'view cash flow',
            'view financial reports',
            'manage currencies',

            // ============================================================
            // SYSTEM SETTINGS
            // ============================================================
            'manage settings',
            'view system logs',
            'manage email templates',
            'manage notifications',
            'manage audit logs',
            'clear cache',
            'manage integrations',
            'view settings',
            'edit settings',
            'manage system configuration',

            // ============================================================
            // DASHBOARD & REPORTS
            // ============================================================
            'view dashboard',
            'view reports',
            'export reports',
            'view analytics',
            'view HR dashboard',
            'view finance dashboard',
            'view operations dashboard',
            'view sales dashboard',

            // ============================================================
            // SUPPORT & TICKETS
            // ============================================================
            'view tickets',
            'respond tickets',
            'manage tickets',
            'close tickets',
            'escalate tickets',
            'assign tickets',

            // ============================================================
            // AUDIT
            // ============================================================
            'view audit trails',
            'export audit reports',
            'manage audit logs',
            'view system security',

            // ============================================================
            // NOTIFICATIONS
            // ============================================================
            'view notifications',
            'send notifications',
            'manage notification templates',
            'configure notifications',

            // ============================================================
            // EXPORT MANAGEMENT
            // ============================================================
            'export all reports',
            'export financial data',
            'export HR data',
            'export operational data',
            'schedule exports',

            // ============================================================
            // JOB SITEMAP & SEO
            // ============================================================
            'manage sitemap',
            'generate sitemap',
            'ping search engines',
            'view seo analytics',
            'manage seo settings',
            'view job seo scores',
            'optimize job seo',

            // ============================================================
            // JOB ALERTS & NOTIFICATIONS
            // ============================================================
            'manage job alerts',
            'view job alerts',
            'send job alerts',
            'configure job notifications',

            // ============================================================
            // JOB PERFORMANCE
            // ============================================================
            'view job performance',
            'view job statistics',
            'view job conversion rates',
            'view job click through rates',
            'view job application rates',

            // ============================================================
            // JOB DASHBOARD
            // ============================================================
            'view job dashboard',
            'view job reports',
            'export job reports',
            'view job analytics dashboard',
            'view recruitment dashboard',

            // ============================================================
            // JOB ATTRIBUTES
            // ============================================================
            'view job categories',
            'create job categories',
            'edit job categories',
            'delete job categories',
            'view job types',
            'create job types',
            'edit job types',
            'delete job types',
            'view job locations',
            'create job locations',
            'edit job locations',
            'delete job locations',
            'view job industries',
            'create job industries',
            'edit job industries',
            'delete job industries',
            'view experience levels',
            'create experience levels',
            'edit experience levels',
            'delete experience levels',
            'view education levels',
            'create education levels',
            'edit education levels',
            'delete education levels',
            'view salary ranges',
            'create salary ranges',
            'edit salary ranges',
            'delete salary ranges',
            'manage job attributes',
            'manage job metadata',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        $this->command->info('Permissions seeded successfully! Total: ' . count($permissions));
    }
}