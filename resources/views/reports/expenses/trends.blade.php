@extends('layouts.admin')

@section('title', 'Expense Trends')
@section('page_title', 'Expense Trends')

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
    <li class="breadcrumb-item text-muted">Trends</li>
@endsection

@section('content')
@can('view expense trends')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.expense-reports.trends') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">Period</label>
                <select name="period" class="form-select form-select-solid">
                    <option value="monthly" {{ ($period ?? 'monthly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="quarterly" {{ ($period ?? 'monthly') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                    <option value="yearly" {{ ($period ?? 'monthly') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">Year</label>
                <select name="year" class="form-select form-select-solid">
                    @foreach($years as $yr)
                        <option value="{{ $yr }}" {{ ($year ?? date('Y')) == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
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
                        <i class="ki-duotone ki-arrow-up fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Periods</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ count($trendData) }}</span>
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">
                            UGX {{ number_format(collect($trendData)->sum('total') / 100, 0) }}
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
                    <div class="symbol symbol-35px symbol-circle bg-light-info me-2">
                        <i class="ki-duotone ki-chart fs-2 text-info">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Average Per Period</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">
                            @php
                                $totalPeriods = count($trendData);
                                $totalAmount = collect($trendData)->sum('total');
                                $avgPerPeriod = $totalPeriods > 0 ? $totalAmount / $totalPeriods : 0;
                            @endphp
                            UGX {{ number_format($avgPerPeriod / 100, 0) }}
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ collect($trendData)->sum('count') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Trends Table -->
<div class="card card-flush shadow-sm">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">
            @if($period == 'monthly')
                Monthly Trends - {{ $year }}
            @elseif($period == 'quarterly')
                Quarterly Trends - {{ $year }}
            @else
                Yearly Trends
            @endif
        </h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>Period</th>
                        <th class="text-center">Records</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Average</th>
                        <th class="text-end">Growth (%)</th>
                        <th class="text-center">Trend</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $previousTotal = 0;
                        $trendItems = [];
                    @endphp
                    
                    @forelse($trendData as $key => $data)
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
                            <td>
                                <span class="fw-bold">
                                    @if($period == 'monthly')
                                        {{ $data['month_name'] }}
                                    @elseif($period == 'quarterly')
                                        {{ $data['quarter_label'] }}
                                    @else
                                        {{ $data['year'] }}
                                    @endif
                                </span>
                            </td>
                            <td class="text-center">{{ $data['count'] }}</td>
                            <td class="text-end fw-bold text-success">UGX {{ number_format($data['total'] / 100, 0) }}</td>
                            <td class="text-end">UGX {{ number_format($data['average'] / 100, 0) }}</td>
                            <td class="text-end">
                                @if($growth != 0)
                                    <span class="text-{{ $growthColor }}">
                                        {{ $growth > 0 ? '+' : '' }}{{ number_format($growth, 1) }}%
                                    </span>
                                @else
                                    <span class="text-muted">0%</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($growth > 0)
                                    <span class="badge badge-light-success">
                                        <i class="ki-duotone ki-arrow-up fs-3"></i> Up
                                    </span>
                                @elseif($growth < 0)
                                    <span class="badge badge-light-danger">
                                        <i class="ki-duotone ki-arrow-down fs-3"></i> Down
                                    </span>
                                @else
                                    <span class="badge badge-light-secondary">Flat</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No trend data available</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td>Total</td>
                        <td class="text-center">{{ collect($trendData)->sum('count') }}</td>
                        <td class="text-end text-success">UGX {{ number_format(collect($trendData)->sum('total') / 100, 0) }}</td>
                        <td class="text-end"></td>
                        <td class="text-end"></td>
                        <td class="text-center"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Trend Chart (Visual Progress Bars) -->
<div class="card card-flush shadow-sm mt-5">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">Visual Trend Analysis</h3>
    </div>
    <div class="card-body">
        @if(count($trendData) > 0)
            @php 
                $maxTotal = collect($trendData)->max('total');
                $maxTotal = $maxTotal > 0 ? $maxTotal : 1;
            @endphp
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-3">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th style="width: 15%;">Period</th>
                            <th style="width: 55%;">Amount</th>
                            <th style="width: 15%;" class="text-end">Value</th>
                            <th style="width: 15%;" class="text-center">Growth</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trendData as $key => $data)
                            @php
                                $percentage = $maxTotal > 0 ? ($data['total'] / $maxTotal) * 100 : 0;
                                $color = $percentage > 75 ? 'danger' : ($percentage > 50 ? 'warning' : ($percentage > 25 ? 'primary' : 'info'));
                                $prevTotal = isset($prevTotals) ? $prevTotals : 0;
                                $growth = $prevTotal > 0 ? (($data['total'] - $prevTotal) / $prevTotal) * 100 : 0;
                                $prevTotals = $data['total'];
                            @endphp
                            <tr>
                                <td>
                                    <span class="fw-bold">
                                        @if($period == 'monthly')
                                            {{ $data['month_name'] }}
                                        @elseif($period == 'quarterly')
                                            {{ $data['quarter_label'] }}
                                        @else
                                            {{ $data['year'] }}
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="progress w-100" style="height: 20px;">
                                            <div class="progress-bar bg-{{ $color }}" style="width: {{ $percentage }}%;">
                                                <span class="text-dark fw-bold" style="position: relative; z-index: 1;">
                                                    {{ number_format($percentage, 1) }}%
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end fw-bold">UGX {{ number_format($data['total'] / 100, 0) }}</td>
                                <td class="text-center">
                                    @php
                                        $growthColor = $growth > 0 ? 'success' : ($growth < 0 ? 'danger' : 'secondary');
                                    @endphp
                                    @if($growth != 0)
                                        <span class="badge badge-light-{{ $growthColor }}">
                                            {{ $growth > 0 ? '+' : '' }}{{ number_format($growth, 1) }}%
                                        </span>
                                    @else
                                        <span class="badge badge-light-secondary">0%</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5 text-muted">No data available for visualization</div>
        @endif
    </div>
</div>

<!-- Export Button -->
<div class="row g-5 g-xl-10 mt-3">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.expense-reports.export', ['type' => 'trends']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
                        <i class="ki-duotone ki-file-down fs-2 me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection