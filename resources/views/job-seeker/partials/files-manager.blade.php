@php
    $cvFiles     = $cvFiles     ?? [];
    $hasAcademic = $hasAcademic ?? false;
    $seekerId    = $seeker->id;
@endphp

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- CV MANAGER                                                 --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div id="cvManager" data-seeker-id="{{ $seekerId }}">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="fw-bold">CV Files</div>
            <div class="text-muted fs-7">
                Upload a CV — the AI extracts the profile fields from it. Max {{ 2 }}.
            </div>
        </div>
        <span class="badge badge-light-primary" id="cvCountBadge">{{ count($cvFiles) }}/2</span>
    </div>

    <div id="cvFileList" class="d-flex flex-column gap-2 mb-3">
        @forelse($cvFiles as $cv)
            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded"
                 data-cv-path="{{ $cv['path'] }}">
                <div class="d-flex align-items-center gap-3">
                    <i class="ki-duotone ki-file fs-3 text-primary"><span class="path1"></span><span class="path2"></span></i>
                    <div>
                        <div class="fw-bold fs-7">{{ $cv['original_name'] ?? 'CV' }}</div>
                        <div class="text-muted fs-8">
                            {{ isset($cv['size']) ? round($cv['size'] / 1024, 1) . ' KB' : '' }}
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-light-danger delete-cv-btn"
                        data-path="{{ $cv['path'] }}" title="Delete CV">
                    <i class="ki-duotone ki-trash fs-4">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                    </i>
                </button>
            </div>
        @empty
            <div class="text-muted fs-7 py-4 text-center" id="cvEmptyState">
                No CV on file yet. Upload one to let the AI fill the profile.
            </div>
        @endforelse
    </div>

    <input type="file" id="cvUploadInput" accept=".pdf,.doc,.docx" class="d-none">

    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-primary" id="cvUploadBtn"
                {{ count($cvFiles) >= 2 ? 'disabled' : '' }}>
            <i class="ki-duotone ki-plus fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
            Upload CV &amp; Extract with AI
        </button>
        @if(count($cvFiles) > 0)
            <button type="button" class="btn btn-sm btn-light-info" id="reExtractCvBtn">
                <i class="ki-duotone ki-arrows-circle fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                Re-extract from CV
            </button>
        @endif
    </div>

    <div id="cvAiStatus" class="mt-3"></div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- ACADEMIC MANAGER                                           --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div id="academicManager" class="mt-8 pt-6 border-top" data-seeker-id="{{ $seekerId }}">
    <div class="fw-bold mb-2">Academic Documents</div>
    <div class="text-muted fs-7 mb-3">One PDF (max 10 MB). Uploading replaces the existing file.</div>

    <div id="academicFileList">
        @if($hasAcademic)
            <div class="d-flex align-items-center justify-content-between p-3 bg-light-success rounded mb-3"
                 data-academic-path="{{ $seeker->academic_documents_path }}">
                <div class="d-flex align-items-center gap-3">
                    <i class="ki-duotone ki-document fs-3 text-success"><span class="path1"></span><span class="path2"></span></i>
                    <div>
                        <div class="fw-bold fs-7">{{ $seeker->academic_documents_original_name }}</div>
                        <div class="text-muted fs-8">
                            {{ $seeker->academic_documents_size ? round($seeker->academic_documents_size / 1024 / 1024, 2) . ' MB' : '' }}
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-light-danger delete-academic-btn" title="Delete document">
                    <i class="ki-duotone ki-trash fs-4">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                    </i>
                </button>
            </div>
        @else
            <div class="text-muted fs-7 py-4 text-center" id="academicEmptyState">
                No academic documents on file.
            </div>
        @endif
    </div>

    <input type="file" id="academicUploadInput" accept=".pdf" class="d-none">

    <button type="button" class="btn btn-sm btn-primary" id="academicUploadBtn">
        <i class="ki-duotone ki-plus fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
        {{ $hasAcademic ? 'Replace academic PDF' : 'Upload academic PDF' }}
    </button>

    <div id="academicAiStatus" class="mt-3"></div>
</div>