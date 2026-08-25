@extends('layouts.admin')

@section('title', 'Expenses by Employee')
@section('page_title', 'Expenses by Employee')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Reports</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.expense-reports') }}" class="text-muted text-hover-primary">Expense Reports</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">By Employee</li>
@endsection

@section('content')
@can('view expense by employee')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.expense-reports.employee') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $startDate ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $endDate ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">Employee</label>
                <select name="employee_id" class="form-select form-select-solid">
                    <option value="">All Employees</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ ($employeeId ?? '') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ki-duotone ki-filter fs-2 me-1"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-primary me-2">
                        <i class="ki-duotone ki-users fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Employees</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $employeeBreakdown->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-success me-2">
                        <i class="ki-duotone ki-dollar fs-2 text-success">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Expenses</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">UGX {{ number_format($employeeBreakdown->sum('total_amount') / 100, 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-info me-2">
                        <i class="ki-duotone ki-chart fs-2 text-info">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Average per Employee</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">
                            @php
                                $totalEmployees = $employeeBreakdown->count();
                                $avgPerEmployee = $totalEmployees > 0 ? $employeeBreakdown->sum('total_amount') / $totalEmployees : 0;
                            @endphp
                            UGX {{ number_format($avgPerEmployee / 100, 0) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-warning me-2">
                        <i class="ki-duotone ki-basket fs-2 text-warning">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Records</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $employeeBreakdown->sum('expense_count') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Employee Breakdown Table -->
<div class="card card-flush shadow-sm">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">Employee Breakdown</h3>
        <div class="card-toolbar">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted fs-7">Per Page:</span>
                <select class="form-select form-select-sm w-70px" onchange="window.location.href=this.value">
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 10]) }}" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 20]) }}" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 50]) }}" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 100]) }}" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>Employee</th>
                        <th>Job Title</th>
                        <th>Department</th>
                        <th class="text-center">Expenses</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Average</th>
                        <th class="text-end">Max</th>
                        <th class="text-center">Pending</th>
                        <th class="text-center">Approved</th>
                        <th class="text-center">Paid</th>
                    </tr>
                </thead>
                <tbody>
                    @php $grandTotal = $employeeBreakdown->sum('total_amount'); @endphp
                    @forelse($employeeBreakdown as $item)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-35px symbol-circle me-2">
                                        <span class="symbol-label bg-light-primary text-primary fw-bold">
                                            {{ substr($item['employee_name'], 0, 2) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $item['employee_name'] }}</div>
                                        <div class="text-muted fs-7">{{ $item['email'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $item['job_title'] ?? 'N/A' }}</td>
                            <td>{{ $item['department_name'] ?? 'N/A' }}</td>
                            <td class="text-center">{{ $item['expense_count'] }}</td>
                            <td class="text-end fw-bold text-success">UGX {{ number_format($item['total_amount'] / 100, 0) }}</td>
                            <td class="text-end">UGX {{ number_format($item['average_expense'] / 100, 0) }}</td>
                            <td class="text-end">UGX {{ number_format($item['max_expense'] / 100, 0) }}</td>
                            <td class="text-center">
                                @if($item['pending_count'] > 0)
                                    <span class="badge badge-light-warning">{{ $item['pending_count'] }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($item['approved_count'] > 0)
                                    <span class="badge badge-light-info">{{ $item['approved_count'] }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($item['paid_count'] > 0)
                                    <span class="badge badge-light-success">{{ $item['paid_count'] }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">No employees found</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td>Total</td>
                        <td></td>
                        <td></td>
                        <td class="text-center">{{ $employeeBreakdown->sum('expense_count') }}</td>
                        <td class="text-end text-success">UGX {{ number_format($grandTotal / 100, 0) }}</td>
                        <td class="text-end"></td>
                        <td class="text-end"></td>
                        <td class="text-center">{{ $employeeBreakdown->sum('pending_count') }}</td>
                        <td class="text-center">{{ $employeeBreakdown->sum('approved_count') }}</td>
                        <td class="text-center">{{ $employeeBreakdown->sum('paid_count') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-5">
            <div class="text-muted fs-7">
                Showing {{ $employeeBreakdown->firstItem() ?? 0 }} to {{ $employeeBreakdown->lastItem() ?? 0 }} of {{ $employeeBreakdown->total() }} entries
            </div>
            <div>
                {{ $employeeBreakdown->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Top Employees Chart -->
<div class="card card-flush shadow-sm mt-5">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">Top 10 Employees by Spending</h3>
    </div>
    <div class="card-body">
        @php $topEmployees = $employeeBreakdown->sortByDesc('total_amount')->take(10); @endphp
        @if($topEmployees->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-3">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>#</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-center">Expenses</th>
                            <th class="text-center">% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topEmployees as $index => $item)
                            <tr>
                                <td><span class="badge badge-light-primary">{{ $index + 1 }}</span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-30px symbol-circle me-2">
                                            <span class="symbol-label bg-light-primary text-primary fw-bold">
                                                {{ substr($item['employee_name'], 0, 2) }}
                                            </span>
                                        </div>
                                        <span class="fw-bold">{{ $item['employee_name'] }}</span>
                                    </div>
                                </td>
                                <td>{{ $item['department_name'] ?? 'N/A' }}</td>
                                <td class="text-end fw-bold text-success">UGX {{ number_format($item['total_amount'] / 100, 0) }}</td>
                                <td class="text-center">{{ $item['expense_count'] }}</td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-2 justify-content-center">
                                        <span>{{ $grandTotal > 0 ? number_format(($item['total_amount'] / $grandTotal) * 100, 1) : 0 }}%</span>
                                        <div class="progress w-50" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: {{ $grandTotal > 0 ? ($item['total_amount'] / $grandTotal) * 100 : 0 }}%;"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5 text-muted">No employee data available</div>
        @endif
    </div>
</div>

<!-- Export Button -->
<div class="row g-5 g-xl-10 mt-3">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.expense-reports.export', ['type' => 'employee']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
                        <i class="ki-duotone ki-file-down fs-2 me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection