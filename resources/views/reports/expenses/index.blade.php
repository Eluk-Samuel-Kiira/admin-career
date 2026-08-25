@extends('layouts.admin')

@section('title', 'Expense Reports Dashboard')
@section('page_title', 'Expense Reports Dashboard')

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
    <li class="breadcrumb-item text-muted">Expense Reports</li>
@endsection

@section('content')
@can('view expense report dashboard')
<!-- Quick Navigation -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-4">
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('admin.expense-reports.summary') }}" class="btn btn-sm btn-primary">
                        <i class="ki-duotone ki-chart-simple fs-2 me-1"></i> Summary
                    </a>
                    <a href="{{ route('admin.expense-reports.category') }}" class="btn btn-sm btn-light">
                        <i class="ki-duotone ki-category fs-2 me-1"></i> By Category
                    </a>
                    <a href="{{ route('admin.expense-reports.vendor') }}" class="btn btn-sm btn-light">
                        <i class="ki-duotone ki-building fs-2 me-1"></i> By Vendor
                    </a>
                    <a href="{{ route('admin.expense-reports.employee') }}" class="btn btn-sm btn-light">
                        <i class="ki-duotone ki-user fs-2 me-1"></i> By Employee
                    </a>
                    <a href="{{ route('admin.expense-reports.payment-method') }}" class="btn btn-sm btn-light">
                        <i class="ki-duotone ki-card fs-2 me-1"></i> By Payment Method
                    </a>
                    <a href="{{ route('admin.expense-reports.trends') }}" class="btn btn-sm btn-light">
                        <i class="ki-duotone ki-arrow-up fs-2 me-1"></i> Trends
                    </a>
                    <a href="{{ route('admin.expense-reports.recurring') }}" class="btn btn-sm btn-light">
                        <i class="ki-duotone ki-sync fs-2 me-1"></i> Recurring
                    </a>
                    <a href="{{ route('admin.expense-reports.tax') }}" class="btn btn-sm btn-light">
                        <i class="ki-duotone ki-dollar fs-2 me-1"></i> Tax Report
                    </a>
                    <a href="{{ route('admin.expense-reports.budget') }}" class="btn btn-sm btn-light">
                        <i class="ki-duotone ki-chart-pie fs-2 me-1"></i> Budget vs Actual
                    </a>
                    <a href="{{ route('admin.expense-reports.audit') }}" class="btn btn-sm btn-light">
                        <i class="ki-duotone ki-shield fs-2 me-1"></i> Audit
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-primary me-2">
                        <i class="ki-duotone ki-dollar fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Expenses</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $summary['total_display'] ?? 'UGX 0' }}</span>
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
                        <i class="ki-duotone ki-calculator fs-2 text-info">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Tax</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $summary['tax_display'] ?? 'UGX 0' }}</span>
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
                        <i class="ki-duotone ki-chart fs-2 text-success">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Average Expense</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $summary['average_display'] ?? 'UGX 0' }}</span>
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $summary['count'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Trends -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Monthly Trends (Last 12 Months)</h3>
            </div>
            <div class="card-body">
                @if(count($monthlyTrends) > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Month</th>
                                    <th class="text-center">Records</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-center">Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $previousTotal = 0; @endphp
                                @foreach($monthlyTrends as $month => $data)
                                    @php
                                        $growth = 0;
                                        if ($previousTotal > 0) {
                                            $growth = (($data['total'] - $previousTotal) / $previousTotal) * 100;
                                        }
                                        $previousTotal = $data['total'];
                                        $growthColor = $growth > 0 ? 'success' : ($growth < 0 ? 'danger' : 'secondary');
                                        $growthIcon = $growth > 0 ? 'arrow-up' : ($growth < 0 ? 'arrow-down' : 'minus');
                                    @endphp
                                    <tr>
                                        <td><span class="fw-bold">{{ $data['month_label'] }}</span></td>
                                        <td class="text-center">{{ $data['count'] }}</td>
                                        <td class="text-end fw-bold text-success">{{ $data['total_display'] }}</td>
                                        <td class="text-center">
                                            @if($growth > 0)
                                                <span class="badge badge-light-success">
                                                    <i class="ki-duotone ki-arrow-up fs-3"></i> +{{ number_format($growth, 1) }}%
                                                </span>
                                            @elseif($growth < 0)
                                                <span class="badge badge-light-danger">
                                                    <i class="ki-duotone ki-arrow-down fs-3"></i> {{ number_format($growth, 1) }}%
                                                </span>
                                            @else
                                                <span class="badge badge-light-secondary">0%</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ collect($monthlyTrends)->sum('count') }}</td>
                                    <td class="text-end text-success">UGX {{ number_format(collect($monthlyTrends)->sum('total') / 100, 0) }}</td>
                                    <td class="text-center"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No monthly trend data available</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Top Categories & Vendors -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-xxl-6 col-xl-6">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Top Categories</h3>
                <a href="{{ route('admin.expense-reports.category') }}" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="card-body">
                @if($topCategories->count() > 0)
                    @foreach($topCategories as $category)
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <span class="fw-bold">{{ $category->category_name }}</span>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold text-success">{{ $category->total_display }}</span>
                                <span class="text-muted ms-2 fs-7">({{ $category->count }})</span>
                            </div>
                        </div>
                        @php
                            $maxTotal = $topCategories->max('total') ?: 1;
                            $percentage = ($category->total / $maxTotal) * 100;
                        @endphp
                        <div class="progress mb-3" style="height: 4px;">
                            <div class="progress-bar bg-primary" style="width: {{ $percentage }}%;"></div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-5 text-muted">No expense data available</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xxl-6 col-xl-6">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Top Vendors</h3>
                <a href="{{ route('admin.expense-reports.vendor') }}" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="card-body">
                @if($topVendors->count() > 0)
                    @foreach($topVendors as $vendor)
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <span class="fw-bold">{{ $vendor->vendor_name }}</span>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold text-success">{{ $vendor->total_display }}</span>
                                <span class="text-muted ms-2 fs-7">({{ $vendor->count }})</span>
                            </div>
                        </div>
                        @php
                            $maxTotal = $topVendors->max('total') ?: 1;
                            $percentage = ($vendor->total / $maxTotal) * 100;
                        @endphp
                        <div class="progress mb-3" style="height: 4px;">
                            <div class="progress-bar bg-info" style="width: {{ $percentage }}%;"></div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-5 text-muted">No vendor data available</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Status Breakdown & Recent Expenses -->
