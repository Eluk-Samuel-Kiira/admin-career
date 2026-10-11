@extends('layouts.admin')

@section('title', 'Auto Apply Agent')
@section('page_title', 'Auto Apply Agent')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Job Seekers</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Auto Apply Agent</li>
@endsection

@section('content')
@can('manage job alerts')

{{-- ── Stats strip ── --}}
<div class="row g-4 mb-6" id="statsRow">
    <div class="col-6 col-lg-3">
        <div class="card bg-light-primary h-100">
            <div class="card-body py-5">
                <div class="fs-2 fw-bold text-primary" id="statRunsToday">—</div>
                <div class="text-muted fw-semibold fs-7">Runs Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card bg-light-success h-100">
            <div class="card-body py-5">
                <div class="fs-2 fw-bold text-success" id="statAppsToday">—</div>
                <div class="text-muted fw-semibold fs-7">Applications Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card bg-light-warning h-100">
            <div class="card-body py-5">
                <div class="fs-2 fw-bold text-warning" id="statRelaysToday">—</div>
                <div class="text-muted fw-semibold fs-7">Relays Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card bg-light-danger h-100">
            <div class="card-body py-5">
                <div class="fs-2 fw-bold text-danger" id="statFailedToday">—</div>
                <div class="text-muted fw-semibold fs-7">Failed Today</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Tabs ── --}}
<div class="card card-flush">
    <div class="card-header align-items-center py-5">
        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold mb-n2">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tab_runs">Runs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab_relays">Relays</a>
            </li>
        </ul>
        <div class="card-toolbar">
            <button class="btn btn-sm btn-light" id="reloadBtn">
                <i class="ki-duotone ki-arrows-circle fs-3 me-1">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                Reload
            </button>
        </div>
    </div>

    <div class="card-body pt-0">
        <div class="tab-content">

            {{-- Runs tab --}}
            <div class="tab-pane fade show active" id="tab_runs">
                <div class="row g-3 mb-5">
                    <div class="col-md-4">
                        <input type="text" id="runsSearch" class="form-control form-control-solid"
                               placeholder="Search seeker name or email...">
                    </div>
                    <div class="col-md-3">
                        <input type="date" id="runsDate" class="form-control form-control-solid">
                    </div>
                    <div class="col-md-3">
                        <select id="runsStatus" class="form-select form-select-solid">
                            <option value="">Any Status</option>
                            <option value="queued">Queued</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="quota_exhausted">Quota Exhausted</option>
                            <option value="no_cv">No CV</option>
                            <option value="no_preferences">No Preferences</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-light-primary w-100" id="applyRunFilters">Apply</button>
                    </div>
                </div>

                <div id="runsWrap" class="text-center py-10">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>

            {{-- Relays tab --}}
            <div class="tab-pane fade" id="tab_relays">
                <div class="text-muted fs-7 mb-5">
                    Jobs that could not be auto-applied and were sent to the seeker to submit themselves.
                </div>
                <div id="relaysWrap" class="text-center py-10">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>

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
    // Stats
    // ─────────────────────────────────────────────────────────
    async function loadStats() {
        try {
            const res = await fetch('{{ route("admin.auto-apply.stats") }}', {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (!data.success) return;

            const s = data.stats;
            document.getElementById('statRunsToday').textContent   = s.runs_today ?? 0;
            document.getElementById('statAppsToday').textContent   = s.applications_today ?? 0;
            document.getElementById('statRelaysToday').textContent = s.relays_today ?? 0;
            document.getElementById('statFailedToday').textContent = s.failed_today ?? 0;
        } catch (e) { /* keep going */ }
    }

    // ─────────────────────────────────────────────────────────
    // Runs table
    // ─────────────────────────────────────────────────────────
    async function loadRuns() {
        const wrap = document.getElementById('runsWrap');
        wrap.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        const params = new URLSearchParams();
        const search = document.getElementById('runsSearch').value.trim();
        const date   = document.getElementById('runsDate').value;
        const status = document.getElementById('runsStatus').value;
        if (search) params.set('search', search);
        if (date)   params.set('date', date);
        if (status) params.set('status', status);

        try {
            const res = await fetch(`{{ route("admin.auto-apply.runs") }}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (!data.success || !data.data?.data?.length) {
                wrap.innerHTML = emptyState('No auto-apply runs match.');
                return;
            }

            wrap.innerHTML = `
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed table-row-gray-300 fs-7 gy-3">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="text-start min-w-140px">Date</th>
                                <th class="text-start min-w-200px">Seeker</th>
                                <th class="text-start min-w-70px">Scanned</th>
                                <th class="text-start min-w-70px">Applied</th>
                                <th class="text-start min-w-70px">Relayed</th>
                                <th class="text-start min-w-70px">Skipped</th>
                                <th class="text-start min-w-70px">Failed</th>
                                <th class="text-start min-w-100px">Quota</th>
                                <th class="text-start min-w-130px">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.data.data.map(runRow).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } catch (e) {
            wrap.innerHTML = `<div class="alert alert-danger">Failed to load runs.</div>`;
        }
    }

    function runRow(r) {
        return `
            <tr>
                <td class="text-start text-muted">${escapeHtml(r.run_date || '')}</td>
                <td class="text-start">
                    <div class="fw-bold text-gray-800">${escapeHtml(r.user || 'Unknown')}</div>
                    <div class="text-muted fs-8">${escapeHtml(r.email || '')}</div>
                </td>
                <td class="text-start"><span class="badge badge-light-secondary">${r.jobs_scanned ?? 0}</span></td>
                <td class="text-start"><span class="badge badge-light-success">${r.applied_count ?? 0}</span></td>
                <td class="text-start"><span class="badge badge-light-warning">${r.relayed_count ?? 0}</span></td>
                <td class="text-start"><span class="badge badge-light-info">${r.skipped_count ?? 0}</span></td>
                <td class="text-start">${r.failed_count > 0
                    ? `<span class="badge badge-light-danger">${r.failed_count}</span>`
                    : `<span class="text-muted">0</span>`}</td>
                <td class="text-start text-muted fs-8">
                    ${r.quota_remaining != null
                        ? `${r.quota_remaining} / ${r.quota_total || '∞'} left`
                        : '∞'}
                </td>
                <td class="text-start">${r.status_badge || ''}</td>
            </tr>
        `;
    }

    // ─────────────────────────────────────────────────────────
    // Relays table
    // ─────────────────────────────────────────────────────────
    async function loadRelays() {
        const wrap = document.getElementById('relaysWrap');
        wrap.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        try {
            const res = await fetch('{{ route("admin.auto-apply.relays") }}', {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (!data.success || !data.data?.data?.length) {
                wrap.innerHTML = emptyState('No relays yet.');
                return;
            }

            wrap.innerHTML = `
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed table-row-gray-300 fs-7 gy-3">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="text-start min-w-200px">Seeker</th>
                                <th class="text-start min-w-220px">Job</th>
                                <th class="text-start min-w-120px">Reason</th>
                                <th class="text-start min-w-150px">Sent Via</th>
                                <th class="text-start min-w-100px">Status</th>
                                <th class="text-start min-w-140px">Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.data.data.map(relayRow).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } catch (e) {
            wrap.innerHTML = `<div class="alert alert-danger">Failed to load relays.</div>`;
        }
    }

    function relayRow(r) {
        const channels = (r.channels_sent || []).map(c =>
            `<span class="badge badge-light-primary me-1">${capitalize(c)}</span>`
        ).join('') || '<span class="text-muted">—</span>';

        const statusClass = { sent: 'success', partial: 'warning', failed: 'danger', queued: 'info' }[r.status] || 'secondary';

        return `
            <tr>
                <td class="text-start">
                    <div class="fw-bold text-gray-800">${escapeHtml(r.user || 'Unknown')}</div>
                    <div class="text-muted fs-8">${escapeHtml(r.email || '')}</div>
                </td>
                <td class="text-start">
                    <div class="fw-semibold text-gray-800">${escapeHtml(r.job_title || '—')}</div>
                    ${r.job_slug ? `<a href="/job/${escapeHtml(r.job_slug)}" target="_blank" class="text-muted fs-8">Open job</a>` : ''}
                </td>
                <td class="text-start text-muted fs-8">${escapeHtml(r.reason || '')}</td>
                <td class="text-start">${channels}</td>
                <td class="text-start"><span class="badge badge-light-${statusClass}">${capitalize(r.status || '')}</span></td>
                <td class="text-start text-muted fs-8">
                    ${r.sent_at ? new Date(r.sent_at).toLocaleString() : '—'}
                </td>
            </tr>
        `;
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────
    function emptyState(msg) {
        return `
            <div class="text-center py-15">
                <i class="ki-duotone ki-information-5 fs-5tx text-muted mb-4 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <h4 class="fw-bold text-gray-800 mb-2">Nothing to show</h4>
                <p class="text-muted">${escapeHtml(msg)}</p>
            </div>`;
    }

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

    // ─────────────────────────────────────────────────────────
    // Boot
    // ─────────────────────────────────────────────────────────
    document.getElementById('reloadBtn')?.addEventListener('click', () => { loadStats(); loadRuns(); loadRelays(); });
    document.getElementById('applyRunFilters')?.addEventListener('click', loadRuns);

    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(a => {
        a.addEventListener('shown.bs.tab', function (e) {
            if (e.target.getAttribute('href') === '#tab_relays') {
                loadRelays();
            }
        });
    });

    loadStats();
    loadRuns();
})();
</script>
@endpush