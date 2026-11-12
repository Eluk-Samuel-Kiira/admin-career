@extends('layouts.admin')

@section('title', 'Auto Apply Agent')
@section('page_title', 'Auto Apply Agent')

@section('content')
@can('manage job alerts')

<div class="container py-6">

    {{-- ── Stats ── --}}
    <div class="row g-4 mb-6">
        <div class="col-md-3">
            <div class="card bg-light-primary h-100">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-primary">{{ $stats['drafts_ready'] }}</div>
                    <div class="text-muted">Drafts Ready</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light-success h-100">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-success">{{ $stats['sent_today'] }}</div>
                    <div class="text-muted">Sent Today</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light-warning h-100">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-warning">{{ $stats['external_pending'] }}</div>
                    <div class="text-muted">External Links Pending</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light-danger h-100">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-danger">{{ $stats['failed_today'] }}</div>
                    <div class="text-muted">Failed Today</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Trigger Button ── --}}
    <div class="card card-flush mb-6 border-primary border-2">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1">Draft Applications for Active Subscribers</h4>
                <p class="text-muted mb-0">This will use AI credits to generate tailored CVs and Cover Letters for all paid users.</p>
                <div id="triggerProgress" class="text-primary fw-semibold fs-7 mt-2 d-none"></div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" id="triggerAllBtn">
                    <i class="ki-duotone ki-rocket fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                    <span class="btn-label">Trigger All Users</span>
                    <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Tabs ── --}}
    <div class="card card-flush">
        <div class="card-header align-items-center py-5">
            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold mb-n2">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tab_drafts">Drafts (Review & Send)</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab_history" id="historyTabLink">Applied History</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab_logs">Activity Logs (AI Runs)</a>
                </li>
            </ul>
            <div class="card-toolbar">
                <button class="btn btn-sm btn-light" id="reloadBtn">
                    <i class="ki-duotone ki-arrows-circle fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                    Reload
                </button>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab_drafts">
                    <div id="draftsWrap" class="text-center py-10">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
                
                {{-- NEW HISTORY TAB --}}
                <div class="tab-pane fade" id="tab_history">
                    <div id="historyWrap" class="text-center py-10">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab_logs">
                    <div id="logsWrap" class="text-center py-10">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════ SIDE DRAWER (all actions) ══════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="actionDrawer" style="width: 900px;">
    <div class="offcanvas-header border-bottom">
        <div class="d-flex align-items-center gap-3">
            <h5 class="offcanvas-title mb-0" id="drawerTitle">Draft</h5>
            <span id="drawerBadge" class="badge badge-light-primary d-none"></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">

        {{-- Seeker / job info --}}
        <div class="mb-5">
            <div class="fw-bold fs-5 text-gray-800" id="drawerSeekerName">—</div>
            <div class="text-muted fs-7" id="drawerSeekerEmail">—</div>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-5">
            <span class="badge badge-light-secondary" id="drawerJobTitle">—</span>
            <span class="badge badge-light-primary" id="drawerChannel">—</span>
        </div>

        {{-- Inside drawer: Job Requirements Panel --}}
        <div class="card bg-light-primary border-primary border border-dashed mb-5">
            <div class="card-body py-4">
                <div class="fw-bold fs-7 text-gray-800 mb-3">
                    Job Requirements (what will be attached)
                </div>
                <div class="d-flex flex-wrap gap-2" id="requirementsWrap"></div>
                <div id="employerContactWrap" class="mt-3 pt-3 border-top border-gray-200 fs-7 text-muted"></div>
            </div>
        </div>

        {{-- Document tabs --}}
        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold mb-4" id="drawerTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#doc_cv">CV</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#doc_cover">Cover Letter</a>
            </li>
        </ul>

        <div class="tab-content mb-5">
            <div class="tab-pane fade show active" id="doc_cv">
                <div class="d-flex justify-content-end gap-2 mb-3">
                    <button class="btn btn-sm btn-light-primary" id="editCvBtn">
                        <i class="ki-duotone ki-pencil fs-4 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        Edit
                    </button>
                    <button class="btn btn-sm btn-light-info" id="copyCvBtn">
                        <i class="ki-duotone ki-copy fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                        Copy
                    </button>
                    <a class="btn btn-sm btn-light-success" id="downloadCvBtn" target="_blank">
                        <i class="ki-duotone ki-file-down fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                        PDF
                    </a>
                </div>
                <div id="cvPreview" class="bg-white p-4 rounded shadow-sm border" style="font-family: Georgia, serif; font-size: 13px; line-height: 1.6; color: #0F1B2D; min-height: 200px;"></div>
                <div id="cvEditorWrap" class="d-none">
                    <textarea id="cvEditor" class="form-control" rows="18"></textarea>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-primary" id="saveCvBtn">
                            <span class="indicator-label">Save</span>
                            <span class="indicator-progress">Saving... <span class="spinner-border spinner-border-sm ms-2"></span></span>
                        </button>
                        <button class="btn btn-light" id="cancelCvBtn">Cancel</button>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="doc_cover">
                <div class="d-flex justify-content-end gap-2 mb-3">
                    <button class="btn btn-sm btn-light-primary" id="editCoverBtn">
                        <i class="ki-duotone ki-pencil fs-4 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        Edit
                    </button>
                    <button class="btn btn-sm btn-light-info" id="copyCoverBtn">
                        <i class="ki-duotone ki-copy fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                        Copy
                    </button>
                    <a class="btn btn-sm btn-light-success" id="downloadCoverBtn" target="_blank">
                        <i class="ki-duotone ki-file-down fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                        PDF
                    </a>
                </div>
                <div id="coverPreview" class="bg-white p-4 rounded shadow-sm border" style="font-family: Georgia, serif; font-size: 13px; line-height: 1.6; color: #0F1B2D; min-height: 200px;"></div>
                <div id="coverEditorWrap" class="d-none">
                    <textarea id="coverEditor" class="form-control" rows="18"></textarea>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-primary" id="saveCoverBtn">
                            <span class="indicator-label">Save</span>
                            <span class="indicator-progress">Saving... <span class="spinner-border spinner-border-sm ms-2"></span></span>
                        </button>
                        <button class="btn btn-light" id="cancelCoverBtn">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="offcanvas-footer border-top p-5 d-flex justify-content-end gap-2">
        <button class="btn btn-light" data-bs-dismiss="offcanvas">Close</button>
        <button class="btn btn-primary" id="drawerSendBtn">
            <span class="indicator-label">
                <i class="ki-duotone ki-send fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                Send Application
            </span>
            <span class="indicator-progress">Sending... <span class="spinner-border spinner-border-sm ms-2"></span></span>
        </button>
    </div>
</div>

{{-- ══════════════════ EMAIL PREVIEW MODAL ══════════════════ --}}
<div class="modal fade" id="emailPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Email Preview — Confirm Send</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="fw-bold fs-7 text-gray-800 mb-1">To:</div>
                    <div class="text-muted" id="emailTo">—</div>
                </div>
                <div class="mb-3">
                    <div class="fw-bold fs-7 text-gray-800 mb-1">Subject:</div>
                    <div class="text-muted" id="emailSubject">—</div>
                </div>
                <div class="mb-3">
                    <div class="fw-bold fs-7 text-gray-800 mb-1">Attachments:</div>
                    <div class="d-flex flex-wrap gap-2" id="emailAttachments"></div>
                </div>
                <div class="mb-3">
                    <div class="fw-bold fs-7 text-gray-800 mb-1">Body:</div>
                    <div id="emailBody" class="bg-light p-3 rounded border" style="font-family: Georgia, serif; font-size: 13px; line-height: 1.6; max-height: 400px; overflow-y: auto;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmSendBtn">
                    <span class="indicator-label">
                        <i class="ki-duotone ki-send fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                        Confirm &amp; Send
                    </span>
                    <span class="indicator-progress">Sending... <span class="spinner-border spinner-border-sm ms-2"></span></span>
                </button>
            </div>
        </div>
    </div>
</div>

