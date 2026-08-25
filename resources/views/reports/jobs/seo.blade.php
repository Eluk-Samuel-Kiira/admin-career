@extends('layouts.admin')

@section('title', 'SEO Performance Report')
@section('page_title', 'SEO Performance Report')

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
    <li class="breadcrumb-item text-muted">SEO Report</li>
@endsection

@section('content')
@can('view job seo')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.jobs-reports.seo') }}" class="row g-3 align-items-end">
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
                <label class="fw-semibold fs-7 mb-1">Min SEO Score</label>
                <select name="min_seo_score" class="form-select form-select-solid">
                    <option value="">All Scores</option>
                    <option value="90" {{ ($minSeoScore ?? '') == 90 ? 'selected' : '' }}>90%+</option>
                    <option value="80" {{ ($minSeoScore ?? '') == 80 ? 'selected' : '' }}>80%+</option>
                    <option value="70" {{ ($minSeoScore ?? '') == 70 ? 'selected' : '' }}>70%+</option>
                    <option value="60" {{ ($minSeoScore ?? '') == 60 ? 'selected' : '' }}>60%+</option>
                    <option value="50" {{ ($minSeoScore ?? '') == 50 ? 'selected' : '' }}>50%+</option>
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
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-warning me-2">
                        <i class="ki-duotone ki-eye fs-2 text-warning">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Search Impressions</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['total_search_impressions'] ?? 0) }}</span>
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
                        <i class="ki-duotone ki-click fs-2 text-info">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Search Clicks</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['total_search_clicks'] ?? 0) }}</span>
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
                        <i class="ki-duotone ki-google fs-2 text-danger">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Avg Google Rank</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['avg_google_rank'] ?? 0, 1) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SEO Meta Summary -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">SEO Meta Summary</h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-3">
                        <div class="border rounded p-4 text-center">
                            <span class="text-muted d-block fs-7">Meta Title</span>
                            <span class="fw-bold fs-2 text-primary">{{ $summary['jobs_with_meta_title'] ?? 0 }}</span>
                            <div class="text-muted fs-7">of {{ $summary['total_jobs'] ?? 0 }} jobs</div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-primary" style="width: {{ $summary['total_jobs'] > 0 ? ($summary['jobs_with_meta_title'] / $summary['total_jobs']) * 100 : 0 }}%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 text-center">
                            <span class="text-muted d-block fs-7">Meta Description</span>
                            <span class="fw-bold fs-2 text-success">{{ $summary['jobs_with_meta_description'] ?? 0 }}</span>
                            <div class="text-muted fs-7">of {{ $summary['total_jobs'] ?? 0 }} jobs</div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: {{ $summary['total_jobs'] > 0 ? ($summary['jobs_with_meta_description'] / $summary['total_jobs']) * 100 : 0 }}%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 text-center">
                            <span class="text-muted d-block fs-7">Keywords</span>
                            <span class="fw-bold fs-2 text-warning">{{ $summary['jobs_with_keywords'] ?? 0 }}</span>
                            <div class="text-muted fs-7">of {{ $summary['total_jobs'] ?? 0 }} jobs</div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-warning" style="width: {{ $summary['total_jobs'] > 0 ? ($summary['jobs_with_keywords'] / $summary['total_jobs']) * 100 : 0 }}%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 text-center">
                            <span class="text-muted d-block fs-7">Focus Keyphrase</span>
                            <span class="fw-bold fs-2 text-info">{{ $summary['jobs_with_focus_keyphrase'] ?? 0 }}</span>
                            <div class="text-muted fs-7">of {{ $summary['total_jobs'] ?? 0 }} jobs</div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-info" style="width: {{ $summary['total_jobs'] > 0 ? ($summary['jobs_with_focus_keyphrase'] / $summary['total_jobs']) * 100 : 0 }}%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SEO Score Distribution -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">SEO Score Distribution</h3>
            </div>
            <div class="card-body">
                @php
                    $jobScores = $jobs->pluck('seo_score')->filter();
                    $scoreRanges = [
                        '90-100' => 0,
                        '80-89' => 0,
                        '70-79' => 0,
                        '60-69' => 0,
                        '50-59' => 0,
                        '0-49' => 0,
                    ];
                    foreach ($jobScores as $score) {
                        if ($score >= 90) $scoreRanges['90-100']++;
                        elseif ($score >= 80) $scoreRanges['80-89']++;
                        elseif ($score >= 70) $scoreRanges['70-79']++;
                        elseif ($score >= 60) $scoreRanges['60-69']++;
                        elseif ($score >= 50) $scoreRanges['50-59']++;
                        else $scoreRanges['0-49']++;
                    }
                    $totalJobsWithScore = $jobScores->count();
                @endphp
                @if($totalJobsWithScore > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Score Range</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-center">%</th>
                                    <th>Distribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scoreRanges as $range => $count)
                                    @php
                                        $percentage = $totalJobsWithScore > 0 ? ($count / $totalJobsWithScore) * 100 : 0;
                                        $barClass = match(true) {
                                            $range === '90-100' => 'bg-success',
                                            $range === '80-89' => 'bg-info',
                                            $range === '70-79' => 'bg-primary',
                                            $range === '60-69' => 'bg-warning',
                                            $range === '50-59' => 'bg-orange',
                                            default => 'bg-danger',
                                        };
                                    @endphp
                                    <tr>
                                        <td><span class="fw-bold">{{ $range }}</span></td>
                                        <td class="text-center">{{ $count }}</td>
                                        <td class="text-center">{{ number_format($percentage, 1) }}%</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="progress w-100" style="height: 8px;">
                                                    <div class="progress-bar {{ $barClass }}" style="width: {{ $percentage }}%;"></div>
                                                </div>
                                                <span class="text-muted fs-7">{{ $count }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $totalJobsWithScore }}</td>
                                    <td class="text-center">100%</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No SEO scores available</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Jobs SEO List -->
<div class="row g-5 g-xl-10">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Jobs SEO Details</h3>
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
                                <th class="text-center">SEO Score</th>
                                <th class="text-center">Content Score</th>
                                <th class="text-center">CTR</th>
                                <th>Meta Title</th>
                                <th>Meta Description</th>
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
                                        <span class="fw-bold">{{ Str::limit($job->job_title, 35) }}</span>
                                    </td>
                                    <td>{{ $job->company?->name ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="{{ $seoClass }} fw-bold">{{ number_format($seoScore, 1) }}%</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="{{ $contentClass }} fw-bold">{{ number_format($contentScore, 1) }}%</span>
                                    </td>
                                    <td class="text-center">{{ number_format($job->click_through_rate ?? 0, 1) }}%</td>
                                    <td>
                                        @if($job->meta_title)
                                            <span class="text-success"><i class="ki-duotone ki-check-circle fs-5 text-success"></i></span>
                                            <span class="text-muted fs-8 d-block">{{ Str::limit($job->meta_title, 30) }}</span>
                                        @else
                                            <span class="text-danger"><i class="ki-duotone ki-cross-circle fs-5 text-danger"></i> Missing</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($job->meta_description)
                                            <span class="text-success"><i class="ki-duotone ki-check-circle fs-5 text-success"></i></span>
                                            <span class="text-muted fs-8 d-block">{{ Str::limit($job->meta_description, 30) }}</span>
                                        @else
                                            <span class="text-danger"><i class="ki-duotone ki-cross-circle fs-5 text-danger"></i> Missing</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">No jobs found</td>
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
                    <a href="{{ route('admin.jobs-reports.export', ['type' => 'seo']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
                        <i class="ki-duotone ki-file-down fs-2 me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection