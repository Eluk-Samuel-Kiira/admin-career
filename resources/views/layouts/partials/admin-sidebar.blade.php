<div id="kt_app_sidebar" class="app-sidebar flex-column"
    data-kt-drawer="true"
    data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}"
    data-kt-drawer-overlay="true"
    data-kt-drawer-width="225px"
    data-kt-drawer-direction="start"
    data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">

    {{-- Logo --}}
    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
        <a href="{{ route('admin.dashboard') }}">
            <img alt="Logo" src="{{ asset('careers.png') }}" class="app-sidebar-logo-default" style="height:50px; width:210px;" />
            <img alt="Logo" src="{{ asset('fav.png') }}" class="app-sidebar-logo-minimize" style="height:30px; width:30px;" />
        </a>
        <div id="kt_app_sidebar_toggle"
            class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate"
            data-kt-toggle="true"
            data-kt-toggle-state="active"
            data-kt-toggle-target="body"
            data-kt-toggle-name="app-sidebar-minimize">
            <i class="ki-duotone ki-black-left-line fs-3 rotate-180">
                <span class="path1"></span><span class="path2"></span>
            </i>
        </div>
    </div>

    {{-- Menu --}}
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
            <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3"
                data-kt-scroll="true"
                data-kt-scroll-activate="true"
                data-kt-scroll-height="auto"
                data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
                data-kt-scroll-wrappers="#kt_app_sidebar_menu"
                data-kt-scroll-offset="5px"
                data-kt-scroll-save-state="true">

                <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6"
                    id="#kt_app_sidebar_menu"
                    data-kt-menu="true"
                    data-kt-menu-expand="false">

                    {{-- Dashboard --}}
                    @can('view dashboard')
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-element-11 fs-2">
                                    <span class="path1"></span><span class="path2"></span>
                                    <span class="path3"></span><span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">Dashboard</span>
                        </a>
                    </div>
                    @endcan

                    {{-- Jobs --}}
                    <div class="menu-item pt-5">
                        <div class="menu-content">
                            <span class="menu-heading fw-bold text-uppercase fs-7">Job Posting</span>
                        </div>
                    </div>
                    
                    {{-- AI Job Posting --}}
                    @can('create jobs')
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.ai.job-posting*') ? 'show here' : '' }}">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-rocket fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">AI - Job Posting</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            @php
                                $countries = [
                                    'AU' => '🇦🇺 Australia',
                                    'UG' => '🇺🇬 Uganda',
                                    'KE' => '🇰🇪 Kenya',
                                    'TZ' => '🇹🇿 Tanzania',
                                    'RW' => '🇷🇼 Rwanda',
                                    'MW' => '🇲🇼 Malawi',
                                    'ZM' => '🇿🇲 Zambia',
                                    'SG' => '🇸🇬 Singapore',
                                ];
                            @endphp
                            @foreach($countries as $code => $name)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->route('country') == $code ? 'active' : '' }}" 
                                href="{{ route('admin.ai.job-posting', $code) }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">{{ $name }}</span>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endcan

                    {{-- Jobs Index --}}
                    @can('view jobs')
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.companies*', 'admin.job-locations*', 'admin.job-posts*', 'admin.job-applications*', 'admin.sitemap*') ? 'show here' : '' }}">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-briefcase fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Jobs Index</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            {{-- Job Posts --}}
                            @can('view jobs')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.job-posts*') ? 'active' : '' }}" href="{{ route('admin.job-posts') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Job Posts</span>
                                </a>
                            </div>
                            @endcan

                            {{-- Companies --}}
                            @can('view company')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.companies*') ? 'active' : '' }}" href="{{ route('admin.companies') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Companies</span>
                                </a>
                            </div>
                            @endcan

                            {{-- Job Locations --}}
                            @can('view job locations')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.job-locations*') ? 'active' : '' }}" href="{{ route('admin.job-locations') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Locations</span>
                                </a>
                            </div>
                            @endcan

                            {{-- Sitemap & SEO --}}
                            @can('manage sitemap')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.sitemap*') ? 'active' : '' }}" href="{{ route('admin.sitemap.dashboard') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Sitemap & SEO</span>
                                </a>
                            </div>
                            @endcan
                            
                            {{-- Social Media --}}
                            @can('view social media platforms')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.social-media*') ? 'active' : '' }}" href="{{ route('admin.social-media') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Social Media</span>
                                </a>
                            </div>
                            @endcan

                        </div>
                    </div>
                    @endcan 

                    {{-- Blog Index --}}
                    @canany(['view blogs'])
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.blogs*') ? 'show here' : '' }}">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-crown fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Career Blogs</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            {{-- Job Posts --}}
                            @can('view blogs')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.blogs*') ? 'active' : '' }}" href="{{ route('admin.blogs') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Career Blogs</span>
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>
                    @endcanany     

                    {{-- Earnings --}}
                    <div class="menu-item pt-5">
                        <div class="menu-content">
                            <span class="menu-heading fw-bold text-uppercase fs-7">Earnings & Expenses</span>
                        </div>
                    </div>
                    {{-- Compensation --}}
                    @canany(['view employees', 'view salary structure', 'view salary payments', 'view phantom equity', 'view profit share periods', 'view profit share', 'view performance reviews', 'view bonuses'])
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.employees*', 'admin.salary-structures*', 'admin.employee-payments*', 'admin.department-profit-share*',  'admin.phantom-equity*', 'admin.profit-share*', 'admin.performance-reviews*', 'admin.compensation*', 'admin.bonuses*') ? 'show here' : '' }}">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-dollar fs-2">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Compensation</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            @can('view employees')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.employees*') ? 'active' : '' }}" href="{{ route('admin.employees') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Employees</span>
                                </a>
                            </div>
                            @endcan
                            @can('view salary structure')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.salary-structures*') ? 'active' : '' }}" href="{{ route('admin.salary-structures') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Salary Structures</span>
                                </a>
                            </div>
                            @endcan
                            @can('view salary payments')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.employee-payments*') ? 'active' : '' }}" href="{{ route('admin.employee-payments') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Payments</span>
                                </a>
                            </div>
                            @endcan
                            @can('view phantom equity')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.phantom-equity*') ? 'active' : '' }}" href="{{ route('admin.phantom-equity') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Phantom Equity</span>
                                </a>
                            </div>
                            @endcan
                            @can('view profit share periods')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.department-profit-share*') ? 'active' : '' }}" href="{{ route('admin.department-profit-share') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Profit Share Periods</span>
                                </a>
                            </div>
                            @endcan
                            @can('view profit share')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.profit-share*') ? 'active' : '' }}" href="{{ route('admin.profit-share') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Profit Share</span>
                                </a>
                            </div>
                            @endcan
                            @can('view performance reviews')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.performance-reviews*') ? 'active' : '' }}" href="{{ route('admin.performance-reviews') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Performance Reviews</span>
                                </a>
                            </div>
                            @endcan
                            @can('view bonuses')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.bonuses*') ? 'active' : '' }}" href="{{ route('admin.bonuses') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Bonuses</span>
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>
                    @endcanany

                    {{-- Expenses --}}
                    @canany(['view expenses', 'view expense categories'])
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.expenses*', 'admin.expense-categories*') ? 'show here' : '' }}">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-abstract-26 fs-2">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Expenses</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            @can('view expenses')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.expenses*') ? 'active' : '' }}" href="{{ route('admin.expenses') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">All Expenses</span>
                                </a>
                            </div>
                            @endcan
                            @can('view expense categories')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.expense-categories*') ? 'active' : '' }}" href="{{ route('admin.expense-categories') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Categories</span>
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>
                    @endcanany

                    {{-- Revenue --}}
                    @can('view deposits')
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.deposits*','admin.financial-report') ? 'show here' : '' }}">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-credit-cart fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Revenue</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.deposits*') ? 'active' : '' }}" href="{{ route('admin.deposits') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Deposits</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endcan

                    {{-- Divider --}}
                    @canany(['view financial reports', 'view expense reports'])
                    <div class="menu-item pt-5">
                        <div class="menu-content">
                            <span class="menu-heading fw-bold text-uppercase fs-7">Reports</span>
                        </div>
                    </div>
                    @endcanany

                    {{-- Reports --}}
                    @canany(['view financial reports', 'view expense reports', 'view jobs reports', 'view job performance', 'view job seo', 'view job trends'])
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion 
                        {{ request()->routeIs(
                            'accounting.*',
                            'admin.expense-reports*',
                            'admin.jobs-reports*'
                        ) ? 'show here' : '' }}">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-chart-pie-3 fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </span>
                            <span class="menu-title">Reports</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            
                            {{-- Financial Reports Submenu --}}
                            @canany(['view payment methods', 'view account balances', 'view transaction ledger', 'view income statement', 'view cash flow', 'view flexible reports'])
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('accounting.payment-methods*', 'accounting.account-balances*', 
                                'accounting.account-balances*', 'accounting.transaction-ledger*', 'accounting.income-statement*', 'accounting.cash-flow*',
                                'accounting.transaction-ledger*','accounting.flexible-report*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Financial Reports</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    @can('view payment methods')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('accounting.payment-methods*') ? 'active' : '' }}" href="{{ route('accounting.payment-methods.index') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Payment Methods</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view account balances')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('accounting.account-balances*') ? 'active' : '' }}" href="{{ route('accounting.account-balances') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Account Balances</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view transaction ledger')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('accounting.transaction-ledger*') ? 'active' : '' }}" href="{{ route('accounting.transaction-ledger') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Transaction Ledger</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view income statement')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('accounting.income-statement*') ? 'active' : '' }}" href="{{ route('accounting.income-statement') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Income Statement</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view cash flow')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('accounting.cash-flow*') ? 'active' : '' }}" href="{{ route('accounting.cash-flow') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Cash Flow</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view flexible reports')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('accounting.flexible-report*') ? 'active' : '' }}" href="{{ route('accounting.flexible-report') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Flexible Report</span>
                                        </a>
                                    </div>
                                    @endcan
                                </div>
                            </div>
                            @endcanany

                            {{-- Expense Reports Submenu --}}
                            @canany(['view expense reports', 'view expense summary', 'view expense by category', 'view expense by vendor', 
                                    'view expense by employee', 'view expense by payment method', 'view expense trends', 
                                    'view recurring expenses', 'view tax reports', 'view budget vs actual', 'view audit trail'])
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.expense-reports*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Expense Reports</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    @can('view expense report dashboard')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.expense-reports') ? 'active' : '' }}" href="{{ route('admin.expense-reports') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Dashboard</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view expense summary')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.expense-reports.summary') ? 'active' : '' }}" href="{{ route('admin.expense-reports.summary') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Summary</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view expense by category')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.expense-reports.category') ? 'active' : '' }}" href="{{ route('admin.expense-reports.category') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">By Category</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view expense by vendor')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.expense-reports.vendor') ? 'active' : '' }}" href="{{ route('admin.expense-reports.vendor') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">By Vendor</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view expense by employee')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.expense-reports.employee') ? 'active' : '' }}" href="{{ route('admin.expense-reports.employee') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">By Employee</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view expense by payment method')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.expense-reports.payment-method') ? 'active' : '' }}" href="{{ route('admin.expense-reports.payment-method') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">By Payment Method</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view expense trends')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.expense-reports.trends') ? 'active' : '' }}" href="{{ route('admin.expense-reports.trends') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Trends</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view recurring expenses')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.expense-reports.recurring') ? 'active' : '' }}" href="{{ route('admin.expense-reports.recurring') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Recurring</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view tax reports')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.expense-reports.tax') ? 'active' : '' }}" href="{{ route('admin.expense-reports.tax') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Tax Report</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view budget vs actual')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.expense-reports.budget') ? 'active' : '' }}" href="{{ route('admin.expense-reports.budget') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Budget vs Actual</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view audit trail')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.expense-reports.audit') ? 'active' : '' }}" href="{{ route('admin.expense-reports.audit') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Audit</span>
                                        </a>
                                    </div>
                                    @endcan
                                </div>
                            </div>
                            @endcanany
                            
                            {{-- Jobs Reports Submenu --}}
                            @canany(['view jobs reports', 'view job summary', 'view job category report', 'view job company report', 
                                    'view job location report', 'view job source report', 'view job performance', 'view job timeline',
                                    'view job seo', 'view job trends', 'view job poster report', 'view job country report'])
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.jobs-reports*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Jobs Reports</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    @can('view jobs reports')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.jobs-reports') ? 'active' : '' }}" href="{{ route('admin.jobs-reports') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Dashboard</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job summary')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.jobs-reports.summary') ? 'active' : '' }}" href="{{ route('admin.jobs-reports.summary') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Summary</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job poster report')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.jobs-reports.poster') ? 'active' : '' }}" href="{{ route('admin.jobs-reports.poster') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">By Poster</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job timeline')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.jobs-reports.timeline') ? 'active' : '' }}" href="{{ route('admin.jobs-reports.timeline') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Timeline</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job country report')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.jobs-reports.country') ? 'active' : '' }}" href="{{ route('admin.jobs-reports.country') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">By Country</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job category report')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.jobs-reports.category') ? 'active' : '' }}" href="{{ route('admin.jobs-reports.category') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">By Category</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job company report')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.jobs-reports.company') ? 'active' : '' }}" href="{{ route('admin.jobs-reports.company') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">By Company</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job location report')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.jobs-reports.location') ? 'active' : '' }}" href="{{ route('admin.jobs-reports.location') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">By Location</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job source report')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.jobs-reports.source') ? 'active' : '' }}" href="{{ route('admin.jobs-reports.source') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">By Source</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job performance')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.jobs-reports.performance') ? 'active' : '' }}" href="{{ route('admin.jobs-reports.performance') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Performance</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job seo')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.jobs-reports.seo') ? 'active' : '' }}" href="{{ route('admin.jobs-reports.seo') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">SEO Report</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job trends')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.jobs-reports.trends') ? 'active' : '' }}" href="{{ route('admin.jobs-reports.trends') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Trends</span>
                                        </a>
                                    </div>
                                    @endcan
                                </div>
                            </div>
                            @endcanany
                                            
                        </div>
                    </div>
                    @endcanany

                    {{-- Divider --}}
                    @canany(['view users', 'view roles', 'view permissions'])
                    <div class="menu-item pt-5">
                        <div class="menu-content">
                            <span class="menu-heading fw-bold text-uppercase fs-7">Management</span>
                        </div>
                    </div>
                    @endcanany

                    
                    {{-- User Management --}}
                    @canany(['view users', 'view roles', 'view permissions'])
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('users.*', 'admin.roles', 'admin.roles.*', 'admin.permissions', 'admin.permissions.*') ? 'show here' : '' }}">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-address-book fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </span>
                            <span class="menu-title">User Management</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            @can('view users')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('users.index') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Users List</span>
                                </a>
                            </div>
                            @endcan
                            @can('view roles')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.roles', 'admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Roles</span>
                                </a>
                            </div>
                            @endcan
                            @can('view permissions')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.permissions', 'admin.permissions.*') ? 'active' : '' }}" href="{{ route('admin.permissions') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Permissions</span>
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>
                    @endcanany

                    {{-- Settings --}}
                    @canany(['view currencies', 'view payment methods', 'view payment sources', 'view payment purposes', 'view departments', 
                            'view job industries', 'view job categories', 'view salary ranges', 'view education levels', 
                            'view experience levels', 'view job types', 'view countries', 'view pages'])
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.countries*', 'admin.currencies*', 'admin.payment-methods*', 
                        'admin.payment-sources*', 'admin.payment-purposes*', 'admin.departments*','admin.salary-ranges*', 'admin.education-levels*',
                        'admin.experience-levels*', 'admin.job-types*', 'admin.job-categories*', 'admin.industries*', 'admin.pages*') ? 'show here' : '' }}">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-setting-2 fs-2">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Settings</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            
                            {{-- Financial Settings Submenu --}}
                            @canany(['view currencies', 'view payment methods', 'view payment sources', 'view payment purposes'])
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.currencies*','admin.payment-methods*', 'admin.payment-sources*','admin.payment-purposes*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Financial Settings</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    @can('view currencies')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.currencies*') ? 'active' : '' }}" href="{{ route('admin.currencies') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Currencies</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view payment methods')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.payment-methods*') ? 'active' : '' }}" href="{{ route('admin.payment-methods') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Payment Methods</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view payment sources')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.payment-sources*') ? 'active' : '' }}" href="{{ route('admin.payment-sources') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Payment Sources</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view payment purposes')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.payment-purposes*') ? 'active' : '' }}" href="{{ route('admin.payment-purposes') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Payment Purposes</span>
                                        </a>
                                    </div>
                                    @endcan
                                </div>
                            </div>
                            @endcanany
                            
                            {{-- Organization Settings --}}
                            @can('view departments')
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.departments*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Organization</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.departments*') ? 'active' : '' }}" href="{{ route('admin.departments') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Departments</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan
                            
                            {{-- Job Settings --}}
                            @canany(['view job industries', 'view job categories', 'view salary ranges', 'view education levels', 
                                    'view experience levels', 'view job types', 'view countries', 'view pages'])
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.countries*', 'admin.salary-ranges*', 'admin.education-levels*',
                                'admin.experience-levels*', 'admin.job-types*', 'admin.job-categories*', 'admin.industries*', 'admin.pages*') ? 'show here' : '' }}">
                                <span class="menu-link">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Job Settings</span>
                                    <span class="menu-arrow"></span>
                                </span>
                                <div class="menu-sub menu-sub-accordion">
                                    {{-- Countries --}}
                                    @can('view countries')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.countries*') ? 'active' : '' }}" href="{{ route('admin.countries') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Countries</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job industries')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.industries*') ? 'active' : '' }}" href="{{ route('admin.industries') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Industries</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job categories')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.job-categories*') ? 'active' : '' }}" href="{{ route('admin.job-categories') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Job Categories</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view salary ranges')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.salary-ranges*') ? 'active' : '' }}" href="{{ route('admin.salary-ranges') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Salary Ranges</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view education levels')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.education-levels*') ? 'active' : '' }}" href="{{ route('admin.education-levels') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Education Levels</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view experience levels')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.experience-levels*') ? 'active' : '' }}" href="{{ route('admin.experience-levels') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Experience Levels</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view job types')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.job-types*') ? 'active' : '' }}" href="{{ route('admin.job-types') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Job Types</span>
                                        </a>
                                    </div>
                                    @endcan
                                    @can('view pages')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->routeIs('admin.pages*') ? 'active' : '' }}" href="{{ route('admin.pages') }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title">Front Pages</span>
                                        </a>
                                    </div>
                                    @endcan
                                </div>
                            </div>
                            @endcanany
                                            
                        </div>
                    </div>
                    @endcanany

                    {{-- Database Migration --}}
                    @role('super_admin')
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.migration*', 'artisan.index*') ? 'show here' : '' }}">
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-abstract-26 fs-2">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Advanced Settings</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('admin.migration.dashboard*') ? 'active' : '' }}" href="{{ route('admin.migration.dashboard') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Database Migration</span>
                                </a>
                            </div>
                        </div>
                        <div class="menu-sub menu-sub-accordion">
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('artisan.index*') ? 'active' : '' }}" href="{{ route('artisan.index') }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title">Artisan Commands</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endrole

                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar footer --}}
    <div class="app-sidebar-footer flex-column-auto pt-2 pb-6 px-6" id="kt_app_sidebar_footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-flex flex-center btn-custom btn-primary overflow-hidden text-nowrap px-0 h-40px w-100">
                <span class="btn-label">Sign Out</span>
                <i class="ki-duotone ki-entrance-right btn-icon fs-2 m-0">
                    <span class="path1"></span><span class="path2"></span>
                </i>
            </button>
        </form>
    </div>

</div>