@extends('layouts.admin')

@section('title', 'Manual Email Dispatcher')
@section('page_title', 'Manual Email Dispatcher')

@section('content')
@can('send manual emails')

<div class="container py-6">

    {{-- ══════════════════ TABS ══════════════════ --}}
    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-6 fs-6" id="mainTabs">
        <li class="nav-item">
            <a class="nav-link active" data-tab="composer" href="javascript:;">
                <i class="ki-duotone ki-sms fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                Compose
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-tab="log" href="javascript:;">
                <i class="ki-duotone ki-time fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                Dispatch Log
                <span class="badge badge-light-primary ms-2" id="logTabBadge">0</span>
            </a>
        </li>
    </ul>

    {{-- ══════════════════ PANE 1: COMPOSER ══════════════════ --}}
    <div id="paneComposer">
        <div class="card card-flush mb-6">
            <div class="card-header align-items-center py-5">
                <h3 class="card-title fw-bold mb-0">
                    <i class="ki-duotone ki-sms fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
                    Compose Email
                </h3>
            </div>

            <div class="card-body pt-0">

                {{-- ─── Purpose (always visible) ─── --}}
                <div class="row g-4 mb-6">
                    <div class="col-md-6">
                        <label class="required fw-semibold fs-6 mb-2">Purpose</label>
                        <select id="purpose" class="form-select form-select-solid form-select-lg">
                            <option value="">Select purpose...</option>
                            <optgroup label="Bulk Email">
                                <option value="product_advert">Product Advertisement</option>
                                <option value="hiring_appointment">Hiring Appointment</option>
                                <option value="manual_outreach">Manual Outreach</option>
                                <option value="newsletter">Newsletter</option>
                            </optgroup>
                            <optgroup label="Single Application">
                                <option value="job_application_help">Job Application Help (on behalf of seeker)</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-3 w-100">
                            <i class="ki-duotone ki-information-5 fs-2tx text-primary me-3">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <div class="fs-8 text-gray-700" id="purposeHint">
                                Choose what kind of email you want to send. The form will adapt.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════
                     BULK EMAIL BLOCK (all purposes EXCEPT job_application_help)
                     ═══════════════════════════════════════════════════ --}}
                <div id="bulkBlock" class="d-none">

                    {{-- Audience + Country --}}
                    <div class="row g-4 mb-6">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Audience</label>
                            <select id="audience" class="form-select form-select-solid form-select-lg">
                                <option value="seekers">Seekers</option>
                                <option value="employers">Employers</option>
                                <option value="custom">Custom (paste emails)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Country (optional filter)</label>
                            <select id="countryFilter" class="form-select form-select-solid form-select-lg">
                                <option value="">All countries</option>
                                @foreach(\App\Models\Job\Country::where('is_active', true)->orderBy('name')->get(['code','name','flag']) as $c)
                                    <option value="{{ $c->code }}">{{ $c->flag ?? '' }} {{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Recipients --}}
                    <div class="mb-6">
                        <label class="required fw-semibold fs-6 mb-2">Recipients</label>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-light-primary" id="selectAllFiltered">
                                <i class="ki-duotone ki-check-squared fs-4 me-1">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                </i>
                                Select all filtered
                            </button>
                            <button type="button" class="btn btn-sm btn-light-success" id="selectAllAudience">
                                <i class="ki-duotone ki-people fs-4 me-1">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                </i>
                                Select ALL <span id="selectAllAudienceLabel">seekers</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-light-warning" id="selectByCountry">
                                <i class="ki-duotone ki-geolocation fs-4 me-1">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                                Select by country
                            </button>
                        </div>

                        <textarea id="manualRecipients" class="form-control form-control-solid mb-3" rows="2"
                                  placeholder="Paste emails, separated by commas or new lines. E.g. a@x.com, b@y.com"></textarea>

                        <div class="input-group mb-3">
                            <span class="input-group-text">
                                <i class="ki-duotone ki-magnifier fs-3">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </span>
                            <input type="text" id="userSearch" class="form-control form-control-solid"
                                   placeholder="Search name, email or phone...">
                        </div>

                        <div id="userResults" class="border rounded p-3" style="max-height: 260px; overflow-y: auto;">
                            <div class="text-muted fs-7 text-center py-4">Type to search, or paste emails above.</div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-muted fs-7">
                                Selected: <span class="fw-bold" id="selectedCount">0</span>
                            </span>
                            <button type="button" class="btn btn-sm btn-light-danger" id="clearRecipientsBtn">
                                Clear all
                            </button>
                        </div>

                        <div id="selectedPills" class="d-flex flex-wrap gap-2 mt-3"></div>
                    </div>

                    <div class="border-top pt-4 mt-4 d-flex justify-content-end">
                        <button type="button" class="btn btn-light-danger" id="drawerDeleteBtn">
                            Delete this log${(d.attachments_detail || []).length ? ' and its files' : ''}
                        </button>
                    </div>

                    {{-- Subject + Body --}}
                    <div class="mb-4">
                        <label class="required fw-semibold fs-6 mb-2">Subject</label>
                        <input type="text" id="emailSubject" class="form-control form-control-solid form-control-lg"
                               placeholder="e.g. New roles at Stardena Careers — apply now">
                    </div>

                    <div class="mb-4">
                        <label class="required fw-semibold fs-6 mb-2">Body</label>
                        <textarea id="emailBody" class="form-control" rows="12"></textarea>
                    </div>

                    {{-- CC / BCC --}}
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">CC</label>
                            <input type="text" id="cc" class="form-control form-control-solid"
                                   placeholder="comma-separated emails">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">BCC</label>
                            <input type="text" id="bcc" class="form-control form-control-solid"
                                   placeholder="comma-separated emails">
                        </div>
                    </div>

                    {{-- Generic attachments --}}
                    <div class="mb-6">
                        <label class="fw-semibold fs-6 mb-2">Attachments</label>
                        <input type="file" id="attachmentInput" class="form-control form-control-solid" multiple>
                        <div class="text-muted fs-8 mt-1">Each file up to 10 MB.</div>
                        <div id="attachmentList" class="d-flex flex-wrap gap-2 mt-3"></div>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════
                     APPLICATION BLOCK (only when purpose = job_application_help)
                     ═══════════════════════════════════════════════════ --}}
                <div id="applicationBlock" class="d-none">

                    <style>
                        .gm-box { border:1px solid var(--bs-gray-300); border-radius:10px; overflow:hidden; }
                        .gm-row { display:flex; align-items:center; gap:10px; padding:4px 14px; border-bottom:1px solid var(--bs-gray-200); min-height:46px; }
                        .gm-row > label { width:78px; flex:none; color:var(--bs-gray-600); font-size:13px; font-weight:600; margin:0; }
                        .gm-row input, .gm-row select { border:0 !important; box-shadow:none !important; background:transparent !important; flex:1; padding:6px 0; min-width:0; }
                        .gm-row .gm-toggle { font-size:12px; color:var(--bs-primary); cursor:pointer; user-select:none; }
                        .gm-foot { padding:12px 14px; background:var(--bs-gray-100); }
                        .gm-file { display:inline-flex; align-items:center; gap:8px; border:1px solid var(--bs-gray-300); background:#fff; border-radius:8px; padding:6px 10px; font-size:12px; }
                    </style>

                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mb-5">
                        <div class="fs-8 text-gray-700">
                            Write it like a normal email. The employer sees <strong>"{Applicant} via Stardena Careers"</strong>,
                            replies go to the applicant, and the applicant is always CC'd.
                        </div>
                    </div>

                    <div class="gm-box mb-5">
                        <div class="gm-row">
                            <label>To</label>
                            <input type="text" id="appTo" placeholder="hr@company.com (separate several with commas)">
                            <span class="gm-toggle" id="toggleBcc">Bcc</span>
                        </div>
                        <div class="gm-row">
                            <label>Cc</label>
                            <input type="text" id="appCc" placeholder="extra CC (applicant is added automatically)">
                        </div>
                        <div class="gm-row d-none" id="appBccRow">
                            <label>Bcc</label>
                            <input type="text" id="appBcc" placeholder="bcc emails">
                        </div>
                        <div class="gm-row">
                            <label>Subject</label>
                            <input type="text" id="appSubject" placeholder="Application: Job Title">
                        </div>

                        <div class="p-0">
                            <textarea id="appBody" rows="12"></textarea>
                        </div>

                        <div class="gm-foot">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <label class="btn btn-sm btn-light-primary mb-0">
                                    <i class="ki-duotone ki-paper-clip fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                                    Attach files (CV, cover letter, certificates)
                                    <input type="file" id="appFileInput" class="d-none" multiple
                                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                </label>
                                <span class="text-muted fs-8">Max 10 MB per file · 20 MB total · first attach the CV</span>
                            </div>
                            <div id="appAttachmentList" class="d-flex flex-wrap gap-2 mt-3"></div>
                        </div>
                    </div>

                    <div class="row g-4 mb-2">
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-7 mb-1">Applicant name</label>
                            <input type="text" id="appSeekerName" class="form-control form-control-solid" placeholder="e.g. Samuel Kiira Eluk">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-7 mb-1">Applicant email (auto CC + Reply-To)</label>
                            <input type="email" id="appSeekerEmail" class="form-control form-control-solid" placeholder="applicant@email.com">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-7 mb-1">Country (footer)</label>
                            <select id="appCountry" class="form-select form-select-solid">
                                @foreach(\App\Models\Job\Country::where('is_active', true)->orderBy('name')->get(['code','name']) as $c)
                                    <option value="{{ $c->code }}" {{ $c->code === (auth()->user()->country_code ?? 'UG') ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-7 mb-1">Job title</label>
                            <input type="text" id="appJobTitle" class="form-control form-control-solid" placeholder="e.g. Audit Assistant">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-7 mb-1">Company</label>
                            <input type="text" id="appCompanyName" class="form-control form-control-solid" placeholder="e.g. Umoja Shoe Company">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-light-success w-100" id="appInsertTemplate">Insert standard application text</button>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════
                     SHARED: Optional deposit + actions
                     ═══════════════════════════════════════════════════ --}}
                <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 mb-6">
                    <i class="ki-duotone ki-dollar fs-2tx text-warning me-4"><span class="path1"></span><span class="path2"></span></i>
                    <div class="flex-grow-1">
                        <div class="fw-bold mb-2">Record a deposit (optional)</div>
                        <div class="text-muted fs-8 mb-3">If this send was paid for, record it here.</div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <input type="number" id="depositAmount" class="form-control form-control-solid"
                                       placeholder="Amount (UGX)" min="0" step="100">
                            </div>
                            <div class="col-md-3">
                                <select id="depositMethod" class="form-select form-select-solid">
                                    <option value="cash">Cash</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank">Bank</option>
                                    <option value="card">Card</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="text" id="depositNotes" class="form-control form-control-solid"
                                       placeholder="Notes (e.g. Paid UGX 5,000 for 3 applications)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light" id="resetBtn">Reset</button>
                    <button type="button" class="btn btn-primary" id="sendBtn" disabled>
                        <span class="indicator-label">
                            <i class="ki-duotone ki-send fs-3 me-1">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <span id="sendBtnLabel">Send</span>
                        </span>
                        <span class="indicator-progress">
                            Sending... <span class="spinner-border spinner-border-sm ms-2"></span>
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ══════════════════ PANE 2: LOG ══════════════════ --}}
    <div id="paneLog" class="d-none">
        <div class="card card-flush">
            <div class="card-header align-items-center py-5">
                <h3 class="card-title fw-bold mb-0">Dispatch Log</h3>
                <div class="card-toolbar gap-2">
                    <button type="button" class="btn btn-sm btn-light-danger d-none" id="bulkDeleteBtn">
                        <i class="ki-duotone ki-trash fs-4 me-1">
                            <span class="path1"></span><span class="path2"></span>
                            <span class="path3"></span><span class="path4"></span>
                        </i>
                        Delete Selected <span id="selectedLogCount">0</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-light" id="reloadLogBtn">
                        <i class="ki-duotone ki-arrows-circle fs-3 me-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Reload
                    </button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div id="logWrap" class="text-center py-10">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ══════════════════ DRAWER: LOG DETAIL ══════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="logDrawer" style="width: 720px;">
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title mb-0" id="logDrawerTitle">Dispatch</h5>
            <div class="text-muted fs-8" id="logDrawerSubtitle"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="logDrawerBody">
        <div class="text-center py-10"><div class="spinner-border text-primary"></div></div>
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

    const selected = new Map();
    const attachments = [];
    const selectedLogIds = new Set();
    let bodyEditor = null;
    let logCache = [];

    const logDrawer = new bootstrap.Offcanvas(document.getElementById('logDrawer'));

    // ── Rich editor (built once, reused for bulk mode) ──
    tinymce.init({
        target: document.getElementById('emailBody'),
        height: 360,
        menubar: false,
        branding: false,
        promotion: false,
        plugins: 'lists link table code wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | alignleft aligncenter alignright | removeformat | code',
        content_style: `body { font-family: Inter, sans-serif; font-size: 14px; line-height: 1.6; padding: 16px; }`,
        setup: (editor) => { editor.on('init', () => { bodyEditor = editor; }); },
    });

    // ── Purpose switch ──
    function applyPurpose() {
        const purpose = document.getElementById('purpose').value;
        const hint    = document.getElementById('purposeHint');
        const bulk    = document.getElementById('bulkBlock');
        const app     = document.getElementById('applicationBlock');
        const sendBtn = document.getElementById('sendBtn');
        const sendLbl = document.getElementById('sendBtnLabel');

        // Reset whenever the mode changes
        if (purpose === 'job_application_help') {
            bulk.classList.add('d-none');
            app.classList.remove('d-none');
            initAppEditor();
            hint.innerHTML = 'Sends a <strong>professional application email</strong> to the employer, CC\'ing the applicant. Uses uploaded PDFs — no bulk recipients.';
            sendLbl.textContent = 'Send Application';
        } else if (purpose) {
            bulk.classList.remove('d-none');
            app.classList.add('d-none');
            hint.innerHTML = 'Sends to <strong>multiple recipients</strong> using the template you build below.';
            sendLbl.textContent = 'Send Emails';
        } else {
            bulk.classList.add('d-none');
            app.classList.add('d-none');
            hint.innerHTML = 'Choose what kind of email you want to send. The form will adapt.';
            sendLbl.textContent = 'Send';
        }

        sendBtn.disabled = !purpose;
    }

    document.getElementById('purpose')?.addEventListener('change', applyPurpose);

    // ── Tabs ──
    document.querySelectorAll('#mainTabs [data-tab]').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('#mainTabs .nav-link').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const which = tab.dataset.tab;
            document.getElementById('paneComposer').classList.toggle('d-none', which !== 'composer');
            document.getElementById('paneLog').classList.toggle('d-none', which !== 'log');
            if (which === 'log') loadLog();
        });
    });

    // ════════════════════════════════════════════════════════
    // BULK MODE
    // ════════════════════════════════════════════════════════
    function addRecipient(email, name = '', userId = null) {
        email = (email || '').trim().toLowerCase();
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;
        if (selected.has(email)) return;
        selected.set(email, { name, user_id: userId });
        renderSelected();
    }
    function renderSelected() {
        document.getElementById('selectedCount').textContent = selected.size;
        const pills = document.getElementById('selectedPills');
        pills.innerHTML = [...selected.entries()].map(([email, meta]) => `
            <span class="badge badge-light-primary fs-7 py-2 px-3 d-inline-flex align-items-center gap-2">
                ${escapeHtml(meta.name || email)}
                <i class="ki-duotone ki-cross fs-6" style="cursor:pointer;" data-remove="${escapeHtml(email)}">
                    <span class="path1"></span><span class="path2"></span>
                </i>
            </span>
        `).join('');
        pills.querySelectorAll('[data-remove]').forEach(el => {
            el.addEventListener('click', () => {
                selected.delete(el.dataset.remove);
                renderSelected();
            });
        });
    }
    function addRecipientsInBulk(recipients) {
        let added = 0;
        recipients.forEach(r => {
            const email = (r.email || '').toLowerCase().trim();
            if (!email || selected.has(email)) return;
            selected.set(email, { name: r.name || '', user_id: r.user_id || null });
            added++;
        });
        renderSelected();
        return added;
    }

    document.getElementById('manualRecipients').addEventListener('input', function () {
        this.value.split(/[\s,;]+/).filter(Boolean).forEach(e => addRecipient(e));
    });

    let searchTimer = null;
    document.getElementById('userSearch').addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        const audience = document.getElementById('audience').value;
        const country = document.getElementById('countryFilter').value;

        if (q.length < 2) {
            document.getElementById('userResults').innerHTML =
                '<div class="text-muted fs-7 text-center py-4">Type at least 2 characters.</div>';
            return;
        }

        searchTimer = setTimeout(async () => {
            const params = new URLSearchParams({ q, audience });
            if (country) params.set('country', country);

            try {
                const res = await fetch(`{{ route("admin.manual-email.search") }}?${params}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                const wrap = document.getElementById('userResults');

                if (!data.success || !data.users?.length) {
                    wrap.innerHTML = '<div class="text-muted fs-7 text-center py-4">No users match.</div>';
                    return;
                }

                wrap.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center p-2 bg-light-primary rounded mb-2">
                        <span class="fw-semibold fs-7">${data.users.length} result(s)</span>
                        <button type="button" class="btn btn-xs btn-primary" id="addAllSearch">Add all ${data.users.length}</button>
                    </div>
                    ${data.users.map(u => `
                        <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                            <div>
                                <div class="fw-bold fs-7">${escapeHtml(u.name)}</div>
                                <div class="text-muted fs-8">${escapeHtml(u.email)} · ${escapeHtml(u.country_code || '')}</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light-primary"
                                    data-add-user data-email="${escapeHtml(u.email)}"
                                    data-name="${escapeHtml(u.name)}" data-id="${u.id}">Add</button>
                        </div>`).join('')}`;

                wrap.querySelectorAll('[data-add-user]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        addRecipient(btn.dataset.email, btn.dataset.name, parseInt(btn.dataset.id));
                    });
                });
                document.getElementById('addAllSearch')?.addEventListener('click', () => {
                    const added = addRecipientsInBulk(data.users.map(u => ({ email: u.email, name: u.name, user_id: u.id })));
                    window.showToast?.('success', `${added} added.`);
                });
            } catch (e) {
                document.getElementById('userResults').innerHTML = '<div class="text-danger fs-7 text-center py-4">Search failed.</div>';
            }
        }, 350);
    });

    document.getElementById('clearRecipientsBtn').addEventListener('click', () => {
        selected.clear();
        document.getElementById('manualRecipients').value = '';
        renderSelected();
    });

    async function fetchBulkRecipients(audience, country = '', q = '') {
        const params = new URLSearchParams({ audience });
        if (country) params.set('country', country);
        if (q)       params.set('q', q);
        const res = await fetch(`{{ route("admin.manual-email.bulk-recipients") }}?${params}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Bulk fetch failed.');
        return data;
    }

    document.getElementById('selectAllFiltered')?.addEventListener('click', async () => {
        const audience = document.getElementById('audience').value;
        if (audience === 'custom') return window.showToast?.('error', 'Custom audience has no user list.');
        if (!confirm('Select all users matching current filters?')) return;
        try {
            const data = await fetchBulkRecipients(audience, document.getElementById('countryFilter').value, document.getElementById('userSearch').value.trim());
            if (!data.count) return window.showToast?.('warning', 'No users match.');
            const added = addRecipientsInBulk(data.recipients);
            window.showToast?.('success', `${added} added.`);
        } catch (e) { window.showToast?.('error', e.message); }
    });

    document.getElementById('selectAllAudience')?.addEventListener('click', async () => {
        const audience = document.getElementById('audience').value;
        if (audience === 'custom') return window.showToast?.('error', 'Choose seekers or employers first.');
        if (!confirm(`Add EVERY active ${audience}?`)) return;
        try {
            const data = await fetchBulkRecipients(audience);
            if (!data.count) return window.showToast?.('warning', 'No active users.');
            const added = addRecipientsInBulk(data.recipients);
            window.showToast?.('success', `${added} added. Total: ${selected.size}.`);
        } catch (e) { window.showToast?.('error', e.message); }
    });

    document.getElementById('selectByCountry')?.addEventListener('click', async () => {
        const audience = document.getElementById('audience').value;
        if (audience === 'custom') return window.showToast?.('error', 'Choose seekers or employers first.');
        const country = document.getElementById('countryFilter').value;
        if (!country) return window.showToast?.('warning', 'Pick a country first.');
        if (!confirm(`Add all ${audience} in ${country}?`)) return;
        try {
            const data = await fetchBulkRecipients(audience, country);
            if (!data.count) return window.showToast?.('warning', 'No users in that country.');
            const added = addRecipientsInBulk(data.recipients);
            window.showToast?.('success', `${added} added.`);
        } catch (e) { window.showToast?.('error', e.message); }
    });

    document.getElementById('audience')?.addEventListener('change', function () {
        document.getElementById('selectAllAudienceLabel').textContent =
            this.value === 'employers' ? 'employers' : 'seekers';
    });

    // ── Attachments (bulk mode) ──
    document.getElementById('attachmentInput').addEventListener('change', async function () {
        if (!this.files.length) return;
        const fd = new FormData();
        for (const f of this.files) fd.append('files[]', f);

        const listEl = document.getElementById('attachmentList');
        const loading = document.createElement('div');
        loading.className = 'text-muted fs-8';
        loading.textContent = 'Uploading…';
        listEl.appendChild(loading);

        try {
            const res = await fetch('{{ route("admin.manual-email.upload") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: fd,
            });
            const data = await res.json();
            if (data.success) {
                data.files.forEach(f => attachments.push(f));
                renderAttachments();
            } else {
                window.showToast?.('error', data.message || 'Upload failed.');
            }
        } catch (e) {
            window.showToast?.('error', 'Upload error.');
        } finally {
            loading.remove();
            this.value = '';
        }
    });

    function renderAttachments() {
        const listEl = document.getElementById('attachmentList');
        listEl.innerHTML = attachments.map((a, i) => `
            <span class="badge badge-light-info fs-8 py-2 px-3 d-inline-flex align-items-center gap-2">
                <i class="ki-duotone ki-file fs-6"><span class="path1"></span><span class="path2"></span></i>
                ${escapeHtml(a.original_name)}
                <i class="ki-duotone ki-cross fs-6" style="cursor:pointer;" data-remove-att="${i}">
                    <span class="path1"></span><span class="path2"></span>
                </i>
            </span>
        `).join('');
        listEl.querySelectorAll('[data-remove-att]').forEach(el => {
            el.addEventListener('click', () => {
                attachments.splice(parseInt(el.dataset.removeAtt), 1);
                renderAttachments();
            });
        });
    }

    // ════════════════════════════════════════════════════════
    // SEND — branches on purpose
    // ════════════════════════════════════════════════════════
    document.getElementById('sendBtn').addEventListener('click', async function () {
        const purpose = document.getElementById('purpose').value;
        if (!purpose) return window.showToast?.('error', 'Select a purpose.');

        const btn = this;
        const label = btn.querySelector('.indicator-label');
        const prog  = btn.querySelector('.indicator-progress');

        if (purpose === 'job_application_help') {
            await sendApplication(btn, label, prog);
        } else {
            await sendBulk(purpose, btn, label, prog);
        }
    });

    async function sendBulk(purpose, btn, label, prog) {
        if (!selected.size) return window.showToast?.('error', 'Add at least one recipient.');

        const audience = document.getElementById('audience').value;
        const subject  = document.getElementById('emailSubject').value.trim();
        const body     = bodyEditor ? bodyEditor.getContent() : document.getElementById('emailBody').value;

        if (!subject) return window.showToast?.('error', 'Enter a subject.');
        if (!body || body.trim().length < 5) return window.showToast?.('error', 'Write a body.');

        const payload = {
            purpose, audience, subject, body,
            recipients: [...selected.entries()].map(([email, meta]) => ({
                email, name: meta.name || '', user_id: meta.user_id || null,
            })),
            attachments: attachments.map(a => a.path),
            cc:  document.getElementById('cc').value.trim() || null,
            bcc: document.getElementById('bcc').value.trim() || null,
            deposit_amount_cents: parseInt(document.getElementById('depositAmount').value) || null,
            deposit_method:       document.getElementById('depositMethod').value,
            deposit_notes:        document.getElementById('depositNotes').value.trim() || null,
        };

        if (!confirm(`Send to ${payload.recipients.length} recipient(s)?`)) return;

        btn.disabled = true;
        label.classList.add('d-none');
        prog.classList.remove('d-none');

        try {
            const res = await fetch('{{ route("admin.manual-email.send") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (data.success) {
                window.showToast?.('success', data.message || 'Queued.');
                resetBulkForm();
                document.querySelector('#mainTabs [data-tab="log"]').click();
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
    }

        // ════════════════════════════════════════════════════════
    // APPLICATION MODE (Gmail-style)
    // ════════════════════════════════════════════════════════
    const appAttachments = [];
    let appEditor = null;
    let appSubjectTouched = false;

    function initAppEditor() {
        if (appEditor || document.getElementById('appBody')?.dataset.init) return;
        document.getElementById('appBody').dataset.init = '1';
        tinymce.init({
            target: document.getElementById('appBody'),
            height: 340, menubar: false, branding: false, promotion: false, statusbar: false,
            plugins: 'lists link',
            toolbar: 'bold italic underline | bullist numlist | link | removeformat',
            content_style: `body { font-family: Inter, sans-serif; font-size: 14px; line-height: 1.6; padding: 14px; }`,
            setup: (ed) => ed.on('init', () => { appEditor = ed; }),
        });
    }

    document.getElementById('toggleBcc')?.addEventListener('click', () => {
        document.getElementById('appBccRow').classList.toggle('d-none');
    });
    document.getElementById('appSubject')?.addEventListener('input', () => { appSubjectTouched = true; });
    document.getElementById('appJobTitle')?.addEventListener('input', function () {
        if (!appSubjectTouched) {
            document.getElementById('appSubject').value = this.value.trim() ? 'Application: ' + this.value.trim() : '';
        }
    });

    document.getElementById('appInsertTemplate')?.addEventListener('click', () => {
        const job     = document.getElementById('appJobTitle').value.trim() || '[Job Title]';
        const company = document.getElementById('appCompanyName').value.trim();
        const name    = document.getElementById('appSeekerName').value.trim() || '[Applicant Name]';
        const hasCover = appAttachments.some(a => /cover/i.test(a.original_name));
        const html =
            `<p>Dear Hiring Manager,</p>` +
            `<p>Please find attached my application for the <strong>${escapeHtml(job)}</strong> position${company ? ' at ' + escapeHtml(company) : ''}.</p>` +
            `<p>${hasCover ? 'My cover letter and CV are' : 'My CV is'} attached and outline${hasCover ? '' : 's'} the experience, qualifications and skills I would bring to the role.</p>` +
            `<p>I would welcome the opportunity to discuss my application with you at your earliest convenience.</p>` +
            `<p>Thank you for your time and consideration.</p>` +
            `<p>Yours faithfully,<br>${escapeHtml(name)}</p>`;
        appEditor ? appEditor.setContent(html) : (document.getElementById('appBody').value = html);
    });

    // Upload attachments (reuses /upload)
    document.getElementById('appFileInput')?.addEventListener('change', async function () {
        if (!this.files.length) return;
        const fd = new FormData();
        for (const f of this.files) fd.append('files[]', f);
        const list = document.getElementById('appAttachmentList');
        const loading = document.createElement('span');
        loading.className = 'text-muted fs-8';
        loading.textContent = 'Uploading…';
        list.appendChild(loading);
        try {
            const res  = await fetch('{{ route("admin.manual-email.upload") }}', {
                method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd,
            });
            const data = await res.json();
            if (data.success) { data.files.forEach(f => appAttachments.push(f)); }
            else window.showToast?.('error', data.message || 'Upload failed.');
        } catch (e) { window.showToast?.('error', 'Upload error.'); }
        finally { loading.remove(); this.value = ''; renderAppAttachments(); }
    });

    function renderAppAttachments() {
        const list = document.getElementById('appAttachmentList');
        list.innerHTML = appAttachments.map((a, i) => `
            <span class="gm-file">
                <i class="ki-duotone ki-file fs-5 text-primary"><span class="path1"></span><span class="path2"></span></i>
                ${escapeHtml(a.original_name)} <span class="text-muted">${Math.round((a.size || 0) / 1024)} KB</span>
                <i class="ki-duotone ki-cross fs-5" style="cursor:pointer" data-rm-app="${i}"><span class="path1"></span><span class="path2"></span></i>
            </span>`).join('');
        list.querySelectorAll('[data-rm-app]').forEach(el => el.addEventListener('click', () => {
            appAttachments.splice(parseInt(el.dataset.rmApp), 1);
            renderAppAttachments();
        }));
    }

    async function sendApplication(btn, label, prog) {
        const val  = id => document.getElementById(id).value.trim();
        const body = appEditor ? appEditor.getContent() : val('appBody');

        if (!val('appTo'))         return window.showToast?.('error', 'Enter the employer email in "To".');
        if (!val('appSubject'))    return window.showToast?.('error', 'Enter a subject.');
        if (!val('appSeekerName')) return window.showToast?.('error', 'Applicant name is required.');
        if (!body || body.replace(/<[^>]*>/g, '').trim().length < 5) return window.showToast?.('error', 'Write the message.');
        if (!appAttachments.length) return window.showToast?.('error', 'Attach at least the CV.');

        const ccPreview = [val('appCc'), val('appSeekerEmail')].filter(Boolean).join(', ');
        if (!confirm(`Send to: ${val('appTo')}\nCC: ${ccPreview || '—'}\nAttachments: ${appAttachments.length}\n\nSend now?`)) return;

        const payload = {
            to: val('appTo'), cc: val('appCc') || null, bcc: val('appBcc') || null,
            subject: val('appSubject'), body,
            seeker_name: val('appSeekerName'), seeker_email: val('appSeekerEmail') || null,
            job_title: val('appJobTitle') || null, company_name: val('appCompanyName') || null,
            country_code: document.getElementById('appCountry').value,
            attachments: appAttachments.map(a => ({ path: a.path, original_name: a.original_name })),
            deposit_amount_cents: parseInt(document.getElementById('depositAmount').value) || null,
            deposit_method: document.getElementById('depositMethod').value,
            deposit_notes: document.getElementById('depositNotes').value.trim() || null,
        };

        btn.disabled = true; label.classList.add('d-none'); prog.classList.remove('d-none');
        try {
            const res  = await fetch('{{ route("admin.manual-email.send-application") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (data.success) {
                window.showToast?.('success', data.message || 'Application sent.');
                resetApplicationForm();
                document.querySelector('#mainTabs [data-tab="log"]').click();
            } else {
                window.showToast?.('error', data.message || 'Send failed.');
            }
        } catch (e) { window.showToast?.('error', 'Network error.'); }
        finally { btn.disabled = false; label.classList.remove('d-none'); prog.classList.add('d-none'); }
    }

    function resetApplicationForm() {
        ['appTo','appCc','appBcc','appSubject','appSeekerName','appSeekerEmail','appJobTitle','appCompanyName','depositAmount','depositNotes']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        appAttachments.length = 0;
        renderAppAttachments();
        appSubjectTouched = false;
        appEditor ? appEditor.setContent('') : (document.getElementById('appBody').value = '');
    }

    function resetBulkForm() {
        selected.clear();
        attachments.length = 0;
        ['manualRecipients','emailSubject','cc','bcc','depositAmount','depositNotes']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        if (bodyEditor) bodyEditor.setContent('');
        renderSelected();
        renderAttachments();
    }

    document.getElementById('resetBtn').addEventListener('click', () => {
        if (!confirm('Clear the form?')) return;
        resetBulkForm();
        resetApplicationForm();
    });

    // ════════════════════════════════════════════════════════
    // LOG
    // ════════════════════════════════════════════════════════
    async function loadLog() {
        const wrap = document.getElementById('logWrap');
        wrap.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        try {
            const res = await fetch('{{ route("admin.manual-email.log") }}', { headers: { 'Accept': 'application/json' } });
            const data = await res.json();

            if (!data.success || !data.data?.data?.length) {
                wrap.innerHTML = `<div class="text-center py-15">
                    <i class="ki-duotone ki-information-5 fs-5tx text-muted mb-4 d-block"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <h4 class="fw-bold text-gray-800 mb-2">No emails sent yet</h4>
                    <p class="text-muted">Your dispatch history will appear here.</p>
                </div>`;
                document.getElementById('logTabBadge').textContent = '0';
                return;
            }

            logCache = data.data.data;
            document.getElementById('logTabBadge').textContent = data.pagination.total;

            const allChecked = logCache.every(d => selectedLogIds.has(d.id));

            wrap.innerHTML = `
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-7 gy-3">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-30px"><input type="checkbox" class="form-check-input" id="selectAllLogs" ${allChecked ? 'checked' : ''}></th>
                                <th class="text-start">Purpose</th>
                                <th class="text-start">Subject</th>
                                <th class="text-start">Audience</th>
                                <th class="text-center">Recipients</th>
                                <th class="text-center">Delivered</th>
                                <th class="text-start">Status</th>
                                <th class="text-start">When</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${logCache.map(d => `
                                <tr>
                                    <td><input type="checkbox" class="form-check-input log-checkbox" value="${d.id}" ${selectedLogIds.has(d.id) ? 'checked' : ''}></td>
                                    <td class="text-start"><span class="badge badge-light-primary">${escapeHtml(d.purpose_label)}</span></td>
                                    <td class="text-start fw-semibold text-gray-800">${escapeHtml(d.subject)}</td>
                                    <td class="text-start"><span class="text-muted">${escapeHtml(d.audience)}</span></td>
                                    <td class="text-center">${d.recipient_count}</td>
                                    <td class="text-center">
                                        <span class="text-success fw-bold">${d.sent_count}</span>
                                        ${d.failed_count > 0 ? `<span class="text-danger"> / ${d.failed_count}</span>` : ''}
                                    </td>
                                    <td class="text-start">${d.status_badge}</td>
                                    <td class="text-start text-muted fs-8">${d.sent_at ? new Date(d.sent_at).toLocaleString() : new Date(d.created_at).toLocaleString()}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-icon btn-light-primary" data-open-log="${d.id}" title="View">
                                            <i class="ki-duotone ki-eye fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                        </button>
                                        <button class="btn btn-sm btn-icon btn-light-danger ms-1" data-delete-log="${d.id}" title="Delete">
                                            <i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                        </button>
                                    </td>
                                </tr>`).join('')}
                        </tbody>
                    </table>
                </div>`;

            wrap.querySelectorAll('[data-open-log]').forEach(b => b.addEventListener('click', () => openLogDrawer(parseInt(b.dataset.openLog))));
            wrap.querySelectorAll('[data-delete-log]').forEach(b => b.addEventListener('click', () => deleteSingleLog(parseInt(b.dataset.deleteLog))));
            wrap.querySelectorAll('.log-checkbox').forEach(cb => {
                cb.addEventListener('change', function () {
                    const id = parseInt(this.value);
                    if (this.checked) selectedLogIds.add(id); else selectedLogIds.delete(id);
                    updateBulkUI();
                });
            });
            document.getElementById('selectAllLogs')?.addEventListener('change', function () {
                wrap.querySelectorAll('.log-checkbox').forEach(cb => {
                    cb.checked = this.checked;
                    const id = parseInt(cb.value);
                    if (this.checked) selectedLogIds.add(id); else selectedLogIds.delete(id);
                });
                updateBulkUI();
            });
            updateBulkUI();
        } catch (e) {
            wrap.innerHTML = '<div class="alert alert-danger">Failed to load log.</div>';
        }
    }

    function updateBulkUI() {
        const btn = document.getElementById('bulkDeleteBtn');
        const cnt = document.getElementById('selectedLogCount');
        if (cnt) cnt.textContent = selectedLogIds.size;
        if (btn) btn.classList.toggle('d-none', selectedLogIds.size === 0);
    }

    async function openLogDrawer(id) {
        const row = logCache.find(x => x.id === id);
        document.getElementById('logDrawerTitle').textContent = row?.subject || 'Dispatch';
        document.getElementById('logDrawerSubtitle').textContent = '';
        document.getElementById('logDrawerBody').innerHTML =
            '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';
        logDrawer.show();

        try {
            const res  = await fetch(`{{ url('admin/manual-email') }}/${id}`, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (!json.success) throw new Error();
            const d = json.data;
            document.getElementById('logDrawerTitle').textContent = d.subject;
            document.getElementById('logDrawerSubtitle').textContent =
                `${d.purpose_label} · ${d.recipient_count} recipient(s) · ${d.sender || 'admin'}`;
            document.getElementById('logDrawerBody').innerHTML = renderLogDetail(d);

            document.getElementById('drawerDeleteBtn')?.addEventListener('click', async () => {
                const n = (d.attachments_detail || []).length;
                if (!confirm(`Delete this log${n ? ' and its ' + n + ' stored file(s)' : ''}? This cannot be undone.`)) return;
                try {
                    const r = await fetch(`{{ url('admin/manual-email') }}/${d.id}`, {
                        method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    });
                    const j = await r.json();
                    if (j.success) {
                        window.showToast?.('success', j.message || 'Deleted.');
                        selectedLogIds.delete(d.id);
                        logDrawer.hide();
                        loadLog();
                    } else window.showToast?.('error', j.message || 'Delete failed.');
                } catch (e) { window.showToast?.('error', 'Network error.'); }
            });
        } catch (e) {
            document.getElementById('logDrawerBody').innerHTML = '<div class="alert alert-danger">Could not load this dispatch.</div>';
        }
    }

    function renderLogDetail(d) {
        const statusColor = { queued: 'secondary', sending: 'info', sent: 'success', partial: 'warning', failed: 'danger' }[d.status] || 'secondary';

        const recipientsHtml = (d.recipients || []).map(r => `
            <tr>
                <td class="text-start">
                    <div class="fw-bold fs-7">${escapeHtml(r.name || r.email)}</div>
                    <div class="text-muted fs-8">${escapeHtml(r.email)}</div>
                </td>
                <td class="text-start">
                    <span class="badge badge-light-${r.status === 'sent' ? 'success' : 'danger'}">${escapeHtml(r.status || 'sent')}</span>
                    ${r.error ? `<div class="text-danger fs-8 mt-1">${escapeHtml(r.error)}</div>` : ''}
                </td>
            </tr>`).join('') || '<tr><td colspan="2" class="text-muted text-center py-3">No recipient detail</td></tr>';

        const attachmentsHtml = (d.attachments_detail || []).map(a => `
            <div class="d-flex justify-content-between align-items-center p-2 border rounded mb-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="ki-duotone ki-file fs-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                    <div>
                        <div class="fw-bold fs-7">${escapeHtml(a.original_name || a.name)}</div>
                        <div class="text-muted fs-8">${a.size ? Math.round(a.size/1024) + ' KB' : ''}${a.exists === false ? ' · <span class="text-danger">file missing</span>' : ''}</div>
                    </div>
                </div>
                ${a.exists === false ? '' : `
                <div class="d-flex gap-2">
                    <a href="${a.url}" target="_blank" rel="noopener" class="btn btn-sm btn-light-primary">View</a>
                    <a href="${a.url}" download="${escapeHtml(a.original_name || '')}" class="btn btn-sm btn-light">Download</a>
                </div>`}
            </div>`).join('') || '<div class="text-muted fs-8">No attachments</div>';

        const depositHtml = d.deposit ? `
            <div class="alert alert-light-warning mb-0">
                <strong>Deposit:</strong> ${escapeHtml(d.deposit.amount_formatted)} (${escapeHtml(d.deposit.method || '')})
                ${d.deposit.notes ? `<br><span class="text-muted">${escapeHtml(d.deposit.notes)}</span>` : ''}
            </div>` : '';

        return `
            <div class="d-flex flex-wrap gap-2 mb-4">
                <span class="badge badge-light-${statusColor}">${escapeHtml(d.status)}</span>
                <span class="badge badge-light-primary">${escapeHtml(d.purpose_label)}</span>
                <span class="badge badge-light-secondary">${escapeHtml(d.audience)}</span>
            </div>
            ${d.employer_email ? `
            <div class="mb-4 fs-7">
                <div><span class="text-muted">To:</span> ${escapeHtml(d.employer_email)}</div>
                ${d.cc  ? `<div><span class="text-muted">Cc:</span> ${escapeHtml(d.cc)}</div>`   : ''}
                ${d.bcc ? `<div><span class="text-muted">Bcc:</span> ${escapeHtml(d.bcc)}</div>` : ''}
                ${d.seeker_name ? `<div><span class="text-muted">Applicant:</span> ${escapeHtml(d.seeker_name)}</div>` : ''}
            </div>` : ''}
            ${d.error_message ? `<div class="alert alert-light-danger fs-8 mb-4">${escapeHtml(d.error_message)}</div>` : ''}
            <div class="mb-4">
                <div class="text-muted fs-8 text-uppercase fw-bold mb-1">Subject</div>
                <div class="fw-bold fs-6">${escapeHtml(d.subject)}</div>
            </div>
            <div class="mb-4">
                <div class="text-muted fs-8 text-uppercase fw-bold mb-1">Body</div>
                <div class="bg-light rounded p-4" style="max-height: 300px; overflow-y: auto; font-size: 13px; line-height: 1.6;">
                    ${d.body || '<span class="text-muted">No body stored</span>'}
                </div>
            </div>
            ${depositHtml ? `<div class="mb-4">${depositHtml}</div>` : ''}
            <div class="mb-4">
                <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Attachments (${(d.attachments_detail || []).length})</div>
                ${attachmentsHtml}
            </div>
            <div class="mb-2">
                <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Recipients (${(d.recipients || []).length})</div>
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 fs-7 gy-2">
                        <thead><tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase"><th class="text-start">Recipient</th><th class="text-start">Status</th></tr></thead>
                        <tbody>${recipientsHtml}</tbody>
                    </table>
                </div>
            </div>`;
    }

    async function deleteSingleLog(id) {
        if (!confirm('Delete this dispatch record and its attached files?')) return;
        try {
            const res = await fetch(`{{ url('admin/manual-email') }}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                window.showToast?.('success', data.message || 'Deleted.');
                selectedLogIds.delete(id);
                updateBulkUI();
                loadLog();
            } else {
                window.showToast?.('error', data.message || 'Delete failed.');
            }
        } catch (e) { window.showToast?.('error', 'Network error.'); }
    }

    document.getElementById('bulkDeleteBtn')?.addEventListener('click', async function () {
        if (!selectedLogIds.size) return;
        if (!confirm(`Delete ${selectedLogIds.size} dispatch record(s) and their files?`)) return;

        const btn = this;
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting…';

        try {
            const res = await fetch('{{ route("admin.manual-email.bulk-delete") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ ids: [...selectedLogIds] }),
            });
            const data = await res.json();
            if (data.success) {
                window.showToast?.('success', data.message || 'Deleted.');
                selectedLogIds.clear();
                updateBulkUI();
                loadLog();
            } else {
                window.showToast?.('error', data.message || 'Delete failed.');
            }
        } catch (e) { window.showToast?.('error', 'Network error.'); }
        finally { btn.disabled = false; btn.innerHTML = original; }
    });

    document.getElementById('reloadLogBtn').addEventListener('click', loadLog);

    function escapeHtml(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    // Boot
    renderSelected();
    applyPurpose();

    fetch('{{ route("admin.manual-email.log") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(d => {
            if (d.success && d.pagination) {
                document.getElementById('logTabBadge').textContent = d.pagination.total;
                logCache = d.data.data;
            }
        })
        .catch(() => {});
})();
</script>
@endpush