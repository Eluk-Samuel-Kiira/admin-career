@extends('layouts.admin')

@section('title', 'CV Review Requests')
@section('page_title', 'CV Review Requests')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Services</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">CV Review Requests</li>
@endsection

@section('content')
@can('view cv review requests')

{{-- Stats strip --}}
<div class="row g-5 g-xl-8 mb-6">
    <div class="col-xl-3">
        <div class="card card-flush bg-light-warning">
            <div class="card-body">
                <div class="fs-2 fw-bold text-warning" id="statPendingPayment">—</div>
                <div class="fw-semibold text-gray-600">Awaiting Payment</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card card-flush bg-light-primary">
            <div class="card-body">
                <div class="fs-2 fw-bold text-primary" id="statInProgress">—</div>
                <div class="fw-semibold text-gray-600">In Progress</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card card-flush bg-light-danger">
            <div class="card-body">
                <div class="fs-2 fw-bold text-danger" id="statOverdue">—</div>
                <div class="fw-semibold text-gray-600">SLA Overdue</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card card-flush bg-light-success">
            <div class="card-body">
                <div class="fs-2 fw-bold text-success" id="statRevenueToday">—</div>
                <div class="fw-semibold text-gray-600">Revenue Today</div>
            </div>
        </div>
    </div>
</div>

<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title flex-wrap gap-2">
            <input type="text" id="searchInput" class="form-control form-control-solid w-250px" placeholder="Search client, UUID, ref..." />

            <select id="statusFilter" class="form-select form-select-solid w-175px">
                <option value="">All Statuses</option>
                @foreach(\App\Http\Controllers\Service\CvReviewRequestController::statuses() as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>

            <select id="paymentFilter" class="form-select form-select-solid w-175px">
                <option value="">All Payments</option>
                <option value="paid">✅ Paid</option>
                <option value="awaiting">⏳ Awaiting</option>
                <option value="not_required">Not Required</option>
            </select>

            <select id="countryFilter" class="form-select form-select-solid w-175px">
                <option value="">All Countries</option>
            </select>

            <select id="assigneeFilter" class="form-select form-select-solid w-175px">
                <option value="">All Assignees</option>
            </select>

            <select id="slaFilter" class="form-select form-select-solid w-150px">
                <option value="">Any SLA</option>
                <option value="overdue">🔴 Overdue</option>
                <option value="due_soon">🟡 Due in 6h</option>
            </select>
        </div>
    </div>

    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-140px">Client</th>
                            <th class="min-w-150px">Service</th>
                            <th class="min-w-120px">Target Role</th>
                            <th class="min-w-100px">Country</th>
                            <th class="min-w-100px">Price</th>
                            <th class="min-w-140px">Payment</th>
                            <th class="min-w-150px">Status</th>
                            <th class="min-w-120px">SLA</th>
                            <th class="min-w-120px">Assignee</th>
                            <th class="text-end min-w-140px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="requestsTableBody"></tbody>
                </table>
            </div>
            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav><ul class="pagination m-0" id="pagination"></ul></nav>
            </div>
        </div>

        <div id="noDataMessage" class="text-center py-10 d-none">
            <p class="text-muted">No CV review requests found.</p>
        </div>
    </div>
</div>

{{-- Detail drawer --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="kt_detail_drawer" style="width:720px;">
    <div class="offcanvas-header">
        <h3 class="offcanvas-title">Request Details</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="detailDrawerBody">
        <div class="text-center py-10"><div class="spinner-border text-primary"></div></div>
    </div>
</div>

{{-- Confirm Payment Modal --}}
<div class="modal fade" id="kt_modal_confirm_payment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Confirm Payment</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="confirmPaymentForm">
                    @csrf
                    <input type="hidden" name="request_id" id="confirm_request_id">
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Payment Reference / Receipt No.</label>
                        <input type="text" class="form-control form-control-solid" name="payment_reference" required />
                        <div class="text-muted fs-7 mt-1">Paste the transaction ID or receipt number from the payment provider.</div>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="confirmPaymentBtn">
                            <span class="indicator-label">Confirm Payment</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Deliver Confirm Modal --}}
<div class="modal fade" id="kt_deliver_modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="fw-bold">Confirm Delivery</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="deliver_summary"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmDeliverBtn">
                    <span class="indicator-label">Send Now</span>
                    <span class="indicator-progress">Sending...
                        <span class="spinner-border spinner-border-sm ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

@endcan
@endsection

@push('scripts')
<script>
/* ============================================================
 * CV Review Requests — Admin Page Script
 * ============================================================ */

const CV_CSRF = document.querySelector('meta[name="csrf-token"]')?.content
             || '{{ csrf_token() }}';

/* ── List state ─────────────────────────────────────────────── */
let currentPage         = 1;
let currentSearch       = '';
let currentStatus       = '';
let currentPaymentState = '';
let currentCountryCode  = '';
let currentAssignee     = '';
let currentSlaFilter    = '';
let adminUsers          = [];

document.addEventListener('DOMContentLoaded', async function () {
    await loadFilters();
    loadStats();
    loadRequests();
    setupEventListeners();
});

/* ── Filters dropdown data ──────────────────────────────────── */
async function loadFilters() {
    try {
        const res  = await fetch('/admin/cv-review-requests/filters', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) return;

        adminUsers = data.admins || [];

        const assigneeFilter = document.getElementById('assigneeFilter');
        if (assigneeFilter) {
            assigneeFilter.innerHTML = '<option value="">All Assignees</option>' +
                adminUsers.map(a => `<option value="${a.id}">${escapeHtml(a.name)}</option>`).join('');
        }

        const countryFilter = document.getElementById('countryFilter');
        if (countryFilter) {
            countryFilter.innerHTML = '<option value="">All Countries</option>' +
                (data.countries || []).map(c =>
                    `<option value="${c.code}">${c.flag} ${escapeHtml(c.name)}</option>`
                ).join('');
        }
    } catch (e) {
        console.error(e);
    }
}

/* ── Stats strip ────────────────────────────────────────────── */
async function loadStats() {
    try {
        const res  = await fetch('/admin/cv-review-requests/stats', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) return;
        document.getElementById('statPendingPayment').textContent = data.stats.pending_payment;
        document.getElementById('statInProgress').textContent     = data.stats.in_progress;
        document.getElementById('statOverdue').textContent        = data.stats.sla_overdue;
        document.getElementById('statRevenueToday').textContent   = (data.stats.revenue_today).toLocaleString();
    } catch (e) { /* silent */ }
}

/* ── Filters + search wiring ────────────────────────────────── */
function setupEventListeners() {
    let timeout;
    document.getElementById('searchInput')?.addEventListener('keyup', function () {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            currentSearch = this.value;
            currentPage = 1;
            loadRequests();
        }, 500);
    });

    document.getElementById('statusFilter')?.addEventListener('change', function () {
        currentStatus = this.value; currentPage = 1; loadRequests();
    });
    document.getElementById('paymentFilter')?.addEventListener('change', function () {
        currentPaymentState = this.value; currentPage = 1; loadRequests();
    });
    document.getElementById('countryFilter')?.addEventListener('change', function () {
        currentCountryCode = this.value; currentPage = 1; loadRequests();
    });
    document.getElementById('assigneeFilter')?.addEventListener('change', function () {
        currentAssignee = this.value; currentPage = 1; loadRequests();
    });
    document.getElementById('slaFilter')?.addEventListener('change', function () {
        currentSlaFilter = this.value; currentPage = 1; loadRequests();
    });
}

/* ── Main table loader ──────────────────────────────────────── */
function loadRequests() {
    const spinner    = document.getElementById('loadingSpinner');
    const table      = document.getElementById('tableContainer');
    const noData     = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');

    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');

    const params = new URLSearchParams({ page: currentPage, per_page: 15 });
    if (currentSearch)       params.set('search', currentSearch);
    if (currentStatus)       params.set('status', currentStatus);
    if (currentPaymentState) params.set('payment_state', currentPaymentState);
    if (currentCountryCode)  params.set('country_code', currentCountryCode);
    if (currentAssignee)     params.set('assigned_admin_id', currentAssignee);
    if (currentSlaFilter)    params.set('sla', currentSlaFilter);

    fetch(`/admin/cv-review-requests/data?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (!data.data || data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            console.error(err);
            window.showToast('error', 'Failed to load requests');
        });
}

/* ── Row renderer ───────────────────────────────────────────── */
function renderTable(requests) {
    const tbody = document.getElementById('requestsTableBody');
    tbody.innerHTML = '';

    requests.forEach(req => {
        const row = tbody.insertRow();

        row.insertCell(0).innerHTML = `
            <div class="d-flex flex-column">
                <span class="fw-bold">${escapeHtml(req.client_name)}</span>
                <span class="text-muted fs-7">${escapeHtml(req.client_email)}</span>
                <span class="text-muted fs-8">#${escapeHtml((req.uuid || '').substring(0, 8))}</span>
            </div>`;

        row.insertCell(1).innerHTML = `<span class="fw-semibold">${escapeHtml(req.service_name)}</span>`;
        row.insertCell(2).innerHTML = req.target_job_title
            ? `<span class="text-truncate d-inline-block" style="max-width:150px;">${escapeHtml(req.target_job_title)}</span>`
            : '<span class="text-muted">—</span>';

        row.insertCell(3).innerHTML = req.country_label;
        row.insertCell(4).innerHTML = `<span class="fw-bold text-primary">${escapeHtml(req.formatted_price)}</span>`;
        row.insertCell(5).innerHTML = req.payment_badge;

        const allowed = req.allowed_next_statuses || [];
        row.insertCell(6).innerHTML = `
            <select class="form-select form-select-sm form-select-solid status-quick-change"
                    data-id="${req.id}" data-current="${req.status}">
                ${buildStatusOptions(req.status, allowed)}
            </select>
            <div class="mt-1">${req.status_badge}</div>`;

        row.insertCell(7).innerHTML = req.sla_badge;
        row.insertCell(8).innerHTML = req.assignee_name
            ? `<span class="badge badge-light-primary">${escapeHtml(req.assignee_name)}</span>`
            : '<span class="text-muted">Unassigned</span>';

        row.insertCell(9).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                ${req.cv_download_url
                    ? `<a href="${req.cv_download_url}" target="_blank"
                           class="btn btn-sm btn-icon btn-light"
                           title="Download CV: ${escapeHtml(req.cv_name || 'CV')}">
                           <i class="ki-duotone ki-file-down fs-3">
                               <span class="path1"></span><span class="path2"></span>
                           </i>
                       </a>`
                    : `<button class="btn btn-sm btn-icon btn-light" disabled
                               title="CV file not available">
                           <i class="ki-duotone ki-file-deleted fs-3 text-muted">
                               <span class="path1"></span><span class="path2"></span>
                           </i>
                       </button>`
                }
                ${(req.payment_badge || '').includes('Awaiting')
                    ? `<button class="btn btn-sm btn-icon btn-light-success"
                               onclick="openConfirmPayment(${req.id})"
                               title="Confirm Payment">
                           <i class="ki-duotone ki-dollar fs-3">
                               <span class="path1"></span><span class="path2"></span>
                           </i>
                       </button>`
                    : ''}
                <button class="btn btn-sm btn-icon btn-light"
                        onclick="openDetail(${req.id})"
                        title="View details">
                    <i class="ki-duotone ki-eye fs-3">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                </button>
            </div>`;
    });

    document.querySelectorAll('.status-quick-change').forEach(sel => {
        sel.addEventListener('change', function () {
            updateStatus(this.dataset.id, this.value, this.dataset.current);
        });
    });
}

function buildStatusOptions(current, allowed) {
    const all = {
        submitted: 'Submitted',
        ai_reviewed: 'AI Reviewed',
        awaiting_payment: 'Awaiting Payment',
        paid: 'Paid',
        in_progress: 'In Progress',
        delivered: 'Delivered',
        revision_requested: 'Revision Requested',
        completed: 'Completed',
        cancelled: 'Cancelled',
    };
    let html = `<option value="${current}" selected>${all[current] ?? current}</option>`;
    (allowed || []).forEach(s => {
        if (s !== current) html += `<option value="${s}">→ ${all[s]}</option>`;
    });
    return html;
}

/* ── Status quick-change (table dropdown) ───────────────────── */
window.updateStatus = function (id, newStatus, current) {
    if (newStatus === current) return;
    if (!confirm(`Move this request to "${newStatus}"?`)) { loadRequests(); return; }

    fetch(`/admin/cv-review-requests/${id}/status`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CV_CSRF,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            loadRequests();
            loadStats();
        } else {
            window.showToast('error', data.message);
            loadRequests();
        }
    })
    .catch(() => { window.showToast('error', 'Failed'); loadRequests(); });
};

/* ── Confirm Payment modal ──────────────────────────────────── */
window.openConfirmPayment = function (id) {
    document.getElementById('confirm_request_id').value = id;
    document.getElementById('confirmPaymentForm').reset();
    document.getElementById('confirm_request_id').value = id;
    new bootstrap.Modal(document.getElementById('kt_modal_confirm_payment')).show();
};

document.getElementById('confirmPaymentForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = document.getElementById('confirmPaymentBtn');
    window.showButtonSpinner(btn);

    const id       = document.getElementById('confirm_request_id').value;
    const formData = new FormData(this);

    fetch(`/admin/cv-review-requests/${id}/confirm-payment`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CV_CSRF, 'Accept': 'application/json' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_confirm_payment'))?.hide();
            loadRequests();
            loadStats();
        } else {
            const msg = data.errors ? Object.values(data.errors).flat().join('\n') : data.message;
            window.showToast('error', msg);
        }
    })
    .catch(() => window.showToast('error', 'Failed to confirm payment'))
    .finally(() => window.hideButtonSpinner(btn));
});

/* ── Detail drawer ──────────────────────────────────────────── */
window.openDetail = function (id) {
    const drawerBody = document.getElementById('detailDrawerBody');
    drawerBody.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';
    new bootstrap.Offcanvas(document.getElementById('kt_detail_drawer')).show();

    fetch(`/admin/cv-review-requests/${id}/detail`, { headers: { 'Accept': 'text/html' } })
        .then(r => r.text())
        .then(html => { drawerBody.innerHTML = html; });
};

/* ── Pagination ─────────────────────────────────────────────── */
function renderPagination(data) {
    const el   = document.getElementById('pagination');
    const info = document.getElementById('paginationInfo');
    el.innerHTML = '';
    info.innerHTML = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total}`;

    const addPage = (page, text, isActive = false, isDisabled = false) => {
        const li = document.createElement('li');
        li.className = `page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = text;
        if (!isDisabled) a.onclick = (e) => { e.preventDefault(); changePage(page); };
        li.appendChild(a);
        el.appendChild(li);
    };

    addPage(data.current_page - 1, 'Previous', false, !data.prev_page_url);
    const start = Math.max(1, data.current_page - 2);
    const end   = Math.min(data.last_page, data.current_page + 2);
    if (start > 1) addPage(1, '1');
    if (start > 2) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    for (let i = start; i <= end; i++) addPage(i, i, i === data.current_page);
    if (end < data.last_page - 1) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    if (end < data.last_page) addPage(data.last_page, data.last_page);
    addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
}

window.changePage = function (page) {
    if (page !== currentPage && page > 0) { currentPage = page; loadRequests(); }
};

/* ── Helpers ────────────────────────────────────────────────── */
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/* ============================================================
 * DELEGATED HANDLERS — drawer buttons
 * ============================================================ */
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-action]');
    if (!trigger) return;

    const action = trigger.dataset.action;

    /* ── Run AI Review ──────────────────────────────────────── */
    if (action === 'run-ai-review') {
        e.preventDefault();
        const id = trigger.dataset.id;
        if (!id) return;
        if (!confirm('Run AI gap review? This takes 10-30 seconds.')) return;

        const original = trigger.innerHTML;
        trigger.disabled = true;
        trigger.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Analysing...';

        fetch(`/admin/cv-review-requests/${id}/run-ai-review`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CV_CSRF, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message || 'AI review done');
                setTimeout(() => {
                    window.openDetail(id);
                    loadRequests();
                    loadStats();
                }, 500);
            } else {
                window.showToast('error', data.message || 'AI review failed');
                trigger.disabled = false;
                trigger.innerHTML = original;
            }
        })
        .catch(err => {
            console.error(err);
            window.showToast('error', 'AI review failed');
            trigger.disabled = false;
            trigger.innerHTML = original;
        });
        return;
    }

    /* ── Toggle edit review ─────────────────────────────────── */
    if (action === 'toggle-edit-review') {
        e.preventDefault();
        document.getElementById('reviewView')?.classList.add('d-none');
        document.getElementById('reviewEdit')?.classList.remove('d-none');
        return;
    }

    /* ── Cancel edit review ─────────────────────────────────── */
    if (action === 'cancel-edit-review') {
        e.preventDefault();
        document.getElementById('reviewEdit')?.classList.add('d-none');
        document.getElementById('reviewView')?.classList.remove('d-none');
        return;
    }

    /* ── Add section row ───────────────────────────────────── */
    if (action === 'add-section') {
        e.preventDefault();
        const list = document.getElementById('edit_sections_list');
        if (!list) return;
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 section-row';
        row.innerHTML = `
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" data-field="section" placeholder="Section">
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" data-field="status">
                    <option value="ok">OK</option>
                    <option value="weak">Weak</option>
                    <option value="missing">Missing</option>
                </select>
            </div>
            <div class="col-md-5">
                <input type="text" class="form-control form-control-sm" data-field="note" placeholder="Note">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-light-danger w-100" data-action="remove-section">
                    <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>`;
        list.appendChild(row);
        return;
    }

    /* ── Remove section row ────────────────────────────────── */
    if (action === 'remove-section') {
        e.preventDefault();
        trigger.closest('.section-row')?.remove();
        return;
    }

    /* ── Save edited review ─────────────────────────────────── */
    if (action === 'save-edited-review') {
        e.preventDefault();
        const id = trigger.dataset.id;
        if (!id) return;

        const original = trigger.innerHTML;
        trigger.disabled = true;
        trigger.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        const sections = [];
        document.querySelectorAll('#edit_sections_list .section-row').forEach(row => {
            sections.push({
                section: row.querySelector('[data-field="section"]').value,
                status:  row.querySelector('[data-field="status"]').value,
                note:    row.querySelector('[data-field="note"]').value,
            });
        });

        const missingEl = document.getElementById('edit_missing');
        const missing = missingEl
            ? missingEl.value.split(',').map(s => s.trim()).filter(Boolean)
            : [];

        const payload = {
            overall_score:  parseInt(document.getElementById('edit_score')?.value || '0', 10),
            summary:        document.getElementById('edit_summary')?.value || '',
            sections:       sections,
            missing_fields: missing,
        };

        fetch(`/admin/cv-review-requests/${id}/save-edited-review`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CV_CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message || 'Review saved');
                setTimeout(() => window.openDetail(id), 400);
            } else {
                window.showToast('error', data.message || 'Save failed');
                trigger.disabled = false;
                trigger.innerHTML = original;
            }
        })
        .catch(err => {
            console.error(err);
            window.showToast('error', 'Save failed');
            trigger.disabled = false;
            trigger.innerHTML = original;
        });
        return;
    }

    /* ── Open deliver modal ─────────────────────────────────── */
    if (action === 'open-deliver-modal') {
        e.preventDefault();
        const id = trigger.dataset.id;

        const channels = [];
        if (document.querySelector('input[name="channel_email"]')?.checked)    channels.push('email');
        if (document.querySelector('input[name="channel_whatsapp"]')?.checked) channels.push('whatsapp');

        if (!channels.length) {
            window.showToast('error', 'Select at least one delivery channel.');
            return;
        }

        const message = document.getElementById('delivery_message')?.value || '';

        const summaryEl = document.getElementById('deliver_summary');
        if (summaryEl) {
            summaryEl.innerHTML = `
                <div class="alert alert-light-primary mb-0">
                    <div class="fw-bold mb-2">Sending via:</div>
                    <ul class="mb-0">
                        ${channels.map(c => `<li>${c === 'email' ? '✉️ Email' : '📱 WhatsApp'}</li>`).join('')}
                    </ul>
                    ${message
                        ? `<div class="mt-3"><div class="fw-bold">Custom message:</div><div class="text-muted">${escapeHtml(message)}</div></div>`
                        : ''}
                </div>`;
        }

        const modalEl = document.getElementById('kt_deliver_modal');
        if (modalEl) {
            modalEl.dataset.requestId = id;
            modalEl.dataset.channels  = JSON.stringify(channels);
            modalEl.dataset.message   = message;
            new bootstrap.Modal(modalEl).show();
        }
        return;
    }

    /* ── Change status (admin override) ─────────────────────── */
    if (action === 'change-status') {
        e.preventDefault();
        const id        = trigger.dataset.id;
        const newStatus = document.getElementById('status_change_select')?.value;
        const note      = document.getElementById('status_change_note')?.value || '';

        if (!id || !newStatus) return;
        if (!confirm(`Change status to "${newStatus}"?`)) return;

        const original = trigger.innerHTML;
        trigger.disabled = true;
        trigger.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Applying...';

        fetch(`/admin/cv-review-requests/${id}/status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CV_CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: newStatus, note })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message || 'Status updated');
                setTimeout(() => {
                    window.openDetail(id);
                    loadRequests();
                    loadStats();
                }, 400);
            } else {
                window.showToast('error', data.message || 'Failed to change status');
                trigger.disabled = false;
                trigger.innerHTML = original;
            }
        })
        .catch(err => {
            console.error(err);
            window.showToast('error', 'Failed to change status');
            trigger.disabled = false;
            trigger.innerHTML = original;
        });
        return;
    }
});

/* ── Confirm deliver (modal footer) ─────────────────────────── */
document.getElementById('confirmDeliverBtn')?.addEventListener('click', function () {
    const modalEl  = document.getElementById('kt_deliver_modal');
    const id       = parseInt(modalEl?.dataset.requestId, 10);
    const channels = JSON.parse(modalEl?.dataset.channels || '[]');
    const message  = modalEl?.dataset.message || '';

    if (!id || !channels.length) {
        window.showToast('error', 'No delivery context set.');
        return;
    }

    const btn = this;
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';

    fetch(`/admin/cv-review-requests/${id}/deliver`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CV_CSRF,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ channels, message })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message || 'Delivered');
            bootstrap.Modal.getInstance(modalEl)?.hide();
            setTimeout(() => window.openDetail(id), 400);
        } else {
            window.showToast('error', data.message || 'Delivery failed');
            btn.disabled = false;
            btn.innerHTML = original;
        }
    })
    .catch(err => {
        console.error(err);
        window.showToast('error', 'Delivery failed');
        btn.disabled = false;
        btn.innerHTML = original;
    });
});
</script>
@endpush