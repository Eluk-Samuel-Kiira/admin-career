@extends('layouts.admin')

@section('title', 'Shortlist Run')
@section('page_title', 'Shortlist Run')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item">
        <a href="{{ route('admin.hiring-runs.index') }}" class="text-muted text-hover-primary">CV Shortlisting Runs</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Run Detail</li>
@endsection

@section('content')
@can('view employer')
<div class="container py-6">

    <div class="card card-flush mb-6">
        <div class="card-header py-5">
            <div>
                <h3 class="fw-bold mb-1" id="runTitle">Loading...</h3>
                <div class="text-muted fs-7" id="runMeta"></div>
            </div>
            <div class="card-toolbar gap-2">
                <a href="{{ route('admin.hiring-runs.export', $uuid) }}" class="btn btn-sm btn-light">
                    <i class="ki-duotone ki-file-down fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                    Export CSV
                </a>
                <a href="{{ route('admin.hiring-runs.index') }}" class="btn btn-sm btn-light">Back</a>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="alert alert-light-primary mb-5" id="jdAlert">
                <div class="fw-bold">Job Description Used</div>
                <div class="text-muted fs-7 mt-1" id="jdPreview">Loading...</div>
            </div>

            <div id="loadingSpinner" class="text-center py-10">
                <div class="spinner-border text-primary"></div>
            </div>

            <div id="tableContainer" class="d-none">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-4">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                                <th class="w-50px">#</th>
                                <th class="min-w-200px">Candidate</th>
                                <th class="min-w-150px">Title</th>
                                <th class="min-w-80px">Exp</th>
                                <th class="min-w-120px">Score</th>
                                <th class="min-w-130px">Recommendation</th>
                                <th class="text-end min-w-120px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="candidatesBody"></tbody>
                    </table>
                </div>
            </div>

            <div id="noData" class="text-center py-15 d-none">
                <p class="text-muted">No candidates in this run.</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="fw-bold mb-0" id="detailName">Candidate</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailBody"></div>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
(function () {
    const UUID = '{{ $uuid }}';
    let candidates = [];

    document.addEventListener('DOMContentLoaded', load);

    function load() {
        fetch(`{{ url('admin/hiring-runs') }}/${UUID}/detail`, {
            headers: { 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(res => {
            document.getElementById('loadingSpinner').classList.add('d-none');

            if (!res.success) {
                document.getElementById('noData').classList.remove('d-none');
                return;
            }

            const r = res.run;
            document.getElementById('runTitle').textContent = r.title;
            document.getElementById('runMeta').innerHTML = `
                Employer: <strong>${esc(r.employer?.name ?? 'Unknown')}</strong> ·
                ${r.total} CVs · ${r.processed} screened · ${r.failed} failed · ${r.status_badge}
            `;
            document.getElementById('jdPreview').textContent =
                r.job_description_preview || 'No description available.';

            candidates = res.data?.data ?? [];
            if (candidates.length === 0) {
                document.getElementById('noData').classList.remove('d-none');
                return;
            }

            renderTable();
            document.getElementById('tableContainer').classList.remove('d-none');
        })
        .catch(() => {
            document.getElementById('loadingSpinner').classList.add('d-none');
            document.getElementById('noData').classList.remove('d-none');
        });
    }

    function renderTable() {
        document.getElementById('candidatesBody').innerHTML = candidates.map((c, i) => `
            <tr>
                <td class="text-muted">${i + 1}</td>
                <td>
                    <div class="fw-bold">${esc(c.name)}</div>
                    <div class="text-muted fs-7">${esc(c.email || '')}</div>
                </td>
                <td>${esc(c.current_title || '-')}</td>
                <td>${c.years_of_experience ?? '-'}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress h-6px flex-grow-1" style="min-width:50px;">
                            <div class="progress-bar bg-${c.score_color}" style="width:${c.score}%"></div>
                        </div>
                        <span class="fw-bold text-${c.score_color}">${c.score}</span>
                    </div>
                </td>
                <td>${c.recommendation_badge}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-icon btn-light" onclick="openDetail(${c.id})">
                        <i class="ki-duotone ki-eye fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    window.openDetail = function (id) {
        const c = candidates.find(x => x.id === id);
        if (!c) return;

        document.getElementById('detailName').textContent = c.name;

        const list = (arr) => arr && arr.length
            ? `<ul class="ps-4 mb-0">${arr.map(x => `<li>${esc(x)}</li>`).join('')}</ul>`
            : '<span class="text-muted">None</span>';

        document.getElementById('detailBody').innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <div class="fs-1 fw-bold text-${c.score_color}">
                        ${c.score}<span class="fs-5">/100</span>
                    </div>
                    <div>${c.recommendation_badge}</div>
                </div>
                ${c.file_url ? `<a href="${c.file_url}" target="_blank" class="btn btn-sm btn-light">Open CV</a>` : ''}
            </div>

            ${c.summary ? `<div class="alert alert-light-${c.score_color} mb-4">${esc(c.summary)}</div>` : ''}

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="fw-bold text-success mb-2">Strengths</div>
                    ${list(c.strengths)}
                </div>
                <div class="col-md-6">
                    <div class="fw-bold text-warning mb-2">Gaps</div>
                    ${list(c.gaps)}
                </div>
                <div class="col-md-6">
                    <div class="fw-bold text-primary mb-2">Matched Skills</div>
                    ${list(c.matched_skills)}
                </div>
                <div class="col-md-6">
                    <div class="fw-bold text-muted mb-2">Missing Skills</div>
                    ${list(c.missing_skills)}
                </div>
                ${c.red_flags && c.red_flags.length ? `
                    <div class="col-12">
                        <div class="fw-bold text-danger mb-2">Red Flags</div>
                        ${list(c.red_flags)}
                    </div>
                ` : ''}
            </div>
        `;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('detailModal')).show();
    };

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }
})();
</script>
@endpush