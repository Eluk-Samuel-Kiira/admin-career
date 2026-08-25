@extends('layouts.admin')

@section('title', 'Jobs Trends Report')
@section('page_title', 'Jobs Trends Report')

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
        <a href="{{ route('admin.jobs-reports') }}" class="text-muted text-hover-primary">Jobs Reports</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Trends</li>
@endsection

@section('content')
@can('view job trends')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.jobs-reports.trends') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">Period</label>
                <select name="period" class="form-select form-select-solid">
                    <option value="monthly" {{ ($period ?? 'monthly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="quarterly" {{ ($period ?? 'monthly') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                    <option value="yearly" {{ ($period ?? 'monthly') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">Year</label>
                <select name="year" class="form-select form-select-solid">
                    @foreach($years as $yr)
                        <option value="{{ $yr }}" {{ ($year ?? date('Y')) == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">Category</label>
                <select name="category_id" class="form-select form-select-solid" data-control="select2">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ ($categoryId ?? '') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">Company</label>
                <select name="company_id" class="form-select form-select-solid" data-control="select2">
                    <option value="">All Companies</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ ($companyId ?? '') == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">Country</label>
                <select name="country_code" class="form-select form-select-solid" data-control="select2">
                    <option value="">All Countries</option>
                    @foreach($countries as $code => $name)
                        <option value="{{ $code }}" {{ ($countryCode ?? '') == $code ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
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
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-primary me-2">
                        <i class="ki-duotone ki-briefcase fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Jobs</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ collect($trendData)->sum('count') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-info me-2">
                        <i class="ki-duotone ki-eye fs-2 text-info">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Views</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format(collect($trendData)->sum('views')) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-success me-2">
                        <i class="ki-duotone ki-profile-user fs-2 text-success">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Applications</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format(collect($trendData)->sum('applications')) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Trends Table -->
<div class="row g-5 g-xl-10">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">
                    @if($period === 'monthly')
                        Monthly Trends ({{ $year }})
                    @elseif($period === 'quarterly')
                        Quarterly Trends ({{ $year }})
                    @else
                        Yearly Trends
                    @endif
                </h3>
            </div>
            <div class="card-body">
                @if(count($trendData) > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>
                                        @if($period === 'monthly')
                                            Month
                                        @elseif($period === 'quarterly')
                                            Quarter
                                        @else
                                            Year
                                        @endif
                                    </th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">Views</th>
                                    <th class="text-end">Applications</th>
                                    <th class="text-end">Views/Job</th>
                                    <th class="text-end">Applications/Job</th>
                                    <th class="text-center">% Change (Jobs)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $previousCount = 0;
                                    $totalJobs = collect($trendData)->sum('count');
                                @endphp
                                @foreach($trendData as $key => $item)
                                    @php
                                        $currentCount = $item['count'];
                                        $percentageChange = $previousCount > 0 ? (($currentCount - $previousCount) / $previousCount) * 100 : 0;
                                        $changeColor = $percentageChange > 0 ? 'text-success' : ($percentageChange < 0 ? 'text-danger' : 'text-muted');
                                        $changeIcon = $percentageChange > 0 ? '↑' : ($percentageChange < 0 ? '↓' : '→');
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-bold">
                                                @if($period === 'monthly')
                                                    {{ $item['month_name'] }}
                                                @elseif($period === 'quarterly')
                                                    {{ $item['quarter_label'] }}
                                                @else
                                                    {{ $item['year'] }}
                                                @endif
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $currentCount }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format($item['views']) }}</td>
                                        <td class="text-end">{{ number_format($item['applications']) }}</td>
                                        <td class="text-end">{{ $currentCount > 0 ? number_format($item['views'] / $currentCount, 1) : 0 }}</td>
                                        <td class="text-end">{{ $currentCount > 0 ? number_format($item['applications'] / $currentCount, 1) : 0 }}</td>
                                        <td class="text-center">
                                            @if($previousCount > 0)
                                                <span class="{{ $changeColor }} fw-bold">
                                                    {{ $changeIcon }} {{ number_format(abs($percentageChange), 1) }}%
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @php $previousCount = $currentCount; @endphp
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $totalJobs }}</td>
                                    <td class="text-end">{{ number_format(collect($trendData)->sum('views')) }}</td>
                                    <td class="text-end">{{ number_format(collect($trendData)->sum('applications')) }}</td>
                                    <td class="text-end">{{ $totalJobs > 0 ? number_format(collect($trendData)->sum('views') / $totalJobs, 1) : 0 }}</td>
                                    <td class="text-end">{{ $totalJobs > 0 ? number_format(collect($trendData)->sum('applications') / $totalJobs, 1) : 0 }}</td>
                                    <td class="text-center"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No trend data available</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Trend Chart - Jobs -->
<div class="row g-5 g-xl-10 mt-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Job Postings Trend</h3>
            </div>
            <div class="card-body">
                @if(count($trendData) > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>
                                        @if($period === 'monthly')
                                            Month
                                        @elseif($period === 'quarterly')
                                            Quarter
                                        @else
                                            Year
                                        @endif
                                    </th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-center">% of Total</th>
                                    <th>Distribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalJobs = collect($trendData)->sum('count'); @endphp
                                @foreach($trendData as $item)
                                    @php
                                        $percentage = $totalJobs > 0 ? ($item['count'] / $totalJobs) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-bold">
                                                @if($period === 'monthly')
                                                    {{ $item['month_name'] }}
                                                @elseif($period === 'quarterly')
                                                    {{ $item['quarter_label'] }}
                                                @else
                                                    {{ $item['year'] }}
                                                @endif
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $item['count'] }}</span>
                                        </td>
                                        <td class="text-center">{{ number_format($percentage, 1) }}%</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="progress w-100" style="height: 8px;">
                                                    <div class="progress-bar bg-primary" style="width: {{ $percentage }}%;"></div>
                                                </div>
                                                <span class="text-muted fs-7">{{ $item['count'] }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $totalJobs }}</td>
                                    <td class="text-center">100%</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No data available</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Views & Applications Trend -->
<div class="row g-5 g-xl-10 mt-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Views & Applications Trend</h3>
            </div>
            <div class="card-body">
                @if(count($trendData) > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>
                                        @if($period === 'monthly')
                                            Month
                                        @elseif($period === 'quarterly')
                                            Quarter
                                        @else
                                            Year
                                        @endif
                                    </th>
                                    <th class="text-end">Views</th>
                                    <th class="text-end">Applications</th>
                                    <th class="text-end">Views per Job</th>
                                    <th class="text-end">Applications per Job</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trendData as $item)
                                    @php
                                        $currentCount = $item['count'] ?? 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-bold">
                                                @if($period === 'monthly')
                                                    {{ $item['month_name'] }}
                                                @elseif($period === 'quarterly')
                                                    {{ $item['quarter_label'] }}
                                                @else
                                                    {{ $item['year'] }}
                                                @endif
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format($item['views']) }}</td>
                                        <td class="text-end">{{ number_format($item['applications']) }}</td>
                                        <td class="text-end">{{ $currentCount > 0 ? number_format($item['views'] / $currentCount, 1) : 0 }}</td>
                                        <td class="text-end">{{ $currentCount > 0 ? number_format($item['applications'] / $currentCount, 1) : 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-end">{{ number_format(collect($trendData)->sum('views')) }}</td>
                                    <td class="text-end">{{ number_format(collect($trendData)->sum('applications')) }}</td>
                                    <td class="text-end">{{ $totalJobs > 0 ? number_format(collect($trendData)->sum('views') / $totalJobs, 1) : 0 }}</td>
                                    <td class="text-end">{{ $totalJobs > 0 ? number_format(collect($trendData)->sum('applications') / $totalJobs, 1) : 0 }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No data available</div>
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
                    <a href="{{ route('admin.jobs-reports.export', ['type' => 'trends']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
                        <i class="ki-duotone ki-file-down fs-2 me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection