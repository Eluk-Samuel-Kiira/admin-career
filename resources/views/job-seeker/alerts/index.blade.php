@extends('layouts.admin')

@section('title', 'Alerts Centre')
@section('page_title', 'Alerts Centre')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Seeker Services</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Alerts Centre</li>
@endsection

@section('content')
@can('manage job alerts')

{{-- ── TABS ── --}}
<ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-6 fs-6" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link active" data-bs-toggle="tab" href="#tab-target" role="tab">
            <i class="ki-duotone ki-target fs-3 me-2"><span class="path1"></span><span class="path2"></span></i> Target Seekers
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-log" role="tab">
            <i class="ki-duotone ki-time fs-3 me-2"><span class="path1"></span><span class="path2"></span></i> Dispatch Logs
        </a>
    </li>
</ul>

<div class="tab-content" id="alertsTabContent">

    {{-- ══════════════════ TAB 1: TARGET SEEKERS ══════════════════ --}}
    <div class="tab-pane fade show active" id="tab-target" role="tabpanel">

        {{-- Dispatch panel --}}
        <div class="card card-flush mb-6">
            <div class="card-header align-items-center py-5">
                <div class="card-title">
                    <h3 class="fw-bold mb-0">Send Bulk Job Alerts</h3>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="row g-4 mb-6">
                    <div class="col-md-4">
                        <label class="fw-semibold fs-7 mb-2">Country</label>
                        <select id="countrySelect" class="form-select form-select-solid">
                            <option value="">Select a country...</option>
                            @foreach(\App\Models\Job\Country::where('is_active', true)->orderBy('name')->get(['code','name','flag']) as $c)
                                <option value="{{ $c->code }}">{{ $c->flag ?? '' }} {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold fs-7 mb-2">Channels</label>
                        <div class="d-flex flex-wrap gap-3 align-items-center pt-2">
                            <label class="form-check form-check-custom form-check-solid form-check-inline">
                                <input class="form-check-input" type="checkbox" id="chEmail" value="email" checked>
                                <span class="form-check-label">Email</span>
                            </label>
                            <label class="form-check form-check-custom form-check-solid form-check-inline">
                                <input class="form-check-input" type="checkbox" id="chWhatsapp" value="whatsapp">
                                <span class="form-check-label">WhatsApp</span>
                            </label>
                            <label class="form-check form-check-custom form-check-solid form-check-inline">
                                <input class="form-check-input" type="checkbox" id="chTelegram" value="telegram">
                                <span class="form-check-label">Telegram</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button class="btn btn-light-primary w-50" id="previewBtn">Preview</button>
                            <button class="btn btn-primary w-50" id="dispatchBtn">Dispatch</button>
                        </div>
                    </div>
                </div>

                <div id="previewBox" class="d-none">
                    <div class="separator my-5"></div>
                    <h5 class="fw-bold mb-4">Preview</h5>
                    <div class="row g-4" id="previewCards"></div>
                </div>
            </div>
        </div>

        {{-- Target List --}}
        <div class="card card-flush mb-6">
            <div class="card-header align-items-center py-5">
                <div class="card-title">
                    <h3 class="fw-bold mb-2">Target Seekers</h3>
                </div>
                <div class="text-muted fs-7">Preview reflects jobs published in the last 3 days.</div>
                <div class="card-toolbar gap-2">
                    <button class="btn btn-sm btn-light" id="reloadSeekersBtn">Reload</button>
                    <button class="btn btn-sm btn-primary d-none" id="sendSelectedBtn">
                        Send to Selected <span id="seekerSelectedCount">0</span>
                    </button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="row g-3 mb-5">
                    <div class="col-md-3">
                        <input type="text" id="seekerSearch" class="form-control form-control-solid" placeholder="Search name/email...">
                    </div>
                    <div class="col-md-3">
                        <select id="seekerCountry" class="form-select form-select-solid">
                            <option value="">All Countries</option>
                            @foreach(\App\Models\Job\Country::where('is_active', true)->orderBy('name')->get(['code','name','flag']) as $c)
                                <option value="{{ $c->code }}">{{ $c->flag ?? '' }} {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2 align-items-center">
                            <input type="number" id="seekerMinJobs" class="form-control form-control-solid" placeholder="Min jobs" min="0" value="0">
                            <span class="text-muted">–</span>
                            <input type="number" id="seekerMaxJobs" class="form-control form-control-solid" placeholder="Max jobs" min="0" value="999">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-light-primary w-100" id="applySeekerFilters">Apply Filters</button>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-3 mb-4 align-items-center">
                    <span class="fw-semibold fs-7 text-gray-700">Send via:</span>
                    <label class="form-check form-check-custom form-check-solid form-check-inline">
                        <input class="form-check-input" type="checkbox" id="seekerChEmail" value="email" checked>
                        <span class="form-check-label">Email</span>
                    </label>
                    <label class="form-check form-check-custom form-check-solid form-check-inline">
                        <input class="form-check-input" type="checkbox" id="seekerChWhatsapp" value="whatsapp">
                        <span class="form-check-label">WhatsApp</span>
                    </label>
                    <label class="form-check form-check-custom form-check-solid form-check-inline">
                        <input class="form-check-input" type="checkbox" id="seekerChTelegram" value="telegram">
                        <span class="form-check-label">Telegram</span>
                    </label>
                    <select id="seekerPerPage" class="form-select form-select-sm w-auto ms-auto">
                        <option value="10">10 / page</option>
                        <option value="25" selected>25 / page</option>
                        <option value="50">50 / page</option>
                    </select>
                </div>

                <div id="seekersWrap" class="text-center py-8">
                    <div class="spinner-border text-primary"></div>
                </div>

                <div id="seekersPagination" class="d-none d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5">
                    <div id="seekersPaginationInfo" class="text-muted fs-7"></div>
                    <nav><ul class="pagination m-0" id="seekersPaginationList"></ul></nav>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════ TAB 2: DISPATCH LOGS ══════════════════ --}}
    <div class="tab-pane fade" id="tab-log" role="tabpanel">
        <div class="card card-flush">
            <div class="card-header align-items-center py-5">
                <h3 class="card-title fw-bold mb-0">Recent Dispatches</h3>
                <div class="card-toolbar gap-2">
                    <button class="btn btn-sm btn-light-danger d-none" id="bulkDeleteSelectedBtn">
                        Delete Selected <span id="selectedCount">0</span>
                    </button>
                    <button class="btn btn-sm btn-warning d-none" id="retryFailedBtn">
                        <i class="ki-duotone ki-arrows-circle fs-4 me-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Retry Failed <span id="retryFailedCount">0</span>
                    </button>
                    <button class="btn btn-sm btn-light" id="reloadLogBtn">Reload</button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">Cleanup</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item" data-cleanup="filtered">Delete matching filters</button></li>
                            <li><button class="dropdown-item" data-cleanup="older_30">Older than 30 days</button></li>
                            <li><button class="dropdown-item" data-cleanup="older_90">Older than 90 days</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item text-danger" data-cleanup="all">Delete everything</button></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">

                {{-- LIVE QUEUE STATUS STRIP --}}
                <div id="queueStatusStrip" class="d-flex flex-wrap align-items-center gap-3 mb-4 p-3 rounded bg-light">
                    <div class="d-flex align-items-center gap-2">
                        <span class="spinner-grow spinner-grow-sm text-secondary" id="queueStatusDot" role="status"></span>
                        <span class="fw-semibold text-gray-700 fs-7">Alerts queue</span>
                    </div>
                    <span class="badge badge-light-info fs-7">
                        In queue: <span id="statQueueSize" class="fw-bold">0</span>
                    </span>
                    <span class="badge badge-light-info fs-7">
                        Delayed: <span id="statDelayed" class="fw-bold">0</span>
                    </span>
                    <span class="badge badge-light-success fs-7">
                        Sent today: <span id="statSentToday" class="fw-bold">0</span>
                    </span>
                    <span class="badge badge-light-warning fs-7">
                        Queued dispatches: <span id="statQueued" class="fw-bold">0</span>
                    </span>
                    <span class="badge badge-light-danger fs-7">
                        Failed today: <span id="statFailedToday" class="fw-bold">0</span>
                    </span>
                    <span class="ms-auto text-muted fs-8" id="queueStatusUpdated">—</span>
                </div>

                <div id="queueEtaHint" class="text-muted fs-8 mb-4 d-none">
                    <i class="ki-duotone ki-time fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>
                    <span id="queueEtaText"></span>
                </div>

                {{-- Filters --}}
                <div class="row g-3 mb-5">
                    <div class="col-md-3">
                        <input type="text" id="logSearch" class="form-control form-control-solid" placeholder="Search seeker...">
                    </div>
                    <div class="col-md-2">
                        <select id="logChannel" class="form-select form-select-solid">
                            <option value="">All Channels</option>
                            <option value="email">Email</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="telegram">Telegram</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="logKind" class="form-select form-select-solid">
                            <option value="">All Kinds</option>
                            <option value="jobs">Job Alert</option>
                            <option value="preferences_prompt">Preferences Prompt</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="logStatus" class="form-select form-select-solid">
                            <option value="">All Statuses</option>
                            <option value="sent">Sent</option>
                            <option value="queued">Queued</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <input type="date" id="logFrom" class="form-control form-control-solid">
                            <input type="date" id="logTo" class="form-control form-control-solid">
                        </div>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                        <button class="btn btn-sm btn-light-primary" id="applyLogFilters">Apply Filters</button>
                        <button class="btn btn-sm btn-light" id="clearLogFilters">Clear</button>
                        <span class="text-muted fs-8 ms-auto" id="logCounts">
                            <span class="badge badge-light-secondary me-1" id="countAll">0</span> total
                            <span class="badge badge-light-success ms-2 me-1" id="countSent">0</span> sent
                            <span class="badge badge-light-info ms-2 me-1" id="countQueued">0</span> queued
                            <span class="badge badge-light-danger ms-2 me-1" id="countFailed">0</span> failed
                        </span>
                        <select id="logPerPage" class="form-select form-select-sm w-auto ms-2">
                            <option value="10">10 / page</option>
                            <option value="25" selected>25 / page</option>
                            <option value="50">50 / page</option>
                        </select>
                    </div>
                </div>

                <div id="logWrap" class="text-center py-10">
                    <div class="spinner-border text-primary"></div>
                </div>

                <div id="logPagination" class="d-none d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5">
                    <div id="logPaginationInfo" class="text-muted fs-7"></div>
                    <nav><ul class="pagination m-0" id="logPaginationList"></ul></nav>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════ OFFCANVAS DRAWER (For Viewing Sent Jobs) ══════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="jobsDrawer" aria-labelledby="jobsDrawerLabel" style="width: 450px;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold" id="jobsDrawerLabel">Jobs Sent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="jobsDrawerBody">
        <!-- Content injected via JS -->
    </div>
</div>

@endcan
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const CSRF = '{{ csrf_token() }}';
    const ROUTES = {
        preview:       '{{ route("admin.alerts.preview") }}',
        dispatch:      '{{ route("admin.alerts.dispatch") }}',
        seekers:       '{{ route("admin.alerts.seekers") }}',
        sendSelected:  '{{ route("admin.alerts.send-to-selected") }}',
        log:           '{{ route("admin.alerts.log") }}',
        bulkDelete:    '{{ route("admin.alerts.bulk-delete") }}',
        queueStatus:   '{{ route("admin.alerts.queue-status") }}',
        retryFailed:   '{{ route("admin.alerts.retry-failed") }}',
    };

    // ─────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────
    function capitalize(t) { return t ? t.charAt(0).toUpperCase() + t.slice(1) : ''; }
    function escapeHtml(t) {
        if (t === null || t === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(t);
        return div.innerHTML;
    }
    function debounce(fn, wait) {
        let timer;
        return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), wait); };
    }
    async function apiJson(url, options = {}) {
        const res = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(options.headers || {}),
            },
            ...options,
        });
        return res.json();
    }

    // ─────────────────────────────────────────────────────────
    // QUEUE STATUS POLLING  (declared first so retry handler can call it)
    // ─────────────────────────────────────────────────────────
    const queuePoll = {
        timer: null,
        intervalMs: 15000,
        active: false,
    };

    function setQueueDotState(busy) {
        const dot = document.getElementById('queueStatusDot');
        if (!dot) return;
        dot.classList.toggle('text-primary', !!busy);
        dot.classList.toggle('text-secondary', !busy);
    }

    async function refreshQueueStatus() {
        try {
            const data = await apiJson(ROUTES.queueStatus);
            if (!data.success) return;

            const q = data.queue || {};
            const d = data.dispatches || {};

            document.getElementById('statQueueSize').textContent   = q.size ?? 0;
            document.getElementById('statDelayed').textContent     = q.delayed ?? 0;
            document.getElementById('statSentToday').textContent   = d.sent_today ?? 0;
            document.getElementById('statQueued').textContent      = d.queued ?? 0;
            document.getElementById('statFailedToday').textContent = d.failed_today ?? 0;
            document.getElementById('queueStatusUpdated').textContent =
                'Updated ' + new Date().toLocaleTimeString();

            // Mirror into the log filter row so both views stay in sync
            const countQueuedEl = document.getElementById('countQueued');
            if (countQueuedEl) countQueuedEl.textContent = d.queued ?? 0;

            // Toggle Retry Failed button
            const retryBtn   = document.getElementById('retryFailedBtn');
            const retryCount = document.getElementById('retryFailedCount');
            if (retryBtn && retryCount) {
                if ((d.failed_total ?? 0) > 0) {
                    retryCount.textContent = d.failed_total;
                    retryBtn.classList.remove('d-none');
                } else {
                    retryBtn.classList.add('d-none');
                }
            }

            // ETA hint
            const eta     = document.getElementById('queueEtaHint');
            const etaText = document.getElementById('queueEtaText');
            const pending = (q.size ?? 0) + (q.delayed ?? 0);
            if (eta && etaText) {
                if (pending > 0) {
                    const minutes = Math.ceil(pending / 20);
                    etaText.textContent = `~${pending} email(s) remaining — about ${minutes} minute(s) at 20/min`;
                    eta.classList.remove('d-none');
                } else {
                    eta.classList.add('d-none');
                }
            }

            // Dot state
            setQueueDotState(pending > 0 || (d.queued ?? 0) > 0);

            // Stop polling once fully drained
            if (pending === 0 && (d.queued ?? 0) === 0) {
                stopQueuePolling();
            }
        } catch (e) { /* silent */ }
    }

    function startQueuePolling() {
        if (queuePoll.active) return;
        queuePoll.active = true;
        refreshQueueStatus();
        queuePoll.timer = setInterval(refreshQueueStatus, queuePoll.intervalMs);
    }

    function stopQueuePolling() {
        if (queuePoll.timer) clearInterval(queuePoll.timer);
        queuePoll.timer = null;
        queuePoll.active = false;
        setQueueDotState(false);
    }

    // ─────────────────────────────────────────────────────────
    // 1. DISPATCH PREVIEW & BULK DISPATCH
    // ─────────────────────────────────────────────────────────
    document.getElementById('previewBtn')?.addEventListener('click', async () => {
        const country = document.getElementById('countrySelect')?.value || '';
        if (!country) { alert('Please select a country first.'); return; }

        const data = await apiJson(ROUTES.preview, {
            method: 'POST',
            body: JSON.stringify({ country }),
        });

        if (!data.success) { alert(data.message || 'Preview failed.'); return; }
        renderPreview(data.summary);
    });

    function renderPreview(s) {
        const box = document.getElementById('previewBox');
        box.classList.remove('d-none');

        const card = (label, value, color) => `
            <div class="col-6 col-md-3">
                <div class="card bg-light-${color} h-100">
                    <div class="card-body py-5">
                        <div class="fs-2 fw-bold text-${color}">${value}</div>
                        <div class="text-muted fw-semibold fs-7">${label}</div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('previewCards').innerHTML = `
            ${card('Total Seekers', s.total, 'primary')}
            ${card('Will get jobs', s.jobs, 'success')}
            ${card('Preferences prompt', s.preferences_prompt, 'warning')}
            ${card('Skipped (inactive)', s.skip, 'secondary')}
            ${card('Email deliveries', s.by_channel.email, 'primary')}
            ${card('WhatsApp', s.by_channel.whatsapp, 'success')}
            ${card('Telegram', s.by_channel.telegram, 'info')}
        `;
    }

    document.getElementById('dispatchBtn')?.addEventListener('click', async (event) => {
        const btn = event.currentTarget;
        const country = document.getElementById('countrySelect')?.value || '';
        if (!country) { alert('Please select a country first.'); return; }

        const label = document.getElementById('countrySelect').options[
            document.getElementById('countrySelect').selectedIndex
        ].text.trim();
        if (!confirm(`Dispatch alerts to seekers in "${label}"?\n\nThis cannot be undone.`)) return;

        const channels = [];
        if (document.getElementById('chEmail').checked)    channels.push('email');
        if (document.getElementById('chWhatsapp').checked) channels.push('whatsapp');
        if (document.getElementById('chTelegram').checked) channels.push('telegram');
        if (channels.length === 0) { alert('Choose at least one channel.'); return; }

        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Dispatching...';

        try {
            const data = await apiJson(ROUTES.dispatch, {
                method: 'POST',
                body: JSON.stringify({ channels, country, mode: 'real' }),
            });
            if (data.success) {
                window.showToast?.('success', data.message || 'Dispatched.');
                startQueuePolling();
                setTimeout(loadLog, 1200);
            } else {
                window.showToast?.('error', data.message || 'Dispatch failed.');
            }
        } catch (e) {
            window.showToast?.('error', 'Network error. Try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });

    // ─────────────────────────────────────────────────────────
    // 2. TARGET SEEKERS LIST & SEND
    // ─────────────────────────────────────────────────────────
    const seekerState = { page: 1, perPage: 25, search: '', country: '', minJobs: 0, maxJobs: 999 };
    const selectedSeekers = new Set();

    document.getElementById('applySeekerFilters')?.addEventListener('click', () => {
        seekerState.search  = document.getElementById('seekerSearch').value.trim();
        seekerState.country = document.getElementById('seekerCountry').value;
        seekerState.minJobs = parseInt(document.getElementById('seekerMinJobs').value) || 0;
        seekerState.maxJobs = parseInt(document.getElementById('seekerMaxJobs').value) || 999;
        seekerState.page    = 1;
        selectedSeekers.clear();
        loadSeekers();
    });

    document.getElementById('seekerSearch')?.addEventListener('input', debounce(() => {
        document.getElementById('applySeekerFilters').click();
    }, 400));

    document.getElementById('seekerPerPage')?.addEventListener('change', function () {
        seekerState.perPage = parseInt(this.value) || 25;
        seekerState.page = 1;
        loadSeekers();
    });
    document.getElementById('reloadSeekersBtn')?.addEventListener('click', loadSeekers);

    async function loadSeekers() {
        const wrap  = document.getElementById('seekersWrap');
        const pager = document.getElementById('seekersPagination');
        pager.classList.add('d-none');
        wrap.innerHTML = `<div class="text-center py-8"><div class="spinner-border text-primary"></div></div>`;

        const params = new URLSearchParams({
            page: seekerState.page,
            per_page: seekerState.perPage,
            min_jobs: seekerState.minJobs,
            max_jobs: seekerState.maxJobs,
        });
        if (seekerState.search)  params.set('search', seekerState.search);
        if (seekerState.country) params.set('country', seekerState.country);

        try {
            const data = await apiJson(`${ROUTES.seekers}?${params}`);

            if (!data.success || !data.data?.length) {
                wrap.innerHTML = `<div class="text-center py-10"><h4 class="fw-bold text-gray-800 mb-1">No seekers match</h4><p class="text-muted">Adjust the filters.</p></div>`;
                return;
            }

            const allChecked = data.data.every(s => selectedSeekers.has(s.id));
            wrap.innerHTML = `
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed table-row-gray-300 fs-7 gy-3">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-30px"><input type="checkbox" class="form-check-input" id="selectAllSeekers" ${allChecked ? 'checked' : ''}></th>
                                <th class="text-start">Seeker</th>
                                <th class="text-start">Country</th>
                                <th class="text-start">Decision</th>
                                <th class="text-start">Jobs</th>
                                <th class="text-start">Channels</th>
                            </tr>
                        </thead>
                        <tbody>${data.data.map(renderSeekerRow).join('')}</tbody>
                    </table>
                </div>`;

            wrap.querySelectorAll('.seeker-checkbox').forEach(cb => {
                cb.addEventListener('change', function () {
                    const id = parseInt(this.value);
                    if (this.checked) selectedSeekers.add(id); else selectedSeekers.delete(id);
                    updateSeekerSelectionUI();
                });
            });

            document.getElementById('selectAllSeekers')?.addEventListener('change', function () {
                wrap.querySelectorAll('.seeker-checkbox').forEach(cb => {
                    cb.checked = this.checked;
                    const id = parseInt(cb.value);
                    if (this.checked) selectedSeekers.add(id); else selectedSeekers.delete(id);
                });
                updateSeekerSelectionUI();
            });

            renderSeekerPagination(data.pagination);
            pager.classList.remove('d-none');
            updateSeekerSelectionUI();
        } catch (e) {
            wrap.innerHTML = `<div class="alert alert-danger">Failed to load seekers.</div>`;
        }
    }

    function renderSeekerRow(s) {
        const decisionBadge = { jobs: 'success', preferences_prompt: 'warning', skip: 'secondary' }[s.decision] || 'secondary';
        const jobsBadge     = s.jobs_count === 0 ? 'danger' : (s.jobs_count >= 5 ? 'success' : 'primary');
        const channelPills  = [
            s.prefs.email    ? '<span class="badge badge-light-primary me-1">Email</span>' : '',
            s.prefs.whatsapp ? '<span class="badge badge-light-success me-1">WA</span>'    : '',
            s.prefs.telegram ? '<span class="badge badge-light-info">TG</span>'            : '',
        ].join('') || '<span class="text-muted fs-8">none</span>';
        const jobTitles = s.job_titles?.length
            ? s.job_titles.map(t => `<div class="text-muted fs-8">• ${escapeHtml(t)}</div>`).join('')
            : '';

        return `
            <tr>
                <td><input type="checkbox" class="form-check-input seeker-checkbox" value="${s.id}" ${selectedSeekers.has(s.id) ? 'checked' : ''}></td>
                <td class="text-start">
                    <div class="fw-bold text-gray-800">${escapeHtml(s.name || 'Unknown')}</div>
                    <div class="text-muted fs-8">${escapeHtml(s.email || '')}</div>
                </td>
                <td class="text-start"><span class="badge badge-light-secondary">${escapeHtml(s.country || '—')}</span></td>
                <td class="text-start"><span class="badge badge-light-${decisionBadge}">${capitalize(s.decision)}</span></td>
                <td class="text-start">
                    <span class="badge badge-light-${jobsBadge}">${s.jobs_count}</span>
                    ${jobTitles}
                </td>
                <td class="text-start">${channelPills}</td>
            </tr>`;
    }

    function updateSeekerSelectionUI() {
        const btn = document.getElementById('sendSelectedBtn');
        const cnt = document.getElementById('seekerSelectedCount');
        if (cnt) cnt.textContent = selectedSeekers.size;
        if (btn) btn.classList.toggle('d-none', selectedSeekers.size === 0);
    }

    function renderSeekerPagination(meta) {
        const el   = document.getElementById('seekersPaginationList');
        const info = document.getElementById('seekersPaginationInfo');
        if (!el || !info) return;
        el.innerHTML = '';
        info.textContent = meta.total ? `Showing ${meta.from || 0} to ${meta.to || 0} of ${meta.total}` : '0 records';

        const add = (page, text, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link'; a.href = '#'; a.textContent = text;
            if (!disabled && !active) {
                a.addEventListener('click', (e) => { e.preventDefault(); seekerState.page = page; loadSeekers(); });
            }
            li.appendChild(a); el.appendChild(li);
        };
        if (meta.current_page > 1) add(meta.current_page - 1, 'Prev');
        const start = Math.max(1, meta.current_page - 2);
        const end   = Math.min(meta.last_page, meta.current_page + 2);
        for (let i = start; i <= end; i++) add(i, i, i === meta.current_page);
        if (meta.current_page < meta.last_page) add(meta.current_page + 1, 'Next');
    }

    document.getElementById('sendSelectedBtn')?.addEventListener('click', async (event) => {
        if (selectedSeekers.size === 0) return;
        const channels = [];
        if (document.getElementById('seekerChEmail').checked)    channels.push('email');
        if (document.getElementById('seekerChWhatsapp').checked) channels.push('whatsapp');
        if (document.getElementById('seekerChTelegram').checked) channels.push('telegram');
        if (channels.length === 0) { window.showToast?.('error', 'Pick at least one channel.'); return; }
        if (!confirm(`Send real alerts to ${selectedSeekers.size} seeker(s) via ${channels.join(', ')}?`)) return;

        const btn = event.currentTarget;
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';

        try {
            const data = await apiJson(ROUTES.sendSelected, {
                method: 'POST',
                body: JSON.stringify({
                    seeker_ids: Array.from(selectedSeekers),
                    channels,
                    mode: 'real',
                }),
            });
            if (data.success) {
                window.showToast?.('success', data.message || 'Queued.');
                selectedSeekers.clear();
                updateSeekerSelectionUI();
                startQueuePolling();
                setTimeout(() => { loadSeekers(); loadLog(); }, 1500);
            } else {
                window.showToast?.('error', data.message || 'Send failed.');
            }
        } catch (e) {
            window.showToast?.('error', 'Network error. Try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });

    // ─────────────────────────────────────────────────────────
    // 3. DISPATCH LOGS & DRAWER
    // ─────────────────────────────────────────────────────────
    const logState = { page: 1, perPage: 25, search: '', channel: '', kind: '', status: '', from: '', to: '' };
    const selectedIds = new Set();
    const jobsDrawer = new bootstrap.Offcanvas(document.getElementById('jobsDrawer'));

    document.getElementById('applyLogFilters')?.addEventListener('click', () => {
        logState.search  = document.getElementById('logSearch').value.trim();
        logState.channel = document.getElementById('logChannel').value;
        logState.kind    = document.getElementById('logKind').value;
        logState.status  = document.getElementById('logStatus').value;
        logState.from    = document.getElementById('logFrom').value;
        logState.to      = document.getElementById('logTo').value;
        logState.page    = 1;
        selectedIds.clear();
        loadLog();
    });

    document.getElementById('clearLogFilters')?.addEventListener('click', () => {
        ['logSearch','logChannel','logKind','logStatus','logFrom','logTo'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        Object.assign(logState, { search: '', channel: '', kind: '', status: '', from: '', to: '', page: 1 });
        selectedIds.clear();
        loadLog();
    });

    document.getElementById('logSearch')?.addEventListener('input', debounce(() => {
        document.getElementById('applyLogFilters').click();
    }, 400));
    document.getElementById('logPerPage')?.addEventListener('change', function () {
        logState.perPage = parseInt(this.value) || 25;
        logState.page = 1;
        loadLog();
    });
    document.getElementById('reloadLogBtn')?.addEventListener('click', loadLog);

    async function loadLog() {
        const wrap  = document.getElementById('logWrap');
        const pager = document.getElementById('logPagination');
        pager.classList.add('d-none');
        wrap.innerHTML = `<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>`;

        const params = new URLSearchParams({ page: logState.page, per_page: logState.perPage });
        if (logState.search)  params.set('search', logState.search);
        if (logState.channel) params.set('channel', logState.channel);
        if (logState.kind)    params.set('kind', logState.kind);
        if (logState.status)  params.set('status', logState.status);
        if (logState.from)    params.set('from', logState.from);
        if (logState.to)      params.set('to', logState.to);

        try {
            const data = await apiJson(`${ROUTES.log}?${params}`);

            if (data.counts) {
                document.getElementById('countAll').textContent     = data.counts.all ?? 0;
                document.getElementById('countSent').textContent    = data.counts.sent ?? 0;
                document.getElementById('countQueued').textContent  = data.counts.queued ?? 0;
                document.getElementById('countFailed').textContent  = data.counts.failed ?? 0;
            }

            if (!data.success || !data.data?.data?.length) {
                wrap.innerHTML = `<div class="text-center py-15"><h4 class="fw-bold text-gray-800 mb-2">No dispatches match</h4></div>`;
                return;
            }

            const selectAllChecked = data.data.data.every(d => selectedIds.has(d.id));
            wrap.innerHTML = `
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed table-row-gray-300 fs-7 gy-3">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-30px"><input type="checkbox" class="form-check-input" id="selectAllRows" ${selectAllChecked ? 'checked' : ''}></th>
                                <th class="min-w-220px text-start">Seeker</th>
                                <th class="min-w-100px text-start">Channel</th>
                                <th class="min-w-150px text-start">Kind</th>
                                <th class="min-w-70px text-start">Jobs</th>
                                <th class="min-w-110px text-start">Status</th>
                                <th class="min-w-160px text-start">Sent</th>
                            </tr>
                        </thead>
                        <tbody>${data.data.data.map(renderLogRow).join('')}</tbody>
                    </table>
                </div>`;

            wrap.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.addEventListener('change', function () {
                    const id = parseInt(this.value);
                    if (this.checked) selectedIds.add(id); else selectedIds.delete(id);
                    updateLogSelectionUI();
                });
            });

            document.getElementById('selectAllRows')?.addEventListener('change', function () {
                wrap.querySelectorAll('.row-checkbox').forEach(cb => {
                    cb.checked = this.checked;
                    const id = parseInt(cb.value);
                    if (this.checked) selectedIds.add(id); else selectedIds.delete(id);
                });
                updateLogSelectionUI();
            });

            renderLogPagination(data.data);
            pager.classList.remove('d-none');
            updateLogSelectionUI();
        } catch (e) {
            wrap.innerHTML = `<div class="alert alert-danger">Failed to load dispatch log.</div>`;
        }
    }

    function renderLogRow(d) {
        const channelClass = { email: 'primary', whatsapp: 'success', telegram: 'info' }[d.channel] || 'secondary';
        const statusClass  = { sent: 'success', queued: 'info', failed: 'danger' }[d.status] || 'secondary';
        const kindLabel    = { jobs: 'Job Alert', preferences_prompt: 'Prefs Prompt' }[d.kind] || d.kind;

        const jobDetailsJson = encodeURIComponent(JSON.stringify(d.job_details || []));

        const retryBtn = d.status === 'failed'
            ? `<button type="button" class="btn btn-icon btn-sm btn-light-warning ms-2" title="Retry this dispatch"
                       onclick="retryOne(event, ${d.id})">
                   <i class="ki-duotone ki-arrows-circle fs-5">
                       <span class="path1"></span><span class="path2"></span>
                   </i>
               </button>`
            : '';

        return `
            <tr>
                <td><input type="checkbox" class="form-check-input row-checkbox" value="${d.id}" ${selectedIds.has(d.id) ? 'checked' : ''}></td>
                <td class="text-start">
                    <a href="#" class="fw-bold text-gray-800 text-hover-primary text-decoration-none"
                       onclick="openJobsDrawer(event, '${escapeHtml(d.user || 'Unknown')}', '${jobDetailsJson}')">
                        ${escapeHtml(d.user || 'Unknown')}
                    </a>
                    <div class="text-muted fs-8">${escapeHtml(d.email || '')}</div>
                </td>
                <td class="text-start"><span class="badge badge-light-${channelClass}">${capitalize(d.channel)}</span></td>
                <td class="text-start"><span class="text-gray-700 fw-semibold">${escapeHtml(kindLabel)}</span></td>
                <td class="text-start"><span class="badge badge-light-secondary">${d.jobs_sent ?? 0}</span></td>
                <td class="text-start">
                    <span class="badge badge-light-${statusClass}">${capitalize(d.status)}</span>
                    ${retryBtn}
                </td>
                <td class="text-start text-muted fs-8">${d.sent_at ? new Date(d.sent_at).toLocaleString() : '-'}</td>
            </tr>`;
    }

    window.openJobsDrawer = function(event, userName, encodedJobs) {
        event.preventDefault();
        const jobs = JSON.parse(decodeURIComponent(encodedJobs));

        document.getElementById('jobsDrawerLabel').textContent = `Jobs Sent to ${userName}`;
        const body = document.getElementById('jobsDrawerBody');

        if (!jobs.length) {
            body.innerHTML = `<div class="alert alert-warning">No jobs were sent (this was likely a Preferences Prompt).</div>`;
        } else {
            body.innerHTML = `
                <div class="d-flex flex-column gap-3">
                    ${jobs.map(j => `
                        <div class="card bg-light-primary border border-primary border-dashed">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-1">${escapeHtml(j.title)}</h5>
                                <div class="text-muted fs-7">
                                    <i class="ki-duotone ki-briefcase fs-6 me-1 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
                                    ${escapeHtml(j.company)}
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>`;
        }
        jobsDrawer.show();
    };

    function updateLogSelectionUI() {
        const btn = document.getElementById('bulkDeleteSelectedBtn');
        const cnt = document.getElementById('selectedCount');
        if (cnt) cnt.textContent = selectedIds.size;
        if (btn) btn.classList.toggle('d-none', selectedIds.size === 0);
    }

    function renderLogPagination(meta) {
        const el   = document.getElementById('logPaginationList');
        const info = document.getElementById('logPaginationInfo');
        if (!el || !info) return;
        el.innerHTML = '';
        info.textContent = meta.total ? `Showing ${meta.from || 0} to ${meta.to || 0} of ${meta.total}` : '0 records';

        const add = (page, text, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link'; a.href = '#'; a.textContent = text;
            if (!disabled && !active) {
                a.addEventListener('click', (e) => { e.preventDefault(); logState.page = page; loadLog(); });
            }
            li.appendChild(a); el.appendChild(li);
        };
        if (meta.current_page > 1) add(meta.current_page - 1, 'Prev');
        const start = Math.max(1, meta.current_page - 2);
        const end   = Math.min(meta.last_page, meta.current_page + 2);
        for (let i = start; i <= end; i++) add(i, i, i === meta.current_page);
        if (meta.current_page < meta.last_page) add(meta.current_page + 1, 'Next');
    }

    document.getElementById('bulkDeleteSelectedBtn')?.addEventListener('click', async () => {
        if (selectedIds.size === 0) return;
        if (!confirm(`Delete ${selectedIds.size} dispatch record(s)?`)) return;

        const btn = document.getElementById('bulkDeleteSelectedBtn');
        btn.disabled = true;
        try {
            const data = await apiJson(ROUTES.bulkDelete, {
                method: 'POST',
                body: JSON.stringify({ mode: 'selected', ids: Array.from(selectedIds) }),
            });
            if (data.success) {
                window.showToast?.('success', data.message || 'Deleted.');
                selectedIds.clear();
                updateLogSelectionUI();
                loadLog();
                refreshQueueStatus();
            } else {
                window.showToast?.('error', data.message || 'Delete failed.');
            }
        } catch (e) {
            window.showToast?.('error', 'Network error.');
        } finally {
            btn.disabled = false;
        }
    });

    // Cleanup menu
    document.querySelectorAll('[data-cleanup]').forEach(el => {
        el.addEventListener('click', async () => {
            const mode = el.dataset.cleanup;
            let body = { mode }, confirmMsg = '';

            if (mode === 'all')           { confirmMsg = 'Delete EVERY dispatch record?'; }
            else if (mode === 'older_30') { body = { mode: 'older_than', older_than_days: 30 }; confirmMsg = 'Delete records > 30 days?'; }
            else if (mode === 'older_90') { body = { mode: 'older_than', older_than_days: 90 }; confirmMsg = 'Delete records > 90 days?'; }
            else if (mode === 'filtered') { body = { mode: 'filtered', filters: logState }; confirmMsg = 'Delete matching current filters?'; }

            if (!confirmMsg || !confirm(confirmMsg)) return;

            try {
                const data = await apiJson(ROUTES.bulkDelete, {
                    method: 'POST',
                    body: JSON.stringify(body),
                });
                if (data.success) {
                    window.showToast?.('success', data.message || 'Deleted.');
                    selectedIds.clear();
                    updateLogSelectionUI();
                    loadLog();
                    refreshQueueStatus();
                } else {
                    window.showToast?.('error', data.message || 'Delete failed.');
                }
            } catch (e) {
                window.showToast?.('error', 'Network error.');
            }
        });
    });

    // ─────────────────────────────────────────────────────────
    // 4. RETRY FAILED (bulk + single)
    // ─────────────────────────────────────────────────────────
    document.getElementById('retryFailedBtn')?.addEventListener('click', async () => {
        const total = parseInt(document.getElementById('retryFailedCount')?.textContent || '0', 10);
        if (!total) return;

        const filters = {};
        if (logState.channel) filters.channel = logState.channel;
        if (logState.kind)    filters.kind    = logState.kind;
        if (logState.from)    filters.from    = logState.from;
        if (logState.to)      filters.to      = logState.to;

        const hasFilters = Object.keys(filters).length > 0;
        const msg = hasFilters
            ? `Retry ${total} failed dispatch(es) matching the current filters?`
            : `Retry ALL ${total} failed dispatch(es)?`;
        if (!confirm(msg)) return;

        const btn = document.getElementById('retryFailedBtn');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Re-queueing...';

        try {
            const data = await apiJson(ROUTES.retryFailed, {
                method: 'POST',
                body: JSON.stringify(filters),
            });
            if (data.success) {
                window.showToast?.('success', data.message || 'Retry queued.');
                startQueuePolling();
                setTimeout(() => { loadLog(); refreshQueueStatus(); }, 1200);
            } else {
                window.showToast?.('error', data.message || 'Retry failed.');
            }
        } catch (e) {
            window.showToast?.('error', 'Network error. Try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });

    window.retryOne = async function (event, id) {
        event.preventDefault();
        if (!confirm('Retry this dispatch?')) return;
        try {
            const data = await apiJson(ROUTES.retryFailed, {
                method: 'POST',
                body: JSON.stringify({ ids: [id] }),
            });
            if (data.success) {
                window.showToast?.('success', data.message || 'Retry queued.');
                startQueuePolling();
                setTimeout(loadLog, 1000);
            } else {
                window.showToast?.('error', data.message || 'Retry failed.');
            }
        } catch (e) {
            window.showToast?.('error', 'Network error.');
        }
    };

    // ─────────────────────────────────────────────────────────
    // 5. LIFECYCLE
    // ─────────────────────────────────────────────────────────
    // Pause polling when tab is hidden, resume when visible
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopQueuePolling();
        } else if (!queuePoll.active) {
            startQueuePolling();
        }
    });

    // Restart polling whenever the Dispatch Logs tab is opened
    document.querySelector('a[href="#tab-log"]')?.addEventListener('shown.bs.tab', () => {
        startQueuePolling();
        loadLog();
    });

    // Boot
    loadSeekers();
    loadLog();
    startQueuePolling();

})();
</script>
@endpush