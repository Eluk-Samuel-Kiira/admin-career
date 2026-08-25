@extends('layouts.admin')

@section('title', 'Expenses by Vendor')
@section('page_title', 'Expenses by Vendor')

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
    <li class="breadcrumb-item text-muted">By Vendor</li>
@endsection

@section('content')
@can('view expense by vendor')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.expense-reports.vendor') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $startDate ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $endDate ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">Vendor Name</label>
                <input type="text" name="vendor_name" class="form-control form-control-solid" placeholder="Search vendor..." value="{{ $vendorName ?? '' }}">
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
                        <i class="ki-duotone ki-building fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Vendors</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $summary['total_vendors'] ?? 0 }}</span>
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
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Amount</span>
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
                        <i class="ki-duotone ki-basket fs-2 text-info">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Transactions</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $summary['total_transactions'] ?? 0 }}</span>
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
                        <i class="ki-duotone ki-chart fs-2 text-warning">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Average Transaction</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $summary['avg_transaction_display'] ?? 'UGX 0' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vendor Breakdown Table -->
<div class="card card-flush shadow-sm">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">Vendor Breakdown</h3>
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
                        <th>Vendor</th>
                        <th class="text-center">Transactions</th>
                        <th class="text-center">Categories Used</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Average</th>
                        <th class="text-end">Largest</th>
                        <th class="text-end">Smallest</th>
                        <th class="text-center">%</th>
                    </tr>
                </thead>
                <tbody>
                    @php $grandTotal = $vendorBreakdown->sum('total_amount'); @endphp
                    @forelse($vendorBreakdown as $item)
                        <tr>
                            <td><span class="fw-bold">{{ $item->vendor_name }}</span></td>
                            <td class="text-center">{{ $item->transaction_count }}</td>
                            <td class="text-center">{{ $item->categories_used }}</td>
                            <td class="text-end fw-bold text-success">{{ $item->total_display }}</td>
                            <td class="text-end">{{ $item->average_display }}</td>
                            <td class="text-end">{{ $item->largest_display }}</td>
                            <td class="text-end">{{ $item->smallest_display }}</td>
                            <td class="text-center">
                                <div class="d-flex align-items-center gap-2 justify-content-center">
                                    <span>{{ $grandTotal > 0 ? number_format(($item->total_amount / $grandTotal) * 100, 1) : 0 }}%</span>
                                    <div class="progress w-50" style="height: 6px;">
                                        <div class="progress-bar bg-primary" style="width: {{ $grandTotal > 0 ? ($item->total_amount / $grandTotal) * 100 : 0 }}%;"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No vendors found</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td>Total</td>
                        <td class="text-center">{{ $vendorBreakdown->sum('transaction_count') }}</td>
                        <td class="text-center"></td>
                        <td class="text-end text-success">UGX {{ number_format($grandTotal / 100, 0) }}</td>
                        <td class="text-end"></td>
                        <td class="text-end"></td>
                        <td class="text-end"></td>
                        <td class="text-center">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-5">
            <div class="text-muted fs-7">
                Showing {{ $vendorBreakdown->firstItem() ?? 0 }} to {{ $vendorBreakdown->lastItem() ?? 0 }} of {{ $vendorBreakdown->total() }} entries
            </div>
            <div>
                {{ $vendorBreakdown->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Top Vendors Chart -->
<div class="card card-flush shadow-sm mt-5">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">Top 10 Vendors</h3>
    </div>
    <div class="card-body">
        @php $topVendors = $vendorBreakdown->take(10); @endphp
        @if($topVendors->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-3">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>#</th>
                            <th>Vendor</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Transactions</th>
                            <th class="text-center">% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topVendors as $index => $item)
                            <tr>
                                <td><span class="badge badge-light-primary">{{ $index + 1 }}</span></td>
                                <td><span class="fw-bold">{{ $item->vendor_name }}</span></td>
                                <td class="text-end fw-bold text-success">{{ $item->total_display }}</td>
                                <td class="text-center">{{ $item->transaction_count }}</td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-2 justify-content-center">
                                        <span>{{ $grandTotal > 0 ? number_format(($item->total_amount / $grandTotal) * 100, 1) : 0 }}%</span>
                                        <div class="progress w-50" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: {{ $grandTotal > 0 ? ($item->total_amount / $grandTotal) * 100 : 0 }}%;"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5 text-muted">No vendor data available</div>
        @endif
    </div>
</div>

<!-- Export Button -->
<div class="row g-5 g-xl-10 mt-3">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.expense-reports.export', ['type' => 'vendor']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
                        <i class="ki-duotone ki-file-down fs-2 me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection