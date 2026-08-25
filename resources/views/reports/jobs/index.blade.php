@extends('layouts.admin')

@section('title', 'Jobs Reports Dashboard')
@section('page_title', 'Jobs Reports Dashboard')

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
    <li class="breadcrumb-item text-muted">Jobs Reports</li>
@endsection

@section('content')
@can('view jobs')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.jobs-reports') }}" class="row g-3 align-items-end">
            <div class="col-md-10">
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $summary['total'] ?? 0 }}</span>
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['views'] ?? 0) }}</span>
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['applications'] ?? 0) }}</span>
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
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ number_format($summary['avg_views'] ?? 0, 1) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Trends Line Chart -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Monthly Trends</h3>
                <div class="card-toolbar">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-light-primary">Peak: {{ collect($monthlyTrends)->max('count') ?? 0 }} jobs</span>
                        <span class="badge badge-light-info">Avg: {{ collect($monthlyTrends)->count() > 0 ? number_format(collect($monthlyTrends)->avg('count'), 1) : 0 }} jobs/month</span>
                        <span class="badge badge-light-success">Total: {{ collect($monthlyTrends)->sum('count') }} jobs</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="monthlyTrendChart" style="width: 100%; height: 350px;"></canvas>
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
                @if(count($statusBreakdown) > 0)
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
                                @foreach($statusBreakdown as $status)
                                    @php
                                        $badgeColor = match($status['label']) {
                                            'Active' => 'success',
                                            'Inactive' => 'danger',
                                            'Expired' => 'secondary',
                                            'Featured' => 'primary',
                                            'Urgent' => 'warning',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-{{ $badgeColor }}">
                                                {{ $status['label'] }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">{{ $status['count'] }}</td>
                                        <td class="text-end">{{ number_format($status['views']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ collect($statusBreakdown)->sum('count') }}</td>
                                    <td class="text-end">{{ number_format(collect($statusBreakdown)->sum('views')) }}</td>
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

<!-- Top Categories & Top Companies -->
<div class="row g-5 g-xl-10 mb-5">
    <!-- Top Categories -->
    <div class="col-xxl-6 col-xl-6">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Top Categories</h3>
                <div class="card-toolbar">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted fs-7">Per Page:</span>
                        <select class="form-select form-select-sm w-70px" onchange="window.location.href=this.value">
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 5, 'category_page' => 1]) }}" {{ ($perPage ?? 10) == 5 ? 'selected' : '' }}>5</option>
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 10, 'category_page' => 1]) }}" {{ ($perPage ?? 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 20, 'category_page' => 1]) }}" {{ ($perPage ?? 10) == 20 ? 'selected' : '' }}>20</option>
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 50, 'category_page' => 1]) }}" {{ ($perPage ?? 10) == 50 ? 'selected' : '' }}>50</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if($topCategories->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Category</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topCategories as $category)
                                    <tr>
                                        <td>{{ $category->category_name }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $category->count }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format($category->views) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $topCategories->sum('count') }}</td>
                                    <td class="text-end">{{ number_format($topCategories->sum('views')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <div class="text-muted fs-7">
                            Showing {{ $topCategories->firstItem() ?? 0 }} to {{ $topCategories->lastItem() ?? 0 }} of {{ $topCategories->total() }} entries
                        </div>
                        <div>
                            {{ $topCategories->appends(request()->except('category_page'))->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No data available</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Top Companies -->
    <div class="col-xxl-6 col-xl-6">
        <div class="card card-flush shadow-sm">
            <div class="card-header py-3">
                <h3 class="card-title fs-5 fw-bold">Top Companies</h3>
                <div class="card-toolbar">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted fs-7">Per Page:</span>
                        <select class="form-select form-select-sm w-70px" onchange="window.location.href=this.value">
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 5, 'company_page' => 1]) }}" {{ ($perPage ?? 10) == 5 ? 'selected' : '' }}>5</option>
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 10, 'company_page' => 1]) }}" {{ ($perPage ?? 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 20, 'company_page' => 1]) }}" {{ ($perPage ?? 10) == 20 ? 'selected' : '' }}>20</option>
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 50, 'company_page' => 1]) }}" {{ ($perPage ?? 10) == 50 ? 'selected' : '' }}>50</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if($topCompanies->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Company</th>
                                    <th class="text-center">Jobs</th>
                                    <th class="text-end">Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topCompanies as $company)
                                    <tr>
                                        <td>{{ $company->company_name }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $company->count }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format($company->views) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $topCompanies->sum('count') }}</td>
                                    <td class="text-end">{{ number_format($topCompanies->sum('views')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <div class="text-muted fs-7">
                            Showing {{ $topCompanies->firstItem() ?? 0 }} to {{ $topCompanies->lastItem() ?? 0 }} of {{ $topCompanies->total() }} entries
                        </div>
                        <div>
                            {{ $topCompanies->appends(request()->except('company_page'))->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">No data available</div>
                @endif
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
    // Monthly Trends Line Chart
    const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
    
    // Get data from PHP
    const chartData = @json($chartData);
    
    if (chartData && chartData.labels && chartData.labels.length > 0) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Jobs Posted',
                        data: chartData.counts,
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
                        data: chartData.views,
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
                        data: chartData.applications,
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
                                return 'Month: ' + context[0].label;
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
    } else {
        // Show message if no data
        document.getElementById('monthlyTrendChart').parentElement.innerHTML = 
            '<div class="text-center py-5 text-muted">No monthly trend data available</div>';
    }
});
</script>
@endpush