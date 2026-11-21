@extends('layouts.admin')

@section('title', 'Auto Apply Agent')
@section('page_title', 'Auto Apply Agent')

@section('content')
@can('manage job alerts')

<div class="container py-6">

    {{-- ── Compact Stats Strip ── --}}
    <div class="row g-3 mb-5">
        <div class="col-6 col-md-3">
            <div class="card bg-light-primary border-0 h-100">
                <div class="card-body py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label bg-primary bg-opacity-10">
                                <i class="ki-duotone ki-document fs-2x text-primary">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                </i>
                            </span>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold text-primary lh-1">{{ $stats['drafts_ready'] }}</div>
                            <div class="text-muted fs-8">Drafts Ready</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card bg-light-success border-0 h-100">
                <div class="card-body py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label bg-success bg-opacity-10">
                                <i class="ki-duotone ki-send fs-2x text-success">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </span>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold text-success lh-1">{{ $stats['sent_today'] }}</div>
                            <div class="text-muted fs-8">Sent Today</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card bg-light-warning border-0 h-100">
                <div class="card-body py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label bg-warning bg-opacity-10">
                                <i class="ki-duotone ki-exit-right-corner fs-2x text-warning">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </span>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold text-warning lh-1">{{ $stats['external_pending'] }}</div>
                            <div class="text-muted fs-8">External Pending</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card bg-light-danger border-0 h-100">
                <div class="card-body py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label bg-danger bg-opacity-10">
                                <i class="ki-duotone ki-information-5 fs-2x text-danger">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                </i>
                            </span>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold text-danger lh-1">{{ $stats['failed_today'] }}</div>
                            <div class="text-muted fs-8">Failed Today</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($topSubs->isNotEmpty())
    <div class="card card-flush mb-6">
        <div class="card-header py-3">
            <h5 class="fw-bold mb-0">Top Applied-For Subscribers</h5>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle fs-7">
                    <thead>
                        <tr class="text-muted fw-bold fs-8 text-uppercase">
                            <th>Subscriber</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">WhatsApp</th>
                            <th class="text-center">External</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Last Applied</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topSubs as $s)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $s->user?->name ?? '—' }}</div>
                                    <div class="text-muted fs-8">{{ $s->user?->email }}</div>
                                </td>
                                <td class="text-center"><span class="badge badge-light-primary">{{ $s->applied_via_email }}</span></td>
                                <td class="text-center"><span class="badge badge-light-success">{{ $s->applied_via_whatsapp }}</span></td>
                                <td class="text-center"><span class="badge badge-light-info">{{ $s->applied_via_external }}</span></td>
                                <td class="text-end"><span class="badge badge-light-secondary fs-6">{{ $s->applied_total }}</span></td>
                                <td class="text-end text-muted">{{ $s->last_applied_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

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

{{-- ══════════════════ EMAIL COMPOSER (Gmail-style) ══════════════════ --}}
<div class="modal fade" id="emailComposeModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title mb-0">
                    <i class="ki-duotone ki-sms fs-3 me-2"><span class="path1"></span><span class="path2"></span></i>
                    Review &amp; Send Application
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">
                <style>
                    .gm-row2 { display:flex; align-items:center; gap:12px; padding:10px 20px; border-bottom:1px solid var(--bs-gray-200); }
                    .gm-row2 > label { width:64px; flex:none; font-size:13px; font-weight:600; color:var(--bs-gray-600); margin:0; }
                    .gm-row2 input { border:0 !important; box-shadow:none !important; background:transparent !important; flex:1; padding:4px 0; }
                    .gm-row2 .gm-toggle2 { font-size:12px; color:var(--bs-primary); cursor:pointer; user-select:none; }
                    .gm-foot2 { padding:14px 20px; background:var(--bs-gray-100); }
                </style>

                {{-- To --}}
                <div class="gm-row2">
                    <label>To</label>
                    <input type="text" id="composeTo" placeholder="hr@company.com">
                </div>

                {{-- Cc (collapsible) --}}
                <div class="gm-row2">
                    <label>Cc</label>
                    <input type="text" id="composeCc" placeholder="extra CC">
                    <span class="gm-toggle2" id="composeToggleBcc">Bcc</span>
                </div>

                {{-- Bcc (hidden by default) --}}
                <div class="gm-row2 d-none" id="composeBccRow">
                    <label>Bcc</label>
                    <input type="text" id="composeBcc" placeholder="bcc emails">
                </div>

                {{-- Subject --}}
                <div class="gm-row2">
                    <label>Subject</label>
                    <input type="text" id="composeSubject" placeholder="Application for …">
                </div>

                {{-- Body (rich editor) --}}
                <div class="p-0">
                    <textarea id="composeBody" rows="16"></textarea>
                </div>

                {{-- Attachments --}}
                <div class="gm-foot2">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="fw-semibold fs-8 text-gray-700">Attachments:</div>
                        <div id="composeAttachments" class="d-flex flex-wrap gap-2"></div>
                    </div>
                    <div class="text-muted fs-8 mt-2">
                        The PDFs are generated from the CV and Cover Letter tabs of the drawer.
                        Edit either tab first if you want to change what gets attached.
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top justify-content-between">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light-info" id="composeSaveDraftBtn">
                        <span class="indicator-label">
                            <i class="ki-duotone ki-save fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                            Save Draft
                        </span>
                        <span class="indicator-progress">Saving… <span class="spinner-border spinner-border-sm ms-2"></span></span>
                    </button>
                    <button type="button" class="btn btn-primary" id="composeSendBtn">
                        <span class="indicator-label">
                            <i class="ki-duotone ki-send fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                            Send Application
                        </span>
                        <span class="indicator-progress">Sending… <span class="spinner-border spinner-border-sm ms-2"></span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════ WHATSAPP COMPOSER ══════════════════ --}}
<div class="modal fade" id="whatsappComposeModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title mb-0">
                    <i class="ki-duotone ki-whatsapp fs-3 me-2 text-success"></i>
                    Send via WhatsApp
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">
                <style>
                    .wa-row { display:flex; align-items:center; gap:12px; padding:10px 20px; border-bottom:1px solid var(--bs-gray-200); }
                    .wa-row > label { width:84px; flex:none; font-size:13px; font-weight:600; color:var(--bs-gray-600); margin:0; }
                    .wa-row input { border:0 !important; box-shadow:none !important; background:transparent !important; flex:1; padding:4px 0; }
                    .wa-foot { padding:14px 20px; background:#E7FFDB; border-top:1px solid #c7f0b8; }
                </style>

                {{-- To --}}
                <div class="wa-row">
                    <label>WhatsApp</label>
                    <input type="tel" id="waComposeTo" placeholder="+256 7XX XXX XXX" autocomplete="off">
                </div>

                {{-- Message --}}
                <div class="p-0">
                    <textarea id="waComposeBody" rows="14" class="form-control border-0 rounded-0" placeholder="Type your message…"></textarea>
                </div>

                {{-- Attachments (informational — WhatsApp sends text; PDFs would be uploaded via WhatsAppChannel separately) --}}
                <div class="wa-foot">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="fw-semibold fs-8 text-gray-700">Attachments:</div>
                        <div id="waComposeAttachments" class="d-flex flex-wrap gap-2"></div>
                    </div>
                    <div class="text-muted fs-8 mt-2">
                        <i class="ki-duotone ki-information-5 fs-7 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        The CV and cover letter are referenced in the message text. The WhatsApp channel
                        handles document delivery per the Meta Cloud API workflow.
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top justify-content-between">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="waComposeSendBtn">
                    <span class="indicator-label">
                        <i class="ki-duotone ki-whatsapp fs-3 me-1"></i>
                        Send on WhatsApp
                    </span>
                    <span class="indicator-progress">Sending… <span class="spinner-border spinner-border-sm ms-2"></span></span>
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

    
    // Retry buttons
    let retryInFlight = false;

    // ═══════════════════════════════════════════════════════════
    // Bootstraps
    // ═══════════════════════════════════════════════════════════
    const drawerEl    = document.getElementById('actionDrawer');
    const drawer      = new bootstrap.Offcanvas(drawerEl);

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
                            setTimeout(() => { progressEl.classList.add('d-none'); loadDrafts(1); }, 2000);
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


    let draftsState = {
        page: 1,
        search: '',
        type: '',        // '' | 'relay' | 'external'
        channel: '',     // '' | 'email' | 'whatsapp'
        per_page: 20,
    };

    async function loadDrafts(page = 1) {
        const wrap = document.getElementById('draftsWrap');
        wrap.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        draftsState.page = page;

        const params = new URLSearchParams();
        params.set('page', page);
        params.set('per_page', draftsState.per_page);
        if (draftsState.search)  params.set('search', draftsState.search);
        if (draftsState.type)    params.set('type', draftsState.type);
        if (draftsState.channel) params.set('channel', draftsState.channel);

        try {
            const res = await fetch(`{{ route("admin.auto-apply.drafts") }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (!data.success) throw new Error('Request failed.');

            const total = data.data.total ?? 0;

            if (total === 0) {
                wrap.innerHTML = `<div class="text-center py-15">
                    <i class="ki-duotone ki-information-5 fs-5tx text-muted mb-4 d-block"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <h4 class="fw-bold text-gray-800 mb-2">No drafts yet</h4>
                    <p class="text-muted">${draftsState.search || draftsState.type || draftsState.channel ? 'No results match your filters.' : 'Trigger the drafting process above.'}</p>
                </div>`;
                return;
            }

            // IMPORTANT: keep the "current page" list for drawer lookups
            currentData = data.data;

            const p = data.pagination;

            let body = '';

            if (data.data.relays.length > 0) {
                body += `
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
                                                <div class="d-flex justify-content-end gap-1">
                                                    <button class="btn btn-sm btn-primary open-drawer-btn" data-id="${r.id}" data-type="relay">
                                                        <i class="ki-duotone ki-eye fs-4 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                                        Open
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-light-danger delete-draft-btn"
                                                            data-id="${r.id}" data-type="relay" title="Delete draft">
                                                        <i class="ki-duotone ki-trash fs-4">
                                                            <span class="path1"></span><span class="path2"></span>
                                                            <span class="path3"></span><span class="path4"></span>
                                                        </i>
                                                    </button>
                                                </div>
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
                body += `
                    <div class="mb-8">
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
                                                <div class="d-flex justify-content-end gap-1">
                                                    <button class="btn btn-sm btn-primary open-drawer-btn" data-id="${e.id}" data-type="external">
                                                        <i class="ki-duotone ki-eye fs-4 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                                        Open
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-light-danger delete-draft-btn"
                                                            data-id="${e.id}" data-type="external" title="Delete draft">
                                                        <i class="ki-duotone ki-trash fs-4">
                                                            <span class="path1"></span><span class="path2"></span>
                                                            <span class="path3"></span><span class="path4"></span>
                                                        </i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            wrap.innerHTML = `
                {{-- Filter bar --}}
                <div class="d-flex flex-wrap gap-2 mb-5">
                    <div class="flex-grow-1" style="min-width:220px;">
                        <input type="text" class="form-control form-control-sm" id="draftsSearchInput"
                            placeholder="Search seeker, email, job, employer email…"
                            value="${escapeHtml(draftsState.search)}">
                    </div>
                    <select class="form-select form-select-sm w-auto" id="draftsTypeFilter">
                        <option value="">All Types</option>
                        <option value="relay"    ${draftsState.type === 'relay' ? 'selected' : ''}>Email / WhatsApp</option>
                        <option value="external" ${draftsState.type === 'external' ? 'selected' : ''}>External Link</option>
                    </select>
                    <select class="form-select form-select-sm w-auto" id="draftsChannelFilter">
                        <option value="">All Channels</option>
                        <option value="email"    ${draftsState.channel === 'email' ? 'selected' : ''}>Email</option>
                        <option value="whatsapp" ${draftsState.channel === 'whatsapp' ? 'selected' : ''}>WhatsApp</option>
                    </select>
                    <button class="btn btn-sm btn-light-primary" id="draftsApplyFilters">Apply</button>
                    <button class="btn btn-sm btn-light" id="draftsResetFilters">Reset</button>
                </div>

                ${body}

                {{-- Footer: count + pagination --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-4">
                    <div class="text-muted fs-7">
                        Showing <strong>${p.from ?? 0}–${p.to ?? 0}</strong> of <strong>${p.total}</strong>
                        · page <strong>${p.current_page}</strong> of <strong>${p.last_page}</strong>
                    </div>
                    <div id="draftsPagination">${p.html || ''}</div>
                </div>
            `;

            // Wire row-open buttons
            wrap.querySelectorAll('.open-drawer-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    openDrawer(parseInt(this.dataset.id), this.dataset.type);
                });
            });

            // Wire filter buttons
            document.getElementById('draftsApplyFilters')?.addEventListener('click', () => {
                draftsState.search  = document.getElementById('draftsSearchInput').value.trim();
                draftsState.type    = document.getElementById('draftsTypeFilter').value;
                draftsState.channel = document.getElementById('draftsChannelFilter').value;
                loadDrafts(1);
            });

            document.getElementById('draftsResetFilters')?.addEventListener('click', () => {
                draftsState = { page: 1, search: '', type: '', channel: '', per_page: 20 };
                loadDrafts(1);
            });

            document.getElementById('draftsSearchInput')?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') document.getElementById('draftsApplyFilters').click();
            });

            // Wire pagination
            document.querySelectorAll('#draftsPagination .page-link[data-page]').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const page = parseInt(link.dataset.page, 10);
                    if (page && page !== draftsState.page) loadDrafts(page);
                });
            });

        } catch (e) {
            console.error('Drafts load error:', e);
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

        // Reset button visibility (in case previous drawer was history/read-only)
        document.getElementById('downloadCvBtn').style.display    = 'inline-flex';
        document.getElementById('downloadCoverBtn').style.display = 'inline-flex';
        document.getElementById('editCvBtn').style.display        = 'inline-flex';
        document.getElementById('editCoverBtn').style.display     = 'inline-flex';

        // ── Build the drawer state ─────────────────────────────
        currentDraft = {
            id: row.id,
            type: row.type,
            cv_html: row.cv_html || '',
            cover_html: row.cover_html || '',
            job_requirements: row.job_requirements || {},
            user_email: row.user_email,
            employer_email: row.employer_email,
            employer_phone: row.employer_phone || row.telephone || null,
            job_title: row.job_title,
            channel: row.channel || 'manual',
            external_url: row.external_url || null,
            qualifications: row.qualifications || '',
            application_procedure: row.application_procedure || '',
        };

        // ── Header ─────────────────────────────────────────────
        document.getElementById('drawerTitle').textContent = type === 'external'
            ? 'External Draft'
            : (currentDraft.channel === 'whatsapp' ? 'WhatsApp Draft' : 'Email Draft');
        document.getElementById('drawerSeekerName').textContent  = row.user_name;
        document.getElementById('drawerSeekerEmail').textContent = row.user_email;
        document.getElementById('drawerJobTitle').textContent    = row.job_title;
        document.getElementById('drawerChannel').textContent     = capitalize(row.channel || 'manual');

        // ── Requirements badges ────────────────────────────────
        const reqs = row.job_requirements || {};
        const reqWrap = document.getElementById('requirementsWrap');
        const reqItems = [];
        if (reqs.is_resume_required)              reqItems.push({ label: 'CV Required',            color: 'success' });
        if (reqs.is_cover_letter_required)        reqItems.push({ label: 'Cover Letter Required',  color: 'info' });
        if (reqs.is_academic_documents_required)  reqItems.push({ label: 'Academic Docs Required', color: 'warning' });

        reqWrap.innerHTML = reqItems.map(r =>
            `<span class="badge badge-light-${r.color} fs-7">${r.label}</span>`
        ).join('') || '<span class="badge badge-light-secondary fs-7">No specific requirements</span>';

        // ── Employer contact block ─────────────────────────────
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
                </div>`;
        } else {
            // ── Resolve employer email: job field → first from job description → first from procedure ──
            let employerEmail = firstFromCommaList(row.employer_email);
            if (!employerEmail) employerEmail = firstEmailFrom(row.qualifications);
            if (!employerEmail) employerEmail = firstEmailFrom(row.application_procedure);

            // ── Resolve employer phone: job field → first from job description → first from procedure ──
            let employerPhone = firstFromCommaList(row.employer_phone || row.telephone || '');
            if (!employerPhone) employerPhone = firstPhoneFrom(row.qualifications);
            if (!employerPhone) employerPhone = firstPhoneFrom(row.application_procedure);

            // Cache on currentDraft so the Send handler uses the exact same values
            currentDraft.employer_email = employerEmail;
            currentDraft.employer_phone = employerPhone;

            if (currentDraft.channel === 'whatsapp') {
                contactHtml += `<div><strong>Sending To:</strong> ${
                    employerPhone
                        ? `<span class="text-success">${employerPhone}</span> (WhatsApp)`
                        : '<span class="text-danger">Phone not found in Job Post</span>'
                }</div>`;
                contactHtml += `<div class="mt-1"><strong>Channel:</strong> <span class="badge badge-light-success">WhatsApp</span></div>`;
            } else {
                contactHtml += `<div><strong>Sending To:</strong> ${
                    employerEmail
                        ? employerEmail
                        : '<span class="text-danger">Email not found in Job Post</span>'
                }</div>`;
                contactHtml += `<div class="mt-1"><strong>CC:</strong> ${row.user_email} (Seeker)</div>`;
            }

            // Manual "mark applied externally"
            contactHtml += `
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-sm btn-warning" id="drawerMarkRelayAppliedBtn">
                        <i class="ki-duotone ki-check-circle fs-4 me-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Mark Applied (submitted externally)
                    </button>
                </div>`;
        }

        contactWrap.innerHTML = contactHtml;

        // ── Bind external "Mark Applied" ──────────────────────
        document.getElementById('drawerMarkAppliedBtn')?.addEventListener('click', async function () {
            if (!confirm('Mark as applied? This will notify the seeker via WhatsApp.')) return;

            const btn = this;
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

            try {
                const res = await fetch(`{{ url('admin/auto-apply/mark') }}/${id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (data.success) {
                    window.showToast?.('success', data.message);
                    drawer.hide();
                    loadDrafts(draftsState.page);
                    loadLogs(logsState.page);
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

        // ── Bind relay "Mark Applied (submitted externally)" ──
        document.getElementById('drawerMarkRelayAppliedBtn')?.addEventListener('click', async function () {
            if (!confirm(
                'Mark this job as applied externally?\n\n' +
                'Use this when you submitted the application through the employer\'s own portal ' +
                '(not via our email/WhatsApp pipeline).\n\n' +
                'The subscriber\'s counter will be updated and the draft will leave the queue.'
            )) return;

            const btn = this;
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

            try {
                const res = await fetch(`{{ url('admin/auto-apply/mark-relay-applied') }}/${id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (data.success) {
                    window.showToast?.('success', data.message);
                    drawer.hide();
                    loadDrafts(draftsState.page);
                    loadLogs(logsState.page);
                } else {
                    window.showToast?.('error', data.message || 'Failed to mark.');
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            } catch (e) {
                window.showToast?.('error', 'Network error.');
                btn.disabled = false;
                btn.innerHTML = original;
            }
        });

        // ── Reset to CV tab + render previews ─────────────────
        new bootstrap.Tab(document.querySelector('#drawerTabs a[href="#doc_cv"]')).show();

        renderPreview('cv',    row.cv_html    || '');
        renderPreview('cover', row.cover_html || '');

        // ── Download links ─────────────────────────────────────
        document.getElementById('downloadCvBtn').href    = `{{ url('admin/auto-apply/download') }}/${id}?type=${type}&document=cv`;
        document.getElementById('downloadCoverBtn').href = `{{ url('admin/auto-apply/download') }}/${id}?type=${type}&document=cover`;

        // ── Drawer Send button — visibility + label per channel ──
        const sendBtn = document.getElementById('drawerSendBtn');
        if (type === 'external') {
            sendBtn.style.display = 'none';
        } else {
            sendBtn.style.display = 'inline-flex';
            const labelEl = sendBtn.querySelector('.indicator-label');
            if (currentDraft.channel === 'whatsapp') {
                labelEl.innerHTML = '<i class="ki-duotone ki-whatsapp fs-4 me-1"></i> Send via WhatsApp';
            } else {
                labelEl.innerHTML = '<i class="ki-duotone ki-send fs-4 me-1"></i> Send Application';
            }
        }

        drawer.show();
    }

    let historyState = {
        page: 1,
        search: '',
        source: '',
        from: '',
        to: '',
        per_page: 20,
    };

    async function loadHistory(page = 1) {
        const wrap = document.getElementById('historyWrap');
        wrap.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        historyState.page = page;

        const params = new URLSearchParams();
        params.set('page', page);
        params.set('per_page', historyState.per_page);
        if (historyState.search) params.set('search', historyState.search);
        if (historyState.source) params.set('source', historyState.source);
        if (historyState.from)   params.set('from', historyState.from);
        if (historyState.to)     params.set('to', historyState.to);

        try {
            const res = await fetch(`{{ route("admin.auto-apply.history") }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });

            let data;
            try {
                data = await res.json();
            } catch (parseErr) {
                console.error('History API returned non-JSON:', await res.text());
                throw new Error(`Server returned ${res.status} (not JSON). Check laravel.log.`);
            }

            if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`);

            if (!data.success) throw new Error('Request failed.');

            currentHistoryData = data.data?.data || [];

            if (currentHistoryData.length === 0) {
                wrap.innerHTML = `
                    <div class="text-center py-15">
                        <i class="ki-duotone ki-information-5 fs-5tx text-muted mb-4 d-block">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                        <h4 class="fw-bold text-gray-800 mb-2">No applied history yet</h4>
                        <p class="text-muted">Jobs applied by auto-apply subscribers will appear here.</p>
                    </div>`;
                return;
            }

            const p = data.pagination;

            wrap.innerHTML = `
                {{-- ── Filter Bar ── --}}
                <div class="d-flex flex-wrap gap-2 mb-5">
                    <div class="flex-grow-1" style="min-width:220px;">
                        <input type="text" class="form-control form-control-sm" id="historySearchInput"
                            placeholder="Search seeker, email, job, company…"
                            value="${escapeHtml(historyState.search)}">
                    </div>
                    <select class="form-select form-select-sm w-auto" id="historySourceFilter">
                        <option value="">All Sources</option>
                        <option value="relay" ${historyState.source === 'relay' ? 'selected' : ''}>Email / WhatsApp</option>
                        <option value="external" ${historyState.source === 'external' ? 'selected' : ''}>External Link</option>
                    </select>
                    <input type="date" class="form-control form-control-sm w-auto" id="historyFromFilter"
                        value="${escapeHtml(historyState.from)}" title="From date">
                    <input type="date" class="form-control form-control-sm w-auto" id="historyToFilter"
                        value="${escapeHtml(historyState.to)}" title="To date">
                    <button class="btn btn-sm btn-light-primary" id="historyApplyFilters">Apply</button>
                    <button class="btn btn-sm btn-light" id="historyResetFilters">Reset</button>
                </div>

                {{-- ── Results Table ── --}}
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
                            ${currentHistoryData.map(h => `
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
                                        <div class="d-flex justify-content-end gap-1">
                                            <button class="btn btn-sm btn-primary" onclick="openHistoryDrawer(${h.id})">
                                                <i class="ki-duotone ki-eye fs-4 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                                Details
                                            </button>
                                            <button class="btn btn-sm btn-icon btn-light-danger delete-history-btn"
                                                    data-id="${h.id}" title="Remove from applied history">
                                                <i class="ki-duotone ki-trash fs-4">
                                                    <span class="path1"></span><span class="path2"></span>
                                                    <span class="path3"></span><span class="path4"></span>
                                                </i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>

                {{-- ── Footer: count + Metronic pagination ── --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-4">
                    <div class="text-muted fs-7">
                        Showing <strong>${p.from ?? 0}–${p.to ?? 0}</strong> of <strong>${p.total}</strong>
                        · page <strong>${p.current_page}</strong> of <strong>${p.last_page}</strong>
                    </div>
                    <div id="historyPagination">
                        ${p.html || ''}
                    </div>
                </div>
            `;

            // ── Wire filter buttons ──
            document.getElementById('historyApplyFilters')?.addEventListener('click', () => {
                historyState.search = document.getElementById('historySearchInput').value.trim();
                historyState.source = document.getElementById('historySourceFilter').value;
                historyState.from   = document.getElementById('historyFromFilter').value;
                historyState.to     = document.getElementById('historyToFilter').value;
                loadHistory(1);
            });

            document.getElementById('historyResetFilters')?.addEventListener('click', () => {
                historyState = { page: 1, search: '', source: '', from: '', to: '', per_page: 20 };
                loadHistory(1);
            });

            // Enter key on search
            document.getElementById('historySearchInput')?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') document.getElementById('historyApplyFilters').click();
            });

            // ── Wire pagination (data-page attributes) ──
            document.querySelectorAll('#historyPagination .page-link[data-page]').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const page = parseInt(link.dataset.page, 10);
                    if (page && page !== historyState.page) loadHistory(page);
                });
            });

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

        document.getElementById('downloadCvBtn').style.display    = 'none';
        document.getElementById('downloadCoverBtn').style.display = 'none';

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
        const previewEl  = document.getElementById(kind === 'cv' ? 'cvPreview'  : 'coverPreview');
        const editorWrap = document.getElementById(kind === 'cv' ? 'cvEditorWrap' : 'coverEditorWrap');
        const html       = kind === 'cv' ? currentDraft.cv_html : currentDraft.cover_html;

        // ⚠️ Reveal the editor FIRST, then init
        previewEl.classList.add('d-none');
        editorWrap.classList.remove('d-none');

        // Give the browser one frame to layout the now-visible wrapper
        requestAnimationFrame(() => {
            initEditor(kind, html || '');
        });
    }

    let cvInitPending = false, coverInitPending = false;

    function initEditor(kind, content) {
        const instance = kind === 'cv' ? cvEditorInstance : coverEditorInstance;
        const pending  = kind === 'cv' ? cvInitPending    : coverInitPending;

        if (instance) {
            instance.setContent(content);
            instance.focus();
            return;
        }
        if (pending) return;

        if (kind === 'cv') cvInitPending = true; else coverInitPending = true;

        // Make sure the textarea holds the content BEFORE init
        const textarea = document.getElementById(kind === 'cv' ? 'cvEditor' : 'coverEditor');
        if (textarea) textarea.value = content;

        tinymce.init({
            target: textarea,
            height: 480,
            menubar: false,
            statusbar: true,
            branding: false,
            promotion: false,
            plugins: 'lists link code wordcount',
            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | alignleft aligncenter alignright | removeformat | code',
            content_style: `
                body { font-family: Georgia, "Times New Roman", serif; font-size: 13px; line-height: 1.6; padding: 16px; color: #0F1B2D; }
                p { margin: 0 0 1em; }
                h1, h2, h3 { margin: 0.8em 0 0.4em; font-weight: bold; }
                ul, ol { margin: 0.4em 0 0.6em 1.4em; }
                li { margin: 0.2em 0; }
                table { border-collapse: collapse; width: 100%; }
                td, th { border: 1px solid #eee; padding: 4px 8px; }
            `,
            setup: (editor) => {
                editor.on('init', () => {
                    if (kind === 'cv') { cvEditorInstance = editor; cvInitPending = false; }
                    else               { coverEditorInstance = editor; coverInitPending = false; }

                    // ⚠️ Force content — textarea's .value is not always picked up
                    editor.setContent(content || '');
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
    // Compose modal (Gmail-style) — boot
    // ═══════════════════════════════════════════════════════════
    const composeModalEl = document.getElementById('emailComposeModal');
    const composeModal   = new bootstrap.Modal(composeModalEl);

    // ═══════════════════════════════════════════════════════════
    // TinyMCE — one editor for the composer body only
    // ═══════════════════════════════════════════════════════════
    let composeEditor = null;

    tinymce.init({
        target: document.getElementById('composeBody'),
        height: 340,
        menubar: false,
        branding: false,
        promotion: false,
        statusbar: false,
        plugins: 'lists link',
        toolbar: 'bold italic underline | bullist numlist | link | removeformat',
        content_style: `body { font-family: Georgia, "Times New Roman", serif; font-size: 13px; line-height: 1.6; padding: 14px; color: #0F1B2D; }`,
        setup: (ed) => ed.on('init', () => { composeEditor = ed; }),
    });

    // Bcc toggle
    document.getElementById('composeToggleBcc')?.addEventListener('click', () => {
        document.getElementById('composeBccRow').classList.toggle('d-none');
    });

    // ═══════════════════════════════════════════════════════════
    // Drawer "Send Application" — email OR WhatsApp depending on channel
    // ═══════════════════════════════════════════════════════════
    document.getElementById('drawerSendBtn')?.addEventListener('click', () => {
        if (!currentDraft || currentDraft.type === 'external') return;

        // ── Branch: WhatsApp-only draft ──
        if (currentDraft.channel === 'whatsapp') {
            openWhatsAppComposer();
            return;
        }

        // ── Email flow ──
        let toEmail = firstFromCommaList(currentDraft.employer_email || '');
        if (!toEmail) toEmail = firstEmailFrom(currentDraft.qualifications);
        if (!toEmail) toEmail = firstEmailFrom(currentDraft.application_procedure);

        document.getElementById('composeTo').value      = toEmail || '';
        document.getElementById('composeCc').value      = currentDraft.user_email || '';
        document.getElementById('composeBcc').value     = '';
        document.getElementById('composeSubject').value = `Application for ${currentDraft.job_title || ''}`;
        document.getElementById('composeBccRow').classList.add('d-none');

        const body = currentDraft.cover_html
            || `<p>Dear Hiring Manager,</p><p>Please find attached my CV and cover letter for the ${escapeHtml(currentDraft.job_title || 'advertised position')}.</p><p>Thank you for your consideration.</p>`;

        if (composeEditor) {
            composeEditor.setContent(body);
        } else {
            const wait = setInterval(() => {
                if (composeEditor) { composeEditor.setContent(body); clearInterval(wait); }
            }, 100);
        }

        const reqs = currentDraft.job_requirements || {};
        const attach = [];
        if (reqs.is_resume_required)              attach.push({ label: 'CV (PDF)',            color: 'success' });
        if (reqs.is_cover_letter_required)        attach.push({ label: 'Cover Letter (PDF)',  color: 'info' });
        if (reqs.is_academic_documents_required)  attach.push({ label: 'Academic Docs (PDF)', color: 'warning' });
        if (attach.length === 0)                  attach.push({ label: 'No documents required', color: 'secondary' });

        document.getElementById('composeAttachments').innerHTML = attach.map(a =>
            `<span class="badge badge-light-${a.color} fs-7 py-2 px-3">
                <i class="ki-duotone ki-paper-clip fs-6 me-1"><span class="path1"></span><span class="path2"></span></i>
                ${a.label}
            </span>`
        ).join('');

        composeModal.show();
    });

    // ═══════════════════════════════════════════════════════════
    // WhatsApp composer
    // ═══════════════════════════════════════════════════════════
    const waModalEl = document.getElementById('whatsappComposeModal');
    const waModal   = new bootstrap.Modal(waModalEl);

    function openWhatsAppComposer() {
        let toPhone = firstFromCommaList(currentDraft.employer_phone || '');
        if (!toPhone) toPhone = firstPhoneFrom(currentDraft.qualifications);
        if (!toPhone) toPhone = firstPhoneFrom(currentDraft.application_procedure);

        document.getElementById('waComposeTo').value = toPhone || '';

        const bodyText = currentDraft.cover_html
            ? plainTextFromHtml(currentDraft.cover_html)
            : `Hello, I am applying for the ${currentDraft.job_title || 'advertised position'}. Please find attached my CV and cover letter.`;

        document.getElementById('waComposeBody').value = bodyText;

        // Attachments badges
        const reqs = currentDraft.job_requirements || {};
        const attach = [];
        if (reqs.is_resume_required)              attach.push({ label: 'CV (PDF)',            color: 'success' });
        if (reqs.is_cover_letter_required)        attach.push({ label: 'Cover Letter (PDF)',  color: 'info' });
        if (reqs.is_academic_documents_required)  attach.push({ label: 'Academic Docs (PDF)', color: 'warning' });
        if (attach.length === 0)                  attach.push({ label: 'No documents required', color: 'secondary' });

        document.getElementById('waComposeAttachments').innerHTML = attach.map(a =>
            `<span class="badge badge-light-${a.color} fs-7 py-2 px-3">
                <i class="ki-duotone ki-paper-clip fs-6 me-1"><span class="path1"></span><span class="path2"></span></i>
                ${a.label}
            </span>`
        ).join('');

        waModal.show();
    }

    function plainTextFromHtml(html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html || '';
        // Convert block elements to line breaks so WhatsApp renders cleanly
        tmp.querySelectorAll('p, h1, h2, h3, li, div').forEach(el => {
            el.appendChild(document.createTextNode('\n'));
        });
        return (tmp.innerText || tmp.textContent || '').trim();
    }

    // ── Send handler ─────────────────────────────────────────
    document.getElementById('waComposeSendBtn')?.addEventListener('click', async function () {
        if (!currentDraft) return;

        const to = document.getElementById('waComposeTo').value.trim();
        if (!to) return window.showToast?.('error', 'Enter a WhatsApp number.');

        const message = document.getElementById('waComposeBody').value.trim();
        if (!message || message.length < 20) {
            return window.showToast?.('error', 'Message is too short.');
        }

        if (!confirm(`Send this application via WhatsApp to ${to}?`)) return;

        const btn   = this;
        const label = btn.querySelector('.indicator-label');
        const prog  = btn.querySelector('.indicator-progress');

        btn.disabled = true;
        label?.classList.add('d-none');
        prog?.classList.remove('d-none');

        try {
            const res = await fetch(`{{ url('admin/auto-apply/send-whatsapp') }}/${currentDraft.id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ phone: to, message: message }),
            });
            const data = await res.json();

            if (data.success) {
                window.showToast?.('success', data.message || 'Application sent via WhatsApp.');
                waModal.hide();
                drawer.hide();
                loadDrafts(draftsState.page);
                loadLogs(logsState.page);
            } else {
                window.showToast?.('error', data.message || 'Send failed.');
                btn.disabled = false;
                label?.classList.remove('d-none');
                prog?.classList.add('d-none');
            }
        } catch (e) {
            console.error(e);
            window.showToast?.('error', 'Network error.');
            btn.disabled = false;
            label?.classList.remove('d-none');
            prog?.classList.add('d-none');
        }
    });

    // ── Save Draft (updates the relay, doesn't send) ────────────
    document.getElementById('composeSaveDraftBtn')?.addEventListener('click', async function () {
        if (!currentDraft) return;

        const btn   = this;
        const label = btn.querySelector('.indicator-label');
        const prog  = btn.querySelector('.indicator-progress');

        const newBody = composeEditor ? composeEditor.getContent() : document.getElementById('composeBody').value;

        btn.disabled = true;
        label.classList.add('d-none');
        prog.classList.remove('d-none');

        try {
            const res = await fetch(`{{ url('admin/auto-apply/update-draft') }}/${currentDraft.id}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ type: currentDraft.type, document: 'cover', content: newBody }),
            });
            const data = await res.json();

            if (data.success) {
                // Update local state so reopening shows the edited version
                currentDraft.cover_html = newBody;
                const list = currentDraft.type === 'external' ? currentData.external : currentData.relays;
                const row  = list.find(x => x.id === currentDraft.id);
                if (row) row.cover_html = newBody;

                // Also update the drawer's cover preview so it reflects the edit
                renderPreview('cover', newBody);

                window.showToast?.('success', 'Draft saved.');
            } else {
                window.showToast?.('error', data.message || 'Save failed.');
            }
        } catch (e) {
            window.showToast?.('error', 'Network error.');
        } finally {
            btn.disabled = false;
            label.classList.remove('d-none');
            prog.classList.add('d-none');
        }
    });

    // ── Send (fires the mail with the edited subject + body) ────
    document.getElementById('composeSendBtn')?.addEventListener('click', async function () {
        if (!currentDraft) return;

        const to = document.getElementById('composeTo').value.trim();
        if (!to) return window.showToast?.('error', 'Enter a recipient email.');

        const subject = document.getElementById('composeSubject').value.trim();
        if (!subject) return window.showToast?.('error', 'Enter a subject.');

        const body = composeEditor ? composeEditor.getContent() : document.getElementById('composeBody').value;
        if (!body || body.trim().length < 20) return window.showToast?.('error', 'Message body is too short.');

        if (!confirm(`Send this application to ${to}?`)) return;

        const btn   = this;
        const label = btn.querySelector('.indicator-label');
        const prog  = btn.querySelector('.indicator-progress');

        btn.disabled = true;
        label.classList.add('d-none');
        prog.classList.remove('d-none');

        try {
            const res = await fetch(`{{ url('admin/auto-apply/send') }}/${currentDraft.id}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    to:      to,
                    cc:      document.getElementById('composeCc').value.trim()  || null,
                    bcc:     document.getElementById('composeBcc').value.trim() || null,
                    subject: subject,
                    body:    body,
                }),
            });
            const data = await res.json();

            if (data.success) {
                window.showToast?.('success', data.message || 'Application sent.');
                composeModal.hide();
                drawer.hide();
                loadDrafts(draftsState.page);
            } else {
                window.showToast?.('error', data.message || 'Send failed.');
            }
        } catch (e) {
            window.showToast?.('error', 'Network error.');
        } finally {
            btn.disabled = false;
            label.classList.remove('d-none');
            prog.classList.add('d-none');
        }
    });

    let logsState = {
        page: 1,
        search: '',
        status: '',
        from: '',
        to: '',
        per_page: 20,
    };

    async function loadLogs(page = 1) {
        const wrap = document.getElementById('logsWrap');
        wrap.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        logsState.page = page;

        const params = new URLSearchParams();
        params.set('page', page);
        params.set('per_page', logsState.per_page);
        if (logsState.search) params.set('search', logsState.search);
        if (logsState.status) params.set('status', logsState.status);
        if (logsState.from)   params.set('from', logsState.from);
        if (logsState.to)     params.set('to', logsState.to);

        try {
            const res = await fetch(`{{ route("admin.auto-apply.logs") }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (!data.success) throw new Error('Request failed.');

            if (!data.data?.data?.length) {
                wrap.innerHTML = `<div class="text-center py-15">
                    <h4 class="fw-bold text-gray-800 mb-2">No activity yet.</h4>
                    <p class="text-muted">${logsState.search || logsState.status || logsState.from || logsState.to ? 'No runs match your filters.' : 'Trigger the drafting process above.'}</p>
                </div>`;
                return;
            }

            const p = data.pagination;

            wrap.innerHTML = `
                {{-- Filter bar --}}
                <div class="d-flex flex-wrap gap-2 mb-5">
                    <div class="flex-grow-1" style="min-width:220px;">
                        <input type="text" class="form-control form-control-sm" id="logsSearchInput"
                            placeholder="Search user name or email…"
                            value="${escapeHtml(logsState.search)}">
                    </div>
                    <select class="form-select form-select-sm w-auto" id="logsStatusFilter">
                        <option value="">All Statuses</option>
                        ${[
                            ['queued','Queued'], ['drafting','Drafting'], ['drafted','Drafted'],
                            ['processing','Processing'], ['completed','Completed'], ['failed','Failed'],
                            ['quota_exhausted','Quota Exhausted'], ['no_cv','No CV'],
                            ['no_preferences','No Preferences'],
                        ].map(([v,l]) =>
                            `<option value="${v}" ${logsState.status === v ? 'selected' : ''}>${l}</option>`
                        ).join('')}
                    </select>
                    <input type="date" class="form-control form-control-sm w-auto" id="logsFromFilter"
                        value="${escapeHtml(logsState.from)}" title="From date">
                    <input type="date" class="form-control form-control-sm w-auto" id="logsToFilter"
                        value="${escapeHtml(logsState.to)}" title="To date">
                    <button class="btn btn-sm btn-light-primary" id="logsApplyFilters">Apply</button>
                    <button class="btn btn-sm btn-light" id="logsResetFilters">Reset</button>
                </div>

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
                                        <div class="d-flex justify-content-end gap-1">
                                            <button class="btn btn-sm btn-light-warning retry-run-btn" data-user-id="${l.user_id}">
                                                <span class="indicator-label">
                                                    <i class="ki-duotone ki-arrows-circle fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                                                    Retry
                                                </span>
                                                <span class="indicator-progress d-none">
                                                    Retrying... <span class="spinner-border spinner-border-sm ms-2"></span>
                                                </span>
                                            </button>
                                            <button class="btn btn-sm btn-icon btn-light-danger delete-run-btn"
                                                    data-user-id="${l.user_id}" data-run-id="${l.id}" title="Delete this run">
                                                <i class="ki-duotone ki-trash fs-4">
                                                    <span class="path1"></span><span class="path2"></span>
                                                    <span class="path3"></span><span class="path4"></span>
                                                </i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>

                {{-- Footer: count + pagination --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-4">
                    <div class="text-muted fs-7">
                        Showing <strong>${p.from ?? 0}–${p.to ?? 0}</strong> of <strong>${p.total}</strong>
                        · page <strong>${p.current_page}</strong> of <strong>${p.last_page}</strong>
                    </div>
                    <div id="logsPagination">${p.html || ''}</div>
                </div>
            `;


            // Retry buttons
            wrap.querySelectorAll('.retry-run-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    if (this.dataset.inFlight === '1') return;
                    if (retryInFlight) { window.showToast?.('error', 'Another retry is in progress.'); return; }
                    if (!confirm('Retry only the failed items for this user? Successful drafts and sent applications will be kept.')) return;

                    const label = this.querySelector('.indicator-label');
                    const prog  = this.querySelector('.indicator-progress');

                    retryInFlight = true;
                    this.dataset.inFlight = '1';
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
                            setTimeout(() => { loadLogs(logsState.page); loadDrafts(draftsState.page); }, 1500);
                        } else {
                            window.showToast?.('error', d.message);
                        }
                    })
                    .catch(() => window.showToast?.('error', 'Network error.'))
                    .finally(() => {
                        retryInFlight = false;
                        this.dataset.inFlight = '0';
                        this.disabled = false;
                        label?.classList.remove('d-none');
                        prog?.classList.add('d-none');
                    });
                });
            });

            // Filters
            document.getElementById('logsApplyFilters')?.addEventListener('click', () => {
                logsState.search = document.getElementById('logsSearchInput').value.trim();
                logsState.status = document.getElementById('logsStatusFilter').value;
                logsState.from   = document.getElementById('logsFromFilter').value;
                logsState.to     = document.getElementById('logsToFilter').value;
                loadLogs(1);
            });

            document.getElementById('logsResetFilters')?.addEventListener('click', () => {
                logsState = { page: 1, search: '', status: '', from: '', to: '', per_page: 20 };
                loadLogs(1);
            });

            document.getElementById('logsSearchInput')?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') document.getElementById('logsApplyFilters').click();
            });

            // Pagination
            document.querySelectorAll('#logsPagination .page-link[data-page]').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const page = parseInt(link.dataset.page, 10);
                    if (page && page !== logsState.page) loadLogs(page);
                });
            });

        } catch (e) {
            console.error('Logs load error:', e);
            wrap.innerHTML = `<div class="alert alert-danger">Failed to load logs.</div>`;
        }
    }

    // ── Helpers ──
    function capitalize(t) { return t ? t.charAt(0).toUpperCase() + t.slice(1) : ''; }
    function escapeHtml(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML;
    }

    // "a@x.com, b@y.com; c@z.com" → "a@x.com"
    // "+256700, +256750" → "+256700"
    function firstFromCommaList(raw) {
        if (!raw) return '';
        return String(raw).split(/[,;]+/).map(s => s.trim()).filter(Boolean)[0] || '';
    }

    // Parse the first email from a block of HTML/text
    function firstEmailFrom(text) {
        if (!text) return '';
        const m = text.match(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i);
        return m ? m[0] : '';
    }

    // Parse the first phone number from a block of HTML/text
    // Handles +256..., 07..., 256..., with spaces/dashes/dots
    function firstPhoneFrom(text) {
        if (!text) return '';
        const m = text.match(/(\+\d[\d\s\-().]{7,17}\d|\b0\d{9,10}\b)/);
        return m ? m[0].replace(/[\s().-]+/g, '') : '';
    }

    document.getElementById('reloadBtn')?.addEventListener('click', () => {
        // ── Reset all filter state ──
        draftsState  = { page: 1, search: '', type: '',    channel: '', per_page: 20 };
        historyState = { page: 1, search: '', source: '',  from: '',    to: '', per_page: 20 };
        logsState    = { page: 1, search: '', status: '',  from: '',    to: '', per_page: 20 };

        // ── Clear the visible inputs (in case any tab is currently rendered) ──
        const clearIfExists = (id, val = '') => {
            const el = document.getElementById(id);
            if (el) el.value = val;
        };
        // Drafts
        clearIfExists('draftsSearchInput');
        clearIfExists('draftsTypeFilter');
        clearIfExists('draftsChannelFilter');
        // History
        clearIfExists('historySearchInput');
        clearIfExists('historySourceFilter');
        clearIfExists('historyFromFilter');
        clearIfExists('historyToFilter');
        // Logs
        clearIfExists('logsSearchInput');
        clearIfExists('logsStatusFilter');
        clearIfExists('logsFromFilter');
        clearIfExists('logsToFilter');

        // ── Reload all three tabs from page 1 with no filters ──
        loadDrafts(1);
        loadHistory(1);
        loadLogs(1);

        window.showToast?.('success', 'Filters cleared and data reloaded.');
    });

    document.querySelectorAll('a[data-bs-toggle="tab"][href^="#tab_"]').forEach(a => {
        a.addEventListener('shown.bs.tab', function (e) {
            const href = e.target.getAttribute('href');
            if (href === '#tab_logs')         loadLogs(logsState.page);
            else if (href === '#tab_history') loadHistory(historyState.page);
            else                              loadDrafts(draftsState.page);
        });
    });

    // ── Delete draft (relay or external) ─────────────────────
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.delete-draft-btn');
        if (!btn || btn.disabled) return;

        if (!confirm('Delete this draft? This cannot be undone.')) return;

        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const res = await fetch(`{{ url('admin/auto-apply/draft') }}/${btn.dataset.id}?type=${btn.dataset.type}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                window.showToast?.('success', data.message || 'Draft deleted.');
                loadDrafts(draftsState.page);
            } else {
                window.showToast?.('error', data.message || 'Delete failed.');
                btn.disabled = false;
                btn.innerHTML = original;
            }
        } catch (err) {
            window.showToast?.('error', 'Network error.');
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });

    // ── Delete AI run log ────────────────────────────────────
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.delete-run-btn');
        if (!btn || btn.disabled) return;

        if (!confirm('Delete this run log? Drafts attached to it will also be removed.')) return;

        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const res = await fetch(`{{ url('admin/auto-apply/run') }}/${btn.dataset.runId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                window.showToast?.('success', data.message || 'Run deleted.');
                loadLogs(logsState.page);
            } else {
                window.showToast?.('error', data.message || 'Delete failed.');
                btn.disabled = false;
                btn.innerHTML = original;
            }
        } catch (err) {
            window.showToast?.('error', 'Network error.');
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });

    // ── Delete one applied-history row ───────────────────────
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.delete-history-btn');
        if (!btn || btn.disabled) return;

        if (!confirm('Remove this entry from the applied history? The subscriber\'s counter will be reduced by 1.')) return;

        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const res = await fetch(`{{ url('admin/auto-apply/history') }}/${btn.dataset.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                window.showToast?.('success', data.message || 'History removed.');
                loadHistory(historyState.page);
            } else {
                window.showToast?.('error', data.message || 'Delete failed.');
                btn.disabled = false;
                btn.innerHTML = original;
            }
        } catch (err) {
            window.showToast?.('error', 'Network error.');
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });

    // Initial boot
    loadDrafts(1);

})();
</script>
@endpush