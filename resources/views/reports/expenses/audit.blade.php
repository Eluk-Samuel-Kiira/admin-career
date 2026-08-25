@extends('layouts.admin')

@section('title', 'Expense Audit Report')
@section('page_title', 'Expense Audit Report')

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
    <li class="breadcrumb-item text-muted">Audit</li>
@endsection

@section('content')
@can('view audit trail')
<!-- Filters -->
<div class="card card-flush shadow-sm mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.expense-reports.audit') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $startDate ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="fw-semibold fs-7 mb-1">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $endDate ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">Audit Type</label>
                <select name="audit_type" class="form-select form-select-solid">
                    @foreach($auditTypes as $key => $label)
                        <option value="{{ $key }}" {{ ($auditType ?? 'all') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="fw-semibold fs-7 mb-1">Employee</label>
                <select name="employee_id" class="form-select form-select-solid">
                    <option value="">All Employees</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ ($employeeId ?? '') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
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
                        <i class="ki-duotone ki-shield fs-2 text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Items</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $auditStats['total_items'] ?? 0 }}</span>
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
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Amount</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $auditStats['total_amount_display'] ?? 'UGX 0' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-35px symbol-circle bg-light-danger me-2">
                        <i class="ki-duotone ki-cross-circle fs-2 text-danger">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Missing Receipts</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $auditStats['missing_receipts'] ?? 0 }}</span>
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
                        <i class="ki-duotone ki-time fs-2 text-warning">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Unapproved</span>
                        <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">{{ $auditStats['unapproved'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats -->
