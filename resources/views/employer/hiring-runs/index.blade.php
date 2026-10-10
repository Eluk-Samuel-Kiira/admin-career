@extends('layouts.admin')

@section('title', 'CV Shortlisting Runs')
@section('page_title', 'CV Shortlisting Runs')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Employers</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">CV Shortlisting Runs</li>
@endsection

@section('content')
@can('view employer')
<div class="container py-6">

    {{-- Stats cards --}}
    <div class="row g-4 mb-6" id="statsRow">
        <div class="col-6 col-lg-3">
            <div class="card bg-light-primary">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Total Runs</div>
                    <div class="fs-1 fw-bold" id="statTotalRuns">—</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card bg-light-info">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">CVs Screened</div>
                    <div class="fs-1 fw-bold" id="statTotalCvs">—</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card bg-light-success">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Runs This Month</div>
                    <div class="fs-1 fw-bold" id="statRunsMonth">—</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card bg-light-warning">
                <div class="card-body">
                    <div class="text-muted fs-7 mb-1">Active Employers</div>
                    <div class="fs-1 fw-bold" id="statActiveEmp">—</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-6" id="topEmpCard" style="display:none;">
        <div class="card-header py-4">
            <h3 class="card-title fw-bold text-gray-800">Top Employers by Runs</h3>
        </div>
        <div class="card-body pt-0">
            <div class="row g-3" id="topEmpList"></div>
        </div>
    </div>

    {{-- Runs table --}}
    <div class="card card-flush">
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput"
                           class="form-control form-control-solid w-250px ps-12"
                           placeholder="Search employer, title, UUID..." />
                </div>
            </div>
            <div class="card-toolbar gap-2">
                <select id="statusFilter" class="form-select form-select-solid w-175px">
                    <option value="">All Statuses</option>
                    <option value="queued">Queued</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
                <select id="sortFilter" class="form-select form-select-solid w-150px">
                    <option value="recent">Newest First</option>
                    <option value="oldest">Oldest First</option>
                </select>
            </div>
        </div>

        <div class="card-body pt-0">
            <div id="loadingSpinner" class="text-center py-10 d-none">
                <div class="spinner-border text-primary"></div>
            </div>

            <div id="tableContainer" class="d-none">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-4">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-220px">Run</th>
                                <th class="min-w-180px">Employer</th>
                                <th class="min-w-90px">Total</th>
                                <th class="min-w-90px">Screened</th>
                                <th class="min-w-90px">Failed</th>
                                <th class="min-w-130px">Status</th>
                                <th class="min-w-120px">Created</th>
                                <th class="text-end min-w-140px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="runsBody"></tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-5">
                    <div id="paginationInfo" class="text-muted fs-7"></div>
                    <nav><ul class="pagination m-0" id="pagination"></ul></nav>
                </div>
            </div>

            <div id="noData" class="text-center py-15 d-none">
                <i class="ki-duotone ki-information-5 fs-5tx text-muted mb-4 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <h4 class="fw-bold text-gray-800 mb-2">No runs match</h4>
                <p class="text-muted">Try clearing filters or a different search.</p>
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

    const state = { page: 1, search: '', status: '', sort: 'recent' };
    let searchDebounce;

    document.addEventListener('DOMContentLoaded', () => {
        loadStats();
        bindEvents();
        load();
    });

    function bindEvents() {
        document.getElementById('searchInput')?.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                state.search = this.value.trim();
                state.page = 1;
                load();
            }, 400);
        });
        document.getElementById('statusFilter')?.addEventListener('change', function () {
            state.status = this.value; state.page = 1; load();
        });
        document.getElementById('sortFilter')?.addEventListener('change', function () {
            state.sort = this.value; state.page = 1; load();
        });
    }

    function loadStats() {
        fetch(`{{ route('admin.hiring-runs.stats') }}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                const s = res.stats;
                document.getElementById('statTotalRuns').textContent = s.total_runs ?? 0;
                document.getElementById('statTotalCvs').textContent = s.total_cvs ?? 0;
                document.getElementById('statRunsMonth').textContent = s.runs_this_month ?? 0;
                document.getElementById('statActiveEmp').textContent = s.active_employers ?? 0;

                const top = s.top_employers ?? [];
                if (top.length) {
                    document.getElementById('topEmpCard').style.display = '';
                    document.getElementById('topEmpList').innerHTML = top.map(e => `
                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                                <div class="fw-semibold">${esc(e.user)}</div>
                                <div class="text-muted fs-7">${e.runs} runs · ${e.cvs} CVs</div>
                            </div>
                        </div>
                    `).join('');
                }
            })
            .catch(() => {});
    }

    function load() {
        show('loading');

        const params = new URLSearchParams({ page: state.page, sort: state.sort });
        if (state.search) params.set('search', state.search);
        if (state.status) params.set('status', state.status);

        fetch(`{{ route('admin.hiring-runs.data') }}?${params}`, {
            headers: { 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data?.data?.length) {
                show('empty');
                return;
            }
            show('table');
            render(res.data.data);
            renderPagination(res.data);
        })
        .catch(() => show('empty'));
    }

    function show(which) {
        document.getElementById('loadingSpinner').classList.toggle('d-none', which !== 'loading');
        document.getElementById('tableContainer').classList.toggle('d-none', which !== 'table');
        document.getElementById('noData').classList.toggle('d-none', which !== 'empty');
    }

    function render(rows) {
        document.getElementById('runsBody').innerHTML = rows.map(r => `
            <tr>
                <td>
                    <div class="fw-bold text-gray-800">${esc(r.title)}</div>
                    <div class="text-muted fs-7">${esc((r.uuid || '').substring(0, 8))}…</div>
                </td>
                <td>
                    <div class="fw-semibold">${esc(r.employer?.name ?? 'Unknown')}</div>
                    <div class="text-muted fs-7">${esc(r.employer?.email ?? '')}</div>
                </td>
                <td><span class="badge badge-light-secondary">${r.total}</span></td>
                <td><span class="badge badge-light-success">${r.processed}</span></td>
                <td>${r.failed > 0 ? `<span class="badge badge-light-danger">${r.failed}</span>` : '<span class="text-muted">0</span>'}</td>
                <td>${r.status_badge}</td>
                <td class="text-muted fs-7">${new Date(r.created_at).toLocaleDateString()}</td>
                <td class="text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ url('admin/hiring-runs') }}/${r.uuid}"
                           class="btn btn-sm btn-icon btn-light" title="View shortlist">
                            <i class="ki-duotone ki-eye fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        </a>
                        <a href="{{ url('admin/hiring-runs') }}/${r.uuid}/export"
                           class="btn btn-sm btn-icon btn-light" title="Export CSV">
                            <i class="ki-duotone ki-file-down fs-4"><span class="path1"></span><span class="path2"></span></i>
                        </a>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(payload) {
        const el = document.getElementById('pagination');
        const info = document.getElementById('paginationInfo');
        const meta = payload.meta ?? {};
        el.innerHTML = '';
        if (info) info.textContent = `${meta.total ?? 0} runs`;

        for (let i = 1; i <= (meta.last_page ?? 1); i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === meta.current_page ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            li.querySelector('a').addEventListener('click', e => {
                e.preventDefault();
                state.page = i;
                load();
            });
            el.appendChild(li);
        }
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }
})();
</script>
@endpush