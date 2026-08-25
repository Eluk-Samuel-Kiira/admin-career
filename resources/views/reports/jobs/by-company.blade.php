@extends('layouts.admin')

@section('title', 'Jobs by Company Report')
@section('page_title', 'Jobs by Company Report')

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
    <li class="breadcrumb-item text-muted">By Company</li>
@endsection

@section('content')
@can('view job company report')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.jobs-reports.company') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $startDate ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $endDate ?? '' }}">
            </div>
            <div class="col-md-3">
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
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-primary me-2">
                        <i class="ki-duotone ki-buildings fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Companies</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $companyBreakdown->total() }}</span>
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($companyBreakdown->sum('total_views')) }}</span>
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($companyBreakdown->sum('total_applications')) }}</span>
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $companyBreakdown->sum('job_count') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Company Breakdown Table -->
<div class="row g-5 g-xl-10">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Company Breakdown</h3>
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
                                <th>Company</th>
                                <th class="text-center">Jobs</th>
                                <th class="text-end">Total Views</th>
                                <th class="text-end">Total Applications</th>
                                <th class="text-end">Avg Views</th>
                                <th class="text-end">Avg Applications</th>
                                <th class="text-end">Max Views</th>
                                <th class="text-end">Max Applications</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($companyBreakdown as $index => $item)
                                <tr>
                                    <td>{{ $companyBreakdown->firstItem() + $index }}</td>
                                    <td>
                                        <span class="fw-bold">{{ $item->company_name }}</span>
                                        @if($item->company_slug)
                                            <div class="text-muted fs-8">{{ $item->company_slug }}</div>
                                        @endif
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">No companies found</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="2">Total</td>
                                <td class="text-center">{{ $companyBreakdown->sum('job_count') }}</td>
                                <td class="text-end">{{ number_format($companyBreakdown->sum('total_views')) }}</td>
                                <td class="text-end">{{ number_format($companyBreakdown->sum('total_applications')) }}</td>
                                <td class="text-end"></td>
                                <td class="text-end"></td>
                                <td class="text-end"></td>
                                <td class="text-end"></td>
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
                        {{ $companyBreakdown->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
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
                    <a href="{{ route('admin.jobs-reports.export', ['type' => 'company']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
                        <i class="ki-duotone ki-file-down fs-2 me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection