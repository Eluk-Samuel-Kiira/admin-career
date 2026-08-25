@extends('layouts.admin')

@section('title', 'Performance Reviews')
@section('page_title', 'Performance Reviews')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Compensation</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Performance Reviews</li>
@endsection

@section('content')
@can('view performance reviews')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Search -->
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Reviews..." />
                </div>

                <!-- Status Filter -->
                <div>
                    <select id="filterStatus" class="form-select form-select-solid w-150px">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="approved">Approved</option>
                    </select>
                </div>

                <!-- Review Period Filter -->
                <div>
                    <select id="filterPeriod" class="form-select form-select-solid w-150px">
                        <option value="">All Periods</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="annual">Annual</option>
                    </select>
                </div>

                <!-- Department Filter -->
                <div>
                    <select id="filterDepartment" class="form-select form-select-solid w-180px">
                        <option value="">All Departments</option>
                    </select>
                </div>

                <!-- Employee Filter -->
                <div>
                    <select id="filterEmployee" class="form-select form-select-solid w-200px">
                        <option value="">All Employees</option>
                    </select>
                </div>
            </div>
        </div>
        @can('create performance reviews')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_review">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> New Review
            </button>
        </div>
        @endcan
    </div>

    <div class="card-body pt-0">
        <!-- Summary Cards -->
        <div class="row g-5 g-xl-10 mb-5">
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-primary me-3">
                                <i class="ki-duotone ki-star fs-2x text-primary">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Total Reviews</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="totalReviews">0</span>
                                    <span class="text-muted ms-2">Records</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-warning me-3">
                                <i class="ki-duotone ki-time fs-2x text-warning">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Pending</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="pendingReviews">0</span>
                                    <span class="text-muted ms-2">Records</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-success me-3">
                                <i class="ki-duotone ki-check-circle fs-2x text-success">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Approved</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="approvedReviews">0</span>
                                    <span class="text-muted ms-2">Records</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-info me-3">
                                <i class="ki-duotone ki-chart fs-2x text-info">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Avg Score</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="avgScore">0</span>
                                    <span class="text-muted ms-2">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading performance reviews...</p>
        </div>

        <!-- Table Container -->
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-150px">Employee</th>
                            <th class="min-w-120px">Period</th>
                            <th class="min-w-100px">Date</th>
                            <th class="min-w-80px text-center">Score</th>
                            <th class="min-w-120px">Rating</th>
                            <th class="min-w-100px">Bonus</th>
                            <th class="min-w-100px">Status</th>
                            <th class="text-end min-w-150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reviewsTableBody"></tbody>
                </table>
            </div>

            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav><ul class="pagination m-0" id="pagination"></ul></nav>
            </div>
        </div>

        <div id="noDataMessage" class="text-center py-10 d-none">
            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <p class="text-muted">No performance reviews found.</p>
        </div>
    </div>
</div>

<!-- Add Review Modal -->
<div class="modal fade" id="kt_modal_add_review" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">New Performance Review</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addReviewForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Employee</label>
                            <select class="form-select form-select-solid" name="user_id" id="add_user_id" required>
                                <option value="">Select Employee</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="add_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Review Period</label>
                            <select class="form-select form-select-solid" name="review_period" id="add_review_period" required>
                                <option value="">Select Period</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="annual">Annual</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Review Date</label>
                            <input type="date" class="form-control form-control-solid" name="review_date" value="{{ date('Y-m-d') }}" required />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Score (%)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="score" placeholder="0-100" required min="0" max="100" />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Overall Rating</label>
                            <select class="form-select form-select-solid" name="overall_rating" id="add_overall_rating" required>
                                <option value="">Select Rating</option>
                                <option value="excellent">Excellent</option>
                                <option value="good">Good</option>
                                <option value="average">Average</option>
                                <option value="below_average">Below Average</option>
                                <option value="poor">Poor</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Reviewer</label>
                            <select class="form-select form-select-solid" name="reviewer_id" id="add_reviewer_id">
                                <option value="">Select Reviewer</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Revenue Contribution</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="revenue_contribution" placeholder="0-100" min="0" max="100" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Client Satisfaction</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="client_satisfaction" placeholder="0-100" min="0" max="100" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Reporting Discipline</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="reporting_discipline" placeholder="0-100" min="0" max="100" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Innovation Score</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="innovation_score" placeholder="0-100" min="0" max="100" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Teamwork Score</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="teamwork_score" placeholder="0-100" min="0" max="100" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Quality Score</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="quality_score" placeholder="0-100" min="0" max="100" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="bonus_eligible" />
                                <span class="form-check-label fw-semibold">Bonus Eligible</span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="promotion_recommended" />
                                <span class="form-check-label fw-semibold">Promotion Recommended</span>
                            </label>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-12">
                            <label class="required fw-semibold fs-6 mb-2">Status</label>
                            <select class="form-select form-select-solid" name="status" id="add_status" required>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="approved">Approved</option>
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Recommendations</label>
                        <textarea class="form-control form-control-solid" name="recommendations" rows="3" placeholder="Recommendations and feedback..."></textarea>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addReviewBtn">
                            <span class="indicator-label">Create Review</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Review Modal -->
<div class="modal fade" id="kt_modal_edit_review" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Performance Review</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editReviewForm">
                    @csrf
                    <input type="hidden" name="review_id" id="edit_review_id">
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Employee</label>
                            <input type="text" class="form-control form-control-solid" id="edit_employee_name" disabled />
                            <input type="hidden" name="user_id" id="edit_user_id" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="edit_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Review Period</label>
                            <select class="form-select form-select-solid" name="review_period" id="edit_review_period" required>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="annual">Annual</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Review Date</label>
                            <input type="date" class="form-control form-control-solid" name="review_date" id="edit_review_date" required />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Score (%)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="score" id="edit_score" required min="0" max="100" />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Overall Rating</label>
                            <select class="form-select form-select-solid" name="overall_rating" id="edit_overall_rating" required>
                                <option value="excellent">Excellent</option>
                                <option value="good">Good</option>
                                <option value="average">Average</option>
                                <option value="below_average">Below Average</option>
                                <option value="poor">Poor</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Reviewer</label>
                            <select class="form-select form-select-solid" name="reviewer_id" id="edit_reviewer_id">
                                <option value="">Select Reviewer</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Revenue Contribution</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="revenue_contribution" id="edit_revenue_contribution" min="0" max="100" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Client Satisfaction</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="client_satisfaction" id="edit_client_satisfaction" min="0" max="100" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Reporting Discipline</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="reporting_discipline" id="edit_reporting_discipline" min="0" max="100" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Innovation Score</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="innovation_score" id="edit_innovation_score" min="0" max="100" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Teamwork Score</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="teamwork_score" id="edit_teamwork_score" min="0" max="100" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Quality Score</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="quality_score" id="edit_quality_score" min="0" max="100" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="bonus_eligible" id="edit_bonus_eligible" />
                                <span class="form-check-label fw-semibold">Bonus Eligible</span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="promotion_recommended" id="edit_promotion_recommended" />
                                <span class="form-check-label fw-semibold">Promotion Recommended</span>
                            </label>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-12">
                            <label class="required fw-semibold fs-6 mb-2">Status</label>
                            <select class="form-select form-select-solid" name="status" id="edit_status" required>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="approved">Approved</option>
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Recommendations</label>
                        <textarea class="form-control form-control-solid" name="recommendations" id="edit_recommendations" rows="3"></textarea>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editReviewBtn">
                            <span class="indicator-label">Update Review</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Review Modal -->
<div class="modal fade" id="kt_modal_view_review" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Review Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body p-7" id="viewReviewContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Review Modal -->
<div class="modal fade" id="kt_modal_approve_review" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Approve Review</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <div class="alert alert-info d-flex align-items-center mb-7">
                    <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                    <div>
                        <strong>Employee: <span id="approve_review_employee"></span></strong><br>
                        <span class="text-muted">Score: <span id="approve_review_score"></span>%</span>
                    </div>
                </div>
                <div class="text-center pt-15">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="confirmApproveReview()">
                        <span class="indicator-label">Approve Review</span>
                        <span class="indicator-progress">Processing... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
let currentPage = 1;
let currentSearch = '';
let currentStatus = '';
let currentPeriod = '';
let currentDepartment = '';
let currentEmployee = '';
let approveReviewId = null;

// Utility functions
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load form data
function loadFormData() {
    fetch('{{ route("admin.performance-reviews.form-data") }}')
        .then(res => res.json())
        .then(data => {
            // Employees
            const empOptions = '<option value="">Select Employee</option>' +
                data.users.map(u => `<option value="${u.id}">${u.name} (${u.email})</option>`).join('');
            document.getElementById('add_user_id').innerHTML = empOptions;

            // Reviewers
            const reviewerOptions = '<option value="">Select Reviewer</option>' +
                data.reviewers.map(r => `<option value="${r.id}">${r.name} (${r.email})</option>`).join('');
            document.getElementById('add_reviewer_id').innerHTML = reviewerOptions;
            document.getElementById('edit_reviewer_id').innerHTML = reviewerOptions;

            // Departments
            const deptOptions = '<option value="">Select Department</option>' +
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('add_department_id').innerHTML = deptOptions;
            document.getElementById('edit_department_id').innerHTML = deptOptions;

            // Filter Departments
            const filterDeptOptions = '<option value="">All Departments</option>' +
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('filterDepartment').innerHTML = filterDeptOptions;

            // Filter Employees
            const filterEmpOptions = '<option value="">All Employees</option>' +
                data.users.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
            document.getElementById('filterEmployee').innerHTML = filterEmpOptions;
        })
        .catch(err => console.error('Error loading form data:', err));
}

// Load reviews
function loadReviews() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');

    if (spinner) spinner.classList.remove('d-none');
    if (table) table.classList.add('d-none');
    if (noData) noData.classList.add('d-none');
    if (pagination) pagination.classList.add('d-none');

    let url = `{{ route("admin.performance-reviews.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentStatus) url += `&status=${currentStatus}`;
    if (currentPeriod) url += `&review_period=${currentPeriod}`;
    if (currentDepartment) url += `&department_id=${currentDepartment}`;
    if (currentEmployee) url += `&user_id=${currentEmployee}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (spinner) spinner.classList.add('d-none');
            if (data.data.length === 0) {
                if (noData) noData.classList.remove('d-none');
            } else {
                if (table) table.classList.remove('d-none');
                renderReviewsTable(data.data);
                renderPagination(data);
                if (pagination) pagination.classList.remove('d-none');
                updateSummary(data.summary);
            }
        })
        .catch(err => {
            if (spinner) spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load reviews');
        });
}

function updateSummary(summary) {
    document.getElementById('totalReviews').innerHTML = summary.total_reviews || 0;
    document.getElementById('pendingReviews').innerHTML = summary.pending_count || 0;
    document.getElementById('approvedReviews').innerHTML = summary.approved_count || 0;
    document.getElementById('avgScore').innerHTML = summary.avg_score ? Math.round(summary.avg_score) : 0;
}

function renderReviewsTable(reviews) {
    const tbody = document.getElementById('reviewsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    reviews.forEach(review => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${review.id}</span>`;
        row.insertCell(1).innerHTML = review.user ? `<div class="fw-bold">${escapeHtml(review.user.name)}</div>` : '-';
        row.insertCell(2).innerHTML = `<span class="badge badge-light-primary">${review.review_period_label}</span>`;
        row.insertCell(3).innerHTML = formatDate(review.review_date);
        row.insertCell(4).innerHTML = `<span class="fw-bold text-center d-block">${review.score}%</span>`;
        row.insertCell(5).innerHTML = review.overall_rating_badge;
        row.insertCell(6).innerHTML = review.bonus_badge;
        row.insertCell(7).innerHTML = review.status_badge;
        row.insertCell(8).innerHTML = getActionButtons(review);
    });
}

function getActionButtons(review) {
    let buttons = `
        <button class="btn btn-sm btn-icon btn-light" onclick="viewReview(${review.id})" title="View">
            <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
        </button>
        <button class="btn btn-sm btn-icon btn-light" onclick="editReview(${review.id})" title="Edit">
            <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
        </button>
    `;

    if (review.status === 'pending' || review.status === 'completed') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="approveReview(${review.id})" title="Approve">
                <i class="ki-duotone ki-check-circle fs-3 text-success"><span class="path1"></span><span class="path2"></span></i>
            </button>
        `;
    }

    buttons += `
        <button class="btn btn-sm btn-icon btn-light" onclick="deleteReview(${review.id})" title="Delete">
            <i class="ki-duotone ki-trash fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
        </button>
    `;

    return `<div class="d-flex justify-content-end gap-2">${buttons}</div>`;
}

function renderPagination(data) {
    const el = document.getElementById('pagination');
    const info = document.getElementById('paginationInfo');
    if (!el) return;

    el.innerHTML = '';
    if (info) {
        info.innerHTML = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} entries`;
    }

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
    let start = Math.max(1, data.current_page - 2);
    let end = Math.min(data.last_page, data.current_page + 2);
    if (start > 1) addPage(1, '1');
    if (start > 2) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    for (let i = start; i <= end; i++) addPage(i, i, i === data.current_page);
    if (end < data.last_page - 1) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    if (end < data.last_page) addPage(data.last_page, data.last_page);
    addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
}

window.changePage = function(page) {
    if (page !== currentPage && page > 0) {
        currentPage = page;
        loadReviews();
    }
};

// View Review
window.viewReview = function(id) {
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_review'));
    const content = document.getElementById('viewReviewContent');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();

    fetch(`/admin/performance-reviews/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        let html = `
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Employee</span><div class="fw-bold fs-4">${data.user?.full_name || data.user?.name || 'N/A'}</div></div>
                <div class="col-md-6"><span class="text-muted">Department</span><div class="fw-bold">${data.department?.name || 'N/A'}</div></div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-4"><span class="text-muted">Review Period</span><div class="fw-bold">${data.review_period}</div></div>
                <div class="col-md-4"><span class="text-muted">Review Date</span><div class="fw-bold">${formatDate(data.review_date)}</div></div>
                <div class="col-md-4"><span class="text-muted">Status</span><div>${data.status_badge}</div></div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-4"><span class="text-muted">Score</span><div class="fw-bold fs-2">${data.score}%</div></div>
                <div class="col-md-4"><span class="text-muted">Rating</span><div>${data.overall_rating_badge}</div></div>
                <div class="col-md-4"><span class="text-muted">Reviewer</span><div class="fw-bold">${data.reviewer?.full_name || data.reviewer?.name || 'N/A'}</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-3"><span class="text-muted">Revenue</span><div class="fw-bold">${data.revenue_contribution || 'N/A'}%</div></div>
                <div class="col-md-3"><span class="text-muted">Client Satisfaction</span><div class="fw-bold">${data.client_satisfaction || 'N/A'}%</div></div>
                <div class="col-md-3"><span class="text-muted">Reporting</span><div class="fw-bold">${data.reporting_discipline || 'N/A'}%</div></div>
                <div class="col-md-3"><span class="text-muted">Innovation</span><div class="fw-bold">${data.innovation_score || 'N/A'}%</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-4"><span class="text-muted">Teamwork</span><div class="fw-bold">${data.teamwork_score || 'N/A'}%</div></div>
                <div class="col-md-4"><span class="text-muted">Quality</span><div class="fw-bold">${data.quality_score || 'N/A'}%</div></div>
                <div class="col-md-4"><span class="text-muted">Attendance</span><div class="fw-bold">${data.attendance_score || 'N/A'}%</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Bonus Eligible</span><div>${data.bonus_eligible ? '<span class="badge badge-light-success">Yes</span>' : '<span class="badge badge-light-danger">No</span>'}</div></div>
                <div class="col-md-6"><span class="text-muted">Promotion Recommended</span><div>${data.promotion_recommended ? '<span class="badge badge-light-success">Yes</span>' : '<span class="badge badge-light-danger">No</span>'}</div></div>
            </div>
            ${data.recommendations ? `
                <div class="separator my-5"></div>
                <div class="row mb-5">
                    <div class="col-md-12"><span class="text-muted">Recommendations</span><div class="fw-bold">${escapeHtml(data.recommendations)}</div></div>
                </div>
            ` : ''}
            ${data.approved_by ? `
                <div class="row mb-5">
                    <div class="col-md-12"><span class="text-muted">Approved By</span><div class="fw-bold">${data.approver?.full_name || data.approver?.name || 'N/A'}</div></div>
                </div>
            ` : ''}
            <div class="row mt-3">
                <div class="col-md-12"><span class="text-muted">Created At</span><div class="fw-bold">${formatDate(data.created_at)}</div></div>
            </div>
        `;

        content.innerHTML = html;
    })
    .catch(err => {
        content.innerHTML = '<div class="text-center text-danger py-5">Failed to load review details</div>';
    });
};

// Edit Review
window.editReview = function(id) {
    fetch(`/admin/performance-reviews/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('edit_review_id').value = data.id;
        document.getElementById('edit_employee_name').value = data.user?.full_name || data.user?.name || '';
        document.getElementById('edit_user_id').value = data.user?.id || '';
        document.getElementById('edit_department_id').value = data.department?.id || '';
        document.getElementById('edit_review_period').value = data.review_period || 'monthly';
        document.getElementById('edit_review_date').value = data.review_date || '';
        document.getElementById('edit_score').value = data.score || 0;
        document.getElementById('edit_overall_rating').value = data.overall_rating || 'average';
        document.getElementById('edit_reviewer_id').value = data.reviewer?.id || '';
        document.getElementById('edit_revenue_contribution').value = data.revenue_contribution || '';
        document.getElementById('edit_client_satisfaction').value = data.client_satisfaction || '';
        document.getElementById('edit_reporting_discipline').value = data.reporting_discipline || '';
        document.getElementById('edit_innovation_score').value = data.innovation_score || '';
        document.getElementById('edit_teamwork_score').value = data.teamwork_score || '';
        document.getElementById('edit_quality_score').value = data.quality_score || '';
        document.getElementById('edit_bonus_eligible').checked = data.bonus_eligible || false;
        document.getElementById('edit_promotion_recommended').checked = data.promotion_recommended || false;
        document.getElementById('edit_status').value = data.status || 'pending';
        document.getElementById('edit_recommendations').value = data.recommendations || '';

        new bootstrap.Modal(document.getElementById('kt_modal_edit_review')).show();
    })
    .catch(err => {
        console.error('Error loading review:', err);
        window.showToast('error', 'Failed to load review details');
    });
};

// Approve Review
window.approveReview = function(id) {
    fetch(`/admin/performance-reviews/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        approveReviewId = id;
        document.getElementById('approve_review_employee').innerHTML = data.user?.full_name || data.user?.name || 'N/A';
        document.getElementById('approve_review_score').innerHTML = data.score || 0;
        new bootstrap.Modal(document.getElementById('kt_modal_approve_review')).show();
    })
    .catch(err => {
        console.error('Error loading review:', err);
        window.showToast('error', 'Failed to load review details');
    });
};

window.confirmApproveReview = function() {
    const btn = document.querySelector('#kt_modal_approve_review .btn-success');
    const label = btn.querySelector('.indicator-label');
    const progress = btn.querySelector('.indicator-progress');

    label.style.display = 'none';
    progress.style.display = 'inline-block';
    btn.disabled = true;

    fetch(`/admin/performance-reviews/${approveReviewId}/approve`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_approve_review'))?.hide();
            loadReviews();
        } else {
            window.showToast('error', data.message);
        }
    })
    .catch(err => window.showToast('error', 'Failed to approve review'))
    .finally(() => {
        label.style.display = 'inline-block';
        progress.style.display = 'none';
        btn.disabled = false;
    });
};

// Delete Review
window.deleteReview = function(id) {
    if (confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
        fetch(`/admin/performance-reviews/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadReviews();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to delete review'));
    }
};

// Submit Add Review
document.getElementById('addReviewForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = document.getElementById('addReviewBtn');
    window.showButtonSpinner(btn);

    const formData = new FormData(this);

    // Fix checkboxes
    const bonusEligible = document.querySelector('input[name="bonus_eligible"]');
    if (bonusEligible) {
        formData.set('bonus_eligible', bonusEligible.checked ? '1' : '0');
    }

    const promotionRecommended = document.querySelector('input[name="promotion_recommended"]');
    if (promotionRecommended) {
        formData.set('promotion_recommended', promotionRecommended.checked ? '1' : '0');
    }

    fetch('{{ route("admin.performance-reviews.store") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            return res.json().then(data => { throw data; });
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_review'));
            if (modal) modal.hide();
            this.reset();
            loadReviews();
            loadFormData();
        } else {
            window.showToast('error', data.message || 'Failed to create review');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to create review';
        if (err.errors) {
            errorMessage = Object.values(err.errors).flat().join('\n');
        } else if (err.message) {
            errorMessage = err.message;
        }
        window.showToast('error', errorMessage);
    })
    .finally(() => {
        window.hideButtonSpinner(btn);
    });

    return false;
});

// Submit Edit Review
document.getElementById('editReviewForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = document.getElementById('editReviewBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_review_id').value;

    const formData = new FormData(this);

    // Fix checkboxes
    const bonusEligible = document.querySelector('#edit_bonus_eligible');
    if (bonusEligible) {
        formData.set('bonus_eligible', bonusEligible.checked ? '1' : '0');
    }

    const promotionRecommended = document.querySelector('#edit_promotion_recommended');
    if (promotionRecommended) {
        formData.set('promotion_recommended', promotionRecommended.checked ? '1' : '0');
    }

    fetch(`/admin/performance-reviews/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            return res.json().then(data => { throw data; });
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_review'));
            if (modal) modal.hide();
            loadReviews();
        } else {
            window.showToast('error', data.message || 'Failed to update review');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to update review';
        if (err.errors) {
            errorMessage = Object.values(err.errors).flat().join('\n');
        } else if (err.message) {
            errorMessage = err.message;
        }
        window.showToast('error', errorMessage);
    })
    .finally(() => {
        window.hideButtonSpinner(btn);
    });

    return false;
});

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    loadFormData();
    loadReviews();

    // Search
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadReviews();
            }, 500);
        });
    }

    // Filters
    document.getElementById('filterStatus')?.addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadReviews();
    });

    document.getElementById('filterPeriod')?.addEventListener('change', function() {
        currentPeriod = this.value;
        currentPage = 1;
        loadReviews();
    });

    document.getElementById('filterDepartment')?.addEventListener('change', function() {
        currentDepartment = this.value;
        currentPage = 1;
        loadReviews();
    });

    document.getElementById('filterEmployee')?.addEventListener('change', function() {
        currentEmployee = this.value;
        currentPage = 1;
        loadReviews();
    });
});
</script>
@endpush