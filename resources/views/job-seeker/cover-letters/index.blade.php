@extends('layouts.admin')

@section('title', 'Cover Letters')
@section('page_title', 'Cover / Application Letters')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Seeker Services</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Cover Letters</li>
@endsection

@section('content')
@can('view cv review requests')

{{-- ── Stat cards ─────────────────────────────────────────── --}}
<div class="row g-4 g-md-5 mb-6">
    @php
        $cards = [
            ['label' => 'Total Letters',      'value' => $stats['total'],            'color' => 'primary', 'icon' => 'ki-document'],
            ['label' => 'Pending Payment',    'value' => $stats['pending_payment'],  'color' => 'warning', 'icon' => 'ki-time'],
            ['label' => 'In Progress',        'value' => $stats['processing'],       'color' => 'info',    'icon' => 'ki-arrows-circle'],
            ['label' => 'Ready',              'value' => $stats['generated'],        'color' => 'success', 'icon' => 'ki-check-circle'],
            ['label' => 'Failed',             'value' => $stats['failed'],           'color' => 'danger',  'icon' => 'ki-information-5'],
            ['label' => 'Revenue (Paid)',     'value' => number_format($stats['revenue']), 'color' => 'dark', 'icon' => 'ki-dollar', 'suffix' => 'UGX'],
        ];
    @endphp

    @foreach($cards as $c)
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-2">
                    <i class="ki-duotone ki-{{ str_replace('ki-', '', $c['icon']) }} fs-2x text-{{ $c['color'] }} me-2">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                </div>
                <div class="fs-2 fw-bold text-gray-900">
                    {{ $c['value'] }}
                    @if(!empty($c['suffix']))
                        <span class="fs-8 text-muted ms-1">{{ $c['suffix'] }}</span>
                    @endif
                </div>
                <div class="text-muted fw-semibold fs-8">{{ $c['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Filters + table ─────────────────────────────────────── --}}
<div class="card card-flush">
    <div class="card-header align-items-center py-4 py-md-5 flex-wrap gap-2">
        <h3 class="card-title fw-bold mb-0">All Letters</h3>
        <div class="card-toolbar">
            <button class="btn btn-sm btn-light" id="reloadBtn">
                <i class="ki-duotone ki-arrows-circle fs-4 me-1">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                Reload
            </button>
        </div>
    </div>

    <div class="card-body pt-0">

        {{-- Filters --}}
        <div class="row g-3 mb-5">
            <div class="col-12 col-md-3">
                <input type="text" id="searchInput" class="form-control form-control-solid"
                       placeholder="Search job, company, seeker, uuid...">
            </div>
            <div class="col-6 col-md-2">
                <select id="statusFilter" class="form-select form-select-solid">
                    <option value="">All Statuses</option>
                    <option value="pending_payment">Pending Payment</option>
                    <option value="paid">Paid</option>
                    <option value="processing">Processing</option>
                    <option value="generated">Ready</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select id="typeFilter" class="form-select form-select-solid">
                    <option value="">All Types</option>
                    <option value="cover">Cover Letter</option>
                    <option value="application">Application Letter</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <div class="d-flex gap-2">
                    <input type="date" id="fromFilter" class="form-control form-control-solid">
                    <input type="date" id="toFilter"   class="form-control form-control-solid">
                </div>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                <button class="btn btn-sm btn-light-primary flex-fill" id="applyBtn">Apply</button>
                <button class="btn btn-sm btn-light flex-fill" id="clearBtn">Clear</button>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="text-muted fs-8">
                <span class="badge badge-light-primary me-1" id="countTotal">0</span> total
                <span class="badge badge-light-success ms-2 me-1" id="countReady">0</span> ready
                <span class="badge badge-light-danger  ms-2 me-1" id="countFailed">0</span> failed
            </div>
            <select id="perPage" class="form-select form-select-sm w-auto">
                <option value="10">10 / page</option>
                <option value="25" selected>25 / page</option>
                <option value="50">50 / page</option>
                <option value="100">100 / page</option>
            </select>
        </div>

        {{-- Table --}}
        <div id="tableWrap" class="text-center py-10">
            <div class="spinner-border text-primary"></div>
        </div>

        {{-- Pagination --}}
        <div id="paginationWrap" class="d-none d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5">
            <div id="paginationInfo" class="text-muted fs-7"></div>
            <nav><ul class="pagination m-0" id="paginationList"></ul></nav>
        </div>

    </div>
</div>

{{-- ── Drawer (offcanvas) ─────────────────────────────────── --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="letterDrawer"
     aria-labelledby="letterDrawerLabel" style="width: min(720px, 100vw);">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="letterDrawerLabel">Letter Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="drawerBody">
        <div class="text-center py-10"><div class="spinner-border text-primary"></div></div>
    </div>
</div>

@endcan
@endsection

@push('styles')
<style>
    .letter-preview {
        font-family: Georgia, "Times New Roman", serif;
        font-size: 0.95rem;
        line-height: 1.65;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .letter-preview p { margin: 0 0 0.75em; }
    .letter-preview strong { font-weight: 600; }
    .letter-preview ul, .letter-preview ol { padding-left: 1.4em; margin-bottom: 0.75em; }
    .letter-preview li { margin-bottom: 0.3em; }
    .letter-preview img, .letter-preview table { max-width: 100%; }
    .letter-preview table { display: block; overflow-x: auto; }

    .kv { display: grid; grid-template-columns: 140px 1fr; gap: 8px 12px; font-size: 0.9rem; }
    .kv dt { color: #6b7280; font-weight: 600; }
    .kv dd { color: #111827; margin: 0; word-break: break-word; }
    @media (max-width: 575.98px) {
        .kv { grid-template-columns: 110px 1fr; font-size: 0.85rem; }
    }

    .status-dot {
        display: inline-block; width: 8px; height: 8px; border-radius: 50%;
        margin-right: 6px; vertical-align: middle;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    const CSRF = '{{ csrf_token() }}';

    const state = {
        page: 1,
        perPage: 25,
        search: '',
        status: '',
        letter_type: '',
        from: '',
        to: '',
    };

    const $ = (s) => document.querySelector(s);

    // ── Filters ────────────────────────────────────────────────
    function applyFilters() {
        state.search      = $('#searchInput').value.trim();
        state.status      = $('#statusFilter').value;
        state.letter_type = $('#typeFilter').value;
        state.from        = $('#fromFilter').value;
        state.to          = $('#toFilter').value;
        state.page        = 1;
        loadTable();
    }

    function clearFilters() {
        ['searchInput','statusFilter','typeFilter','fromFilter','toFilter'].forEach(id => {
            const el = $('#' + id);
            if (el) el.value = '';
        });
        Object.assign(state, { search: '', status: '', letter_type: '', from: '', to: '', page: 1 });
        loadTable();
    }

    $('#applyBtn')?.addEventListener('click', applyFilters);
    $('#clearBtn')?.addEventListener('click', clearFilters);
    $('#reloadBtn')?.addEventListener('click', loadTable);
    $('#perPage')?.addEventListener('change', function () {
        state.perPage = parseInt(this.value) || 25;
        state.page = 1;
        loadTable();
    });

    let debounceT;
    $('#searchInput')?.addEventListener('input', function () {
        clearTimeout(debounceT);
        debounceT = setTimeout(applyFilters, 400);
    });

    // ── Table load ─────────────────────────────────────────────
    async function loadTable() {
        const wrap = $('#tableWrap');
        const pag  = $('#paginationWrap');
        if (pag) pag.classList.add('d-none');

        wrap.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.search)      params.set('search',      state.search);
        if (state.status)      params.set('status',      state.status);
        if (state.letter_type) params.set('letter_type', state.letter_type);
        if (state.from)        params.set('from',        state.from);
        if (state.to)          params.set('to',          state.to);

        try {
            const res  = await fetch(`{{ route('admin.cover-letters.data') }}?${params}`, {
                headers: { 'Accept': 'application/json' }
            });
            const body = await res.json();

            if (!body.success || !body.data?.data?.length) {
                wrap.innerHTML = `
                    <div class="text-center py-15">
                        <i class="ki-duotone ki-information-5 fs-5tx text-muted mb-4 d-block">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                        <h4 class="fw-bold text-gray-800 mb-2">No letters match</h4>
                        <p class="text-muted">Adjust the filters or clear them.</p>
                    </div>`;
                return;
            }

            renderCounters(body.data.data);
            wrap.innerHTML = renderTable(body.data.data);
            bindRowActions();
            renderPagination(body.data);
            pag.classList.remove('d-none');
        } catch (e) {
            wrap.innerHTML = `<div class="alert alert-danger">Failed to load letters.</div>`;
        }
    }

    function renderCounters(rows) {
        const total = rows.length;
        const ready = rows.filter(r => r.status === 'generated').length;
        const failed = rows.filter(r => r.status === 'failed').length;
        $('#countTotal').textContent  = total;
        $('#countReady').textContent  = ready;
        $('#countFailed').textContent = failed;
    }

    function renderTable(rows) {
        return `
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-7 gy-3">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase gs-0">
                        <th class="text-start">Seeker</th>
                        <th class="text-start">Target Role</th>
                        <th class="text-start">Type</th>
                        <th class="text-start">Status</th>
                        <th class="text-start">Price</th>
                        <th class="text-start">Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows.map(r => `
                        <tr>
                            <td class="text-start">
                                <div class="fw-semibold text-gray-800">${escapeHtml(r.user_name)}</div>
                                <div class="text-muted fs-8">${escapeHtml(r.user_email || '')}</div>
                            </td>
                            <td class="text-start">
                                <div class="fw-semibold text-gray-800">${escapeHtml(r.job_title || '—')}</div>
                                <div class="text-muted fs-8">${escapeHtml(r.company_name || '')}</div>
                            </td>
                            <td class="text-start">
                                <span class="badge badge-light-info">${escapeHtml(r.letter_type_label)}</span>
                            </td>
                            <td class="text-start">${r.status_badge}</td>
                            <td class="text-start fw-semibold">${escapeHtml(r.price_label)}</td>
                            <td class="text-start text-muted fs-8">${formatDate(r.created_at)}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-light-primary viewBtn" data-uuid="${escapeAttr(r.uuid)}">
                                    <i class="ki-duotone ki-eye fs-4 me-1">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                    View
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>`;
    }

    function bindRowActions() {
        document.querySelectorAll('.viewBtn').forEach(btn => {
            btn.addEventListener('click', () => openDrawer(btn.dataset.uuid));
        });
    }

    function renderPagination(meta) {
        const el = $('#paginationList');
        const info = $('#paginationInfo');
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
                    state.page = page;
                    loadTable();
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

    // ── Drawer ─────────────────────────────────────────────────
    let drawerInstance = null;
    function getDrawer() {
        if (!drawerInstance) {
            drawerInstance = new bootstrap.Offcanvas('#letterDrawer');
        }
        return drawerInstance;
    }

    async function openDrawer(uuid) {
        const body = $('#drawerBody');
        body.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';
        getDrawer().show();

        try {
            const res = await fetch(`{{ url('admin/cover-letters') }}/${uuid}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (!data.success) {
                body.innerHTML = `<div class="alert alert-danger">${escapeHtml(data.message || 'Failed to load.')}</div>`;
                return;
            }

            body.innerHTML = renderDrawer(data.data);
            bindDrawerActions(data.data);
        } catch (e) {
            body.innerHTML = `<div class="alert alert-danger">Network error loading details.</div>`;
        }
    }

    function renderDrawer(d) {
        const statusColor = {
            pending_payment: 'warning',
            paid:            'info',
            processing:      'primary',
            generated:       'success',
            failed:          'danger',
        }[d.status] || 'secondary';

        const content = d.content
            ? `<div class="letter-preview border rounded p-4 p-md-6 bg-white mt-3">${d.content}</div>`
            : `<div class="alert alert-light-${statusColor} mt-3 mb-0 fs-7">
                   <strong>No content yet.</strong>
                   ${d.status === 'failed'
                        ? escapeHtml(d.error_message || 'Generation failed.')
                        : 'Letter has not been generated yet.'}
               </div>`;

        return `
            <div class="d-flex flex-column gap-3">

                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-bold fs-4 mb-1">${escapeHtml(d.job_title || '—')}</div>
                        <div class="text-muted fs-7">${escapeHtml(d.company_name || '')}</div>
                    </div>
                    <div class="text-end">
                        ${d.status_badge}
                        ${d.is_user_edited
                            ? '<div class="badge badge-light-primary ms-1">Edited</div>' : ''}
                    </div>
                </div>

                <div class="separator my-2"></div>

                <dl class="kv mb-0">
                    <dt>Seeker</dt><dd>${escapeHtml(d.user_name)}${d.user_email ? ` &lt;${escapeHtml(d.user_email)}&gt;` : ''}</dd>
                    <dt>Type</dt><dd>${escapeHtml(d.letter_type_label)}</dd>
                    <dt>Price</dt><dd>${escapeHtml(d.price_label)}</dd>
                    <dt>Payment ref</dt><dd>${escapeHtml(d.payment_reference || '—')}</dd>
                    <dt>Paid at</dt><dd>${formatDate(d.paid_at)}</dd>
                    <dt>Generated at</dt><dd>${formatDate(d.generated_at)}</dd>
                    <dt>Regenerations</dt><dd>${d.regeneration_count} / ${d.max_regenerations}</dd>
                    <dt>CV source</dt><dd>${escapeHtml(d.cv_source || '—')}</dd>
                    <dt>Created</dt><dd>${formatDate(d.created_at)}</dd>
                    <dt class="text-muted">UUID</dt><dd class="text-muted fs-8">${escapeHtml(d.uuid)}</dd>
                </dl>

                ${d.error_message ? `
                    <div class="alert alert-light-danger mb-0 fs-7">
                        <strong>Error:</strong> ${escapeHtml(d.error_message)}
                    </div>` : ''}

                ${d.status === 'generated' ? `
                    <div class="d-flex gap-2 mt-2">
                        <a href="{{ url('admin/cover-letters') }}/${d.uuid}/download"
                           class="btn btn-sm btn-primary">
                            <i class="ki-duotone ki-file-down fs-4 me-1">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            Download PDF
                        </a>
                    </div>
                    ${content}
                ` : content}

            </div>`;
    }

    function bindDrawerActions(d) {
        // No extra actions beyond download link for now.
    }

    // ── Helpers ────────────────────────────────────────────────
    function escapeHtml(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }
    function escapeAttr(t) {
        return escapeHtml(t).replace(/"/g, '&quot;');
    }
    function formatDate(iso) {
        if (!iso) return '—';
        const d = new Date(iso);
        return d.toLocaleString(undefined, {
            year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    }

    // ── Boot ───────────────────────────────────────────────────
    loadTable();

})();
</script>
@endpush