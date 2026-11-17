{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- CREATE CV REVIEW WIZARD — partial (no <script>, no <style>)     --}}
{{--    All JS/CSS lives in index.blade.php @push('scripts'/'styles') --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="createReviewModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="fw-bold mb-0">Create CV Review</h3>
                    <div class="text-muted fs-8">Pick a seeker, their CV, and a target job. AI review runs immediately.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="createReviewForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body" style="max-height:70vh;overflow-y:auto;">

                    {{-- ── STEP 1: SEEKER ─────────────────────── --}}
                    <div class="mb-6">
                        <label class="fw-bold fs-6 mb-2 d-block">1. Seeker</label>

                        <div class="position-relative">
                            <input type="text"
                                   id="userSearch"
                                   class="form-control form-control-solid"
                                   placeholder="Type a name, email, or phone…"
                                   autocomplete="off"
                                   style="width:100%" />
                            <input type="hidden" name="user_id" id="selectedUserId">
                            <div class="picker-results" id="userResults" hidden></div>
                        </div>

                        <div id="userSubBadge" class="mt-2 fs-7"></div>

                        <div class="form-text fs-8 mt-1">
                            Can't find them?
                            <a href="{{ route('admin.seekers.index') }}" target="_blank">Register the seeker first</a>,
                            then come back.
                        </div>
                    </div>

                    {{-- ── STEP 2: CV ──────────────────────────── --}}
                    <div class="mb-6">
                        <label class="fw-bold fs-6 mb-2 d-block">2. CV Source</label>

                        <div class="btn-group w-100 mb-3" role="group">
                            <input type="radio" class="btn-check" name="cv_source" id="cv_existing" value="existing" checked>
                            <label class="btn btn-outline btn-outline-dashed" for="cv_existing">From Seeker's Library</label>

                            <input type="radio" class="btn-check" name="cv_source" id="cv_uploaded" value="uploaded">
                            <label class="btn btn-outline btn-outline-dashed" for="cv_uploaded">Upload New</label>
                        </div>

                        <div id="cvExistingPanel">
                            <div class="position-relative">
                                <input type="text"
                                       id="cvPicker"
                                       class="form-control form-control-solid"
                                       placeholder="Pick a CV from the seeker's library…"
                                       autocomplete="off"
                                       style="width:100%" />
                                <input type="hidden" name="cv_path" id="selectedCvPath">
                                <div class="picker-results" id="cvResults" hidden></div>
                            </div>

                            <div class="text-muted fs-8 mt-1">
                                Choose from the seeker's saved CVs.
                            </div>

                            <div id="cvEmptyHint" class="alert alert-light-warning py-2 mt-2 mb-0 fs-8 d-none">
                                This seeker has no CVs on file. Switch to <strong>Upload New</strong> to add one.
                            </div>

                            <div id="cvLoadingHint" class="text-muted fs-8 mt-1 d-none">
                                <span class="spinner-border spinner-border-sm me-1"></span>
                                Loading CVs…
                            </div>
                        </div>

                        <div id="cvUploadPanel" class="d-none">
                            <input type="file" name="cv_file" accept=".pdf,.doc,.docx"
                                   class="form-control" id="cv_file" />
                            <div class="text-muted fs-8 mt-1">PDF, DOC, or DOCX — max 10 MB.</div>
                        </div>
                    </div>

                    {{-- ── STEP 3: JOB ─────────────────────────── --}}
                    <div class="mb-6">
                        <label class="fw-bold fs-6 mb-2 d-block">
                            3. Target Job <span class="text-muted fw-normal fs-7">(optional)</span>
                        </label>

                        <div class="d-flex flex-wrap gap-3 mb-3">
                            <label class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="job_source" value="db" checked>
                                <span class="form-check-label">Search jobs</span>
                            </label>
                            <label class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="job_source" value="manual">
                                <span class="form-check-label">Title + Company</span>
                            </label>
                            <label class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="job_source" value="paste">
                                <span class="form-check-label">Paste JD</span>
                            </label>
                            <label class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="job_source" value="none">
                                <span class="form-check-label">Skip</span>
                            </label>
                        </div>

                        <div id="jobDbPanel">
                            <div class="position-relative">
                                <input type="text"
                                       id="jobSearch"
                                       class="form-control form-control-solid"
                                       placeholder="Search jobs by title or company…"
                                       autocomplete="off"
                                       style="width:100%" />
                                <input type="hidden" name="job_post_id" id="selectedJobId">
                                <div class="picker-results" id="jobResults" hidden></div>
                            </div>
                        </div>

                        <div id="jobManualPanel" class="d-none row g-3">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-7 mb-1">Job Title</label>
                                <input type="text" name="target_job_title" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-7 mb-1">Company</label>
                                <input type="text" name="target_company_name" class="form-control" />
                            </div>
                        </div>

                        <div id="jobPastePanel" class="d-none row g-3">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-7 mb-1">Job Title</label>
                                <input type="text" name="target_job_title" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-7 mb-1">Company</label>
                                <input type="text" name="target_company_name" class="form-control" />
                            </div>
                            <div class="col-12">
                                <label class="fw-semibold fs-7 mb-1">Full Job Post</label>
                                <textarea name="target_job_description" rows="6" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>

                    <div id="createReviewError" class="alert alert-light-danger d-none"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="createReviewSubmit">
                        <span class="indicator-label">
                            <i class="ki-duotone ki-rocket fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                            Create &amp; Run AI Review
                        </span>
                        <span class="indicator-progress">
                            Creating… <span class="spinner-border spinner-border-sm ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>