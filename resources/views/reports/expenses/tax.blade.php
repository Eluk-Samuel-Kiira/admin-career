@extends('layouts.admin')

@section('title', 'Tax Report')
@section('page_title', 'Tax Report')

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
    <li class="breadcrumb-item text-muted">Tax Report</li>
@endsection

@section('content')
@can('view tax reports')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.expense-reports.tax') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $startDate ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $endDate ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">Category</label>
                <select name="category_id" class="form-select form-select-solid">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ ($categoryId ?? '') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">Tax Type</label>
                <select name="tax_type" class="form-select form-select-solid">
                    <option value="all" {{ ($taxType ?? 'all') == 'all' ? 'selected' : '' }}>All</option>
                    <option value="taxable" {{ ($taxType ?? 'all') == 'taxable' ? 'selected' : '' }}>Taxable</option>
                    <option value="non_taxable" {{ ($taxType ?? 'all') == 'non_taxable' ? 'selected' : '' }}>Non-Taxable</option>
                </select>
            </div>
            <div class="col-md-2">
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
                        <i class="ki-duotone ki-dollar fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Amount</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $taxSummary['total_amount_display'] ?? 'UGX 0' }}</span>
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $taxSummary['total_tax_display'] ?? 'UGX 0' }}</span>
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
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Taxable Expenses</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $taxSummary['taxable_expenses'] ?? 0 }}</span>
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
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Non-Taxable</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $taxSummary['non_taxable_expenses'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tax Summary Table -->
<div class="card card-flush shadow-sm">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">Tax Summary by Category</h3>
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
                        <th>Category</th>
                        <th class="text-center">Expenses</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Tax Amount</th>
                        <th class="text-end">Grand Total</th>
                        <th class="text-center">Tax Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @php $grandTotal = $taxByCategory->sum('grand_total'); @endphp
                    @forelse($taxByCategory as $item)
                        <tr>
                            <td><span class="fw-bold">{{ $item['category_name'] }}</span></td>
                            <td class="text-center">{{ $item['expense_count'] }}</td>
                            <td class="text-end">{{ $item['subtotal_display'] }}</td>
                            <td class="text-end fw-bold text-info">{{ $item['tax_display'] }}</td>
                            <td class="text-end fw-bold text-success">{{ $item['grand_display'] }}</td>
                            <td class="text-center">
                                @php
                                    $taxRate = $item['subtotal'] > 0 ? ($item['tax_total'] / $item['subtotal']) * 100 : 0;
                                @endphp
                                <span class="badge badge-light-primary">{{ number_format($taxRate, 1) }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No tax data available</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td>Total</td>
                        <td class="text-center">{{ $taxByCategory->sum('expense_count') }}</td>
                        <td class="text-end">{{ $baseCurrency->formatAmount($taxByCategory->sum('subtotal')) }}</td>
                        <td class="text-end text-info">{{ $baseCurrency->formatAmount($taxByCategory->sum('tax_total')) }}</td>
                        <td class="text-end text-success">{{ $baseCurrency->formatAmount($taxByCategory->sum('grand_total')) }}</td>
                        <td class="text-center">
                            @php
                                $totalSubtotal = $taxByCategory->sum('subtotal');
                                $totalTax = $taxByCategory->sum('tax_total');
                                $overallRate = $totalSubtotal > 0 ? ($totalTax / $totalSubtotal) * 100 : 0;
                            @endphp
                            <span class="badge badge-light-success">{{ number_format($overallRate, 1) }}%</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-5">
            <div class="text-muted fs-7">
                Showing {{ $expenses->firstItem() ?? 0 }} to {{ $expenses->lastItem() ?? 0 }} of {{ $expenses->total() }} entries
            </div>
            <div>
                {{ $expenses->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Tax Rate Distribution -->
<div class="card card-flush shadow-sm mt-5">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">Tax Rate Distribution</h3>
    </div>
    <div class="card-body">
        @php
            // Initialize rate ranges properly
            $rateRanges = [
                '0%' => ['min' => 0, 'max' => 0, 'count' => 0, 'total' => 0],
                '1-5%' => ['min' => 0.01, 'max' => 5, 'count' => 0, 'total' => 0],
                '6-10%' => ['min' => 5.01, 'max' => 10, 'count' => 0, 'total' => 0],
                '11-15%' => ['min' => 10.01, 'max' => 15, 'count' => 0, 'total' => 0],
                '16-20%' => ['min' => 15.01, 'max' => 20, 'count' => 0, 'total' => 0],
                '20%+' => ['min' => 20.01, 'max' => PHP_FLOAT_MAX, 'count' => 0, 'total' => 0],
            ];
            
            // Calculate distribution
            foreach ($expenses as $expense) {
                $rate = $expense->total_amount > 0 ? ($expense->tax_amount / $expense->total_amount) * 100 : 0;
                foreach ($rateRanges as $key => &$range) {
                    if ($rate >= $range['min'] && $rate <= $range['max']) {
                        $range['count']++;
                        $range['total'] += $expense->tax_amount;
                        break;
                    }
                }
            }
            // Unset reference to avoid issues
            unset($range);
            
            $totalTaxable = 0;
            foreach ($rateRanges as $data) {
                $totalTaxable += $data['count'];
            }
        @endphp
        
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>Rate Range</th>
                        <th class="text-center">Expenses</th>
                        <th class="text-end">Total Tax</th>
                        <th class="text-center">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rateRanges as $range => $data)
                        <tr>
                            <td><span class="fw-bold">{{ $range }}</span></td>
                            <td class="text-center">{{ $data['count'] }}</td>
                            <td class="text-end">{{ $baseCurrency->formatAmount($data['total']) }}</td>
                            <td class="text-center">
                                <div class="d-flex align-items-center gap-2 justify-content-center">
                                    <span>{{ $totalTaxable > 0 ? number_format(($data['count'] / $totalTaxable) * 100, 1) : 0 }}%</span>
                                    <div class="progress w-50" style="height: 6px;">
                                        <div class="progress-bar bg-primary" style="width: {{ $totalTaxable > 0 ? ($data['count'] / $totalTaxable) * 100 : 0 }}%;"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @if($totalTaxable == 0)
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">No taxable expenses found</td>
                        </tr>
                    @endif
                </tbody>
                @if($totalTaxable > 0)
                <tfoot>
                    <tr class="fw-bold">
                        <td>Total</td>
                        <td class="text-center">{{ $totalTaxable }}</td>
                        <td class="text-end text-info">{{ $baseCurrency->formatAmount(array_sum(array_column($rateRanges, 'total'))) }}</td>
                        <td class="text-center">100%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<!-- Export Button -->
<div class="row g-5 g-xl-10 mt-3">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.expense-reports.export', ['type' => 'tax']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
                        <i class="ki-duotone ki-file-down fs-2 me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection