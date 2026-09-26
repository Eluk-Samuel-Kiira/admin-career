@extends('layouts.admin')

@section('title', 'Compliance Review')
@section('page_title', 'Compliance Review')

@section('content')
@can('view employer compliance')

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- STATS STRIP                                                  --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="row g-5 g-xl-8 mb-6">

    {{-- Pending --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush bg-light-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fs-2 fw-bold text-warning" id="statPending">—</div>
                        <div class="fw-semibold text-gray-600">Pending Review</div>
                    </div>
                    <i class="ki-duotone ki-time fs-2x text-warning">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </div>
            </div>
        </div>
    </div>

    {{-- Verified --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush bg-light-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fs-2 fw-bold text-success" id="statVerified">—</div>
                        <div class="fw-semibold text-gray-600">Verified Employers</div>
                        <div class="text-muted fs-8 mt-1">
                            <span id="statVerifiedWeek">—</span> this week
                        </div>
                    </div>
                    <i class="ki-duotone ki-verify fs-2x text-success">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </div>
            </div>
        </div>
    </div>

    {{-- Rejected --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush bg-light-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fs-2 fw-bold text-danger" id="statRejected">—</div>
                        <div class="fw-semibold text-gray-600">Rejected</div>
                    </div>
                    <i class="ki-duotone ki-cross-circle fs-2x text-danger">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </div>
            </div>
        </div>
    </div>

    {{-- Avg Compliance --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush bg-light-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fs-2 fw-bold text-primary" id="statAvgProgress">—</div>
                        <div class="fw-semibold text-gray-600">Avg. Compliance</div>
                        <div class="text-muted fs-8 mt-1">
                            across <span id="statTotal">—</span> employers
                        </div>
                    </div>
                    <i class="ki-duotone ki-chart-simple fs-2x text-primary">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- TABLE                                                        --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title flex-wrap gap-2">
            <input type="text" id="searchInput" class="form-control form-control-solid w-250px" placeholder="Search company or email..." />

            <select id="statusFilter" class="form-select form-select-solid w-175px">
                <option value="submitted">Pending Review</option>
                <option value="verified">Verified</option>
                <option value="rejected">Rejected</option>
                <option value="incomplete">Incomplete</option>
                <option value="all">All</option>
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
                            <th class="min-w-180px">Company</th>
                            <th class="min-w-140px">Contact</th>
                            <th class="min-w-80px">Country</th>
                            <th class="min-w-140px">Profile</th>
                            <th class="min-w-140px">Compliance</th>
                            <th class="min-w-130px">Submitted</th>
                            <th class="min-w-120px">Status</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="complianceTableBody"></tbody>
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
<div class="offcanvas offcanvas-end" tabindex="-1" id="kt_compliance_drawer" style="width:720px;">
    <div class="offcanvas-header">
        <h3 class="offcanvas-title">Compliance Submission</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="complianceDrawerBody">
        <div class="text-center py-10"><div class="spinner-border text-primary"></div></div>
    </div>
</div>

@endcan
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
let currentPage = 1;
let currentSearch = '';
let currentStatus = 'submitted';

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
});

// ── Load stats ────────────────────────────────────────────
function loadStats() {
    fetch('/admin/compliance/stats', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            document.getElementById('statPending').textContent      = data.stats.pending;
            document.getElementById('statVerified').textContent     = data.stats.verified;
            document.getElementById('statRejected').textContent     = data.stats.rejected;
            document.getElementById('statAvgProgress').textContent  = data.stats.avg_progress + '%';
            document.getElementById('statVerifiedWeek').textContent = data.stats.verified_this_week;

            const total = data.stats.pending + data.stats.verified
                        + data.stats.rejected + data.stats.incomplete;
            document.getElementById('statTotal').textContent = total;
        })
        .catch(() => { /* silent */ });
}

// ── Load table ────────────────────────────────────────────
function loadData() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');

    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');

    const params = new URLSearchParams({ page: currentPage, status: currentStatus });
    if (currentSearch) params.set('search', currentSearch);

    fetch(`/admin/compliance/data?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
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
    const tbody = document.getElementById('complianceTableBody');
    tbody.innerHTML = '';

    items.forEach(item => {
        const row = tbody.insertRow();

        const statusBadge = {
            incomplete: '<span class="badge badge-light-warning">Incomplete</span>',
            submitted:  '<span class="badge badge-light-info">Under Review</span>',
            verified:   '<span class="badge badge-light-success">Verified</span>',
            rejected:   '<span class="badge badge-light-danger">Rejected</span>',
        }[item.compliance_status] || '<span class="badge badge-light-secondary">Unknown</span>';

        // ── Company column
        row.insertCell(0).innerHTML = `
            <div class="d-flex align-items-center gap-3">
                <img src="${item.company_logo}" alt="" style="width:36px;height:36px;object-fit:contain;border-radius:6px;background:#f8f9fa;" />
                <div>
                    <div class="fw-bold">${escapeHtml(item.company_name)}</div>
                    <div class="text-muted fs-8">#${item.id}</div>
                </div>
            </div>`;

        // ── Contact
        row.insertCell(1).innerHTML = `
            <div class="text-muted fs-7">${escapeHtml(item.contact_name || '—')}</div>
            <div class="text-muted fs-8">${escapeHtml(item.contact_email || '')}</div>`;

        // ── Country
        row.insertCell(2).innerHTML = `<span class="badge badge-light-info">${escapeHtml(item.country_code || '—')}</span>`;

        // ── Profile completeness
        const profilePct = item.profile_completeness ?? 0;
        const profileColor = profilePct >= 80 ? 'success' : (profilePct >= 50 ? 'warning' : 'danger');
        row.insertCell(3).innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <div class="progress h-6px flex-grow-1" style="min-width:60px;">
                    <div class="progress-bar bg-${profileColor}" style="width: ${profilePct}%"></div>
                </div>
                <span class="fw-bold fs-8 text-${profileColor}">${profilePct}%</span>
            </div>`;

        // ── Compliance progress
        const compPct = item.compliance_progress ?? 0;
        const compColor = compPct >= 100 ? 'success' : (compPct >= 50 ? 'primary' : 'secondary');
        row.insertCell(4).innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <div class="progress h-6px flex-grow-1" style="min-width:60px;">
                    <div class="progress-bar bg-${compColor}" style="width: ${compPct}%"></div>
                </div>
                <span class="fw-bold fs-8 text-${compColor}">${compPct}%</span>
            </div>`;

        // ── Submitted
        row.insertCell(5).innerHTML = item.compliance_submitted_at
            ? `<span class="text-muted fs-7">${new Date(item.compliance_submitted_at).toLocaleDateString()}</span>`
            : '<span class="text-muted fs-7">—</span>';

        // ── Status
        row.insertCell(6).innerHTML = statusBadge;

        // ── Actions
        row.insertCell(7).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="openDetail(${item.id})" title="View">
                    <i class="ki-duotone ki-eye fs-3">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                </button>
            </div>`;
    });
}

window.openDetail = function (id) {
    const drawerBody = document.getElementById('complianceDrawerBody');
    drawerBody.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';
    new bootstrap.Offcanvas(document.getElementById('kt_compliance_drawer')).show();

    fetch(`/admin/compliance/${id}`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error(res.message);
            drawerBody.innerHTML = renderDetail(res.data);
        })
        .catch(err => {
            drawerBody.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
        });
};

function renderDetail(data) {
    const docs = data.documents.map(doc => `
        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded mb-2">
            <div class="d-flex align-items-center gap-3">
                <i class="ki-duotone ki-file fs-2 text-${doc.uploaded ? 'success' : 'muted'}">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <div>
                    <div class="fw-bold fs-7">${escapeHtml(doc.label)}</div>
                    ${doc.uploaded
                        ? `<div class="text-muted fs-8">${escapeHtml(doc.file_name || '')}</div>`
                        : '<div class="text-muted fs-8">Not uploaded</div>'}
                </div>
            </div>
            ${doc.uploaded
                ? `<a href="${doc.file_url}" target="_blank" class="btn btn-sm btn-light-primary">View</a>`
                : '<span class="badge badge-light-secondary">Missing</span>'}
        </div>`).join('');

    return `
        <div class="d-flex flex-column gap-6">
            <div class="d-flex align-items-center gap-4">
                <img src="${data.company_logo}" alt="" style="width:60px;height:60px;object-fit:contain;border-radius:8px;background:#f8f9fa;" />
                <div>
                    <div class="fs-4 fw-bold">${escapeHtml(data.company_name)}</div>
                    <div class="text-muted fs-7">${escapeHtml(data.contact_name || '')} · ${escapeHtml(data.contact_email || '')}</div>
                    <div class="text-muted fs-7">${escapeHtml(data.country_code || '')}</div>
                </div>
            </div>

            ${data.compliance_notes ? `
                <div class="alert alert-light-${data.compliance_status === 'rejected' ? 'danger' : 'info'}">
                    <div class="fw-bold fs-7 mb-1">Notes</div>
                    <div class="fs-7">${escapeHtml(data.compliance_notes)}</div>
                </div>
            ` : ''}

            <div>
                <h5 class="fw-bold mb-3">Documents</h5>
                ${docs}
            </div>

            ${data.compliance_status === 'submitted' ? `
                <div class="border-top pt-5">
                    <h5 class="fw-bold mb-3">Decision</h5>
                    <div class="d-flex gap-3">
                        <button class="btn btn-success flex-grow-1" onclick="verifyEmployer(${data.id})">
                            <i class="ki-duotone ki-check-circle fs-4 me-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            Approve
                        </button>
                        <button class="btn btn-danger flex-grow-1" onclick="rejectEmployer(${data.id})">
                            <i class="ki-duotone ki-cross-circle fs-4 me-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            Reject
                        </button>
                    </div>
                </div>
            ` : ''}
        </div>`;
}

window.verifyEmployer = function (id) {
    if (!confirm('Approve this employer? They will be marked as Verified.')) return;

    fetch(`/admin/compliance/${id}/verify`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Offcanvas.getInstance(document.getElementById('kt_compliance_drawer'))?.hide();
            loadStats();
            loadData();
        } else {
            window.showToast('error', data.message);
        }
    })
    .catch(() => window.showToast('error', 'Verify failed.'));
};

window.rejectEmployer = function (id) {
    const notes = prompt('Reason for rejection (required):');
    if (!notes || !notes.trim()) return;

    fetch(`/admin/compliance/${id}/reject`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ notes })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Offcanvas.getInstance(document.getElementById('kt_compliance_drawer'))?.hide();
            loadStats();
            loadData();
        } else {
            window.showToast('error', data.message);
        }
    })
    .catch(() => window.showToast('error', 'Reject failed.'));
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
    const start = Math.max(1, data.current_page - 2);
    const end = Math.min(data.last_page, data.current_page + 2);
    for (let i = start; i <= end; i++) addPage(i, i, i === data.current_page);
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