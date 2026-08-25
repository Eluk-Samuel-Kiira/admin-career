@extends('layouts.admin')

@section('title', 'Job Performance Report')
@section('page_title', 'Job Performance Report')

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
    <li class="breadcrumb-item text-muted">Performance</li>
@endsection

@section('content')
@can('view job performance')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.jobs-reports.performance') }}" class="row g-3 align-items-end">
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
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Avg Views/Job</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['avg_views'] ?? 0, 1) }}</span>
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
                        <i class="ki-duotone ki-star fs-2 text-info">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Avg SEO Score</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['avg_seo_score'] ?? 0, 1) }}%</span>
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
                        <i class="ki-duotone ki-check-circle fs-2 text-success">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Avg Content Score</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['avg_content_score'] ?? 0, 1) }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performance by Category -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Performance by Category</h3>
            </div>
            <div class="card-body">
                @if($performanceByCategory->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Category</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">Total Views</th>
                                    <th class="text-end">Total Applications</th>
                                    <th class="text-end">Avg Views</th>
                                    <th class="text-end">Avg Applications</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($performanceByCategory as $item)
                                    <tr>
                                        <td>{{ $item['category_name'] }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $item['count'] }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format($item['total_views']) }}</td>
                                        <td class="text-end">{{ number_format($item['total_applications']) }}</td>
                                        <td class="text-end">{{ number_format($item['avg_views'], 1) }}</td>
                                        <td class="text-end">{{ number_format($item['avg_applications'], 1) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $performanceByCategory->sum('count') }}</td>
                                    <td class="text-end">{{ number_format($performanceByCategory->sum('total_views')) }}</td>
                                    <td class="text-end">{{ number_format($performanceByCategory->sum('total_applications')) }}</td>
                                    <td class="text-end"></td>
                                    <td class="text-end"></td>
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

<!-- Top Performing Jobs -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Top 10 Performing Jobs</h3>
            </div>
            <div class="card-body">
                @if($topJobs->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>#</th>
                                    <th>Job Title</th>
                                    <th>Company</th>
                                    <th>Category</th>
                                    <th class="text-center">Views</th>
                                    <th class="text-center">Applications</th>
                                    <th class="text-center">CTR</th>
                                    <th class="text-center">SEO Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topJobs as $index => $job)
                                    @php
                                        $seoScore = $job->seo_score ?? 0;
                                        $seoTextClass = $seoScore >= 70 ? 'text-success' : ($seoScore >= 50 ? 'text-warning' : 'text-danger');
                                        $seoBarClass = $seoScore >= 70 ? 'bg-success' : ($seoScore >= 50 ? 'bg-warning' : 'bg-danger');
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <span class="fw-bold">{{ Str::limit($job->job_title, 40) }}</span>
                                        </td>
                                        <td>{{ $job->company?->name ?? 'N/A' }}</td>
                                        <td>{{ $job->jobCategory?->name ?? 'N/A' }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-light-info">{{ number_format($job->view_count) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-success">{{ number_format($job->application_count) }}</span>
                                        </td>
                                        <td class="text-center">{{ number_format($job->click_through_rate ?? 0, 1) }}%</td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center gap-2 justify-content-center">
                                                <span class="{{ $seoTextClass }}">
                                                    {{ number_format($seoScore, 1) }}%
                                                </span>
                                                <div class="progress w-50" style="height: 6px;">
                                                    <div class="progress-bar {{ $seoBarClass }}" 
                                                         style="width: {{ $seoScore }}%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No jobs found</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Jobs List with Pagination -->
<div class="row g-5 g-xl-10">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Jobs Performance List</h3>
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
                                <th>Job Title</th>
                                <th>Company</th>
                                <th class="text-center">Views</th>
                                <th class="text-center">Applications</th>
                                <th class="text-center">Clicks</th>
                                <th class="text-center">CTR</th>
                                <th class="text-center">SEO Score</th>
                                <th class="text-center">Content Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jobs as $job)
                                @php
                                    $seoScore = $job->seo_score ?? 0;
                                    $contentScore = $job->content_quality_score ?? 0;
                                    $seoClass = $seoScore >= 70 ? 'text-success' : ($seoScore >= 50 ? 'text-warning' : 'text-danger');
                                    $contentClass = $contentScore >= 70 ? 'text-success' : ($contentScore >= 50 ? 'text-warning' : 'text-danger');
                                @endphp
                                <tr>
                                    <td>
                                        <span class="fw-bold">{{ Str::limit($job->job_title, 40) }}</span>
                                    </td>
                                    <td>{{ $job->company?->name ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-light-info">{{ number_format($job->view_count) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-light-success">{{ number_format($job->application_count) }}</span>
                                    </td>
                                    <td class="text-center">{{ number_format($job->click_count) }}</td>
                                    <td class="text-center">{{ number_format($job->click_through_rate ?? 0, 1) }}%</td>
                                    <td class="text-center">
                                        <span class="{{ $seoClass }}">
                                            {{ number_format($seoScore, 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="{{ $contentClass }}">
                                            {{ number_format($contentScore, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">No jobs found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-5">
                    <div class="text-muted fs-7">
                        Showing {{ $jobs->firstItem() ?? 0 }} to {{ $jobs->lastItem() ?? 0 }} of {{ $jobs->total() }} entries
                    </div>
                    <div>
                        {{ $jobs->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                    </div>
                </div>
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
                    <a href="{{ route('admin.jobs-reports.export', ['type' => 'performance']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
                        <i class="ki-duotone ki-file-down fs-2 me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection