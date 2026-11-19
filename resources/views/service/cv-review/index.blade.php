@extends('layouts.admin')

@section('title', 'CV Review Requests')
@section('page_title', 'CV Review Requests')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Services</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">CV Review Requests</li>
@endsection

@section('content')
@can('view cv review requests')

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- HEADER --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
    <div>
        {{--
        <h2 class="fw-bold mb-1">CV Review Requests</h2>
        <div class="text-muted fs-7">Manage CV reviews from the seeker app and create new ones for subscribers.</div>
        --}}
    </div>
    <button type="button" class="btn btn-primary" id="createReviewBtn">
        <i class="ki-duotone ki-plus fs-3 me-1">
            <span class="path1"></span><span class="path2"></span>
        </i>
        Create CV Review
    </button>
</div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- STATS STRIP — single compact card --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div class="card card-flush mb-6">
    <div class="card-body py-4">
        <div class="row g-0 align-items-center">

            {{-- Awaiting Payment --}}
            <div class="col-6 col-md-3 d-flex align-items-center gap-3 px-3 py-2 border-end-md">
                <span class="symbol symbol-40px">
                    <span class="symbol-label bg-light-warning">
                        <i class="ki-duotone ki-time fs-2x text-warning">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </span>
                </span>
                <div class="d-flex flex-column">
                    <span class="text-muted fw-semibold fs-8 text-uppercase">Awaiting Payment</span>
                    <span class="fw-bold fs-3 text-warning" id="statPendingPayment">—</span>
                </div>
            </div>

            {{-- In Progress --}}
            <div class="col-6 col-md-3 d-flex align-items-center gap-3 px-3 py-2 border-end-md">
                <span class="symbol symbol-40px">
                    <span class="symbol-label bg-light-primary">
                        <i class="ki-duotone ki-arrows-circle fs-2x text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </span>
                </span>
                <div class="d-flex flex-column">
                    <span class="text-muted fw-semibold fs-8 text-uppercase">In Progress</span>
                    <span class="fw-bold fs-3 text-primary" id="statInProgress">—</span>
                </div>
            </div>

            {{-- SLA Overdue --}}
            <div class="col-6 col-md-3 d-flex align-items-center gap-3 px-3 py-2 border-end-md">
                <span class="symbol symbol-40px">
                    <span class="symbol-label bg-light-danger">
                        <i class="ki-duotone ki-information-5 fs-2x text-danger">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                    </span>
                </span>
                <div class="d-flex flex-column">
                    <span class="text-muted fw-semibold fs-8 text-uppercase">SLA Overdue</span>
                    <span class="fw-bold fs-3 text-danger" id="statOverdue">—</span>
                </div>
            </div>

            {{-- Revenue Today --}}
            <div class="col-6 col-md-3 d-flex align-items-center gap-3 px-3 py-2">
                <span class="symbol symbol-40px">
                    <span class="symbol-label bg-light-success">
                        <i class="ki-duotone ki-dollar fs-2x text-success">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </span>
                </span>
                <div class="d-flex flex-column">
                    <span class="text-muted fw-semibold fs-8 text-uppercase">Revenue Today</span>
                    <span class="fw-bold fs-3 text-success" id="statRevenueToday">—</span>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- TABS --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="originTabs">
    <li class="nav-item">
        <a class="nav-link active" data-origin="seeker" href="javascript:void(0)">
            <i class="ki-duotone ki-user fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
            Seeker Reviews
            <span class="badge badge-light-primary ms-2" id="tabCountSeeker">—</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-origin="subscriber" href="javascript:void(0)">
            <i class="ki-duotone ki-crown fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
            Subscriber Reviews
            <span class="badge badge-light-info ms-2" id="tabCountSubscriber">—</span>
        </a>
    </li>
</ul>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- TABLE --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title flex-wrap gap-2">
            <input type="text" id="searchInput" class="form-control form-control-solid w-250px" placeholder="Search client, UUID, ref..." />

            <select id="statusFilter" class="form-select form-select-solid w-175px">
                <option value="">All Statuses</option>
                @foreach(\App\Http\Controllers\Service\CvReviewRequestController::statuses() as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>

            <select id="paymentFilter" class="form-select form-select-solid w-175px">
                <option value="">All Payments</option>
                <option value="paid">✅ Paid</option>
                <option value="awaiting">⏳ Awaiting</option>
                <option value="not_required">Not Required</option>
            </select>

            <select id="modeFilter" class="form-select form-select-solid w-150px">
                <option value="">All Modes</option>
                <option value="ai">🤖 AI Rewrite</option>
                <option value="human">👤 HR Expert</option>
            </select>

            <select id="countryFilter" class="form-select form-select-solid w-175px">
                <option value="">All Countries</option>
            </select>

            <select id="assigneeFilter" class="form-select form-select-solid w-175px">
                <option value="">All Assignees</option>
            </select>

            <select id="slaFilter" class="form-select form-select-solid w-150px">
                <option value="">Any SLA</option>
                <option value="overdue">🔴 Overdue</option>
                <option value="due_soon">🟡 Due in 6h</option>
            </select>
        </div>
    </div>

    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-140px">Client</th>
                            <th class="min-w-150px">Service</th>
                            <th class="min-w-120px">Target Role</th>
                            <th class="min-w-100px">Country</th>
                            <th class="min-w-100px">Price</th>
                            <th class="min-w-140px">Payment</th>
                            <th class="min-w-150px">Status</th>
                            <th class="min-w-120px">Assignee</th>
                            <th class="text-end min-w-180px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="requestsTableBody"></tbody>
                </table>
            </div>
            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav><ul class="pagination m-0" id="pagination"></ul></nav>
            </div>
        </div>

        <div id="noDataMessage" class="text-center py-10 d-none">
            <p class="text-muted">No CV review requests found for this tab.</p>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- DETAIL DRAWER --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="kt_detail_drawer" style="width:920px;">
    <div class="offcanvas-header">
        <h3 class="offcanvas-title">Request Details</h3>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="detailDrawerBody">
        <div class="text-center py-10"><div class="spinner-border text-primary"></div></div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- CONFIRM PAYMENT MODAL --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="kt_modal_confirm_payment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Confirm Payment</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="confirmPaymentForm">
                    @csrf
                    <input type="hidden" name="request_id" id="confirm_request_id">

                    <div class="fv-row mb-5">
                        <label class="required fw-semibold fs-6 mb-2">Payment Method</label>
                        <select class="form-select form-select-solid" name="payment_method_id" id="confirm_payment_method" required>
                            <option value="">Select the wallet that received this payment...</option>
                        </select>
                        <div class="text-muted fs-7 mt-1">
                            The money will be credited to this payment method's balance and logged in the transaction journal.
                        </div>
                    </div>

                    <div class="fv-row mb-5">
                        <label class="required fw-semibold fs-6 mb-2">How was the money received?</label>
                        <select class="form-select form-select-solid" name="deposit_method" id="confirm_deposit_method" required>
                            <option value="">Select method...</option>
                            <option value="cash">Cash</option>
                            <option value="mobile_money">Mobile Money (MTN / Airtel)</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>

                    <div class="fv-row mb-5">
                        <label class="required fw-semibold fs-6 mb-2">Payment Reference / Receipt No.</label>
                        <input type="text" class="form-control form-control-solid" name="payment_reference" required />
                    </div>

                    <div class="fv-row mb-5">
                        <label class="fw-semibold fs-6 mb-2">Amount (optional)</label>
                        <input type="number" class="form-control form-control-solid" name="amount_cents" id="confirm_amount"
                               placeholder="Leave blank to use the CV review amount" min="1">
                        <div class="text-muted fs-7 mt-1" id="confirm_amount_hint"></div>
                    </div>

                    <div class="fv-row mb-5">
                        <label class="fw-semibold fs-6 mb-2">Note (internal)</label>
                        <input type="text" class="form-control form-control-solid" name="note"
                               placeholder="e.g. Cash received at office, receipt R-1042" />
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="confirmPaymentBtn">
                            <span class="indicator-label">Confirm Payment</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- DELIVER CONFIRM MODAL --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="kt_deliver_modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="fw-bold">Confirm Delivery</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="deliver_summary"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmDeliverBtn">
                    <span class="indicator-label">Send Now</span>
                    <span class="indicator-progress">Sending...
                        <span class="spinner-border spinner-border-sm ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- CREATE REVIEW WIZARD --}}
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

                    {{-- ── STEP 1: USER ─────────────────────────── --}}
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
                            Can't find them? <a href="{{ route('admin.seekers.index') }}" target="_blank">Register the seeker first</a>, then come back.
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

<div class="modal fade" id="docEditModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-a4">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="fw-bold mb-0" id="docEditTitle">Edit document</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 cv-a4-stage">
                <textarea id="docEditor" style="visibility:hidden;"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="docSaveBtn">Save changes</button>
            </div>
        </div>
    </div>
</div>

@endcan
@endsection

@push('styles')
<style>
    .picker-results {
        position: absolute;
        z-index: 1060;
        left: 0; right: 0;
        top: 100%;
        margin-top: 4px;
        max-height: 280px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid var(--bs-gray-300);
        border-radius: 0.475rem;
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
    }
    .picker-results .picker-item {
        padding: 0.6rem 0.9rem;
        cursor: pointer;
        font-size: 0.875rem;
        border-bottom: 1px solid var(--bs-gray-100);
    }
    .picker-results .picker-item:last-child { border-bottom: 0; }
    .picker-results .picker-item:hover,
    .picker-results .picker-item.active { background: var(--bs-gray-100); }
    .picker-results .picker-empty,
    .picker-results .picker-loading {
        padding: 0.75rem 0.9rem;
        color: var(--bs-gray-600);
        font-size: 0.8rem;
        text-align: center;
    }
    .tox-tinymce {
        border-radius: 0.475rem !important;
        border-color: var(--bs-gray-300) !important;
    }
    .rt-editor:focus { outline: 2px solid var(--bs-primary); outline-offset: -1px; }

    .modal-dialog.modal-a4 {
        --bs-modal-width: min(1000px, calc(100vw - 1rem));
        max-width: var(--bs-modal-width);
        width: var(--bs-modal-width);
        margin: .5rem auto;
    }
    .modal-a4 .modal-content {
        height: calc(100vh - 1rem);
    }
    .modal-a4 .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 0;
        background: #525659;
        overflow: hidden;
    }
    .modal-a4 .tox-tinymce {
        border: 0 !important;
        border-radius: 0 !important;
    }
</style>
@endpush



@push('scripts')
<script src="{{ asset('js/cv-editor.js') }}"></script>
<script>
    /* ═══════════════════════════════════════════════════════════════
    * CV Review Requests — Admin Page Script
    * ═══════════════════════════════════════════════════════════════ */

    const CV_CSRF = document.querySelector('meta[name="csrf-token"]')?.content
                || '{{ csrf_token() }}';

    /* ── List state ─────────────────────────────────────────────── */
    let currentPage         = 1;
    let currentSearch       = '';
    let currentStatus       = '';
    let currentPaymentState = '';
    let currentCountryCode  = '';
    let currentAssignee     = '';
    let currentSlaFilter    = '';
    let currentMode         = '';
    let currentOrigin       = 'seeker';
    let adminUsers          = [];
    let paymentMethods      = [];

    /* ── Helpers ────────────────────────────────────────────────── */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /* ═══════════════════════════════════════════════════════════════
    * BOOT — everything runs after DOM is ready
    * ═══════════════════════════════════════════════════════════════ */
    document.addEventListener('DOMContentLoaded', async function () {
        await loadFilters();
        loadStats();
        loadRequests();
        setupEventListeners();
        setupOriginTabs();
        setupWizard();
    });

    /* ── Tab wiring ─────────────────────────────────────────────── */
    function setupOriginTabs() {
        document.querySelectorAll('#originTabs a').forEach(a => {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('#originTabs a').forEach(x => x.classList.remove('active'));
                a.classList.add('active');
                currentOrigin = a.dataset.origin;
                currentPage = 1;
                loadRequests();
            });
        });
    }

    /* ── Filters dropdown data ──────────────────────────────────── */
    async function loadFilters() {
        try {
            const res  = await fetch('/admin/cv-review-requests/filters', { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.success) return;

            adminUsers     = data.admins || [];
            paymentMethods = data.payment_methods || [];

            const assigneeFilter = document.getElementById('assigneeFilter');
            if (assigneeFilter) {
                assigneeFilter.innerHTML = '<option value="">All Assignees</option>' +
                    adminUsers.map(a => `<option value="${a.id}">${escapeHtml(a.name)}</option>`).join('');
            }

            const countryFilter = document.getElementById('countryFilter');
            if (countryFilter) {
                countryFilter.innerHTML = '<option value="">All Countries</option>' +
                    (data.countries || []).map(c =>
                        `<option value="${c.code}">${c.flag} ${escapeHtml(c.name)}</option>`
                    ).join('');
            }
        } catch (e) {
            console.error('[filters]', e);
        }
    }

    /* ── Stats strip ────────────────────────────────────────────── */
    async function loadStats() {
        try {
            const res  = await fetch('/admin/cv-review-requests/stats', { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.success) return;
            document.getElementById('statPendingPayment').textContent = data.stats.pending_payment;
            document.getElementById('statInProgress').textContent     = data.stats.in_progress;
            document.getElementById('statOverdue').textContent        = data.stats.sla_overdue;
            document.getElementById('statRevenueToday').textContent   = (data.stats.revenue_today).toLocaleString();
        } catch (e) { /* silent */ }
    }

    /* ── Filters + search wiring ────────────────────────────────── */
    function setupEventListeners() {
        let timeout;
        document.getElementById('searchInput')?.addEventListener('keyup', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadRequests();
            }, 500);
        });

        document.getElementById('statusFilter')?.addEventListener('change', function () {
            currentStatus = this.value; currentPage = 1; loadRequests();
        });
        document.getElementById('paymentFilter')?.addEventListener('change', function () {
            currentPaymentState = this.value; currentPage = 1; loadRequests();
        });
        document.getElementById('countryFilter')?.addEventListener('change', function () {
            currentCountryCode = this.value; currentPage = 1; loadRequests();
        });
        document.getElementById('modeFilter')?.addEventListener('change', function () {
            currentMode = this.value; currentPage = 1; loadRequests();
        });
        document.getElementById('assigneeFilter')?.addEventListener('change', function () {
            currentAssignee = this.value; currentPage = 1; loadRequests();
        });
        document.getElementById('slaFilter')?.addEventListener('change', function () {
            currentSlaFilter = this.value; currentPage = 1; loadRequests();
        });
    }

    /* ── Main table loader ──────────────────────────────────────── */
    function loadRequests() {
        const spinner    = document.getElementById('loadingSpinner');
        const table      = document.getElementById('tableContainer');
        const noData     = document.getElementById('noDataMessage');
        const pagination = document.getElementById('paginationContainer');

        spinner.classList.remove('d-none');
        table.classList.add('d-none');
        noData.classList.add('d-none');
        pagination.classList.add('d-none');

        const params = new URLSearchParams({ page: currentPage, per_page: 15, origin: currentOrigin });
        if (currentSearch)       params.set('search', currentSearch);
        if (currentStatus)       params.set('status', currentStatus);
        if (currentPaymentState) params.set('payment_state', currentPaymentState);
        if (currentCountryCode)  params.set('country_code', currentCountryCode);
        if (currentAssignee)     params.set('assigned_admin_id', currentAssignee);
        if (currentSlaFilter)    params.set('sla', currentSlaFilter);
        if (currentMode)         params.set('rewrite_mode', currentMode);

        fetch(`/admin/cv-review-requests/data?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                spinner.classList.add('d-none');
                if (!data.data || data.data.length === 0) {
                    noData.classList.remove('d-none');
                } else {
                    table.classList.remove('d-none');
                    renderTable(data.data);
                    renderPagination(data);
                    pagination.classList.remove('d-none');
                }
                if (data.tab_counts) {
                    document.getElementById('tabCountSeeker').textContent     = data.tab_counts.seeker ?? 0;
                    document.getElementById('tabCountSubscriber').textContent = data.tab_counts.subscriber ?? 0;
                }
            })
            .catch(err => {
                spinner.classList.add('d-none');
                console.error(err);
                window.showToast?.('error', 'Failed to load requests');
            });
    }

    /* ── Row renderer ───────────────────────────────────────────── */
    function renderTable(requests) {
        const tbody = document.getElementById('requestsTableBody');
        tbody.innerHTML = '';

        requests.forEach(req => {
            const row = tbody.insertRow();

            const subscriberBadge = req.is_subscriber_created
                ? '<span class="badge badge-light-info badge-sm mt-1">SUB</span>'
                : '';

            row.insertCell(0).innerHTML = `
                <div class="d-flex flex-column">
                    <span class="fw-bold">${escapeHtml(req.client_name)}</span>
                    <span class="text-muted fs-7">${escapeHtml(req.client_email)}</span>
                    <span class="text-muted fs-8">#${escapeHtml((req.uuid || '').substring(0, 8))} ${subscriberBadge}</span>
                </div>`;

            row.insertCell(1).innerHTML = `
                <div class="d-flex flex-column">
                    <span class="fw-semibold">${escapeHtml(req.service_name)}</span>
                    ${req.rewrite_mode === 'ai'
                        ? '<span class="badge badge-light-info badge-sm mt-1">AI Rewrite</span>'
                        : req.rewrite_mode === 'human'
                            ? '<span class="badge badge-light-warning badge-sm mt-1">HR Expert</span>'
                            : ''}
                </div>`;

            row.insertCell(2).innerHTML = req.target_job_title
                ? `<span class="text-truncate d-inline-block" style="max-width:150px;">${escapeHtml(req.target_job_title)}</span>`
                : '<span class="text-muted">—</span>';

            row.insertCell(3).innerHTML = req.country_label;
            row.insertCell(4).innerHTML = `<span class="fw-bold text-primary">${escapeHtml(req.formatted_price)}</span>`;
            row.insertCell(5).innerHTML = req.payment_badge;

            const allowed = req.allowed_next_statuses || [];
            row.insertCell(6).innerHTML = `
                <select class="form-select form-select-sm form-select-solid status-quick-change"
                        data-id="${req.id}" data-current="${req.status}">
                    ${buildStatusOptions(req.status, allowed)}
                </select>
                <div class="mt-1">${req.status_badge}</div>
                ${req.seeker_gap_answers && Object.keys(req.seeker_gap_answers).length > 0
                    ? `<span class="badge badge-light-success badge-sm mt-1">✓ ${Object.keys(req.seeker_gap_answers).length} answers</span>`
                    : ''}`;
                    
            row.insertCell(7).innerHTML = req.assignee_name
                ? `<span class="badge badge-light-primary">${escapeHtml(req.assignee_name)}</span>`
                : '<span class="text-muted">Unassigned</span>';

            row.insertCell(8).innerHTML = `
                <div class="d-flex justify-content-end gap-1">
                    ${req.cv_download_url
                        ? `<a href="${req.cv_download_url}" target="_blank" class="btn btn-sm btn-icon btn-light" title="Original CV">
                            <i class="ki-duotone ki-file-down fs-3"><span class="path1"></span><span class="path2"></span></i>
                        </a>`
                        : `<button class="btn btn-sm btn-icon btn-light" disabled title="No CV">
                            <i class="ki-duotone ki-file-deleted fs-3 text-muted"><span class="path1"></span><span class="path2"></span></i>
                        </button>`}
                    ${req.rewrite_download_url
                        ? `<a href="${req.rewrite_download_url}" class="btn btn-sm btn-icon btn-light-success" title="AI CV">
                            <i class="ki-duotone ki-document fs-3"><span class="path1"></span><span class="path2"></span></i>
                        </a>`
                        : ''}
                    ${req.has_cover_letter
                        ? `<a href="/admin/cv-review-requests/${req.id}/cover-letter" target="_blank" class="btn btn-sm btn-icon btn-light-info" title="Cover letter">
                            <i class="ki-duotone ki-message-text-2 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        </a>`
                        : ''}
                    ${(req.payment_badge || '').includes('Awaiting') && !req.is_subscriber_created
                        ? `<button class="btn btn-sm btn-icon btn-light-success" onclick="openConfirmPayment(${req.id})" title="Confirm Payment">
                            <i class="ki-duotone ki-dollar fs-3"><span class="path1"></span><span class="path2"></span></i>
                        </button>`
                        : ''}
                    <button class="btn btn-sm btn-icon btn-light" onclick="openDetail(${req.id})" title="Details">
                        <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </button>
                </div>`;

            const sel = row.querySelector('.status-quick-change');
            sel?.addEventListener('change', function () {
                updateStatus(this.dataset.id, this.value, this.dataset.current);
            });
        });
    }

    function buildStatusOptions(current, allowed) {
        const all = {
            submitted: 'Submitted', ai_reviewed: 'AI Reviewed', awaiting_payment: 'Awaiting Payment',
            paid: 'Paid', paid_ai: 'Paid (AI)', paid_human: 'Paid (HR)',
            generating: 'Generating', generated: 'Generated',
            in_progress: 'In Progress', delivered: 'Delivered',
            revision_requested: 'Revision Requested', completed: 'Completed', cancelled: 'Cancelled',
        };
        let html = `<option value="${current}" selected>${all[current] ?? current}</option>`;
        (allowed || []).forEach(s => {
            if (s !== current) html += `<option value="${s}">→ ${all[s] ?? s}</option>`;
        });
        return html;
    }

    /* ── Status quick-change ────────────────────────────────────── */
    window.updateStatus = function (id, newStatus, current) {
        if (newStatus === current) return;
        if (!confirm(`Move this request to "${newStatus}"?`)) { loadRequests(); return; }

        fetch(`/admin/cv-review-requests/${id}/status`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CV_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ status: newStatus })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.showToast?.('success', data.message);
                loadRequests();
                loadStats();
            } else {
                window.showToast?.('error', data.message);
                loadRequests();
            }
        })
        .catch(() => { window.showToast?.('error', 'Failed'); loadRequests(); });
    };

    /* ── Confirm Payment modal ──────────────────────────────────── */
    window.openConfirmPayment = function (id, defaultAmount) {
        document.getElementById('confirm_request_id').value = id;
        document.getElementById('confirmPaymentForm').reset();
        document.getElementById('confirm_request_id').value = id;

        const sel = document.getElementById('confirm_payment_method');
        sel.innerHTML = '<option value="">Select the wallet that received this payment...</option>' +
            paymentMethods.map(pm => `<option value="${pm.id}">${escapeHtml(pm.name)} (${escapeHtml(pm.code)})</option>`).join('');

        const hint = document.getElementById('confirm_amount_hint');
        if (defaultAmount && defaultAmount > 0) {
            hint.textContent = `The CV review request currently has ${defaultAmount} cents recorded. Leave blank to use that.`;
            document.getElementById('confirm_amount').placeholder = defaultAmount;
        } else {
            hint.textContent = 'This CV review has no amount set. You must provide one.';
        }

        new bootstrap.Modal(document.getElementById('kt_modal_confirm_payment')).show();
    };

    document.getElementById('confirmPaymentForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const method = document.getElementById('confirm_deposit_method')?.value;
        if (!method) {
            window.showToast?.('error', 'Please select how the money was received.');
            return;
        }

        const btn = document.getElementById('confirmPaymentBtn');
        window.showButtonSpinner?.(btn);

        const id       = document.getElementById('confirm_request_id').value;
        const formData = new FormData(this);

        fetch(`/admin/cv-review-requests/${id}/confirm-payment`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CV_CSRF, 'Accept': 'application/json' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.showToast?.('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_confirm_payment'))?.hide();
                loadRequests();
                loadStats();
                setTimeout(() => window.openDetail(id), 400);
            } else {
                const msg = data.errors ? Object.values(data.errors).flat().join('\n') : data.message;
                window.showToast?.('error', msg);
            }
        })
        .catch(() => window.showToast?.('error', 'Failed to confirm payment'))
        .finally(() => window.hideButtonSpinner?.(btn));
    });

    window.openDetail = function (id) {
        const drawerBody = document.getElementById('detailDrawerBody');
        drawerBody.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';
        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('kt_detail_drawer')).show();

        fetch(`/admin/cv-review-requests/${id}/detail`, { headers: { 'Accept': 'text/html' } })
            .then(r => r.text())
            .then(html => {
                drawerBody.innerHTML = html;

                if (drawerBody.querySelector('[data-gen-busy="1"]')) {
                    setTimeout(() => {
                        if (document.getElementById('kt_detail_drawer')?.classList.contains('show')) {
                            window.openDetail(id);
                        }
                    }, 8000);
                }
            });
    };

    /* ── Pagination ─────────────────────────────────────────────── */
    function renderPagination(data) {
        const el   = document.getElementById('pagination');
        const info = document.getElementById('paginationInfo');
        el.innerHTML = '';
        info.innerHTML = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total}`;

        const addPage = (page, text, isActive = false, isDisabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            if (!isDisabled) a.onclick = (e) => { e.preventDefault(); changePage(page); };
            li.appendChild(a);
            el.appendChild(li);
        };

        addPage(data.current_page - 1, 'Previous', false, !data.prev_page_url);
        const start = Math.max(1, data.current_page - 2);
        const end   = Math.min(data.last_page, data.current_page + 2);
        if (start > 1) addPage(1, '1');
        if (start > 2) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        for (let i = start; i <= end; i++) addPage(i, i, i === data.current_page);
        if (end < data.last_page - 1) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        if (end < data.last_page) addPage(data.last_page, data.last_page);
        addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
    }

    window.changePage = function (page) {
        if (page !== currentPage && page > 0) { currentPage = page; loadRequests(); }
    };

    /* ═══════════════════════════════════════════════════════════════
    * DELEGATED HANDLERS — drawer + row buttons
    * ═══════════════════════════════════════════════════════════════ */
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-action]');
        if (!trigger) return;

        const action = trigger.dataset.action;

        if (action === 'run-ai-review') {
            e.preventDefault();
            const id = trigger.dataset.id;
            if (!id) return;
            if (!confirm('Run AI gap review? This takes 10-30 seconds.')) return;

            const original = trigger.innerHTML;
            trigger.disabled = true;
            trigger.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Analysing...';

            fetch(`/admin/cv-review-requests/${id}/run-ai-review`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CV_CSRF, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.showToast?.('success', data.message || 'AI review done');
                    setTimeout(() => { window.openDetail(id); loadRequests(); loadStats(); }, 500);
                } else {
                    window.showToast?.('error', data.message || 'AI review failed');
                    trigger.disabled = false;
                    trigger.innerHTML = original;
                }
            })
            .catch(err => {
                console.error(err);
                window.showToast?.('error', 'AI review failed');
                trigger.disabled = false;
                trigger.innerHTML = original;
            });
            return;
        }

        if (action === 'toggle-edit-review') {
            e.preventDefault();
            document.getElementById('reviewView')?.classList.add('d-none');
            document.getElementById('reviewEdit')?.classList.remove('d-none');
            return;
        }

        if (action === 'cancel-edit-review') {
            e.preventDefault();
            document.getElementById('reviewEdit')?.classList.add('d-none');
            document.getElementById('reviewView')?.classList.remove('d-none');
            return;
        }

        if (action === 'add-section') {
            e.preventDefault();
            const list = document.getElementById('edit_sections_list');
            if (!list) return;
            const row = document.createElement('div');
            row.className = 'row g-2 mb-2 section-row';
            row.innerHTML = `
                <div class="col-md-4"><input type="text" class="form-control form-control-sm" data-field="section" placeholder="Section"></div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" data-field="status">
                        <option value="ok">OK</option><option value="weak">Weak</option><option value="missing">Missing</option>
                    </select>
                </div>
                <div class="col-md-5"><input type="text" class="form-control form-control-sm" data-field="note" placeholder="Note"></div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-light-danger w-100" data-action="remove-section">
                        <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </div>`;
            list.appendChild(row);
            return;
        }

        if (action === 'remove-section') {
            e.preventDefault();
            trigger.closest('.section-row')?.remove();
            return;
        }

        if (action === 'save-edited-review') {
            e.preventDefault();
            const id = trigger.dataset.id;
            if (!id) return;

            const original = trigger.innerHTML;
            trigger.disabled = true;
            trigger.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

            const sections = [];
            document.querySelectorAll('#edit_sections_list .section-row').forEach(row => {
                sections.push({
                    section: row.querySelector('[data-field="section"]').value,
                    status:  row.querySelector('[data-field="status"]').value,
                    note:    row.querySelector('[data-field="note"]').value,
                });
            });

            const missingEl = document.getElementById('edit_missing');
            const missing = missingEl ? missingEl.value.split(',').map(s => s.trim()).filter(Boolean) : [];

            const payload = {
                overall_score:  parseInt(document.getElementById('edit_score')?.value || '0', 10),
                summary:        document.getElementById('edit_summary')?.value || '',
                sections:       sections,
                missing_fields: missing,
            };

            fetch(`/admin/cv-review-requests/${id}/save-edited-review`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CV_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.showToast?.('success', data.message || 'Review saved');
                    setTimeout(() => window.openDetail(id), 400);
                } else {
                    window.showToast?.('error', data.message || 'Save failed');
                    trigger.disabled = false;
                    trigger.innerHTML = original;
                }
            })
            .catch(err => {
                console.error(err);
                window.showToast?.('error', 'Save failed');
                trigger.disabled = false;
                trigger.innerHTML = original;
            });
            return;
        }

        if (action === 'open-deliver-modal') {
            e.preventDefault();
            const id = trigger.dataset.id;

            const channels = [];
            if (document.querySelector('input[name="channel_email"]')?.checked)    channels.push('email');
            if (document.querySelector('input[name="channel_whatsapp"]')?.checked) channels.push('whatsapp');

            if (!channels.length) { window.showToast?.('error', 'Select at least one delivery channel.'); return; }

            const message = document.getElementById('delivery_message')?.value || '';

            const summaryEl = document.getElementById('deliver_summary');
            if (summaryEl) {
                summaryEl.innerHTML = `
                    <div class="alert alert-light-primary mb-0">
                        <div class="fw-bold mb-2">Sending via:</div>
                        <ul class="mb-0">${channels.map(c => `<li>${c === 'email' ? '✉️ Email' : '📱 WhatsApp'}</li>`).join('')}</ul>
                        ${message ? `<div class="mt-3"><div class="fw-bold">Custom message:</div><div class="text-muted">${escapeHtml(message)}</div></div>` : ''}
                    </div>`;
            }

            const modalEl = document.getElementById('kt_deliver_modal');
            if (modalEl) {
                modalEl.dataset.requestId = id;
                modalEl.dataset.channels  = JSON.stringify(channels);
                modalEl.dataset.message   = message;
                new bootstrap.Modal(modalEl).show();
            }
            return;
        }

        if (action === 'change-status') {
            e.preventDefault();
            const id        = trigger.dataset.id;
            const newStatus = document.getElementById('status_change_select')?.value;
            const note      = document.getElementById('status_change_note')?.value || '';

            if (!id || !newStatus) return;
            if (!confirm(`Change status to "${newStatus}"?`)) return;

            const original = trigger.innerHTML;
            trigger.disabled = true;
            trigger.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Applying...';

            fetch(`/admin/cv-review-requests/${id}/status`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CV_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ status: newStatus, note })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.showToast?.('success', data.message || 'Status updated');
                    setTimeout(() => { window.openDetail(id); loadRequests(); loadStats(); }, 400);
                } else {
                    window.showToast?.('error', data.message || 'Failed to change status');
                    trigger.disabled = false;
                    trigger.innerHTML = original;
                }
            })
            .catch(err => {
                console.error(err);
                window.showToast?.('error', 'Failed to change status');
                trigger.disabled = false;
                trigger.innerHTML = original;
            });
            return;
        }

        if (action === 'submit-answers') {
            e.preventDefault();
            const form = document.getElementById('adminAnswersForm');
            if (!form) return;
            form.requestSubmit();
            return;
        }

            if (action === 'generate-missing') {
            e.preventDefault();
            const id = trigger.dataset.id;
            const original = trigger.innerHTML;

            trigger.disabled = true;
            trigger.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Starting…';

            fetch(`/admin/cv-review-requests/${id}/generate-missing`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CV_CSRF, 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.showToast?.('success', data.message);
                    setTimeout(() => { window.openDetail(id); loadRequests(); loadStats(); }, 500);
                } else {
                    window.showToast?.('error', data.message || 'Could not start generation');
                    trigger.disabled = false;
                    trigger.innerHTML = original;
                }
            })
            .catch(() => {
                window.showToast?.('error', 'Network error');
                trigger.disabled = false;
                trigger.innerHTML = original;
            });
            return;
        }

        if (action === 'skip-answers') {
            e.preventDefault();
            const id = trigger.dataset.id;
            if (!id) return;
            if (!confirm('Mark the user as having already provided the answers?')) return;

            const original = trigger.innerHTML;
            trigger.disabled = true;
            trigger.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Skipping...';

            fetch(`/admin/cv-review-requests/${id}/submit-answers`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CV_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ answers: { _skipped: 'User had already answered these questions outside the system.' } })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.showToast?.('success', data.message, 'Saved');
                    setTimeout(() => { window.openDetail(id); loadRequests(); loadStats(); }, 400);
                } else {
                    window.showToast?.('error', data.message, 'Error');
                    trigger.disabled = false;
                    trigger.innerHTML = original;
                }
            })
            .catch(() => {
                window.showToast?.('error', 'Network error', 'Error');
                trigger.disabled = false;
                trigger.innerHTML = original;
            });
            return;
        }
    });

    /* ── Confirm deliver (modal footer) ─────────────────────────── */
    document.getElementById('confirmDeliverBtn')?.addEventListener('click', function () {
        const modalEl  = document.getElementById('kt_deliver_modal');
        const id       = parseInt(modalEl?.dataset.requestId, 10);
        const channels = JSON.parse(modalEl?.dataset.channels || '[]');
        const message  = modalEl?.dataset.message || '';

        if (!id || !channels.length) { window.showToast?.('error', 'No delivery context set.'); return; }

        const btn = this;
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';

        fetch(`/admin/cv-review-requests/${id}/deliver`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CV_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ channels, message })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.showToast?.('success', data.message || 'Delivered');
                bootstrap.Modal.getInstance(modalEl)?.hide();
                setTimeout(() => window.openDetail(id), 400);
            } else {
                window.showToast?.('error', data.message || 'Delivery failed');
                btn.disabled = false;
                btn.innerHTML = original;
            }
        })
        .catch(err => {
            console.error(err);
            window.showToast?.('error', 'Delivery failed');
            btn.disabled = false;
            btn.innerHTML = original;
        });
    });

    /* ── Admin answers form submit ──────────────────────────────── */
    document.addEventListener('submit', async function (e) {
        const form = e.target;
        if (form.id !== 'adminAnswersForm') return;
        e.preventDefault();

        const id = form.dataset.id;
        const fd = new FormData(form);
        const answers = {};
        for (const [k, v] of fd.entries()) {
            const m = k.match(/answers\[(.+)\]/);
            if (m) answers[m[1]] = v;
        }

        if (!Object.keys(answers).length) { window.showToast?.('error', 'Please fill in at least one answer.', 'Missing'); return; }

        const btn = document.getElementById('submitAnswersBtn');
        window.showButtonSpinner?.(btn);

        try {
            const res = await fetch(`/admin/cv-review-requests/${id}/submit-answers`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CV_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ answers })
            });
            const data = await res.json();

            if (!data.success) {
                window.showToast?.('error', data.message || 'Save failed', 'Error');
                window.hideButtonSpinner?.(btn);
                return;
            }

            window.showToast?.('success', data.message, 'Saved');
            setTimeout(() => { window.openDetail(id); loadRequests(); loadStats(); }, 400);
        } catch (err) {
            window.showToast?.('error', 'Network error: ' + err.message, 'Error');
            window.hideButtonSpinner?.(btn);
        }
    });

    /* ═══════════════════════════════════════════════════════════════
    * CREATE REVIEW WIZARD — vanilla JS (no jQuery, no Select2)
    * ═══════════════════════════════════════════════════════════════ */
    function setupWizard() {
        const modalEl = document.getElementById('createReviewModal');
        if (!modalEl) return;

        // ── Guard: bail loudly if markup wasn't updated ──────────
        const required = [
            'userSearch', 'userResults', 'selectedUserId', 'userSubBadge',
            'cvPicker', 'cvResults', 'selectedCvPath', 'cvEmptyHint', 'cvLoadingHint',
            'jobSearch', 'jobResults', 'selectedJobId',
            'createReviewForm', 'createReviewSubmit', 'createReviewError',
        ];
        const missing = required.filter(id => !document.getElementById(id));
        if (missing.length) {
            console.error('[wizard] Missing elements in the markup:', missing);
            return;
        }

        // ── Element refs ─────────────────────────────────────────
        const userInput      = document.getElementById('userSearch');
        const userResults    = document.getElementById('userResults');
        const cvInput        = document.getElementById('cvPicker');
        const cvResults      = document.getElementById('cvResults');
        const jobInput       = document.getElementById('jobSearch');
        const jobResults     = document.getElementById('jobResults');

        const selectedUserId = document.getElementById('selectedUserId');
        const selectedCvPath = document.getElementById('selectedCvPath');
        const selectedJobId  = document.getElementById('selectedJobId');
        const userSubBadge   = document.getElementById('userSubBadge');
        const cvEmptyHint    = document.getElementById('cvEmptyHint');
        const cvLoadingHint  = document.getElementById('cvLoadingHint');
        const cvExistingPanel = document.getElementById('cvExistingPanel');
        const cvUploadPanel   = document.getElementById('cvUploadPanel');
        const jobDbPanel      = document.getElementById('jobDbPanel');
        const jobManualPanel  = document.getElementById('jobManualPanel');
        const jobPastePanel   = document.getElementById('jobPastePanel');

        // ── Picker factory ───────────────────────────────────────
        function makePicker({ input, resultsBox, fetchUrl, buildQuery, renderItem, onSelect, onClear, minChars = 2, delay = 250 }) {
            let timer = null;
            let lastQuery = '';
            let activeIdx = -1;

            function clearResults() {
                resultsBox.innerHTML = '';
                resultsBox.hidden = true;
                activeIdx = -1;
            }

            function showLoading() {
                resultsBox.innerHTML = '<div class="picker-loading">Searching…</div>';
                resultsBox.hidden = false;
            }

            function showEmpty() {
                resultsBox.innerHTML = '<div class="picker-empty">No results</div>';
                resultsBox.hidden = false;
            }

            function renderResults(items) {
                resultsBox.innerHTML = '';
                activeIdx = -1;
                if (!items || items.length === 0) { showEmpty(); return; }

                items.forEach((item, idx) => {
                    const row = document.createElement('div');
                    row.className = 'picker-item';
                    row.dataset.idx = idx;
                    row.innerHTML = renderItem(item);
                    row.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        selectItem(item);
                    });
                    row.addEventListener('mouseenter', () => {
                        resultsBox.querySelectorAll('.picker-item').forEach(el => el.classList.remove('active'));
                        row.classList.add('active');
                        activeIdx = idx;
                    });
                    resultsBox.appendChild(row);
                });
                resultsBox.hidden = false;
            }

            function selectItem(item) {
                input.value = renderItem(item).replace(/<[^>]+>/g, '').trim();
                clearResults();
                onSelect(item);
            }

            async function search(query) {
                if (query.length < minChars) { clearResults(); return; }
                showLoading();
                try {
                    const qs = buildQuery(query);
                    const res = await fetch(`${fetchUrl}?${qs}`, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) { clearResults(); return; }
                    const data = await res.json();
                    renderResults(data.data || []);
                } catch (err) {
                    console.error('[picker]', err);
                    clearResults();
                }
            }

            input.addEventListener('input', () => {
                const q = input.value.trim();
                if (q === '') { clearResults(); onClear(); return; }
                if (q === lastQuery) return;
                lastQuery = q;
                clearTimeout(timer);
                timer = setTimeout(() => search(q), delay);
            });

            input.addEventListener('focus', () => {
                const q = input.value.trim();
                if (q.length >= minChars) search(q);
            });

            input.addEventListener('keydown', (e) => {
                const items = resultsBox.querySelectorAll('.picker-item');
                if (!items.length) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeIdx = Math.min(activeIdx + 1, items.length - 1);
                    items.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
                    items[activeIdx].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeIdx = Math.max(activeIdx - 1, 0);
                    items.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
                    items[activeIdx].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter' && activeIdx >= 0) {
                    e.preventDefault();
                    items[activeIdx].dispatchEvent(new Event('mousedown'));
                } else if (e.key === 'Escape') {
                    clearResults();
                }
            });

            input.addEventListener('blur', () => setTimeout(clearResults, 150));

            return { clearResults, input };
        }

        // ── Build the three pickers ──────────────────────────────
        const userPicker = makePicker({
            input:      userInput,
            resultsBox: userResults,
            fetchUrl:   '/admin/cv-review-requests/search-seekers',
            buildQuery: q => `q=${encodeURIComponent(q)}`,
            renderItem: s => `<div class="fw-semibold">${escapeHtml(s.label)}</div>`,
            onSelect: (s) => {
                selectedUserId.value = s.id;
                userSubBadge.innerHTML = s.is_subscriber
                    ? '<span class="badge badge-light-success">✓ Active subscriber — payment will be skipped</span>'
                    : '<span class="badge badge-light-warning">Not a subscriber — will require manual payment</span>';
                loadUserCvs(s.id);
            },
            onClear: () => {
                selectedUserId.value = '';
                userSubBadge.innerHTML = '';
                cvInput.value = '';
                selectedCvPath.value = '';
                cvResults.hidden = true;
            },
        });

        const cvPicker = makePicker({
            input:      cvInput,
            resultsBox: cvResults,
            fetchUrl:   '/admin/cv-review-requests/cv-search',
            buildQuery: q => `user_id=${encodeURIComponent(selectedUserId.value)}&q=${encodeURIComponent(q)}`,
            minChars:   0,
            renderItem: c => `<div class="fw-semibold">${escapeHtml(c.label)}</div>`,
            onSelect: (c) => {
                selectedCvPath.value = c.path;
                cvEmptyHint.classList.add('d-none');
            },
            onClear: () => { selectedCvPath.value = ''; },
        });

        makePicker({
            input:      jobInput,
            resultsBox: jobResults,
            fetchUrl:   '/admin/cv-review-requests/search-jobs',
            buildQuery: q => `q=${encodeURIComponent(q)}`,
            renderItem: j => `<div class="fw-semibold">${escapeHtml(j.label)}</div>`,
            onSelect: (j) => { selectedJobId.value = j.id; },
            onClear: () => { selectedJobId.value = ''; },
        });

        // ── Load CVs when a seeker is picked ─────────────────────
        async function loadUserCvs(userId) {
            if (!userId) return;

            cvInput.value = '';
            selectedCvPath.value = '';
            cvPicker.clearResults();
            cvLoadingHint.classList.remove('d-none');
            cvEmptyHint.classList.add('d-none');

            try {
                const res = await fetch(`/admin/cv-review-requests/cv-search?user_id=${encodeURIComponent(userId)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                cvLoadingHint.classList.add('d-none');

                const items = data.data || [];
                if (items.length === 0) {
                    cvEmptyHint.classList.remove('d-none');
                    document.getElementById('cv_existing').checked = false;
                    document.getElementById('cv_uploaded').checked = true;
                    cvExistingPanel.classList.add('d-none');
                    cvUploadPanel.classList.remove('d-none');
                    return;
                }

                cvInput.value = items[0].label;
                selectedCvPath.value = items[0].path;
                cvPicker.clearResults();
            } catch (err) {
                console.error('[loadUserCvs]', err);
                cvLoadingHint.classList.add('d-none');
                cvEmptyHint.classList.remove('d-none');
            }
        }

        // ── Panel toggles ────────────────────────────────────────
        document.querySelectorAll('input[name="cv_source"]').forEach(r => {
            r.addEventListener('change', () => {
                cvExistingPanel.classList.toggle('d-none', r.value !== 'existing');
                cvUploadPanel.classList.toggle('d-none', r.value !== 'uploaded');
            });
        });

        document.querySelectorAll('input[name="job_source"]').forEach(r => {
            r.addEventListener('change', () => {
                jobDbPanel.classList.toggle('d-none', r.value !== 'db');
                jobManualPanel.classList.toggle('d-none', r.value !== 'manual');
                jobPastePanel.classList.toggle('d-none', r.value !== 'paste');
            });
        });

        // ── Reset on modal open ──────────────────────────────────
        modalEl.addEventListener('show.bs.modal', () => {
            document.getElementById('createReviewForm')?.reset();
            userInput.value = '';
            cvInput.value = '';
            jobInput.value = '';
            selectedUserId.value = '';
            selectedCvPath.value = '';
            selectedJobId.value = '';
            userSubBadge.innerHTML = '';
            cvEmptyHint.classList.add('d-none');
            cvLoadingHint.classList.add('d-none');
            document.getElementById('createReviewError').classList.add('d-none');
            cvResults.hidden = true;
            userResults.hidden = true;
            jobResults.hidden = true;

            cvExistingPanel.classList.remove('d-none');
            cvUploadPanel.classList.add('d-none');
            jobDbPanel.classList.remove('d-none');
            jobManualPanel.classList.add('d-none');
            jobPastePanel.classList.add('d-none');
        });

        // ── Open button ──────────────────────────────────────────
        document.getElementById('createReviewBtn')?.addEventListener('click', () => {
            new bootstrap.Modal(modalEl).show();
        });

        // ── Submit ───────────────────────────────────────────────
        document.getElementById('createReviewForm')?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn   = document.getElementById('createReviewSubmit');
            const errEl = document.getElementById('createReviewError');
            errEl.classList.add('d-none');

            const fd = new FormData(this);

            if (!fd.get('user_id')) {
                errEl.textContent = 'Please pick a seeker first.';
                errEl.classList.remove('d-none');
                return;
            }
            if (fd.get('cv_source') === 'existing' && !fd.get('cv_path')) {
                errEl.textContent = 'Please pick a CV from the seeker\'s library, or switch to Upload New.';
                errEl.classList.remove('d-none');
                return;
            }
            if (fd.get('cv_source') === 'uploaded') {
                const file = fd.get('cv_file');
                if (!file || !file.size) {
                    errEl.textContent = 'Please choose a file to upload.';
                    errEl.classList.remove('d-none');
                    return;
                }
            }

            window.showButtonSpinner?.(btn);

            try {
                const res = await fetch('/admin/cv-review-requests/create-for-user', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CV_CSRF, 'Accept': 'application/json' },
                    body: fd,
                });
                const data = await res.json();

                if (!data.success) {
                    const msg = data.errors ? Object.values(data.errors).flat().join('\n') : data.message;
                    errEl.textContent = msg;
                    errEl.classList.remove('d-none');
                    window.hideButtonSpinner?.(btn);
                    return;
                }

                window.showToast?.('success', data.message, 'Created');
                bootstrap.Modal.getInstance(modalEl)?.hide();
                loadRequests();
                loadStats();

                if (data.data?.id) {
                    setTimeout(() => window.openDetail(data.data.id), 400);
                }
            } catch (err) {
                errEl.textContent = 'Network error: ' + err.message;
                errEl.classList.remove('d-none');
                window.hideButtonSpinner?.(btn);
            }
        });
    }
</script>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const modalEl  = document.getElementById('docEditModal');
    const textarea = document.getElementById('docEditor');   // the <textarea>, not a div
    const titleEl  = document.getElementById('docEditTitle');
    const saveBtn  = document.getElementById('docSaveBtn');
    const drawerEl = document.getElementById('kt_detail_drawer');

    let current    = null;   // { doc, id }
    let tinyEditor = null;   // TinyMCE instance, created on first open

    const toast    = (type, msg) => (window.showToast ? window.showToast(type, msg) : alert(msg));
    const sourceEl = (doc, id) => document.getElementById(`doc-${doc}-${id}`);

    // Focus trap off, so TinyMCE's link/table/colour dialogs can take focus inside the modal
    const bsModal = new bootstrap.Modal(modalEl, { focus: false });

    const TINY_CDN  = 'https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js';
    const EDITOR_JS = '{{ asset('js/cv-editor.js') }}?v=4';

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = src;
            s.referrerPolicy = 'origin';
            s.onload  = () => resolve(src);
            s.onerror = () => reject(new Error('Could not load ' + src));
            document.head.appendChild(s);
        });
    }

    // Loads TinyMCE and the shared options once. Safe to call many times.
    let libsPromise = null;
    function ensureLibs() {
        if (!libsPromise) {
            libsPromise = (async () => {
                if (!window.tinymce)       await loadScript(TINY_CDN);
                if (!window.cvEditorOptions) await loadScript(EDITOR_JS);
            })().catch(err => { libsPromise = null; throw err; });
        }
        return libsPromise;
    }

    async function ensureEditor(initialHtml) {
        try {
            await ensureLibs();
        } catch (err) {
            console.error('[doc] editor libraries', err);
            toast('error', err.message + '. Check your connection or the console.');
            return null;
        }

        if (tinyEditor) {
            tinyEditor.setContent(initialHtml);
            return tinyEditor;
        }

        const bodyEl = modalEl.querySelector('.modal-body');
        const height = Math.max(600, bodyEl.clientHeight);
        const width  = bodyEl.clientWidth;

        const [ed] = await tinymce.init(
            window.cvEditorOptions(textarea, (editor) => {
                editor.setContent(initialHtml);
            }, { height, width, resize: false })
        );
        tinyEditor = ed;
        return ed;
    }

    // ── Open: close the drawer first, then show the modal ────────
    function openEditor(doc, id) {
        const src = sourceEl(doc, id);
        console.log('[doc] edit', { doc, id, found: !!src });

        if (!src) return toast('error', 'Document not found. Reopen the request and try again.');

        current = { doc, id };
        titleEl.textContent = doc === 'cv' ? 'Edit AI-Rewritten CV' : 'Edit Cover Letter';

        const html = src.innerHTML;
        const show = async () => {
            bsModal.show();
            await ensureEditor(html);
        };

        if (drawerEl.classList.contains('show')) {
            drawerEl.addEventListener('hidden.bs.offcanvas', show, { once: true });
            bootstrap.Offcanvas.getInstance(drawerEl)?.hide();
        } else {
            show();
        }
    }

    // ── Save ─────────────────────────────────────────────────────
    saveBtn.addEventListener('click', async () => {
        if (!current) return;

        const content = (tinyEditor ? tinyEditor.getContent() : textarea.value).trim();
        const textLen = content.replace(/<[^>]*>/g, '').trim().length;
        console.log('[doc] save', { ...current, textLen });

        if (textLen < 20) return toast('error', 'The document is too short to save.');

        const label = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';

        try {
            const res = await fetch(`/admin/cv-review-requests/${current.id}/save-document`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ doc: current.doc, content }),
            });
            const raw = await res.text();
            console.log('[doc] save response', res.status, raw.slice(0, 300));

            let data;
            try { data = JSON.parse(raw); }
            catch { return toast('error', `Server returned HTTP ${res.status}. Check the console.`); }

            if (!data.success) {
                const msg = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
                return toast('error', msg || 'Save failed');
            }

            const id = current.id;
            bsModal.hide();
            toast('success', 'Saved. The PDF will use this version.');
            setTimeout(() => window.openDetail(id), 300);
        } catch (err) {
            console.error('[doc] save error', err);
            toast('error', 'Network error: ' + err.message);
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = label;
        }
    });

    // ── Destroy the editor on close, so the next open starts clean ──
    modalEl.addEventListener('hidden.bs.modal', () => {
        if (tinyEditor) {
            tinyEditor.remove();
            tinyEditor = null;
        }
    });

    // ── Copy ─────────────────────────────────────────────────────
    async function copy(doc, id) {
        const src = sourceEl(doc, id);
        console.log('[doc] copy', { doc, id, found: !!src });
        if (!src) return toast('error', 'Document not found.');

        const text = (src.textContent || '').replace(/\u00a0/g, ' ').trim();
        if (!text) return toast('error', 'The document is empty.');

        try {
            if (navigator.clipboard && window.ClipboardItem) {
                await navigator.clipboard.write([
                    new ClipboardItem({
                        'text/html':  new Blob([src.innerHTML], { type: 'text/html' }),
                        'text/plain': new Blob([text], { type: 'text/plain' }),
                    }),
                ]);
                return toast('success', 'Copied with formatting.');
            }
        } catch (err) {
            console.warn('[doc] rich copy failed', err);
        }

        try {
            await navigator.clipboard.writeText(text);
            return toast('success', 'Copied as plain text.');
        } catch (err) {
            console.warn('[doc] writeText failed', err);
        }

        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0;';
        document.body.appendChild(ta);
        ta.select();
        const ok = document.execCommand('copy');
        ta.remove();
        ok ? toast('success', 'Copied as plain text.')
           : toast('error', 'Browser blocked copying. Select the text and press Ctrl+C.');
    }

    /* ── Edit submitted answers: toggle view <-> edit ───────────── */
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-action="edit-answers"]')) {
            e.preventDefault();
            document.getElementById('answersViewWrap')?.classList.add('d-none');
            document.getElementById('answersEditWrap')?.classList.remove('d-none');
        }
        if (e.target.closest('[data-action="cancel-edit-answers"]')) {
            e.preventDefault();
            document.getElementById('answersEditWrap')?.classList.add('d-none');
            document.getElementById('answersViewWrap')?.classList.remove('d-none');
        }
    });

    /* ── Edit submitted answers: save ───────────────────────────── */
    document.addEventListener('submit', async function (e) {
        const form = e.target;
        if (form.id !== 'adminAnswersEditForm') return;
        e.preventDefault();

        const id = form.dataset.id;
        const answers = {};
        for (const [k, v] of new FormData(form).entries()) {
            const m = k.match(/^answers\[(.+)\]$/);
            if (m) answers[m[1]] = v;
        }

        if (!Object.values(answers).some(v => v.trim() !== '')) {
            window.showToast?.('error', 'Please keep at least one answer.');
            return;
        }

        const btn = document.getElementById('saveAnswersEditBtn');
        window.showButtonSpinner?.(btn);

        try {
            const res = await fetch(`/admin/cv-review-requests/${id}/update-answers`, {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': CV_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ answers }),
            });
            const data = await res.json();

            if (!data.success) {
                window.showToast?.('error', data.message || 'Save failed');
                window.hideButtonSpinner?.(btn);
                return;
            }

            window.showToast?.('success', data.message);
            setTimeout(() => { window.openDetail(id); loadRequests(); }, 400);
        } catch (err) {
            window.showToast?.('error', 'Network error: ' + err.message);
            window.hideButtonSpinner?.(btn);
        }
    });

    // ── Delegated clicks (drawer buttons only) ───────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        if (btn.dataset.action === 'doc-edit') {
            e.preventDefault();
            openEditor(btn.dataset.doc, btn.dataset.requestId);
        } else if (btn.dataset.action === 'doc-copy') {
            e.preventDefault();
            copy(btn.dataset.doc, btn.dataset.requestId);
        }
    });

    console.log('[doc] ready');
})();
</script>
@endpush