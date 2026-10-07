@extends('layouts.admin')

@section('title', 'Job Sharing')
@section('page_title', 'Job Sharing')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Jobs Index</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Job Sharing</li>
@endsection

@section('content')
@can('view jobs')

<div class="card card-flush mb-5">
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <select id="rangeFilter" class="form-select form-select-solid w-175px">
                    <option value="today" selected>Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="last3">Last 3 Days</option>
                    <option value="week">Last 7 Days</option>
                    <option value="all">All Jobs</option>
                </select>

                <select id="countryFilter" class="form-select form-select-solid w-150px">
                    <option value="">All Countries</option>
                    @foreach(\App\Helpers\CountryHelper::getCountriesWithFlags() as $c)
                        <option value="{{ $c['code'] }}">{{ $c['flag'] }} {{ $c['name'] }}</option>
                    @endforeach
                </select>

                <button type="button" id="reloadBtn" class="btn btn-sm btn-light">
                    <i class="ki-duotone ki-arrows-circle fs-3 me-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    Reload
                </button>
            </div>
        </div>
        <div class="card-toolbar">
            <span class="badge badge-light-primary fs-7 py-3 px-5" id="totalBadge">0 jobs</span>
            <span class="badge badge-light-info fs-7 py-3 px-5 ms-2" id="batchBadge">0 batches</span>
        </div>
    </div>

    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3 text-muted">Loading jobs...</p>
        </div>

        <div id="batchesContainer" class="d-none d-flex flex-column gap-5"></div>

        <div id="noDataMessage" class="text-center py-15 d-none">
            <i class="ki-duotone ki-search-list fs-5tx text-muted mb-4 d-block">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <h4 class="fw-bold text-gray-800 mb-2">No jobs to share</h4>
            <p class="text-muted">Try a different date range or country filter.</p>
        </div>
    </div>
</div>

@endcan
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const state = {
        range: 'today',
        country: '',
    };

    document.addEventListener('DOMContentLoaded', () => {
        bindEvents();
        load();
    });

    function bindEvents() {
        document.getElementById('rangeFilter')?.addEventListener('change', function () {
            state.range = this.value;
            load();
        });
        document.getElementById('countryFilter')?.addEventListener('change', function () {
            state.country = this.value;
            load();
        });
        document.getElementById('reloadBtn')?.addEventListener('click', load);
    }

    function load() {
        showState('loading');

        const params = new URLSearchParams({
            range: state.range,
        });
        if (state.country) params.set('country', state.country);

        fetch(`{{ route('admin.job-sharing.data') }}?${params.toString()}`, {
            headers: { 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data || res.data.length === 0) {
                showState('empty');
                updateCounts(0, 0);
                return;
            }
            showState('list');
            renderBatches(res.data);
            updateCounts(res.total_jobs, res.total_batches);
        })
        .catch(() => {
            showState('empty');
            updateCounts(0, 0);
            window.showToast?.('error', 'Failed to load jobs.');
        });
    }

    function showState(which) {
        document.getElementById('loadingSpinner').classList.toggle('d-none', which !== 'loading');
        document.getElementById('batchesContainer').classList.toggle('d-none', which !== 'list');
        document.getElementById('noDataMessage').classList.toggle('d-none', which !== 'empty');
    }

    function updateCounts(jobs, batches) {
        document.getElementById('totalBadge').textContent = `${jobs} job${jobs === 1 ? '' : 's'}`;
        document.getElementById('batchBadge').textContent = `${batches} batch${batches === 1 ? '' : 'es'}`;
    }

    function renderBatches(batches) {
        const container = document.getElementById('batchesContainer');
        container.innerHTML = batches.map(b => renderBatch(b)).join('');
        container.querySelectorAll('[data-copy-batch]').forEach(btn => {
            btn.addEventListener('click', () => copyBatch(btn));
        });
    }

    function renderBatch(batch) {
        const rows = batch.jobs.map(j => `
            <tr>
                <td class="fw-semibold">${escapeHtml(j.title)}</td>
                <td>${escapeHtml(j.company)}</td>
                <td class="text-muted fs-7">${escapeHtml(j.deadline || 'Open')}</td>
                <td>
                    <a href="${escapeHtml(j.url)}" target="_blank" rel="noopener"
                       class="text-primary text-decoration-none fs-7">
                        ${escapeHtml(j.url.replace(/^https?:\/\//, ''))}
                    </a>
                </td>
            </tr>
        `).join('');

        return `
            <div class="card border border-gray-300 bg-white">
                <div class="card-header min-h-50px">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-light-primary">PART ${batch.part}</span>
                        <span class="fw-bold">${batch.count} job${batch.count === 1 ? '' : 's'}</span>
                    </div>
                    <div class="card-toolbar">
                        <button type="button"
                                class="btn btn-sm btn-primary"
                                data-copy-batch="${batch.part}">
                            <i class="ki-duotone ki-copy fs-4 me-1">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            Copy Batch
                        </button>
                    </div>
                </div>

                <div class="card-body pt-2">
                    <div class="table-responsive mb-4">
                        <table class="table table-sm align-middle table-row-dashed fs-7">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase">
                                    <th>Title</th>
                                    <th>Company</th>
                                    <th>Deadline</th>
                                    <th>URL</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>

                    <details>
                        <summary class="fw-semibold fs-7 text-muted cursor-pointer">Preview share text</summary>
                        <pre class="mt-3 p-4 bg-light rounded border border-gray-300 fs-8"
                             style="white-space:pre-wrap; word-break:break-word;">${escapeHtml(batch.share_text)}</pre>
                    </details>
                </div>
            </div>
        `;
    }

    function copyBatch(btn) {
        const part = btn.dataset.copyBatch;

        const params = new URLSearchParams({ range: state.range });
        if (state.country) params.set('country', state.country);

        fetch(`/admin/job-sharing/batch/${part}?${params.toString()}`, {
            headers: { 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.share_text) {
                window.showToast?.('error', 'Could not prepare batch.');
                return;
            }

            navigator.clipboard.writeText(res.share_text)
                .then(() => {
                    const original = btn.innerHTML;
                    btn.innerHTML = '<i class="ki-duotone ki-check fs-4 me-1"><span class="path1"></span><span class="path2"></span></i> Copied';
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-success');
                    setTimeout(() => {
                        btn.innerHTML = original;
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-primary');
                    }, 1500);
                })
                .catch(() => {
                    // Fallback for older browsers
                    const ta = document.createElement('textarea');
                    ta.value = res.share_text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    window.showToast?.('success', 'Batch copied to clipboard.');
                });
        })
        .catch(() => window.showToast?.('error', 'Network error.'));
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
})();
</script>
@endpush