<div class="row g-5 g-xl-10 mb-5">
    <div class="col-md-4">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <span class="text-muted">High Value Items</span>
                    <span class="fw-bold text-danger">{{ $auditStats['high_value'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Compliance Rate</span>
                    <span class="fw-bold text-success">
                        @php
                            $totalItems = $auditStats['total_items'] ?? 0;
                            $missingReceipts = $auditStats['missing_receipts'] ?? 0;
                            $complianceRate = $totalItems > 0 ? (($totalItems - $missingReceipts) / $totalItems) * 100 : 0;
                        @endphp
                        {{ number_format($complianceRate, 1) }}%
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-flush shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Avg Age (Days)</span>
                    <span class="fw-bold text-info">
                        @php
                            $avgAge = $auditItems->avg(function($item) {
                                return \Carbon\Carbon::parse($item->created_at)->diffInDays(\Carbon\Carbon::today());
                            }) ?? 0;
                        @endphp
                        {{ number_format($avgAge, 0) }} days
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Audit Items Table -->
<div class="card card-flush shadow-sm">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">Audit Items</h3>
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
                        <th>Date</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Department</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th>Issues</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditItems as $item)
                        @php
                            $issues = [];
                            $hasReceipt = !empty($item->receipt_url);
                            $isApproved = !is_null($item->approved_at);
                            
                            if ($item->category && $item->category->requires_receipt && !$hasReceipt) {
                                $issues[] = 'Missing Receipt';
                            }
                            if ($item->category && $item->category->requires_approval && !$isApproved) {
                                $issues[] = 'Not Approved';
                            }
                            if ($item->total_amount >= 1000000) { // 1,000,000 UGX threshold
                                $issues[] = 'High Value';
                            }
                            $issueCount = count($issues);
                            $issueBadgeColor = $issueCount > 1 ? 'danger' : ($issueCount == 1 ? 'warning' : 'success');
                            $issueLabel = $issueCount > 1 ? $issueCount . ' Issues' : ($issueCount == 1 ? '1 Issue' : 'No Issues');
                        @endphp
                        <tr>
                            <td><span class="fw-bold">{{ $item->expense_number }}</span></td>
                            <td>{{ $item->date->format('M d, Y') }}</td>
                            <td>{{ Str::limit($item->description, 40) }}</td>
                            <td>{{ $item->category?->name ?? 'N/A' }}</td>
                            <td>{{ $item->department?->name ?? 'N/A' }}</td>
                            <td class="text-end fw-bold text-success">{{ $item->formatted_amount }}</td>
                            <td>{!! $item->payment_status_badge !!}</td>
                            <td>
                                <span class="badge badge-light-{{ $issueBadgeColor }}">
                                    {{ $issueLabel }}
                                </span>
                                @if($issueCount > 0)
                                    <div class="fs-7 text-muted mt-1">
                                        @if(in_array('Missing Receipt', $issues))
                                            <span class="text-danger">📄 No Receipt</span>
                                        @endif
                                        @if(in_array('Not Approved', $issues))
                                            <span class="text-warning">⏳ Unapproved</span>
                                        @endif
                                        @if(in_array('High Value', $issues))
                                            <span class="text-danger">💰 High Value</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-icon btn-light" onclick="viewExpense({{ $item->id }})" title="View Details">
                                    <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">No audit items found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-5">
            <div class="text-muted fs-7">
                Showing {{ $auditItems->firstItem() ?? 0 }} to {{ $auditItems->lastItem() ?? 0 }} of {{ $auditItems->total() }} entries
            </div>
            <div>
                {{ $auditItems->appends(request()->except('page'))->links() }}
            </div>
        </div>
    </div>
</div>

<!-- By Category Summary -->
<div class="card card-flush shadow-sm mt-5">
    <div class="card-header py-3">
        <h3 class="card-title fs-5 fw-bold">Issues by Category</h3>
    </div>
    <div class="card-body">
        @if($byCategory->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-3">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>Category</th>
                            <th class="text-center">Total Items</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-center">Missing Receipts</th>
                            <th class="text-center">Unapproved</th>
                            <th class="text-center">Issue Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byCategory as $item)
                            @php
                                $totalItems = $item['count'];
                                $missingReceipts = $item['missing_receipts'];
                                $unapproved = $item['unapproved'];
                                $issueRate = $totalItems > 0 ? (($missingReceipts + $unapproved) / $totalItems) * 100 : 0;
                            @endphp
                            <tr>
                                <td><span class="fw-bold">{{ $item['category'] }}</span></td>
                                <td class="text-center">{{ $totalItems }}</td>
                                <td class="text-end text-success">{{ $item['total_display'] }}</td>
                                <td class="text-center">
                                    @if($missingReceipts > 0)
                                        <span class="badge badge-light-danger">{{ $missingReceipts }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($unapproved > 0)
                                        <span class="badge badge-light-warning">{{ $unapproved }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-2 justify-content-center">
                                        <span>{{ number_format($issueRate, 1) }}%</span>
                                        <div class="progress w-50" style="height: 6px;">
                                            <div class="progress-bar bg-{{ $issueRate > 50 ? 'danger' : ($issueRate > 25 ? 'warning' : 'success') }}" 
                                                 style="width: {{ $issueRate }}%;">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5 text-muted">No category data available</div>
        @endif
    </div>
</div>

<!-- View Expense Modal -->
<div class="modal fade" id="kt_modal_view_expense" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Expense Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body p-7" id="viewExpenseContent">
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading expense details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <a href="#" id="viewExpenseFullLink" class="btn btn-primary" target="_blank">
                    <i class="ki-duotone ki-exit-right fs-2 me-1"></i> View Full Page
                </a>
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
                    <a href="{{ route('admin.expense-reports.export', ['type' => 'audit']) . '?' . http_build_query(request()->except('page', 'per_page')) }}" class="btn btn-sm btn-success">
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
<script>
// View Expense Details in Modal
function viewExpense(id) {
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_expense'));
    const content = document.getElementById('viewExpenseContent');
    const fullLink = document.getElementById('viewExpenseFullLink');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center py-10">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading expense details...</p>
        </div>
    `;
    
    // Set full page link
    fullLink.href = `/admin/expenses/${id}`;
    
    modal.show();
    
    // Fetch expense details
    fetch(`/admin/expenses/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Failed to load expense details');
        }
        return res.json();
    })
    .then(data => {
        const currency = data.currency_symbol || 'UGX';
        const totalAmount = data.total_amount / 100;
        const grossAmount = data.gross_amount / 100;
        const taxAmount = data.tax_amount / 100;
        const netAmount = data.net_amount / 100;
        
        let html = `
            <div class="row mb-5">
                <div class="col-md-6">
                    <span class="text-muted">Expense #</span>
                    <div class="fw-bold fs-4">${data.expense_number}</div>
                </div>
                <div class="col-md-6">
                    <span class="text-muted">Status</span>
                    <div>${data.payment_status_badge}</div>
                </div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-6">
                    <span class="text-muted">Date</span>
                    <div class="fw-bold">${formatDate(data.date)}</div>
                </div>
                <div class="col-md-6">
                    <span class="text-muted">Category</span>
                    <div class="fw-bold">${data.category?.name || 'N/A'}</div>
                </div>
            </div>
            <div class="row mb-5">
                <div class="col-md-12">
                    <span class="text-muted">Description</span>
                    <div class="fw-bold">${escapeHtml(data.description) || 'N/A'}</div>
                </div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-3">
                    <span class="text-muted">Gross Amount</span>
                    <div class="fw-bold text-success">${currency} ${formatNumber(grossAmount)}</div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted">Tax Amount</span>
                    <div class="fw-bold">${currency} ${formatNumber(taxAmount)}</div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted">Net Amount</span>
                    <div class="fw-bold">${currency} ${formatNumber(netAmount)}</div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted">Total Amount</span>
                    <div class="fw-bold text-success fs-3">${currency} ${formatNumber(totalAmount)}</div>
                </div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-6">
                    <span class="text-muted">Department</span>
                    <div class="fw-bold">${data.department?.name || 'N/A'}</div>
                </div>
                <div class="col-md-6">
                    <span class="text-muted">Vendor</span>
                    <div class="fw-bold">${escapeHtml(data.vendor_name) || 'N/A'}</div>
                </div>
            </div>
            <div class="row mb-5">
                <div class="col-md-6">
                    <span class="text-muted">Payment Method</span>
                    <div class="fw-bold">${data.payment_method?.name || 'N/A'}</div>
                </div>
                <div class="col-md-6">
                    <span class="text-muted">Receipt</span>
                    <div class="fw-bold">${data.receipt_number || 'N/A'}</div>
                </div>
            </div>
        `;
        
        // Add approval info if available
        if (data.approved_at) {
            html += `
                <div class="row mb-5">
                    <div class="col-md-6">
                        <span class="text-muted">Approved At</span>
                        <div class="fw-bold">${formatDate(data.approved_at)}</div>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted">Approved By</span>
                        <div class="fw-bold">${data.approver?.name || 'N/A'}</div>
                    </div>
                </div>
            `;
        }
        
        // Add paid info if available
        if (data.paid_date) {
            html += `
                <div class="row mb-5">
                    <div class="col-md-12">
                        <span class="text-muted">Paid Date</span>
                        <div class="fw-bold">${formatDate(data.paid_date)}</div>
                    </div>
                </div>
            `;
        }
        
        // Add notes if available
        if (data.notes) {
            html += `
                <div class="separator my-5"></div>
                <div class="row mb-5">
                    <div class="col-md-12">
                        <span class="text-muted">Notes</span>
                        <div class="fw-bold">${escapeHtml(data.notes)}</div>
                    </div>
                </div>
            `;
        }
        
        // Add audit issues
        const issues = [];
        if (data.category?.requires_receipt && !data.receipt_url) {
            issues.push('Missing Receipt');
        }
        if (data.category?.requires_approval && !data.approved_at) {
            issues.push('Not Approved');
        }
        if (data.total_amount >= 1000000) {
            issues.push('High Value');
        }
        
        if (issues.length > 0) {
            html += `
                <div class="separator my-5"></div>
                <div class="alert alert-warning">
                    <div class="fw-bold">Audit Issues Found:</div>
                    <ul class="mb-0 mt-2">
                        ${issues.map(issue => `<li>${issue}</li>`).join('')}
                    </ul>
                </div>
            `;
        }
        
        content.innerHTML = html;
    })
    .catch(err => {
        console.error('Error:', err);
        content.innerHTML = `
            <div class="text-center py-10 text-danger">
                <i class="ki-duotone ki-information-5 fs-2tx mb-3 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <p>Failed to load expense details</p>
                <p class="text-muted fs-7">${err.message}</p>
            </div>
        `;
    });
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatNumber(value) {
    if (value === null || value === undefined) return '0';
    return Number(value).toLocaleString(undefined, { 
        minimumFractionDigits: 0, 
        maximumFractionDigits: 0 
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<style>
.modal-body .separator {
    height: 1px;
    background: #e8ebed;
    margin: 10px 0;
}
</style>
@endpush