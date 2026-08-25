@extends('layouts.admin')

@section('title', 'Jobs by Country Report')
@section('page_title', 'Jobs by Country Report')

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
    <li class="breadcrumb-item text-muted">By Country</li>
@endsection

@section('content')
@can('view job country report')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.jobs-reports.country') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $startDate ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $endDate ?? '' }}">
            </div>
            <div class="col-md-3">
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
                        <i class="ki-duotone ki-global fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Countries</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $countryBreakdown->total() }}</span>
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
                        <i class="ki-duotone ki-eye fs-2 text-info">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Views</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($countryBreakdown->sum('total_views')) }}</span>
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
                        <i class="ki-duotone ki-profile-user fs-2 text-success">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Applications</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($countryBreakdown->sum('total_applications')) }}</span>
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
                        <i class="ki-duotone ki-briefcase fs-2 text-warning">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Jobs</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $countryBreakdown->sum('job_count') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Country Breakdown Table -->
<div class="row g-5 g-xl-10">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Country Breakdown</h3>
                <div class="card-toolbar">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted fs-7">Per Page:</span>
                        <select class="form-select form-select-sm w-70px" onchange="window.location.href=this.value">
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 10, 'page' => 1]) }}" {{ ($perPage ?? 20) == 10 ? 'selected' : '' }}>10</option>
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 20, 'page' => 1]) }}" {{ ($perPage ?? 20) == 20 ? 'selected' : '' }}>20</option>
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 50, 'page' => 1]) }}" {{ ($perPage ?? 20) == 50 ? 'selected' : '' }}>50</option>
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 100, 'page' => 1]) }}" {{ ($perPage ?? 20) == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-3">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th>#</th>
                                <th>Country</th>
                                <th class="text-center">Jobs</th>
                                <th class="text-end">Total Views</th>
                                <th class="text-end">Total Applications</th>
                                <th class="text-end">Avg Views</th>
                                <th class="text-end">Avg Applications</th>
                                <th class="text-end">Max Views</th>
                                <th class="text-end">Max Applications</th>
                                <th class="text-center">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalJobs = $countryBreakdown->sum('job_count'); @endphp
                            @forelse($countryBreakdown as $index => $item)
                                @php
                                    $percentage = $totalJobs > 0 ? ($item->job_count / $totalJobs) * 100 : 0;
                                    $countryName = $countries[$item->country_code] ?? $item->country_code;
                                    $flag = match($item->country_code) {
                                        'AU' => '🇦🇺',
                                        'UG' => '🇺🇬',
                                        'KE' => '🇰🇪',
                                        'TZ' => '🇹🇿',
                                        'RW' => '🇷🇼',
                                        'MW' => '🇲🇼',
                                        'ZM' => '🇿🇲',
                                        'SG' => '🇸🇬',
                                        default => '🌍'
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $countryBreakdown->firstItem() + $index }}</td>
                                    <td>
                                        <span class="fw-bold">{{ $flag }} {{ $countryName }}</span>
                                        <div class="text-muted fs-8">{{ $item->country_code }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-light-primary">{{ $item->job_count }}</span>
                                    </td>
                                    <td class="text-end">{{ number_format($item->total_views) }}</td>
                                    <td class="text-end">{{ number_format($item->total_applications) }}</td>
                                    <td class="text-end">{{ number_format($item->avg_views, 1) }}</td>
                                    <td class="text-end">{{ number_format($item->avg_applications, 1) }}</td>
                                    <td class="text-end">{{ number_format($item->max_views) }}</td>
                                    <td class="text-end">{{ number_format($item->max_applications) }}</td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center gap-2 justify-content-center">
                                            <span>{{ number_format($percentage, 1) }}%</span>
                                            <div class="progress w-50" style="height: 6px;">
                                                <div class="progress-bar bg-primary" style="width: {{ $percentage }}%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-5">No countries found</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="2">Total</td>
                                <td class="text-center">{{ $totalJobs }}</td>
                                <td class="text-end">{{ number_format($countryBreakdown->sum('total_views')) }}</td>
                                <td class="text-end">{{ number_format($countryBreakdown->sum('total_applications')) }}</td>
                                <td class="text-end"></td>
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
                        Showing {{ $countryBreakdown->firstItem() ?? 0 }} to {{ $countryBreakdown->lastItem() ?? 0 }} of {{ $countryBreakdown->total() }} entries
                    </div>
                    <div>
                        {{ $countryBreakdown->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Country Summary Chart -->
<div class="row g-5 g-xl-10 mt-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Country Summary</h3>
            </div>
            <div class="card-body">
                @if($countryBreakdown->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Country</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">Views/Job</th>
                                    <th class="text-end">Applications/Job</th>
                                    <th class="text-end">Application Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($countryBreakdown as $item)
                                    @php
                                        $viewsPerJob = $item->job_count > 0 ? $item->total_views / $item->job_count : 0;
                                        $appsPerJob = $item->job_count > 0 ? $item->total_applications / $item->job_count : 0;
                                        $appRate = $item->total_views > 0 ? ($item->total_applications / $item->total_views) * 100 : 0;
                                        $countryName = $countries[$item->country_code] ?? $item->country_code;
                                        $flag = match($item->country_code) {
                                            'AU' => '🇦🇺',
                                            'UG' => '🇺🇬',
                                            'KE' => '🇰🇪',
                                            'TZ' => '🇹🇿',
                                            'RW' => '🇷🇼',
                                            'MW' => '🇲🇼',
                                            'ZM' => '🇿🇲',
                                            'SG' => '🇸🇬',
                                            default => '🌍'
                                        };
                                    @endphp
                                    <tr>
                                        <td><span class="fw-bold">{{ $flag }} {{ $countryName }}</span></td>
                                        <td class="text-center">{{ $item->job_count }}</td>
                                        <td class="text-end">{{ number_format($viewsPerJob, 1) }}</td>
                                        <td class="text-end">{{ number_format($appsPerJob, 1) }}</td>
                                        <td class="text-end">
                                            <span class="{{ $appRate > 5 ? 'text-success' : ($appRate > 2 ? 'text-warning' : 'text-danger') }}">
                                                {{ number_format($appRate, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
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
                    <a href="{{ route('admin.jobs-reports.export', ['type' => 'country']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
                        <i class="ki-duotone ki-file-down fs-2 me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection