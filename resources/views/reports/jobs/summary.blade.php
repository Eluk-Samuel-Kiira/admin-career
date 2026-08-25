@extends('layouts.admin')

@section('title', 'Jobs Summary Report')
@section('page_title', 'Jobs Summary Report')

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
    <li class="breadcrumb-item text-muted">Summary</li>
@endsection

@section('content')
@can('view job summary')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.jobs-reports.summary') }}" class="row g-3 align-items-end">
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
                <label class="fw-semibold fs-7 mb-1">Status</label>
                <select name="status" class="form-select form-select-solid">
                    <option value="">All Status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ ($status ?? '') == $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
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
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['total_views'] ?? 0) }}</span>
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['total_applications'] ?? 0) }}</span>
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
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Avg Views/Job</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['average_views'] ?? 0, 1) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Summary Cards -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-30px symbol-circle bg-light-success me-2">
                        <i class="ki-duotone ki-check-circle fs-2 text-success">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Active</span>
                        <span class="fw-bold text-gray-800">{{ $summary['active_jobs'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-30px symbol-circle bg-light-secondary me-2">
                        <i class="ki-duotone ki-alarm fs-2 text-secondary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Expired</span>
                        <span class="fw-bold text-gray-800">{{ $summary['expired_jobs'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-30px symbol-circle bg-light-primary me-2">
                        <i class="ki-duotone ki-star fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Featured</span>
                        <span class="fw-bold text-gray-800">{{ $summary['featured_jobs'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-30px symbol-circle bg-light-danger me-2">
                        <i class="ki-duotone ki-fire fs-2 text-danger">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Urgent</span>
                        <span class="fw-bold text-gray-800">{{ $summary['urgent_jobs'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-30px symbol-circle bg-light-info me-2">
                        <i class="ki-duotone ki-send fs-2 text-info">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Pinged</span>
                        <span class="fw-bold text-gray-800">{{ $summary['pinged_jobs'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-30px symbol-circle bg-light-success me-2">
                        <i class="ki-duotone ki-google fs-2 text-success">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Indexed</span>
                        <span class="fw-bold text-gray-800">{{ $summary['indexed_jobs'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Tables -->
<div class="row g-5 g-xl-10 mb-5">
    <!-- Category Breakdown -->
    <div class="col-xxl-6 col-xl-6">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Jobs by Category</h3>
            </div>
            <div class="card-body">
                @if($categoryBreakdown->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Category</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">Views</th>
                                    <th class="text-end">Applications</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categoryBreakdown as $item)
                                    <tr>
                                        <td>{{ $item['category_name'] }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $item['count'] }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format($item['views']) }}</td>
                                        <td class="text-end">{{ number_format($item['applications']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $categoryBreakdown->sum('count') }}</td>
                                    <td class="text-end">{{ number_format($categoryBreakdown->sum('views')) }}</td>
                                    <td class="text-end">{{ number_format($categoryBreakdown->sum('applications')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <div class="text-muted fs-7">
                            Showing {{ $categoryBreakdown->firstItem() ?? 0 }} to {{ $categoryBreakdown->lastItem() ?? 0 }} of {{ $categoryBreakdown->total() }} entries
                        </div>
                        <div>
                            {{ $categoryBreakdown->appends(request()->except('category_page'))->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No data available</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Company Breakdown -->
    <div class="col-xxl-6 col-xl-6">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Jobs by Company</h3>
            </div>
            <div class="card-body">
                @if($companyBreakdown->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Company</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">Views</th>
                                    <th class="text-end">Applications</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($companyBreakdown as $item)
                                    <tr>
                                        <td>{{ $item['company_name'] }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $item['count'] }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format($item['views']) }}</td>
                                        <td class="text-end">{{ number_format($item['applications']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $companyBreakdown->sum('count') }}</td>
                                    <td class="text-end">{{ number_format($companyBreakdown->sum('views')) }}</td>
                                    <td class="text-end">{{ number_format($companyBreakdown->sum('applications')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <div class="text-muted fs-7">
                            Showing {{ $companyBreakdown->firstItem() ?? 0 }} to {{ $companyBreakdown->lastItem() ?? 0 }} of {{ $companyBreakdown->total() }} entries
                        </div>
                        <div>
                            {{ $companyBreakdown->appends(request()->except('company_page'))->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No data available</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Location & Source Breakdown -->
<div class="row g-5 g-xl-10 mb-5">
    <!-- Location Breakdown -->
    <div class="col-xxl-6 col-xl-6">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Jobs by Location</h3>
            </div>
            <div class="card-body">
                @if($locationBreakdown->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Location</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">Views</th>
                                    <th class="text-end">Applications</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($locationBreakdown as $item)
                                    <tr>
                                        <td>{{ $item['location_name'] }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $item['count'] }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format($item['views']) }}</td>
                                        <td class="text-end">{{ number_format($item['applications']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $locationBreakdown->sum('count') }}</td>
                                    <td class="text-end">{{ number_format($locationBreakdown->sum('views')) }}</td>
                                    <td class="text-end">{{ number_format($locationBreakdown->sum('applications')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <div class="text-muted fs-7">
                            Showing {{ $locationBreakdown->firstItem() ?? 0 }} to {{ $locationBreakdown->lastItem() ?? 0 }} of {{ $locationBreakdown->total() }} entries
                        </div>
                        <div>
                            {{ $locationBreakdown->appends(request()->except('location_page'))->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No data available</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Source Breakdown -->
    <div class="col-xxl-6 col-xl-6">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Jobs by Source</h3>
            </div>
            <div class="card-body">
                @if($sourceBreakdown->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Source</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">Views</th>
                                    <th class="text-end">Applications</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sourceBreakdown as $item)
                                    <tr>
                                        <td>
                                            @php
                                                $sourceLabels = [
                                                    'competitor_website' => 'Competitor Website',
                                                    'whatsapp' => 'WhatsApp',
                                                    'newspaper' => 'Newspaper',
                                                    'employer_website' => 'Employer Website',
                                                    'linkedin' => 'LinkedIn',
                                                    'facebook' => 'Facebook',
                                                    'other' => 'Other'
                                                ];
                                            @endphp
                                            {{ $sourceLabels[$item['source']] ?? $item['source'] }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $item['count'] }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format($item['views']) }}</td>
                                        <td class="text-end">{{ number_format($item['applications']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $sourceBreakdown->sum('count') }}</td>
                                    <td class="text-end">{{ number_format($sourceBreakdown->sum('views')) }}</td>
                                    <td class="text-end">{{ number_format($sourceBreakdown->sum('applications')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <div class="text-muted fs-7">
                            Showing {{ $sourceBreakdown->firstItem() ?? 0 }} to {{ $sourceBreakdown->lastItem() ?? 0 }} of {{ $sourceBreakdown->total() }} entries
                        </div>
                        <div>
                            {{ $sourceBreakdown->appends(request()->except('source_page'))->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No data available</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Daily Breakdown -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Daily Breakdown</h3>
            </div>
            <div class="card-body">
                @if($dailyBreakdown->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Date</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">Views</th>
                                    <th class="text-end">Applications</th>
                                    <th class="text-end">Clicks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dailyBreakdown as $item)
                                    <tr>
                                        <td>{{ $item['date_formatted'] }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $item['count'] }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format($item['views']) }}</td>
                                        <td class="text-end">{{ number_format($item['applications']) }}</td>
                                        <td class="text-end">{{ number_format($item['clicks']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $dailyBreakdown->sum('count') }}</td>
                                    <td class="text-end">{{ number_format($dailyBreakdown->sum('views')) }}</td>
                                    <td class="text-end">{{ number_format($dailyBreakdown->sum('applications')) }}</td>
                                    <td class="text-end">{{ number_format($dailyBreakdown->sum('clicks')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <div class="text-muted fs-7">
                            Showing {{ $dailyBreakdown->firstItem() ?? 0 }} to {{ $dailyBreakdown->lastItem() ?? 0 }} of {{ $dailyBreakdown->total() }} entries
                        </div>
                        <div>
                            {{ $dailyBreakdown->appends(request()->except('daily_page'))->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No daily data available</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Status Breakdown -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Status Breakdown</h3>
            </div>
            <div class="card-body">
                @if($statusBreakdown->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Status</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statusBreakdown as $item)
                                    @php
                                        $badgeColor = match($item['label']) {
                                            'Active' => 'success',
                                            'Inactive' => 'danger',
                                            'Expired' => 'secondary',
                                            'Featured' => 'primary',
                                            'Urgent' => 'warning',
                                            'Pinged' => 'info',
                                            'Indexed' => 'success',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-{{ $badgeColor }}">
                                                {{ $item['label'] }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">{{ $item['count'] }}</td>
                                        <td class="text-end">{{ number_format($item['views']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $statusBreakdown->sum('count') }}</td>
                                    <td class="text-end">{{ number_format($statusBreakdown->sum('views')) }}</td>
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

<!-- Daily Trend Line Chart -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Daily Job Posting Trend</h3>
                <div class="card-toolbar">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-light-primary">Peak: {{ $summary['peak_daily_posts'] ?? 0 }} jobs</span>
                        <span class="badge badge-light-info">Avg: {{ number_format($summary['avg_daily_posts'] ?? 0, 1) }} jobs/day</span>
                        <span class="badge badge-light-success">Total: {{ $summary['total_jobs'] ?? 0 }} jobs</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="dailyTrendChart" style="width: 100%; height: 350px;"></canvas>
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
                    <a href="{{ route('admin.jobs-reports.export', ['type' => 'summary']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
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
    // Daily Trend Chart
    const ctx = document.getElementById('dailyTrendChart').getContext('2d');
    const chartLabels = @json($chartLabels);
    const chartCounts = @json($chartCounts);
    const chartViews = @json($chartViews);
    const chartApplications = @json($chartApplications);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: 'Jobs Posted',
                    data: chartCounts,
                    borderColor: '#009ef7',
                    backgroundColor: 'rgba(0, 158, 247, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#009ef7',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    yAxisID: 'y'
                },
                {
                    label: 'Views',
                    data: chartViews,
                    borderColor: '#50cd89',
                    backgroundColor: 'rgba(80, 205, 137, 0.05)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: '#50cd89',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    yAxisID: 'y1'
                },
                {
                    label: 'Applications',
                    data: chartApplications,
                    borderColor: '#f1416c',
                    backgroundColor: 'rgba(241, 65, 108, 0.05)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: '#f1416c',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    yAxisID: 'y'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 12,
                            weight: '600'
                        },
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            return 'Date: ' + context[0].label;
                        },
                        label: function(context) {
                            let label = context.dataset.label || '';
                            let value = context.raw || 0;
                            return label + ': ' + value.toLocaleString();
                        },
                        footer: function(context) {
                            let total = 0;
                            context.forEach(item => {
                                total += item.raw || 0;
                            });
                            return 'Total activity: ' + total;
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
                            size: 10
                        }
                    }
                },
                y: {
                    position: 'left',
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 10
                        }
                    },
                    title: {
                        display: true,
                        text: 'Jobs / Applications',
                        font: {
                            size: 11,
                            weight: '600'
                        }
                    }
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 10
                        }
                    },
                    title: {
                        display: true,
                        text: 'Views',
                        font: {
                            size: 11,
                            weight: '600'
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush