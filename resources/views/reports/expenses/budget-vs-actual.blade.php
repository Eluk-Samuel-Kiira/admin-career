@extends('layouts.admin')

@section('title', 'Budget vs Actual Report')
@section('page_title', 'Budget vs Actual Report')

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
    <li class="breadcrumb-item text-muted">Budget vs Actual</li>
@endsection

@section('content')
@can('view budget vs actual')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.expense-reports.budget') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">Year</label>
                <select name="year" class="form-select form-select-solid">
                    @foreach($years as $yr)
                        <option value="{{ $yr }}" {{ ($year ?? date('Y')) == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">Month</label>
                <select name="month" class="form-select form-select-solid">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" {{ ($month ?? date('m')) == $num ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
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
                        <i class="ki-duotone ki-wallet fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Monthly Budget</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $summary['total_budget_monthly_display'] ?? 'UGX 0' }}</span>
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
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Actual Monthly</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $summary['total_actual_monthly_display'] ?? 'UGX 0' }}</span>
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
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Monthly Variance</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">
                            {{ $summary['total_variance_monthly_display'] ?? 'UGX 0' }}
                            @if(($summary['total_variance_monthly'] ?? 0) > 0)
                                <span class="text-success fs-7">(Under Budget)</span>
                            @elseif(($summary['total_variance_monthly'] ?? 0) < 0)
                                <span class="text-danger fs-7">(Over Budget)</span>
                            @endif
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
                        <i class="ki-duotone ki-calendar fs-2 text-warning">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Budget Status</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">
                            @php
                                $totalVariance = $summary['total_variance_monthly'] ?? 0;
                                if ($totalVariance > 0) {
                                    echo '<span class="text-success">✅ Under Budget</span>';
                                } elseif ($totalVariance < 0) {
                                    echo '<span class="text-danger">⚠️ Over Budget</span>';
                                } else {
                                    echo '<span class="text-info">📊 On Budget</span>';
                                }
                            @endphp
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Budget vs Actual Table -->
<div class="card card-flush shadow-sm">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">Budget vs Actual - {{ $year }}</h3>
        <div class="card-toolbar">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted fs-7">Month:</span>
                <span class="fw-bold">{{ $months[$month] ?? 'All' }}</span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th style="min-width: 200px;">Category</th>
                        <th class="text-end">Budget</th>
                        <th class="text-end">Actual</th>
                        <th class="text-end">Variance</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Utilization</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $totalBudget = 0;
                        $totalActual = 0;
                        $totalVariance = 0;
                    @endphp
                    @forelse($budgetData as $item)
                        @php
                            $budget = $item['budget_monthly'];
                            $actual = $item['actual_monthly'];
                            $variance = $item['variance_monthly'];
                            $utilization = $budget > 0 ? ($actual / $budget) * 100 : 0;
                            
                            $totalBudget += $budget;
                            $totalActual += $actual;
                            $totalVariance += $variance;
                            
                            $statusClass = $variance > 0 ? 'success' : ($variance < 0 ? 'danger' : 'secondary');
                            $statusIcon = $variance > 0 ? '↓' : ($variance < 0 ? '↑' : '→');
                            $statusText = $variance > 0 ? 'Under' : ($variance < 0 ? 'Over' : 'On');
                            
                            $progressColor = $utilization > 100 ? 'danger' : ($utilization > 80 ? 'warning' : 'success');
                        @endphp
                        <tr>
                            <td>
                                <span class="fw-bold">{{ $item['category']->name }}</span>
                                <span class="badge badge-light-primary ms-2">{{ $item['category']->code }}</span>
                            </td>
                            <td class="text-end">{{ $item['budget_monthly_display'] }}</td>
                            <td class="text-end fw-bold">{{ $item['actual_monthly_display'] }}</td>
                            <td class="text-end fw-bold text-{{ $statusClass }}">
                                {{ $item['variance_monthly_display'] }}
                                @if($variance != 0)
                                    <span class="fs-7">({{ number_format(abs($item['variance_percentage_monthly']), 1) }}%)</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light-{{ $statusClass }}">
                                    {{ $statusIcon }} {{ $statusText }} Budget
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center gap-2 justify-content-center">
                                    <span>{{ number_format($utilization, 1) }}%</span>
                                    <div class="progress w-50" style="height: 8px;">
                                        <div class="progress-bar bg-{{ $progressColor }}" 
                                             style="width: {{ min($utilization, 100) }}%;">
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No budget data available</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($budgetData) > 0)
                <tfoot>
                    <tr class="fw-bold">
                        <td>TOTAL</td>
                        <td class="text-end">{{ $baseCurrency->formatAmount($totalBudget) }}</td>
                        <td class="text-end">{{ $baseCurrency->formatAmount($totalActual) }}</td>
                        <td class="text-end text-{{ $totalVariance > 0 ? 'success' : ($totalVariance < 0 ? 'danger' : 'secondary') }}">
                            {{ $baseCurrency->formatAmount($totalVariance) }}
                            @php
                                $totalVariancePercent = $totalBudget > 0 ? ($totalVariance / $totalBudget) * 100 : 0;
                            @endphp
                            @if($totalVariance != 0)
                                <span class="fs-7">({{ number_format(abs($totalVariancePercent), 1) }}%)</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @php
                                $totalUtilization = $totalBudget > 0 ? ($totalActual / $totalBudget) * 100 : 0;
                                $totalStatusClass = $totalUtilization > 100 ? 'danger' : ($totalUtilization > 80 ? 'warning' : 'success');
                            @endphp
                            <span class="badge badge-light-{{ $totalStatusClass }}">
                                {{ number_format($totalUtilization, 1) }}% Used
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-{{ $totalStatusClass }}" 
                                     style="width: {{ min($totalUtilization, 100) }}%;">
                                </div>
                            </div>
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<!-- Budget Status Summary -->
<div class="row g-5 g-xl-10 mt-5">
    <div class="col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Budget Status Summary</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light-success rounded">
                            <div class="fs-1 fw-bold text-success">{{ $summary['under_budget_count'] ?? 0 }}</div>
                            <div class="text-muted fs-7">Under Budget</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light-danger rounded">
                            <div class="fs-1 fw-bold text-danger">{{ $summary['over_budget_count'] ?? 0 }}</div>
                            <div class="text-muted fs-7">Over Budget</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light-info rounded">
                            <div class="fs-1 fw-bold text-info">{{ $summary['on_budget_count'] ?? 0 }}</div>
                            <div class="text-muted fs-7">On Budget</div>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-semibold">Annual Budget</span>
                        <span class="fw-bold">{{ $summary['total_budget_annual_display'] ?? 'UGX 0' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-semibold">Annual Actual</span>
                        <span class="fw-bold">{{ $summary['total_actual_annual_display'] ?? 'UGX 0' }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold">Annual Variance</span>
                        <span class="fw-bold text-{{ ($summary['total_variance_annual'] ?? 0) > 0 ? 'success' : (($summary['total_variance_annual'] ?? 0) < 0 ? 'danger' : 'secondary') }}">
                            {{ $summary['total_variance_annual_display'] ?? 'UGX 0' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Budget vs Actual Chart</h3>
            </div>
            <div class="card-body">
                @if(count($budgetData) > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Category</th>
                                    <th class="text-center">Budget</th>
                                    <th class="text-center">Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $topItems = array_slice($budgetData, 0, 10); @endphp
                                @foreach($topItems as $item)
                                    @php
                                        $budget = $item['budget_monthly'];
                                        $actual = $item['actual_monthly'];
                                        $maxValue = max($budget, $actual, 1);
                                    @endphp
                                    <tr>
                                        <td>{{ $item['category']->name }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="text-muted fs-7 text-end" style="min-width: 60px;">{{ $baseCurrency->formatAmount($budget) }}</span>
                                                <div class="progress w-100" style="height: 12px;">
                                                    <div class="progress-bar bg-primary" style="width: {{ ($budget / $maxValue) * 100 }}%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress w-100" style="height: 12px;">
                                                    <div class="progress-bar bg-success" style="width: {{ ($actual / $maxValue) * 100 }}%;"></div>
                                                </div>
                                                <span class="text-muted fs-7 text-start" style="min-width: 60px;">{{ $baseCurrency->formatAmount($actual) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No data available for chart</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Export Button -->
<div class="row g-5 g-xl-10 mt-3">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.expense-reports.export', ['type' => 'budget']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
                        <i class="ki-duotone ki-file-down fs-2 me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection