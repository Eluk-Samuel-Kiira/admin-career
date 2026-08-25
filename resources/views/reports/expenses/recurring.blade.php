@extends('layouts.admin')

@section('title', 'Recurring Expenses')
@section('page_title', 'Recurring Expenses')

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
    <li class="breadcrumb-item text-muted">Recurring</li>
@endsection

@section('content')
@can('view recurring expenses')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.expense-reports.recurring') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">Frequency</label>
                <select name="frequency" class="form-select form-select-solid">
                    <option value="">All Frequencies</option>
                    @foreach($frequencies as $key => $label)
                        <option value="{{ $key }}" {{ ($frequency ?? '') == $key ? 'selected' : '' }}>
                            {{ $label }}
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
                <label class="fw-semibold fs-7 mb-1">Status</label>
                <select name="status" class="form-select form-select-solid">
                    <option value="active" {{ ($status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="upcoming" {{ ($status ?? 'active') == 'upcoming' ? 'selected' : '' }}>Upcoming (7 days)</option>
                    <option value="overdue" {{ ($status ?? 'active') == 'overdue' ? 'selected' : '' }}>Overdue</option>
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
                        <i class="ki-duotone ki-sync fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Recurring</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $recurringExpenses->total() }}</span>
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
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Monthly Total</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">
                            UGX {{ number_format($recurringExpenses->sum('total_amount') / 100, 0) }}
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
                    <div class="symbol symbol-35px symbol-circle bg-light-info me-2">
                        <i class="ki-duotone ki-calendar fs-2 text-info">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Next 30 Days</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $upcomingNext30Days->count() }}</span>
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
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Annual Projection</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">
                            @php
                                $annualTotal = $recurringExpenses->sum(function($expense) {
                                    $multiplier = match($expense->recurring_frequency) {
                                        'weekly' => 52,
                                        'monthly' => 12,
                                        'quarterly' => 4,
                                        'yearly' => 1,
                                        default => 12
                                    };
                                    return $expense->total_amount * $multiplier;
                                });
                            @endphp
                            UGX {{ number_format($annualTotal / 100, 0) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recurring Expenses Table -->
<div class="card card-flush shadow-sm">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">Recurring Expenses List</h3>
        <div class="card-toolbar">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted fs-7">Per Page:</span>
                <select class="form-select form-select-sm w-70px" onchange="window.location.href=this.value">
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 10]) }}" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 20]) }}" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 50]) }}" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 100]) }}" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-3">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>Expense #</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Frequency</th>
                        <th class="text-end">Amount</th>
                        <th>Next Date</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recurringExpenses as $expense)
                        @php
                            $today = \Carbon\Carbon::today();
                            $nextDate = $expense->next_recurring_date ? \Carbon\Carbon::parse($expense->next_recurring_date) : null;
                            $statusClass = 'secondary';
                            $statusLabel = 'Inactive';
                            
                            if ($nextDate) {
                                if ($nextDate->lt($today)) {
                                    $statusClass = 'danger';
                                    $statusLabel = 'Overdue';
                                } elseif ($nextDate->lte($today->copy()->addDays(7))) {
                                    $statusClass = 'warning';
                                    $statusLabel = 'Upcoming';
                                } else {
                                    $statusClass = 'success';
                                    $statusLabel = 'Active';
                                }
                            }
                        @endphp
                        <tr>
                            <td><span class="fw-bold">{{ $expense->expense_number }}</span></td>
                            <td>{{ Str::limit($expense->description, 50) }}</td>
                            <td>{{ $expense->category?->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-light-primary">
                                    {{ ucfirst($expense->recurring_frequency) }}
                                </span>
                            </td>
                            <td class="text-end fw-bold text-success">
                                {{ $baseCurrency->formatAmount($expense->total_amount) }}
                            </td>
                            <td>
                                @if($nextDate)
                                    <span class="fw-bold">{{ $nextDate->format('M d, Y') }}</span>
                                    <span class="text-muted fs-7">({{ $nextDate->diffForHumans() }})</span>
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-light-{{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.expenses.show', $expense->id) }}" class="btn btn-sm btn-icon btn-light" target="_blank">
                                    <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No recurring expenses found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-5">
            <div class="text-muted fs-7">
                Showing {{ $recurringExpenses->firstItem() ?? 0 }} to {{ $recurringExpenses->lastItem() ?? 0 }} of {{ $recurringExpenses->total() }} entries
            </div>
            <div>
                {{ $recurringExpenses->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Frequency Breakdown -->
<div class="card card-flush shadow-sm mt-5">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">Frequency Breakdown</h3>
    </div>
    <div class="card-body">
        @if($byFrequency->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-3">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>Frequency</th>
                            <th class="text-center">Count</th>
                            <th class="text-end">Monthly Total</th>
                            <th class="text-end">Annual Total</th>
                            <th class="text-center">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalMonthly = $byFrequency->sum('total_monthly'); @endphp
                        @foreach($byFrequency as $freq => $data)
                            <tr>
                                <td><span class="fw-bold">{{ ucfirst($freq) }}</span></td>
                                <td class="text-center">{{ $data['count'] }}</td>
                                <td class="text-end text-success">{{ $data['total_monthly_display'] }}</td>
                                <td class="text-end text-info">{{ $data['total_annual_display'] }}</td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-2 justify-content-center">
                                        <span>{{ $totalMonthly > 0 ? number_format(($data['total_monthly'] / $totalMonthly) * 100, 1) : 0 }}%</span>
                                        <div class="progress w-50" style="height: 6px;">
                                            <div class="progress-bar bg-primary" style="width: {{ $totalMonthly > 0 ? ($data['total_monthly'] / $totalMonthly) * 100 : 0 }}%;"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td class="text-center">{{ $byFrequency->sum('count') }}</td>
                            <td class="text-end text-success">{{ $baseCurrency->formatAmount($byFrequency->sum('total_monthly')) }}</td>
                            <td class="text-end text-info">{{ $baseCurrency->formatAmount($byFrequency->sum('total_annual')) }}</td>
                            <td class="text-center">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="text-center py-5 text-muted">No frequency data available</div>
        @endif
    </div>
</div>

<!-- Upcoming Next 30 Days -->
<div class="card card-flush shadow-sm mt-5">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">Upcoming in Next 30 Days</h3>
    </div>
    <div class="card-body">
        @if($upcomingNext30Days->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-3">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>Expense #</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Next Date</th>
                            <th>Days Left</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcomingNext30Days as $expense)
                            @php
                                $nextDate = \Carbon\Carbon::parse($expense->next_recurring_date);
                                $daysLeft = $today->diffInDays($nextDate);
                                $colorClass = $daysLeft <= 7 ? 'warning' : 'primary';
                            @endphp
                            <tr>
                                <td><span class="fw-bold">{{ $expense->expense_number }}</span></td>
                                <td>{{ Str::limit($expense->description, 50) }}</td>
                                <td class="fw-bold text-success">{{ $baseCurrency->formatAmount($expense->total_amount) }}</td>
                                <td>{{ $nextDate->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge badge-light-{{ $colorClass }}">
                                        {{ $daysLeft }} days
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5 text-muted">No upcoming recurring expenses in the next 30 days</div>
        @endif
    </div>
</div>

<!-- Export Button -->
<div class="row g-5 g-xl-10 mt-3">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.expense-reports.export', ['type' => 'recurring']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
                        <i class="ki-duotone ki-file-down fs-2 me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection