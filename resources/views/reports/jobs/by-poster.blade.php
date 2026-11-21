@extends('layouts.admin')

@section('title', 'Jobs by Poster Report')
@section('page_title', 'Jobs by Poster Report')

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
    <li class="breadcrumb-item text-muted">By Poster</li>
@endsection

@section('content')
@can('view job poster report')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.jobs-reports.poster') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $startDate ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $endDate ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">Poster</label>
                <select name="poster_id" class="form-select form-select-solid" data-control="select2">
                    <option value="">All Posters</option>
                    @foreach($posters as $poster)
                        <option value="{{ $poster->id }}" {{ ($posterId ?? '') == $poster->id ? 'selected' : '' }}>
                            {{ $poster->name }} ({{ $poster->email }})
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
                        <i class="ki-duotone ki-user fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Posters</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $posterBreakdown->total() }}</span>
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($posterBreakdown->sum('total_views')) }}</span>
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($posterBreakdown->sum('total_applications')) }}</span>
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
                        <i class="ki-duotone ki-briefcase fs-2 text-warning">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Jobs</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $posterBreakdown->sum('job_count') }}</span>
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
                        <i class="ki-duotone ki-dollar fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Earnings</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">UGX {{ number_format($posterBreakdown->sum('job_count') * 100) }}</span>
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
                        <i class="ki-duotone ki-chart fs-2 text-danger">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Avg Earnings/Poster</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">
                            UGX {{ $posterBreakdown->total() > 0 ? number_format(($posterBreakdown->sum('job_count') * 100) / $posterBreakdown->total()) : 0 }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Poster Activity Line Chart - Multi-Poster -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Poster Activity Over Time (Hourly)</h3>
                <div class="card-toolbar">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-light-primary" id="totalPostsBadge">Total: --</span>
                        <span class="badge badge-light-success" id="posterCountBadge">Posters: --</span>
                        <span class="badge badge-light-info" id="dateRangeBadge">Range: --</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="posterActivityContainer">
                    <div class="text-center py-5" id="activityLoading">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Loading activity data...</p>
                    </div>
                    <canvas id="posterActivityChart" style="width: 100%; height: 400px; display: none;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hourly Distribution Heatmap -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">24-Hour Posting Pattern</h3>
                <div class="card-toolbar">
                    <span class="badge badge-light-info">Shows when posters are most active</span>
                </div>
            </div>
            <div class="card-body">
                <div id="hourlyDistributionContainer">
                    <canvas id="hourlyDistributionChart" style="width: 100%; height: 200px; display: none;"></canvas>
                    <div class="text-center py-5 text-muted" id="hourlyLoading">Loading hourly data...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Earnings Bar Chart -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Poster Performance & Earnings</h3>
            </div>
            <div class="card-body">
                @if($posterBreakdown->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Poster</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">Views</th>
                                    <th class="text-end">Applications</th>
                                    <th class="text-end">Earnings (UGX)</th>
                                    <th>Performance Bar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $maxJobs = $posterBreakdown->max('job_count') ?: 1;
                                @endphp
                                @foreach($posterBreakdown as $item)
                                    @php
                                        $percentage = ($item->job_count / $maxJobs) * 100;
                                        $earnings = $item->job_count * 100;
                                        $barColor = $percentage >= 80 ? 'bg-success' : ($percentage >= 50 ? 'bg-primary' : ($percentage >= 30 ? 'bg-warning' : 'bg-danger'));
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $item->poster_name }}</span>
                                            <div class="text-muted fs-8">{{ $item->poster_email }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $item->job_count }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format($item->total_views) }}</td>
                                        <td class="text-end">{{ number_format($item->total_applications) }}</td>
                                        <td class="text-end fw-bold text-success">UGX {{ number_format($earnings) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="progress w-100" style="height: 10px;">
                                                    <div class="progress-bar {{ $barColor }}" style="width: {{ $percentage }}%;"></div>
                                                </div>
                                                <span class="text-muted fs-7">{{ number_format($percentage, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $posterBreakdown->sum('job_count') }}</td>
                                    <td class="text-end">{{ number_format($posterBreakdown->sum('total_views')) }}</td>
                                    <td class="text-end">{{ number_format($posterBreakdown->sum('total_applications')) }}</td>
                                    <td class="text-end text-success">UGX {{ number_format($posterBreakdown->sum('job_count') * 100) }}</td>
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

<!-- Visual Bar Chart Summary -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Top Posters Summary</h3>
            </div>
            <div class="card-body">
                @if($posterBreakdown->count() > 0)
                    @php 
                        $topPosters = $posterBreakdown->take(10);
                        $maxEarnings = $topPosters->max('job_count') * 100 ?: 1;
                    @endphp
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>#</th>
                                    <th>Poster</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">Earnings (UGX)</th>
                                    <th>Earnings Bar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topPosters as $index => $item)
                                    @php
                                        $earnings = $item->job_count * 100;
                                        $percentage = ($earnings / $maxEarnings) * 100;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-primary">{{ $index + 1 }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $item->poster_name }}</span>
                                            <div class="text-muted fs-8">{{ $item->poster_email }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $item->job_count }}</span>
                                        </td>
                                        <td class="text-end fw-bold text-success">UGX {{ number_format($earnings) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="progress w-100" style="height: 12px;">
                                                    <div class="progress-bar bg-success" style="width: {{ $percentage }}%;"></div>
                                                </div>
                                                <span class="text-muted fs-7">{{ number_format($percentage, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="2">Total</td>
                                    <td class="text-center">{{ $topPosters->sum('job_count') }}</td>
                                    <td class="text-end text-success">UGX {{ number_format($topPosters->sum('job_count') * 100) }}</td>
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

<!-- Poster Breakdown Table -->
<div class="row g-5 g-xl-10">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Poster Breakdown</h3>
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
                                <th>Poster</th>
                                <th>Email</th>
                                <th class="text-center">Jobs</th>
                                <th class="text-end">Views</th>
                                <th class="text-end">Applications</th>
                                <th class="text-end">Avg Views</th>
                                <th class="text-end">Avg Applications</th>
                                <th class="text-end">Earnings (UGX)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($posterBreakdown as $index => $item)
                                @php $earnings = $item->job_count * 100; @endphp
                                <tr>
                                    <td>{{ $posterBreakdown->firstItem() + $index }}</td>
                                    <td>
                                        <span class="fw-bold">{{ $item->poster_name }}</span>
                                    </td>
                                    <td>{{ $item->poster_email }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-light-primary">{{ $item->job_count }}</span>
                                    </td>
                                    <td class="text-end">{{ number_format($item->total_views) }}</td>
                                    <td class="text-end">{{ number_format($item->total_applications) }}</td>
                                    <td class="text-end">{{ number_format($item->avg_views, 1) }}</td>
                                    <td class="text-end">{{ number_format($item->avg_applications, 1) }}</td>
                                    <td class="text-end fw-bold text-success">UGX {{ number_format($earnings) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-5">No posters found</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="4">Total</td>
                                <td class="text-center">{{ $posterBreakdown->sum('job_count') }}</td>
                                <td class="text-end">{{ number_format($posterBreakdown->sum('total_views')) }}</td>
                                <td class="text-end">{{ number_format($posterBreakdown->sum('total_applications')) }}</td>
                                <td class="text-end"></td>
                                <td class="text-end"></td>
                                <td class="text-end text-success">UGX {{ number_format($posterBreakdown->sum('job_count') * 100) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-5">
                    <div class="text-muted fs-7">
                        Showing {{ $posterBreakdown->firstItem() ?? 0 }} to {{ $posterBreakdown->lastItem() ?? 0 }} of {{ $posterBreakdown->total() }} entries
                    </div>
                    <div>
                        {{ $posterBreakdown->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
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
                    <a href="{{ route('admin.jobs-reports.export', ['type' => 'poster']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
                        <i class="ki-duotone ki-file-down fs-2 me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Poster Activity Chart Styles */
    #posterActivityChart {
        max-height: 400px;
    }
    #hourlyDistributionChart {
        max-height: 200px;
    }
</style>



@endcan
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    // ── Guard: Chart.js must be loaded ──────────────────────
    function chartReady() {
        if (typeof Chart === 'undefined') {
            console.error('[by-poster] Chart.js is not loaded. Cannot render charts.');
            return false;
        }
        return true;
    }

    // ── Show a friendly "no data" message in a container ─────
    function showEmpty(containerId, message) {
        const el = document.getElementById(containerId);
        if (!el) return;
        el.innerHTML = `<div class="text-center py-5 text-muted">${message}</div>`;
    }

    // ── Destroy old chart instances so re-runs don't double-draw ──
    let posterChartInstance  = null;
    let hourlyChartInstance  = null;

    function destroyCharts() {
        if (posterChartInstance)  { try { posterChartInstance.destroy();  } catch (e) {} posterChartInstance  = null; }
        if (hourlyChartInstance)  { try { hourlyChartInstance.destroy();  } catch (e) {} hourlyChartInstance  = null; }
    }

    // ═══════════════════════════════════════════════════════════
    // Loader
    // ═══════════════════════════════════════════════════════════
    function loadPosterActivity() {
        const loading       = document.getElementById('activityLoading');
        const hourlyLoading = document.getElementById('hourlyLoading');
        const canvas        = document.getElementById('posterActivityChart');
        const hourlyCanvas  = document.getElementById('hourlyDistributionChart');

        // Show loading, hide canvases
        if (loading)       loading.style.display = 'block';
        if (hourlyLoading) hourlyLoading.style.display = 'block';
        if (canvas)        canvas.style.display = 'none';
        if (hourlyCanvas)  hourlyCanvas.style.display = 'none';

        if (!chartReady()) {
            if (loading)       loading.style.display = 'none';
            if (hourlyLoading) hourlyLoading.style.display = 'none';
            return;
        }

        // Collect form filters
        const params = new URLSearchParams();
        document.querySelectorAll('form select, form input').forEach(field => {
            if (field.name && field.value) params.append(field.name, field.value);
        });

        const url = '{{ route("admin.jobs-reports.poster-activity") }}?' + params.toString();

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(payload => {
                if (loading)       loading.style.display = 'none';
                if (hourlyLoading) hourlyLoading.style.display = 'none';

                // ── Guard: response must have the expected shape ──
                if (!payload || !payload.success || !payload.data) {
                    console.warn('[by-poster] Unexpected response:', payload);
                    showEmpty('posterActivityContainer', 'No activity data available for the selected filters.');
                    showEmpty('hourlyDistributionContainer', 'No hourly data available.');
                    return;
                }

                const data = payload.data;
                const labels = Array.isArray(data.labels) ? data.labels : [];
                const posterDatasets = Array.isArray(data.poster_datasets) ? data.poster_datasets : [];
                const hourlyDistribution = Array.isArray(data.hourly_distribution) ? data.hourly_distribution : [];

                // ── Empty state ──
                if (labels.length === 0 || posterDatasets.length === 0) {
                    showEmpty('posterActivityContainer', 'No activity data available for the selected filters.');
                    showEmpty('hourlyDistributionContainer', 'No hourly data available.');
                    return;
                }

                // ── Update badges ──
                const setText = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
                setText('totalPostsBadge', 'Total: ' + (data.total_posts ?? 0) + ' posts');
                setText('dateRangeBadge', 'Range: ' + labels.length + ' hours');
                setText('posterCountBadge', 'Posters: ' + (data.poster_count ?? 0));

                // ── Show canvases ──
                if (canvas)       canvas.style.display = 'block';
                if (hourlyCanvas) hourlyCanvas.style.display = 'block';

                // ── Destroy old instances ──
                destroyCharts();

                // ═════════════════════════════════════════════════════
                // Chart 1 — Multi-poster line chart
                // ═════════════════════════════════════════════════════
                const colors = [
                    '#009ef7', '#50cd89', '#f1416c', '#ffc700', '#7239ea',
                    '#f6b100', '#1c4f8b', '#e37b3c', '#6f4e37', '#2e8b57',
                    '#4169e1', '#dc143c', '#ff8c00', '#8b008b', '#2f4f4f',
                ];

                const datasets = posterDatasets.map((poster, i) => {
                    const color = colors[i % colors.length];
                    return {
                        label: poster.name || ('Poster ' + (i + 1)),
                        data: Array.isArray(poster.data) ? poster.data : [],
                        borderColor: color,
                        backgroundColor: color + '20',
                        borderWidth: 2.5,
                        fill: false,
                        tension: 0.3,
                        pointBackgroundColor: color,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        spanGaps: true,
                    };
                });

                try {
                    posterChartInstance = new Chart(canvas.getContext('2d'), {
                        type: 'line',
                        data: { labels, datasets },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: { font: { size: 11, weight: '600' }, padding: 15, usePointStyle: true },
                                },
                                tooltip: {
                                    callbacks: {
                                        title: (items) => 'Hour: ' + items[0].label,
                                        label: (ctx) => {
                                            const v = ctx.raw || 0;
                                            return `${ctx.dataset.label}: ${v} job${v !== 1 ? 's' : ''}`;
                                        },
                                        footer: (items) => {
                                            const total = items.reduce((sum, i) => sum + (i.raw || 0), 0);
                                            return 'Total: ' + total + ' jobs';
                                        },
                                    },
                                },
                            },
                            scales: {
                                x: { grid: { display: false }, ticks: { maxRotation: 45, font: { size: 9 } } },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(0,0,0,0.05)' },
                                    ticks: { stepSize: 1, font: { size: 10 } },
                                    title: { display: true, text: 'Jobs Posted', font: { size: 11, weight: '600' } },
                                },
                            },
                        },
                    });
                } catch (e) {
                    console.error('[by-poster] Failed to render poster line chart:', e);
                    showEmpty('posterActivityContainer', 'Could not render the activity chart. See console for details.');
                }

                // ═════════════════════════════════════════════════════
                // Chart 2 — Hourly distribution bar chart
                // ═════════════════════════════════════════════════════
                if (hourlyDistribution.length > 0) {
                    const hourLabels = hourlyDistribution.map(h => h.label || h.hour);
                    const hourCounts = hourlyDistribution.map(h => h.count || 0);

                    try {
                        hourlyChartInstance = new Chart(hourlyCanvas.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: hourLabels,
                                datasets: [{
                                    label: 'Total Posts',
                                    data: hourCounts,
                                    backgroundColor: hourCounts.map(c => c > 0 ? 'rgba(0,158,247,0.7)' : 'rgba(228,230,239,0.5)'),
                                    borderColor: hourCounts.map(c => c > 0 ? '#009ef7' : '#e4e6ef'),
                                    borderWidth: 2,
                                    borderRadius: 4,
                                    barPercentage: 0.8,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: (ctx) => `${ctx.label}: ${ctx.raw || 0} posts`,
                                        },
                                    },
                                },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 9 } } },
                                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { stepSize: 1, font: { size: 9 } } },
                                },
                            },
                        });
                    } catch (e) {
                        console.error('[by-poster] Failed to render hourly bar chart:', e);
                        showEmpty('hourlyDistributionContainer', 'Could not render the hourly chart. See console for details.');
                    }
                }
            })
            .catch(err => {
                console.error('[by-poster] Activity load failed:', err);
                if (loading)       loading.style.display = 'none';
                if (hourlyLoading) hourlyLoading.style.display = 'none';
                showEmpty('posterActivityContainer', 'Failed to load activity data: ' + err.message);
                showEmpty('hourlyDistributionContainer', 'Failed to load hourly data.');
            });
    }

    // ── Run on page load ──
    document.addEventListener('DOMContentLoaded', loadPosterActivity);

    // ── Re-run after filter form submit (page reloads anyway, but handle SPA-style if you move to that later) ──
    document.querySelector('form')?.addEventListener('submit', function () {
        // The form is a normal GET submit — the page will reload. Nothing to do.
    });

    // Expose for manual re-run
    window.reloadPosterActivity = loadPosterActivity;

})();
</script>
@endpush