@extends('layouts.admin')

@section('title', 'Jobs Timeline Report')
@section('page_title', 'Jobs Timeline Report')

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
    <li class="breadcrumb-item text-muted">Timeline</li>
@endsection

@section('content')
@can('view job trends')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.jobs-reports.timeline') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">Period</label>
                <select name="period" class="form-select form-select-solid" id="periodSelect" onchange="this.form.submit()">
                    @foreach($periods as $key => $label)
                        <option value="{{ $key }}" {{ ($period ?? 'weekly') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $startDate ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $endDate ?? '' }}">
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
                <label class="fw-semibold fs-7 mb-1">Poster</label>
                <select name="poster_id" class="form-select form-select-solid" data-control="select2">
                    <option value="">All Posters</option>
                    @foreach($posters as $poster)
                        <option value="{{ $poster->id }}" {{ ($posterId ?? '') == $poster->id ? 'selected' : '' }}>
                            {{ $poster->name }}
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
            <div class="col-12 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="ki-duotone ki-filter fs-2 me-1"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6">
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $summary['total_jobs'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6">
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['total_views'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6">
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['total_applications'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-warning me-2">
                        <i class="ki-duotone ki-chart fs-2 text-warning">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Peak Jobs</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $summary['peak_count'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-danger me-2">
                        <i class="ki-duotone ki-star fs-2 text-danger">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Avg Jobs/Period</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['avg_count'] ?? 0, 1) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-primary me-2">
                        <i class="ki-duotone ki-calendar fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Date Range</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.6rem, 1.5vw, 1rem);">
                            {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js - Jobs Distribution Bar Chart -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Jobs Distribution Over Time</h3>
                <div class="card-toolbar">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-light-primary">Total Jobs: {{ array_sum($countData) }}</span>
                        <span class="badge badge-light-info">Peak: {{ $summary['peak_count'] ?? 0 }}</span>
                        <span class="badge badge-light-success">Avg: {{ number_format($summary['avg_count'] ?? 0, 1) }}</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="jobsTimelineChart" style="width: 100%; height: 400px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Timeline Data Table</h3>
            </div>
            <div class="card-body">
                @if(count($labels) > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Period</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">% of Total</th>
                                    <th>Distribution</th>
                                    <th class="text-center">Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $totalJobs = array_sum($countData);
                                    $maxCount = max($countData) ?: 1;
                                @endphp
                                @foreach($labels as $index => $label)
                                    @php
                                        $count = $countData[$index] ?? 0;
                                        $percentage = $totalJobs > 0 ? ($count / $totalJobs) * 100 : 0;
                                        $barWidth = ($count / $maxCount) * 100;
                                        $trendValue = $trend[$index] ?? 0;
                                        $trendColor = $trendValue > 0 ? 'text-success' : ($trendValue < 0 ? 'text-danger' : 'text-muted');
                                        $trendIcon = $trendValue > 0 ? '↑' : ($trendValue < 0 ? '↓' : '→');
                                    @endphp
                                    <tr>
                                        <td><span class="fw-bold">{{ $label }}</span></td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $count }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format($percentage, 1) }}%</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="progress w-100" style="height: 12px;">
                                                    <div class="progress-bar bg-primary" style="width: {{ $barWidth }}%;"></div>
                                                </div>
                                                <span class="text-muted fs-7">{{ $count }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($index > 0)
                                                <span class="{{ $trendColor }} fw-bold">
                                                    {{ $trendIcon }} {{ number_format(abs($trendValue), 1) }}%
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $totalJobs }}</td>
                                    <td class="text-end">100%</td>
                                    <td></td>
                                    <td class="text-center"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No data available for the selected period</div>
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
                    <a href="{{ route('admin.jobs-reports.export', ['type' => 'timeline']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
                        <i class="ki-duotone ki-file-down fs-2 me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data from PHP
    const labels = @json($labels);
    const countData = @json($countData);
    const totalJobs = countData.reduce((a, b) => a + b, 0);
    
    // Chart 1: Jobs Timeline - Bar Chart
    const ctx1 = document.getElementById('jobsTimelineChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Jobs Posted',
                    data: countData,
                    backgroundColor: 'rgba(0, 158, 247, 0.75)',
                    borderColor: '#009ef7',
                    borderWidth: 2,
                    borderRadius: 6,
                    barPercentage: 0.7,
                    categoryPercentage: 0.85
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 13,
                            weight: '600'
                        },
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'rectRounded'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.raw || 0;
                            let percentage = totalJobs > 0 ? ((value / totalJobs) * 100).toFixed(1) : 0;
                            return 'Jobs: ' + value.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0,
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        },
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush