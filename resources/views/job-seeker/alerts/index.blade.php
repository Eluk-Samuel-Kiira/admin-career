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

{{-- ── Dispatch panel ── --}}
<div class="card card-flush mb-6">
    <div class="card-header align-items-center py-5">
        <div class="card-title">
            <h3 class="fw-bold mb-0">Send Job Alerts</h3>
        </div>
    </div>

    <div class="card-body pt-0">

        {{-- Filters row --}}
        <div class="row g-4 mb-6">
            <div class="col-md-4">
                <label class="fw-semibold fs-7 mb-2">Country</label>
                <select id="countrySelect" class="form-select form-select-solid">
                    <option value="">Select a country...</option>
                    @foreach(\App\Models\Job\Country::where('is_active', true)->orderBy('name')->get(['code','name','flag']) as $c)
                        <option value="{{ $c->code }}">{{ $c->flag ?? '' }} {{ $c->name }}</option>
                    @endforeach
                </select>
                <div class="text-muted fs-8 mt-1">Only seekers from the selected country are processed.</div>
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
                <div class="text-muted fs-8 mt-1">WhatsApp and Telegram require an active seeker subscription.</div>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button class="btn btn-light-primary w-50" id="previewBtn">
                        <i class="ki-duotone ki-eye fs-3 me-1">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                        Preview
                    </button>
                    <button class="btn btn-primary w-50" id="dispatchBtn">
                        <i class="ki-duotone ki-send fs-3 me-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Dispatch
                    </button>
                </div>
            </div>
        </div>

        {{-- Preview area --}}
        <div id="previewBox" class="d-none">
            <div class="separator my-5"></div>
            <h5 class="fw-bold mb-4">Preview</h5>
            <div class="row g-4" id="previewCards"></div>
        </div>

    </div>
</div>

{{-- ── Recent dispatches ── --}}
<div class="card card-flush">
    <div class="card-header align-items-center py-5">
        <h3 class="card-title fw-bold mb-0">Recent Dispatches</h3>
        <div class="card-toolbar gap-2">
            <button class="btn btn-sm btn-light-danger d-none" id="bulkDeleteSelectedBtn">
                <i class="ki-duotone ki-trash fs-3 me-1">
                    <span class="path1"></span><span class="path2"></span>
                    <span class="path3"></span><span class="path4"></span>
                </i>
                Delete Selected <span id="selectedCount">0</span>
            </button>
            <button class="btn btn-sm btn-light" id="reloadLogBtn">
                <i class="ki-duotone ki-arrows-circle fs-3 me-1">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                Reload
            </button>
            <div class="dropdown">
                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ki-duotone ki-trash fs-3 me-1">
                        <span class="path1"></span><span class="path2"></span>
                        <span class="path3"></span><span class="path4"></span>
                    </i>
                    Cleanup
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button class="dropdown-item" data-cleanup="filtered">
                            Delete matching filters
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item" data-cleanup="older_30">
                            Delete entries older than 30 days
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item" data-cleanup="older_90">
                            Delete entries older than 90 days
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button class="dropdown-item text-danger" data-cleanup="all">
                            Delete everything
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card-body pt-0">

        {{-- Filters --}}
        <div class="row g-3 mb-5">
            <div class="col-md-3">
                <input type="text" id="logSearch" class="form-control form-control-solid"
                       placeholder="Search seeker or error...">
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
                    <option value="profile_prompt">Profile Prompt</option>
                    <option value="cv_prompt">CV Prompt</option>
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
                <button class="btn btn-sm btn-light-primary" id="applyLogFilters">
                    <i class="ki-duotone ki-magnifier fs-4 me-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    Apply Filters
                </button>
                <button class="btn btn-sm btn-light" id="clearLogFilters">Clear</button>

                <span class="text-muted fs-8 ms-auto" id="logCounts">
                    <span class="badge badge-light-secondary me-1" id="countAll">0</span> total
                    <span class="badge badge-light-success ms-2 me-1" id="countSent">0</span> sent
                    <span class="badge badge-light-danger ms-2 me-1" id="countFailed">0</span> failed
                </span>

                <select id="logPerPage" class="form-select form-select-sm w-auto ms-2">
                    <option value="10">10 / page</option>
                    <option value="25" selected>25 / page</option>
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                </select>
            </div>
        </div>

        {{-- Table --}}
        <div id="logWrap" class="text-center py-10">
            <div class="spinner-border text-primary"></div>
        </div>

        {{-- Pagination --}}
        <div id="logPagination" class="d-none d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5">
            <div id="logPaginationInfo" class="text-muted fs-7"></div>
            <nav><ul class="pagination m-0" id="logPaginationList"></ul></nav>
        </div>
    </div>
</div>

@endcan
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const CSRF = '{{ csrf_token() }}';

    // ─────────────────────────────────────────────────────────
    // Preview + Dispatch (unchanged from your earlier version)
    // ─────────────────────────────────────────────────────────
    document.getElementById('previewBtn')?.addEventListener('click', async () => {
        const country = document.getElementById('countrySelect')?.value || '';
        if (!country) { alert('Please select a country first.'); return; }

        const res = await fetch('{{ route("admin.alerts.preview") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ country }),
        });
        const data = await res.json();

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
            ${card('Total Seekers',      s.total,              'primary')}
            ${card('Will get jobs',       s.jobs,               'success')}
            ${card('Preferences prompt',  s.preferences_prompt, 'warning')}
            ${card('Profile prompt',      s.profile_prompt,     'info')}
            ${card('CV prompt',           s.cv_prompt,          'danger')}
            ${card('Email deliveries',    s.by_channel.email,    'primary')}
            ${card('WhatsApp (subs.)',    s.by_channel.whatsapp, 'success')}
            ${card('Telegram (subs.)',    s.by_channel.telegram, 'info')}
        `;
    }

    document.getElementById('dispatchBtn')?.addEventListener('click', async (event) => {
        const btn = event.currentTarget;

        const country = document.getElementById('countrySelect')?.value || '';
        if (!country) { alert('Please select a country first.'); return; }

        const label = document.getElementById('countrySelect')
            .options[document.getElementById('countrySelect').selectedIndex]
            .text.trim();

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
            const res = await fetch('{{ route("admin.alerts.dispatch") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ channels, country, mode: 'real' }),
            });
            const data = await res.json();

            if (data.success) {
                window.showToast?.('success', data.message || 'Dispatched.');
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
    // Log state
    // ─────────────────────────────────────────────────────────
    const logState = {
        page: 1,
        perPage: 25,
        search: '',
        channel: '',
        kind: '',
        status: '',
        from: '',
        to: '',
    };

    const selectedIds = new Set();

    // ─────────────────────────────────────────────────────────
    // Log filters
    // ─────────────────────────────────────────────────────────
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
        ['logSearch','logChannel','logKind','logStatus','logFrom','logTo']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        Object.assign(logState, {
            search: '', channel: '', kind: '', status: '', from: '', to: '', page: 1,
        });
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

    // ─────────────────────────────────────────────────────────
    // Load log
    // ─────────────────────────────────────────────────────────
    async function loadLog() {
        const wrap = document.getElementById('logWrap');
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
            const res = await fetch(`{{ route("admin.alerts.log") }}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (data.counts) {
                document.getElementById('countAll').textContent    = data.counts.all ?? 0;
                document.getElementById('countSent').textContent   = data.counts.sent ?? 0;
                document.getElementById('countFailed').textContent = data.counts.failed ?? 0;
            }

            if (!data.success || !data.data?.data?.length) {
                wrap.innerHTML = `
                    <div class="text-center py-15">
                        <i class="ki-duotone ki-information-5 fs-5tx text-muted mb-4 d-block">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                        <h4 class="fw-bold text-gray-800 mb-2">No dispatches match</h4>
                        <p class="text-muted">Adjust the filters or clear them.</p>
                    </div>`;
                return;
            }

            const selectAllChecked = data.data.data.every(d => selectedIds.has(d.id));

            wrap.innerHTML = `
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed table-row-gray-300 fs-7 gy-3">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-30px">
                                    <input type="checkbox" class="form-check-input" id="selectAllRows" ${selectAllChecked ? 'checked' : ''}>
                                </th>
                                <th class="min-w-220px text-start">Seeker</th>
                                <th class="min-w-100px text-start">Channel</th>
                                <th class="min-w-150px text-start">Kind</th>
                                <th class="min-w-70px text-start">Jobs</th>
                                <th class="min-w-110px text-start">Status</th>
                                <th class="min-w-160px text-start">Sent</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.data.data.map(renderRow).join('')}
                        </tbody>
                    </table>
                </div>`;

            wrap.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.addEventListener('change', function () {
                    const id = parseInt(this.value);
                    if (this.checked) selectedIds.add(id); else selectedIds.delete(id);
                    updateSelectionUI();
                });
            });

            document.getElementById('selectAllRows')?.addEventListener('change', function () {
                wrap.querySelectorAll('.row-checkbox').forEach(cb => {
                    cb.checked = this.checked;
                    const id = parseInt(cb.value);
                    if (this.checked) selectedIds.add(id); else selectedIds.delete(id);
                });
                updateSelectionUI();
            });

            renderLogPagination(data.data);
            pager.classList.remove('d-none');
            updateSelectionUI();
        } catch (e) {
            wrap.innerHTML = `<div class="alert alert-danger">Failed to load dispatch log.</div>`;
        }
    }

    function renderRow(d) {
        const channelClass = {
            email: 'primary', whatsapp: 'success', telegram: 'info',
        }[d.channel] || 'secondary';

        const statusClass = {
            sent: 'success', queued: 'info', failed: 'danger',
        }[d.status] || 'secondary';

        const kindLabel = {
            jobs: 'Job Alert',
            preferences_prompt: 'Preferences Prompt',
            profile_prompt: 'Profile Prompt',
            cv_prompt: 'CV Prompt',
        }[d.kind] || d.kind;

        return `
            <tr>
                <td>
                    <input type="checkbox" class="form-check-input row-checkbox" value="${d.id}"
                           ${selectedIds.has(d.id) ? 'checked' : ''}>
                </td>
                <td class="text-start">
                    <div class="fw-bold text-gray-800">${escapeHtml(d.user || 'Unknown')}</div>
                    <div class="text-muted fs-8">${escapeHtml(d.email || '')}</div>
                </td>
                <td class="text-start"><span class="badge badge-light-${channelClass}">${capitalize(d.channel)}</span></td>
                <td class="text-start"><span class="text-gray-700 fw-semibold">${escapeHtml(kindLabel)}</span></td>
                <td class="text-start"><span class="badge badge-light-secondary">${d.jobs_sent ?? 0}</span></td>
                <td class="text-start">
                    <span class="badge badge-light-${statusClass}">${capitalize(d.status)}</span>
                    ${d.error_message ? `<div class="text-danger fs-8 mt-1" title="${escapeHtml(d.error_message)}">${escapeHtml(d.error_message.substring(0, 60))}${d.error_message.length > 60 ? '…' : ''}</div>` : ''}
                </td>
                <td class="text-start text-muted fs-8">
                    ${d.sent_at
                        ? new Date(d.sent_at).toLocaleString()
                        : (d.created_at ? new Date(d.created_at).toLocaleString() + ' <span class="text-muted">(queued)</span>' : '-')}
                </td>
            </tr>`;
    }

    function updateSelectionUI() {
        const btn = document.getElementById('bulkDeleteSelectedBtn');
        const cnt = document.getElementById('selectedCount');
        if (cnt) cnt.textContent = selectedIds.size;
        if (btn) btn.classList.toggle('d-none', selectedIds.size === 0);
    }

    function renderLogPagination(meta) {
        const el = document.getElementById('logPaginationList');
        const info = document.getElementById('logPaginationInfo');
        if (!el || !info) return;

        el.innerHTML = '';
        info.textContent = meta.total
            ? `Showing ${meta.from || 0} to ${meta.to || 0} of ${meta.total}`
            : '0 records';

        const add = (page, text, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            if (!disabled && !active) {
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    logState.page = page;
                    loadLog();
                });
            }
            li.appendChild(a);
            el.appendChild(li);
        };

        if (meta.current_page > 1) add(meta.current_page - 1, 'Prev');
        const start = Math.max(1, meta.current_page - 2);
        const end   = Math.min(meta.last_page, meta.current_page + 2);
        if (start > 1) add(1, '1');
        if (start > 2) el.insertAdjacentHTML('beforeend', '<li class="page-item disabled"><span class="page-link">…</span></li>');
        for (let i = start; i <= end; i++) add(i, i, i === meta.current_page);
        if (end < meta.last_page - 1) el.insertAdjacentHTML('beforeend', '<li class="page-item disabled"><span class="page-link">…</span></li>');
        if (end < meta.last_page) add(meta.last_page, meta.last_page);
        if (meta.current_page < meta.last_page) add(meta.current_page + 1, 'Next');
    }

    // ─────────────────────────────────────────────────────────
    // Bulk delete selected
    // ─────────────────────────────────────────────────────────
    document.getElementById('bulkDeleteSelectedBtn')?.addEventListener('click', async (event) => {
        if (selectedIds.size === 0) return;

        if (!confirm(`Delete ${selectedIds.size} dispatch record(s)?\n\nThis cannot be undone.`)) return;

        const btn = event.currentTarget;
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

        try {
            const res = await fetch('{{ route("admin.alerts.bulk-delete") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    mode: 'selected',
                    ids: Array.from(selectedIds),
                }),
            });
            const data = await res.json();
            if (data.success) {
                window.showToast?.('success', data.message || 'Deleted.');
                selectedIds.clear();
                updateSelectionUI();
                loadLog();
            } else {
                window.showToast?.('error', data.message || 'Delete failed.');
            }
        } catch (e) {
            window.showToast?.('error', 'Network error.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });

    // ─────────────────────────────────────────────────────────
    // Cleanup menu
    // ─────────────────────────────────────────────────────────
    document.querySelectorAll('[data-cleanup]').forEach(el => {
        el.addEventListener('click', async () => {
            const mode = el.dataset.cleanup;

            let body, confirmMsg;
            if (mode === 'all') {
                body = { mode: 'all' };
                confirmMsg = 'This will delete EVERY alert dispatch record.\n\nAre you absolutely sure?';
            } else if (mode === 'older_30') {
                body = { mode: 'older_than', older_than_days: 30 };
                confirmMsg = 'Delete all alert dispatch records older than 30 days?';
            } else if (mode === 'older_90') {
                body = { mode: 'older_than', older_than_days: 90 };
                confirmMsg = 'Delete all alert dispatch records older than 90 days?';
            } else if (mode === 'filtered') {
                body = {
                    mode: 'filtered',
                    filters: {
                        channel: logState.channel || undefined,
                        kind:    logState.kind || undefined,
                        status:  logState.status || undefined,
                        from:    logState.from || undefined,
                        to:      logState.to || undefined,
                    },
                };
                confirmMsg = 'Delete all records matching the current filters?';
            } else {
                return;
            }

            if (!confirm(confirmMsg)) return;

            try {
                const res = await fetch('{{ route("admin.alerts.bulk-delete") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (data.success) {
                    window.showToast?.('success', data.message || 'Deleted.');
                    selectedIds.clear();
                    updateSelectionUI();
                    loadLog();
                } else {
                    window.showToast?.('error', data.message || 'Delete failed.');
                }
            } catch (e) {
                window.showToast?.('error', 'Network error.');
            }
        });
    });

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────
    function capitalize(t) {
        if (!t) return '';
        return t.charAt(0).toUpperCase() + t.slice(1);
    }
    function escapeHtml(t) {
        if (t === null || t === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(t);
        return div.innerHTML;
    }
    function debounce(fn, wait) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), wait);
        };
    }

    // ─────────────────────────────────────────────────────────
    // Boot
    // ─────────────────────────────────────────────────────────
    loadLog();

})();
</script>
@endpush