@endcan
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    'use strict';

    const CSRF = '{{ csrf_token() }}';

    // ═══════════════════════════════════════════════════════════
    // Bootstraps
    // ═══════════════════════════════════════════════════════════
    const drawerEl    = document.getElementById('actionDrawer');
    const drawer      = new bootstrap.Offcanvas(drawerEl);
    const emailModalEl = document.getElementById('emailPreviewModal');
    const emailModal  = new bootstrap.Modal(emailModalEl);

    // Drawer state
    let currentDraft  = null;
     let currentHistoryData = [];
    let currentData   = null;   
    let cvEditorInstance    = null;
    let coverEditorInstance = null;

    // ═══════════════════════════════════════════════════════════
    // Trigger Drafting (unchanged polling)
    // ═══════════════════════════════════════════════════════════
    document.getElementById('triggerAllBtn')?.addEventListener('click', async (event) => {
        if (!confirm('This will use AI credits and may take several minutes. Continue?')) return;

        const btn        = event.currentTarget;
        const label      = btn.querySelector('.btn-label');
        const spinner    = btn.querySelector('.spinner-border');
        const progressEl = document.getElementById('triggerProgress');
        const original   = label.textContent;

        btn.disabled = true;
        label.textContent = 'Queuing...';
        spinner.classList.remove('d-none');
        progressEl.classList.remove('d-none');
        progressEl.textContent = 'Dispatching AI jobs to queue...';

        try {
            const res = await fetch('{{ route("admin.auto-apply.trigger") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ all: true }),
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Trigger failed.');

            label.textContent = 'Drafting...';
            progressEl.textContent = 'AI is generating drafts...';

            let elapsed = 0;
            const poll = setInterval(async () => {
                elapsed += 3000;
                try {
                    const statusRes = await fetch('{{ route("admin.auto-apply.status") }}', { headers: { 'Accept': 'application/json' } });
                    const statusData = await statusRes.json();

                    if (statusData.success) {
                        const relayed = statusData.run?.relayed_count || 0;
                        progressEl.textContent = `${relayed} drafts ready · ${statusData.queue_size} queued`;
                        if (statusData.is_done) {
                            clearInterval(poll);
                            btn.disabled = false; label.textContent = original; spinner.classList.add('d-none');
                            progressEl.textContent = `✅ Done — ${relayed} drafts ready!`;
                            setTimeout(() => { progressEl.classList.add('d-none'); loadDrafts(); }, 2000);
                            return;
                        }
                    }
                    if (elapsed >= 600000) {
                        clearInterval(poll);
                        btn.disabled = false; label.textContent = original; spinner.classList.add('d-none');
                    }
                } catch (e) {}
            }, 3000);

        } catch (e) {
            btn.disabled = false; label.textContent = original; spinner.classList.add('d-none'); progressEl.classList.add('d-none');
            window.showToast?.('error', e.message);
        }
    });

    // ═══════════════════════════════════════════════════════════
    // Load Drafts
    // ═══════════════════════════════════════════════════════════
    async function loadDrafts() {
        const wrap = document.getElementById('draftsWrap');
        wrap.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        try {
            const res = await fetch('{{ route("admin.auto-apply.drafts") }}', { headers: { 'Accept': 'application/json' } });
            const data = await res.json();

            if (!data.success || (data.data.relays.length === 0 && data.data.external.length === 0)) {
                wrap.innerHTML = `<div class="text-center py-15">
                    <i class="ki-duotone ki-information-5 fs-5tx text-muted mb-4 d-block"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <h4 class="fw-bold text-gray-800 mb-2">No drafts yet</h4>
                    <p class="text-muted">Trigger the drafting process above.</p>
                </div>`;
                return;
            }

            currentData = data.data;

            let html = '';

            if (data.data.relays.length > 0) {
                html += `
                    <div class="mb-8">
                        <h5 class="fw-bold mb-4 text-gray-800">Email &amp; WhatsApp Applications</h5>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                        <th class="text-start min-w-150px">Seeker</th>
                                        <th class="text-start min-w-200px">Job</th>
                                        <th class="text-center min-w-80px">Channel</th>
                                        <th class="text-end min-w-200px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.data.relays.map(r => `
                                        <tr>
                                            <td class="text-start">
                                                <div class="fw-bold text-gray-800">${escapeHtml(r.user_name)}</div>
                                                <div class="text-muted fs-8">${escapeHtml(r.user_email)}</div>
                                            </td>
                                            <td class="text-start">
                                                <div class="fw-semibold text-gray-800">${escapeHtml(r.job_title)}</div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-light-primary">${capitalize(r.channel)}</span>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-primary open-drawer-btn" data-id="${r.id}" data-type="relay">
                                                    <i class="ki-duotone ki-eye fs-4 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                                    Open
                                                </button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            if (data.data.external.length > 0) {
                html += `
                    <div>
                        <h5 class="fw-bold mb-4 text-gray-800">External Links (Submit Manually)</h5>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                        <th class="text-start min-w-150px">Seeker</th>
                                        <th class="text-start min-w-200px">Job</th>
                                        <th class="text-end min-w-200px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.data.external.map(e => `
                                        <tr>
                                            <td class="text-start">
                                                <div class="fw-bold text-gray-800">${escapeHtml(e.user_name)}</div>
                                                <div class="text-muted fs-8">${escapeHtml(e.user_email)}</div>
                                            </td>
                                            <td class="text-start">
                                                <div class="fw-semibold text-gray-800">${escapeHtml(e.job_title)}</div>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-primary open-drawer-btn" data-id="${e.id}" data-type="external">
                                                    <i class="ki-duotone ki-eye fs-4 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                                    Open
                                                </button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            wrap.innerHTML = html;

            wrap.querySelectorAll('.open-drawer-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    openDrawer(parseInt(this.dataset.id), this.dataset.type);
                });
            });

        } catch (e) {
            wrap.innerHTML = `<div class="alert alert-danger">Failed to load drafts.</div>`;
        }
    }

    // ═══════════════════════════════════════════════════════════
    // Open Drawer
    // ═══════════════════════════════════════════════════════════
    function openDrawer(id, type) {
        const list = type === 'external' ? currentData.external : currentData.relays;
        const row  = list.find(x => x.id === id);
        if (!row) return;

        // ← Reset buttons (in case previous drawer was history)
        document.getElementById('editCvBtn').style.display = 'inline-flex';
        document.getElementById('editCoverBtn').style.display = 'inline-flex';

        currentDraft = {
            id: row.id,
            type: row.type,
            cv_html: row.cv_html || '',
            cover_html: row.cover_html || '',
            job_requirements: row.job_requirements || {},
            user_email: row.user_email,
            job_title: row.job_title,
            channel: row.channel || 'manual',
            external_url: row.external_url || null,
        };

        // Header
        document.getElementById('drawerTitle').textContent = type === 'external' ? 'External Draft' : 'Email/WhatsApp Draft';
        document.getElementById('drawerSeekerName').textContent  = row.user_name;
        document.getElementById('drawerSeekerEmail').textContent = row.user_email;
        document.getElementById('drawerJobTitle').textContent    = row.job_title;
        document.getElementById('drawerChannel').textContent     = capitalize(row.channel || 'manual');

        // 1. Requirements Badges
        const reqs = row.job_requirements || {};
        const reqWrap = document.getElementById('requirementsWrap');
        const reqItems = [];
        if (reqs.is_resume_required)              reqItems.push({ label: 'CV Required',             color: 'success' });
        if (reqs.is_cover_letter_required)        reqItems.push({ label: 'Cover Letter Required',   color: 'info' });
        if (reqs.is_academic_documents_required)  reqItems.push({ label: 'Academic Docs Required',  color: 'warning' });

        reqWrap.innerHTML = reqItems.map(r =>
            `<span class="badge badge-light-${r.color} fs-7">${r.label}</span>`
        ).join('') || '<span class="badge badge-light-secondary fs-7">No specific requirements</span>';

        // 2. Employer Contact / Link Info
        const contactWrap = document.getElementById('employerContactWrap');
        let contactHtml = '';

        if (type === 'external') {
            contactHtml += `<div class="mb-2"><strong>Application Link:</strong> <a href="${row.external_url}" target="_blank" class="text-primary">${row.external_url}</a></div>`;
            contactHtml += `
                <div class="d-flex gap-2 mt-3">
                    <a href="${row.external_url}" target="_blank" class="btn btn-sm btn-light-primary">
                        <i class="ki-duotone ki-exit-right-corner fs-4 me-1"><span class="path1"></span><span class="path2"></span></i> 
                        Open Application Page
                    </a>
                    <button class="btn btn-sm btn-warning" id="drawerMarkAppliedBtn">
                        <i class="ki-duotone ki-check-circle fs-4 me-1"><span class="path1"></span><span class="path2"></span></i> 
                        Mark Applied & Send to Seeker
                    </button>
                </div>
            `;
        } else {
            // Try to get employer email from the row, or parse it from the text
            let employerEmail = row.employer_email;
            if (!employerEmail && row.qualifications) {
                const match = row.qualifications.match(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i);
                if (match) employerEmail = match[0];
            }
            if (!employerEmail && row.application_procedure) {
                const match = row.application_procedure.match(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i);
                if (match) employerEmail = match[0];
            }

            contactHtml += `<div><strong>Sending To:</strong> ${employerEmail || '<span class="text-danger">Email not found in Job Post</span>'}</div>`;
            contactHtml += `<div class="mt-1"><strong>CC:</strong> ${row.user_email} (Seeker)</div>`;
        }
        contactWrap.innerHTML = contactHtml;

        // 3. Bind Mark Applied button
        document.getElementById('drawerMarkAppliedBtn')?.addEventListener('click', async function() {
            if (!confirm('Mark as applied? This will notify the seeker via WhatsApp.')) return;
            
            const btn = this;
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

            try {
                const res = await fetch(`{{ url('admin/auto-apply/mark') }}/${id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                if (data.success) {
                    window.showToast?.('success', data.message);
                    drawer.hide();
                    loadDrafts(); // Refresh the list
                } else {
                    window.showToast?.('error', data.message);
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            } catch (e) {
                window.showToast?.('error', 'Network error.');
                btn.disabled = false;
                btn.innerHTML = original;
            }
        });

        // Reset to CV tab
        new bootstrap.Tab(document.querySelector('#drawerTabs a[href="#doc_cv"]')).show();

        // Render previews
        renderPreview('cv',   row.cv_html    || '');
        renderPreview('cover', row.cover_html || '');

        // Download links
        document.getElementById('downloadCvBtn').href    = `{{ url('admin/auto-apply/download') }}/${id}?type=${type}&document=cv`;
        document.getElementById('downloadCoverBtn').href = `{{ url('admin/auto-apply/download') }}/${id}?type=${type}&document=cover`;

        // Hide send button for external
        document.getElementById('drawerSendBtn').style.display = type === 'external' ? 'none' : 'inline-flex';

        drawer.show();
    }

    // ═══════════════════════════════════════════════════════════
    // Load Applied History
    // ═══════════════════════════════════════════════════════════
    async function loadHistory() {
        const wrap = document.getElementById('historyWrap');
        wrap.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        try {
            const res = await fetch('{{ route("admin.auto-apply.history") }}', { 
                headers: { 'Accept': 'application/json' } 
            });

            // Handle non-JSON responses (500 errors)
            let data;
            try {
                data = await res.json();
            } catch (parseErr) {
                console.error('History API returned non-JSON:', await res.text());
                throw new Error(`Server returned ${res.status} (not JSON). Check laravel.log.`);
            }

            if (!res.ok) {
                throw new Error(data.message || `HTTP ${res.status}`);
            }

            if (!data.success || !data.data?.data?.length) {
                wrap.innerHTML = `<div class="text-center py-15">
                    <i class="ki-duotone ki-information-5 fs-5tx text-muted mb-4 d-block"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <h4 class="fw-bold text-gray-800 mb-2">No applied history yet</h4>
                    <p class="text-muted">Jobs you mark as applied will appear here.</p>
                </div>`;
                return;
            }

            currentHistoryData = data.data.data;

            wrap.innerHTML = `
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-7 gy-3">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="text-start">Date Applied</th>
                                <th class="text-start">Seeker</th>
                                <th class="text-start">Job / Company</th>
                                <th class="text-start">Source</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.data.data.map(h => `
                                <tr>
                                    <td class="text-start text-muted">${new Date(h.applied_at).toLocaleString()}</td>
                                    <td class="text-start">
                                        <div class="fw-bold">${escapeHtml(h.seeker_name)}</div>
                                        <div class="text-muted fs-8">${escapeHtml(h.seeker_email || '')}</div>
                                    </td>
                                    <td class="text-start">
                                        <div class="fw-semibold text-gray-800">${escapeHtml(h.job_title)}</div>
                                        <div class="text-muted fs-8">${escapeHtml(h.company || '')}</div>
                                    </td>
                                    <td class="text-start">
                                        <span class="badge badge-light-success fs-8">${escapeHtml(h.source || 'Applied')}</span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary" onclick="openHistoryDrawer(${h.id})">
                                            <i class="ki-duotone ki-eye fs-4 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                            Details
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } catch (e) {
            console.error('History load error:', e);
            wrap.innerHTML = `<div class="alert alert-danger">
                <strong>Failed to load history.</strong><br>
                <small>${escapeHtml(e.message)}</small>
            </div>`;
        }
    }

        // Add this new function to open the history inside the drawer
    window.openHistoryDrawer = function(id) {
        const row = currentHistoryData.find(x => x.id === id);
        if (!row) return;

        // Setup drawer as read-only
        document.getElementById('drawerTitle').textContent = 'Applied History (Read-Only)';
        document.getElementById('drawerSeekerName').textContent  = row.seeker_name;
        document.getElementById('drawerSeekerEmail').textContent = row.seeker_email;
        document.getElementById('drawerJobTitle').textContent    = row.job_title;
        document.getElementById('drawerChannel').textContent     = 'Applied';

        // Hide Edit buttons for read-only
        document.getElementById('editCvBtn').style.display = 'none';
        document.getElementById('editCoverBtn').style.display = 'none';
        
        // Hide the Send button (history is read-only)
        document.getElementById('drawerSendBtn').style.display = 'none';

        // Show job info and links
        const contactWrap = document.getElementById('employerContactWrap');
        contactWrap.innerHTML = `
            <div class="mb-2"><strong>Applied On:</strong> ${new Date(row.applied_at).toLocaleString()}</div>
            <div class="mb-2"><strong>Employer Email:</strong> ${row.employer_email || 'N/A'}</div>
            <div class="mb-2"><strong>Employer Phone:</strong> ${row.employer_phone || 'N/A'}</div>
            ${row.job_url ? `<div class="mb-2"><strong>Job URL:</strong> <a href="${row.job_url}" target="_blank" class="text-primary">${row.job_url}</a></div>` : ''}
        `;

        // Hide requirements badges (not needed in read-only)
        document.getElementById('requirementsWrap').innerHTML = '<span class="badge badge-light-success">Applied</span>';

        // Render HTML
        renderPreview('cv', row.cv_html);
        renderPreview('cover', row.cover_html);

        // Reset to CV tab
        new bootstrap.Tab(document.querySelector('#drawerTabs a[href="#doc_cv"]')).show();

        // Show drawer
        drawer.show();
    };

    // ═══════════════════════════════════════════════════════════
    // Preview / Edit Rendering
    // ═══════════════════════════════════════════════════════════
    function renderPreview(kind, html) {
        const previewEl = document.getElementById(kind === 'cv' ? 'cvPreview' : 'coverPreview');
        const editorWrap = document.getElementById(kind === 'cv' ? 'cvEditorWrap' : 'coverEditorWrap');
        const editorEl   = document.getElementById(kind === 'cv' ? 'cvEditor' : 'coverEditor');

        previewEl.innerHTML = html || '<p class="text-muted">No content generated.</p>';
        previewEl.classList.remove('d-none');
        editorWrap.classList.add('d-none');
    }

    function startEdit(kind) {
        const previewEl = document.getElementById(kind === 'cv' ? 'cvPreview' : 'coverPreview');
        const editorWrap = document.getElementById(kind === 'cv' ? 'cvEditorWrap' : 'coverEditorWrap');
        const editorEl   = document.getElementById(kind === 'cv' ? 'cvEditor' : 'coverEditor');

        const html = kind === 'cv' ? currentDraft.cv_html : currentDraft.cover_html;

        previewEl.classList.add('d-none');
        editorWrap.classList.remove('d-none');

        initEditor(kind, html || '');
    }

    function initEditor(kind, content) {
        const targetId = kind === 'cv' ? 'cvEditor' : 'coverEditor';
        const instance = kind === 'cv' ? cvEditorInstance : coverEditorInstance;

        if (instance) {
            instance.setContent(content);
            instance.focus();
            return;
        }

        tinymce.init({
            target: document.getElementById(targetId),
            height: 420,
            menubar: false,
            statusbar: true,
            branding: false,
            promotion: false,
            plugins: 'lists link code wordcount',
            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | alignleft aligncenter alignright | removeformat | code',
            content_style: `
                body { font-family: 'Georgia', serif; font-size: 13px; line-height: 1.6; padding: 16px; color: #0F1B2D; }
                p { margin: 0 0 1em; }
                h1, h2, h3 { margin: 0.8em 0 0.4em; font-weight: bold; }
                ul, ol { margin: 0.4em 0 0.6em 1.4em; }
                li { margin: 0.2em 0; }
                table { border-collapse: collapse; width: 100%; }
                td, th { border: 1px solid #eee; padding: 4px 8px; }
            `,
            setup: (editor) => {
                editor.on('init', () => {
                    if (kind === 'cv') cvEditorInstance = editor;
                    else coverEditorInstance = editor;
                    editor.setContent(content);
                    editor.focus();
                });
            }
        });
    }

    // ═══════════════════════════════════════════════════════════
    // Wire edit buttons
    // ═══════════════════════════════════════════════════════════
    document.getElementById('editCvBtn')?.addEventListener('click', () => startEdit('cv'));
    document.getElementById('editCoverBtn')?.addEventListener('click', () => startEdit('cover'));

    document.getElementById('cancelCvBtn')?.addEventListener('click', () => renderPreview('cv', currentDraft.cv_html));
    document.getElementById('cancelCoverBtn')?.addEventListener('click', () => renderPreview('cover', currentDraft.cover_html));

    // Save handlers with proper spinner
    document.getElementById('saveCvBtn')?.addEventListener('click', function () {
        saveDraft('cv', this);
    });
    document.getElementById('saveCoverBtn')?.addEventListener('click', function () {
        saveDraft('cover', this);
    });

    async function saveDraft(kind, btn) {
        const editor = kind === 'cv' ? cvEditorInstance : coverEditorInstance;
        if (!editor) return;

        const content = editor.getContent();
        if (!content || content.trim().length < 20) {
            window.showToast?.('error', 'The content is too short.');
            return;
        }

        const label = btn.querySelector('.indicator-label');
        const prog  = btn.querySelector('.indicator-progress');
        btn.disabled = true;
        label?.classList.add('d-none');
        prog?.classList.remove('d-none');

        try {
            const res = await fetch(`{{ url('admin/auto-apply/update-draft') }}/${currentDraft.id}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    type: currentDraft.type,
                    document: kind,
                    content: content
                })
            });
            const data = await res.json();

            if (data.success) {
                // Update local state
                if (kind === 'cv') currentDraft.cv_html = content;
                else currentDraft.cover_html = content;

                window.showToast?.('success', 'Draft saved.');

                // Immediately re-render preview (no page reload)
                renderPreview(kind, content);

                // Also update in-memory data so opening again shows new content
                const list = currentDraft.type === 'external' ? currentData.external : currentData.relays;
                const row  = list.find(x => x.id === currentDraft.id);
                if (row) {
                    if (kind === 'cv') row.cv_html = content;
                    else row.cover_html = content;
                }
            } else {
                window.showToast?.('error', data.message || 'Failed to save.');
            }
        } catch (e) {
            window.showToast?.('error', 'Network error.');
        } finally {
            btn.disabled = false;
            label?.classList.remove('d-none');
            prog?.classList.add('d-none');
        }
    }

    // Copy buttons
    document.getElementById('copyCvBtn')?.addEventListener('click', () => copyToClipboard(currentDraft.cv_html));
    document.getElementById('copyCoverBtn')?.addEventListener('click', () => copyToClipboard(currentDraft.cover_html));

    function copyToClipboard(html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html || '';
        const text = tmp.innerText || tmp.textContent || '';
        navigator.clipboard.writeText(text)
            .then(() => window.showToast?.('success', 'Copied.'))
            .catch(() => window.showToast?.('error', 'Failed to copy.'));
    }

    // ═══════════════════════════════════════════════════════════
    // Send — show email preview modal first
    // ═══════════════════════════════════════════════════════════
    document.getElementById('drawerSendBtn')?.addEventListener('click', () => {
        if (!currentDraft || currentDraft.type === 'external') return;

        // Populate email preview
        document.getElementById('emailTo').textContent      = currentDraft.user_email || '—';
        document.getElementById('emailSubject').textContent = `Application for ${currentDraft.job_title}`;

        // Attachments list — computed from job requirements
        const reqs = currentDraft.job_requirements || {};
        const attach = [];
        if (reqs.is_resume_required)              attach.push({ label: 'CV (PDF)',            color: 'success' });
        if (reqs.is_cover_letter_required)        attach.push({ label: 'Cover Letter (PDF)',  color: 'info' });
        if (reqs.is_academic_documents_required)  attach.push({ label: 'Academic Docs (PDF)', color: 'warning' });
        if (attach.length === 0) attach.push({ label: 'No documents required', color: 'secondary' });

        document.getElementById('emailAttachments').innerHTML = attach.map(a =>
            `<span class="badge badge-light-${a.color} fs-7">${a.label}</span>`
        ).join('');

        // Body — the cover letter (or CV summary fallback)
        document.getElementById('emailBody').innerHTML =
            currentDraft.cover_html || '<p class="text-muted">No cover letter body.</p>';

        emailModal.show();
    });

    // Confirm send
    document.getElementById('confirmSendBtn')?.addEventListener('click', async function () {
        if (!currentDraft) return;

        const label = this.querySelector('.indicator-label');
        const prog  = this.querySelector('.indicator-progress');
        this.disabled = true;
        label?.classList.add('d-none');
        prog?.classList.remove('d-none');

        try {
            const res = await fetch(`{{ url('admin/auto-apply/send') }}/${currentDraft.id}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (data.success) {
                window.showToast?.('success', data.message || 'Sent.');
                emailModal.hide();
                drawer.hide();
                loadDrafts();
            } else {
                window.showToast?.('error', data.message || 'Send failed.');
            }
        } catch (e) {
            window.showToast?.('error', 'Network error.');
        } finally {
            this.disabled = false;
            label?.classList.remove('d-none');
            prog?.classList.add('d-none');
        }
    });

    // ═══════════════════════════════════════════════════════════
    // Load Logs (with drawer integration)
    // ═══════════════════════════════════════════════════════════
    async function loadLogs() {
        const wrap = document.getElementById('logsWrap');
        wrap.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        try {
            const res = await fetch('{{ route("admin.auto-apply.logs") }}', { headers: { 'Accept': 'application/json' } });
            const data = await res.json();

            if (!data.success || !data.data?.data?.length) {
                wrap.innerHTML = `<div class="text-center py-15"><h4 class="fw-bold">No activity yet.</h4></div>`;
                return;
            }

            wrap.innerHTML = `
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-7 gy-3">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="text-start">Date</th>
                                <th class="text-start">Seeker</th>
                                <th class="text-center">Scanned</th>
                                <th class="text-center">Applied</th>
                                <th class="text-center">Relayed</th>
                                <th class="text-center">Failed</th>
                                <th class="text-start">Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.data.data.map(l => `
                                <tr>
                                    <td class="text-start text-muted">${escapeHtml(l.run_date)}</td>
                                    <td class="text-start">
                                        <div class="fw-bold">${escapeHtml(l.user_name)}</div>
                                        <div class="text-muted fs-8">${escapeHtml(l.user_email)}</div>
                                    </td>
                                    <td class="text-center"><span class="badge badge-light-secondary">${l.jobs_scanned}</span></td>
                                    <td class="text-center"><span class="badge badge-light-success">${l.applied_count}</span></td>
                                    <td class="text-center"><span class="badge badge-light-warning">${l.relayed_count}</span></td>
                                    <td class="text-center">
                                        ${l.failed_count > 0
                                            ? `<span class="badge badge-light-danger" title="${escapeHtml(l.error_message || '')}">${l.failed_count}</span>`
                                            : '0'}
                                    </td>
                                    <td class="text-start">
                                        ${l.status_badge}
                                        ${l.error_message ? `<div class="text-danger fs-8 mt-1" style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escapeHtml(l.error_message)}">${escapeHtml(l.error_message)}</div>` : ''}
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light-warning retry-run-btn" data-user-id="${l.user_id}">
                                            <span class="indicator-label">
                                                <i class="ki-duotone ki-arrows-circle fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                                                Retry
                                            </span>
                                            <span class="indicator-progress">Retrying... <span class="spinner-border spinner-border-sm ms-2"></span></span>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;

            wrap.querySelectorAll('.retry-run-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    if (!confirm('Clear failed drafts and retry for this user?')) return;

                    const label = this.querySelector('.indicator-label');
                    const prog  = this.querySelector('.indicator-progress');
                    this.disabled = true;
                    label?.classList.add('d-none');
                    prog?.classList.remove('d-none');

                    fetch(`{{ url('admin/auto-apply/retry-user') }}/${this.dataset.userId}`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            window.showToast?.('success', d.message);
                            setTimeout(() => { loadLogs(); loadDrafts(); }, 1500);
                        } else {
                            window.showToast?.('error', d.message);
                        }
                    })
                    .catch(() => window.showToast?.('error', 'Network error.'))
                    .finally(() => {
                        this.disabled = false;
                        label?.classList.remove('d-none');
                        prog?.classList.add('d-none');
                    });
                });
            });
        } catch (e) {
            wrap.innerHTML = `<div class="alert alert-danger">Failed to load logs.</div>`;
        }
    }

    // ── Helpers ──
    function capitalize(t) { return t ? t.charAt(0).toUpperCase() + t.slice(1) : ''; }
    function escapeHtml(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML;
    }

    // ── Boot ──
    document.getElementById('reloadBtn')?.addEventListener('click', () => { loadDrafts(); loadLogs(); loadHistory(); });
    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(a => {
        a.addEventListener('shown.bs.tab', function (e) {
            const href = e.target.getAttribute('href');
            if (href === '#tab_logs') loadLogs();
            else if (href === '#tab_history') loadHistory();
            else loadDrafts();
        });
    });

    loadDrafts();
})();
</script>
@endpush