<div class="row g-5 g-xl-10">
    <div class="col-xxl-4 col-xl-4">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Status Breakdown</h3>
            </div>
            <div class="card-body">
                @if(count($statusBreakdown) > 0)
                    @foreach($statusBreakdown as $status => $data)
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <span class="fw-bold">{{ $data['label'] }}</span>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold text-{{ $status == 'paid' ? 'success' : ($status == 'pending' ? 'warning' : ($status == 'approved' ? 'info' : 'danger')) }}">
                                    {{ $data['count'] }}
                                </span>
                                <span class="text-muted ms-2 fs-7">({{ $data['total'] > 0 ? 'UGX ' . number_format($data['total'] / 100, 0) : 'UGX 0' }})</span>
                            </div>
                        </div>
                        @php
                            $totalStatus = collect($statusBreakdown)->sum('count');
                            $percentage = $totalStatus > 0 ? ($data['count'] / $totalStatus) * 100 : 0;
                            $badgeColor = $status == 'paid' ? 'success' : ($status == 'pending' ? 'warning' : ($status == 'approved' ? 'info' : 'danger'));
                        @endphp
                        <div class="progress mb-3" style="height: 6px;">
                            <div class="progress-bar bg-{{ $badgeColor }}" style="width: {{ $percentage }}%;"></div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-5 text-muted">No status data available</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xxl-8 col-xl-8">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Recent Expenses</h3>
                <a href="{{ route('admin.expense-reports.summary') }}" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-3">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th>Date</th>
                                <th>Expense #</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentExpenses as $expense)
                                <tr>
                                    <td>{{ $expense->date->format('M d, Y') }}</td>
                                    <td>{{ $expense->expense_number }}</td>
                                    <td>{{ Str::limit($expense->description, 30) }}</td>
                                    <td>{{ $expense->category?->name ?? 'N/A' }}</td>
                                    <td class="text-end fw-bold text-success">
                                        UGX {{ number_format($expense->total_amount / 100, 0) }}
                                    </td>
                                    <td>{!! $expense->payment_status_badge !!}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">No recent expenses found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted fs-7">
                        Showing {{ $recentExpenses->firstItem() ?? 0 }} to {{ $recentExpenses->lastItem() ?? 0 }} of {{ $recentExpenses->total() }} entries
                    </div>
                    <div>
                        {{ $recentExpenses->appends(request()->except('page'))->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection