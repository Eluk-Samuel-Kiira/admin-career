@extends('layouts.admin')

@section('title', 'Job Submissions')
@section('page_title', 'Job Submissions')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Employer</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Job Submissions</li>
@endsection

@section('content')
@can('view job submissions')

{{-- Stats strip --}}
<div class="row g-5 g-xl-8 mb-6">
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush bg-light-warning">
            <div class="card-body">
                <div class="fs-2 fw-bold text-warning" id="statAwaitingPayment">—</div>
                <div class="fw-semibold text-gray-600">Awaiting Payment</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush bg-light-info">
            <div class="card-body">
                <div class="fs-2 fw-bold text-info" id="statReadyReview">—</div>
                <div class="fw-semibold text-gray-600">Ready for Review</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush bg-light-success">
            <div class="card-body">
                <div class="fs-2 fw-bold text-success" id="statPublished">—</div>
                <div class="fw-semibold text-gray-600">Published</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush bg-light-primary">
            <div class="card-body">
                <div class="fs-2 fw-bold text-primary" id="statRevenue">—</div>
                <div class="fw-semibold text-gray-600">Revenue (This Month)</div>
            </div>
        </div>
    </div>
</div>

{{-- Table card --}}
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title flex-wrap gap-2">
            <input type="text" id="searchInput" class="form-control form-control-solid w-250px" placeholder="Search title, company, or UUID..." />

            <select id="statusFilter" class="form-select form-select-solid w-175px">
                <option value="">All Statuses</option>
                <option value="pending_payment">Awaiting Payment</option>
                <option value="pending_review">Under Review</option>
                <option value="published">Published</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>

            <select id="paymentFilter" class="form-select form-select-solid w-150px">
                <option value="">All Payments</option>
                <option value="paid">✅ Paid</option>
                <option value="unpaid">⏳ Unpaid</option>
                <option value="free">Free</option>
            </select>
        </div>
    </div>

    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary"></div>
        </div>

        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                            <th class="min-w-220px">Company / Job</th>
                            <th class="min-w-140px">Package</th>
                            <th class="min-w-120px">Amount</th>
                            <th class="min-w-120px">Payment</th>
                            <th class="min-w-140px">Status</th>
                            <th class="min-w-130px">Submitted</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="submissionsTableBody"></tbody>
                </table>
            </div>
            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav><ul class="pagination m-0" id="pagination"></ul></nav>
            </div>
        </div>

        <div id="noDataMessage" class="text-center py-10 d-none">
            <p class="text-muted">No submissions found.</p>
        </div>
    </div>
</div>

{{-- Detail drawer --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="kt_submission_drawer" style="width:800px;">
    <div class="offcanvas-header">
        <h3 class="offcanvas-title">Job Submission</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="submissionDrawerBody">
        <div class="text-center py-10"><div class="spinner-border text-primary"></div></div>
    </div>
</div>

{{-- Mark Paid Modal --}}
<div class="modal fade" id="kt_mark_paid_modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="fw-bold">Confirm Payment</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="markPaidForm">
                @csrf
                <input type="hidden" name="submission_uuid" id="mark_paid_uuid">
                <div class="modal-body">
                    <div class="fv-row mb-5">
                        <label class="fw-semibold mb-2">Payment reference (optional)</label>
                        <input type="text" name="payment_reference" class="form-control"
                               placeholder="e.g. MTN-89432, Bank Ref 12345" maxlength="255" />
                        <div class="text-muted fs-8 mt-1">
                            Leave blank if the employer already provided one.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="markPaidBtn">
                        <span class="indicator-label">Confirm Payment</span>
                        <span class="indicator-progress">
                            Saving... <span class="spinner-border spinner-border-sm ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endcan
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
let currentPage = 1;
let currentSearch = '';
let currentStatus = '';
let currentPayment = '';

document.addEventListener('DOMContentLoaded', function () {
    loadStats();
    loadData();

    let timeout;
    document.getElementById('searchInput')?.addEventListener('keyup', function () {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            currentSearch = this.value;
            currentPage = 1;
            loadData();
        }, 500);
    });

    document.getElementById('statusFilter')?.addEventListener('change', function () {
        currentStatus = this.value;
        currentPage = 1;
        loadData();
    });

    document.getElementById('paymentFilter')?.addEventListener('change', function () {
        currentPayment = this.value;
        currentPage = 1;
        loadData();
    });
});

function loadStats() {
    fetch('/admin/job-submissions/stats', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            document.getElementById('statAwaitingPayment').textContent = data.stats.awaiting_payment;
            document.getElementById('statReadyReview').textContent     = data.stats.ready_review;
            document.getElementById('statPublished').textContent       = data.stats.published;
            document.getElementById('statRevenue').textContent         = (data.stats.paid_amount_cents).toLocaleString();
        })
        .catch(() => {});
}

function loadData() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');

    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');

    const params = new URLSearchParams({ page: currentPage });
    if (currentSearch)  params.set('search', currentSearch);
    if (currentStatus)  params.set('status', currentStatus);
    if (currentPayment) params.set('payment', currentPayment);

    fetch(`/admin/job-submissions/data?${params}`, { headers: { 'Accept': 'application/json' } })
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
        .catch(() => {
            spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load submissions.');
        });
}

function renderTable(items) {
    const tbody = document.getElementById('submissionsTableBody');
    tbody.innerHTML = '';

    items.forEach(item => {
        const row = tbody.insertRow();

        row.insertCell(0).innerHTML = `
            <div class="d-flex align-items-center gap-3">
                <img src="${item.company_logo}" alt="" style="width:36px;height:36px;object-fit:contain;border-radius:6px;background:#f8f9fa;" />
                <div>
                    <div class="fw-bold text-truncate" style="max-width:200px;">${escapeHtml(item.job_title)}</div>
                    <div class="text-muted fs-8">${escapeHtml(item.company_name)} · ${escapeHtml(item.user_email)}</div>
                </div>
            </div>`;

        const meta = item.package_meta || {};
        const badge = meta.badge ? `<span class="badge badge-light-${badgeColor(meta.badge)} ms-1">${escapeHtml(meta.badge)}</span>` : '';
        row.insertCell(1).innerHTML = `
            <div class="fw-bold fs-7">${escapeHtml(item.service_name)}</div>
            ${badge}`;

        row.insertCell(2).innerHTML = item.is_free
            ? '<span class="text-muted">FREE</span>'
            : `<span class="fw-bold text-primary">${escapeHtml(item.formatted_amount)}</span>`;

        row.insertCell(3).innerHTML = item.payment_badge;
        row.insertCell(4).innerHTML = item.status_badge;
        row.insertCell(5).innerHTML = `<span class="text-muted fs-7">${new Date(item.created_at).toLocaleDateString()}</span>`;

        row.insertCell(6).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="openSubmission('${item.uuid}')" title="View">
                    <i class="ki-duotone ki-eye fs-3">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                </button>
            </div>`;
    });
}

function badgeColor(b) {
    if (b === 'popular') return 'warning';
    if (b === 'urgent') return 'danger';
    if (b === 'enterprise') return 'dark';
    return 'secondary';
}

window.openSubmission = function (uuid) {
    const body = document.getElementById('submissionDrawerBody');
    body.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';
    new bootstrap.Offcanvas(document.getElementById('kt_submission_drawer')).show();

    fetch(`/admin/job-submissions/${uuid}`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error(res.message);
            body.innerHTML = renderDetail(res.data);
        })
        .catch(err => {
            body.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
        });
};

function renderDetail(s) {
    const meta = s.package_meta || {};
    const features = [];
    if (meta.is_featured)    features.push(`Featured${meta.featured_days ? ' for ' + meta.featured_days + ' days' : ''}`);
    if (meta.is_urgent)      features.push('Urgent badge');
    if (meta.enterprise_ats) features.push('Enterprise ATS');
    if (meta.whatsapp_distribution === 'unlimited') features.push('Unlimited WhatsApp distribution');
    if (meta.whatsapp_distribution === 'limited')   features.push(`WhatsApp × ${meta.whatsapp_count}`);
    if (meta.email_alerts === 'unlimited')          features.push('Email alerts: unlimited');
    if (meta.email_alerts === 'limited')            features.push(`Email alerts × ${meta.email_alert_count}`);
    if (meta.paid_ads_boost) features.push('Paid ads boost');
    if (meta.priority_placement) features.push('Priority placement');

    return `
        <div class="d-flex flex-column gap-6">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div class="d-flex gap-3">
                    <img src="${s.company_logo || ''}" alt="" style="width:56px;height:56px;object-fit:contain;border-radius:8px;background:#f8f9fa;" />
                    <div>
                        <div class="fs-4 fw-bold">${escapeHtml(s.job_title)}</div>
                        <div class="text-muted fs-7">${escapeHtml(s.company_name)}</div>
                        <div class="text-muted fs-8">${escapeHtml(s.user_name)} · ${escapeHtml(s.user_email)}</div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="mb-1">${s.status_badge}</div>
                    <div>${s.payment_badge}</div>
                </div>
            </div>

            {{-- Package box --}}
            <div class="card bg-light-primary">
                <div class="card-body py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-5">${escapeHtml(s.service_name)}</div>
                            ${features.length ? `<div class="text-muted fs-7 mt-1">${features.join(' · ')}</div>` : ''}
                        </div>
                        <div class="text-end">
                            <div class="fs-3 fw-bold text-primary">${s.is_free ? 'FREE' : s.formatted_amount}</div>
                            <div class="text-muted fs-8">${s.currency_code || ''}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment status if pending --}}
            ${s.payment_status === 'pending' ? `
                <div class="alert alert-light-warning d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-bold">Payment pending</div>
                        <div class="text-muted fs-7">${s.payment_reference ? 'Reference: ' + escapeHtml(s.payment_reference) : 'No reference provided yet'}</div>
                    </div>
                    <button class="btn btn-sm btn-success" onclick="openMarkPaid('${s.uuid}')">
                        <i class="ki-duotone ki-check fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                        Mark Paid
                    </button>
                </div>
            ` : ''}

            ${s.payment_status === 'paid' ? `
                <div class="alert alert-light-success">
                    <div class="fw-bold">✅ Payment confirmed</div>
                    <div class="text-muted fs-7">Reference: ${escapeHtml(s.payment_reference || '—')} · ${s.paid_at ? new Date(s.paid_at).toLocaleString() : ''}</div>
                </div>
            ` : ''}

            {{-- Rejection reason --}}
            ${s.rejection_reason ? `
                <div class="alert alert-light-danger">
                    <div class="fw-bold">Rejection reason</div>
                    <div class="fs-7">${escapeHtml(s.rejection_reason)}</div>
                </div>
            ` : ''}

            {{-- Content --}}
            <div>
                <h5 class="fw-bold mb-3">Submitted Content</h5>
                <div class="p-4 bg-light rounded font-monospace fs-7" style="white-space:pre-wrap;max-height:400px;overflow:auto;">${escapeHtml(s.content)}</div>
            </div>

            {{-- Job Post link if published --}}
            ${s.job_post_id ? `
                <div class="alert alert-light-success d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-bold">✅ Published</div>
                        <div class="text-muted fs-7">Job post ID #${s.job_post_id}</div>
                    </div>
                    <a href="/jobs/${s.job_post_slug}" target="_blank" class="btn btn-sm btn-success">
                        View Live
                    </a>
                </div>
            ` : ''}

            {{-- Actions --}}
            ${!s.job_post_id && s.status !== 'published' ? `
                <div class="border-top pt-5">
                    <h5 class="fw-bold mb-3">Actions</h5>

                    <div class="alert alert-light-info mb-4">
                        <i class="ki-duotone ki-information-5 fs-3 me-2">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                        <span class="fs-7">
                            <strong>Heads up:</strong> "Mark as Published" only updates this submission's status.
                            You still need to create the actual job post using the
                            <a href="/admin/job-posts/create" target="_blank">Job Post form</a> — copy the content below.
                        </span>
                    </div>

                    <div class="d-flex gap-3 flex-wrap">
                        <button class="btn btn-light-primary" onclick="copyContent('${s.uuid}')">
                            <i class="ki-duotone ki-copy fs-4 me-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            Copy Content
                        </button>

                        <a href="/admin/job-posts/create" target="_blank" class="btn btn-light-info">
                            <i class="ki-duotone ki-plus-square fs-4 me-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            Open Job Form
                        </a>

                        <button class="btn btn-success flex-grow-1" onclick="markPublished('${s.uuid}')">
                            <i class="ki-duotone ki-check-circle fs-4 me-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            Mark as Published
                        </button>

                        <button class="btn btn-danger" onclick="rejectSubmission('${s.uuid}')">
                            <i class="ki-duotone ki-cross-circle fs-4">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </button>
                    </div>
                </div>
            ` : ''}

            ${s.status === 'published' ? `
                <div class="alert alert-light-success">
                    <div class="fw-bold">✅ Marked as published</div>
                    <div class="text-muted fs-7">
                        ${s.job_post_id
                            ? 'Linked to job post #' + s.job_post_id
                            : 'Not linked to a job post yet. Verify the job is live on the site.'}
                    </div>
                </div>
            ` : ''}

        </div>`;
}

// ── Mark Paid ─────────────────────────────────────────────
window.openMarkPaid = function (uuid) {
    document.getElementById('mark_paid_uuid').value = uuid;
    document.getElementById('markPaidForm').reset();
    document.getElementById('mark_paid_uuid').value = uuid;
    new bootstrap.Modal(document.getElementById('kt_mark_paid_modal')).show();
};

document.getElementById('markPaidForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = document.getElementById('markPaidBtn');
    window.showButtonSpinner(btn);
    const uuid = document.getElementById('mark_paid_uuid').value;
    const formData = new FormData(this);

    fetch(`/admin/job-submissions/${uuid}/mark-paid`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_mark_paid_modal'))?.hide();
            loadStats(); loadData();
            setTimeout(() => openSubmission(uuid), 400);
        } else {
            window.showToast('error', data.message);
        }
    })
    .catch(() => window.showToast('error', 'Failed.'))
    .finally(() => window.hideButtonSpinner(btn));
});

// ── Mark as Published ────────────────────────────────────
window.markPublished = function (uuid) {
    const confirmed = confirm(
        'Mark this submission as published?\n\n' +
        'IMPORTANT: This does NOT create the job on the public site. ' +
        'You must still create the job post separately through the Job Post form.\n\n' +
        'Continue?'
    );
    if (!confirmed) return;

    fetch(`/admin/job-submissions/${uuid}/mark-published`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            if (data.warning) {
                setTimeout(() => {
                    window.showToast('info', data.warning, 'Reminder');
                }, 800);
            }
            bootstrap.Offcanvas.getInstance(document.getElementById('kt_submission_drawer'))?.hide();
            loadStats(); loadData();
        } else {
            window.showToast('error', data.message);
        }
    })
    .catch(() => window.showToast('error', 'Failed.'));
};

// ── Copy content to clipboard ────────────────────────────
window.copyContent = function (uuid) {
    // The content is already in the DOM. Grab it from the drawer.
    const contentEl = document.querySelector('#submissionDrawerBody .font-monospace');
    if (!contentEl) {
        window.showToast('error', 'Content not found.');
        return;
    }

    navigator.clipboard.writeText(contentEl.textContent).then(() => {
        window.showToast('success', 'Job content copied to clipboard.');
    }).catch(() => {
        window.showToast('error', 'Copy failed — please select and copy manually.');
    });
};

// ── Reject ────────────────────────────────────────────────
window.rejectSubmission = function (uuid) {
    const reason = prompt('Reason for rejection (required):');
    if (!reason || !reason.trim()) return;

    fetch(`/admin/job-submissions/${uuid}/reject`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ reason })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Offcanvas.getInstance(document.getElementById('kt_submission_drawer'))?.hide();
            loadStats(); loadData();
        } else {
            window.showToast('error', data.message);
        }
    })
    .catch(() => window.showToast('error', 'Failed.'));
};

function renderPagination(data) {
    const el = document.getElementById('pagination');
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
        if (!isDisabled) a.onclick = (e) => { e.preventDefault(); currentPage = page; loadData(); };
        li.appendChild(a);
        el.appendChild(li);
    };

    addPage(data.current_page - 1, 'Previous', false, !data.prev_page_url);
    for (let i = Math.max(1, data.current_page - 2); i <= Math.min(data.last_page, data.current_page + 2); i++) {
        addPage(i, i, i === data.current_page);
    }
    addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush