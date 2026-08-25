@extends('layouts.admin')

@section('title', 'Employee Management')
@section('page_title', 'Employee Management')

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
    <li class="breadcrumb-item text-muted">Employees</li>
@endsection

@section('content')
@can('view employees')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Search -->
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Employees..." />
                </div>
                
                <!-- Status Filter -->
                <div>
                    <select id="filterStatus" class="form-select form-select-solid w-150px">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <!-- Department Filter -->
                <div>
                    <select id="filterDepartment" class="form-select form-select-solid w-180px">
                        <option value="">All Departments</option>
                    </select>
                </div>

                <!-- Employee Type Filter -->
                <div>
                    <select id="filterType" class="form-select form-select-solid w-150px">
                        <option value="">All Types</option>
                        <option value="full_time">Full Time</option>
                        <option value="part_time">Part Time</option>
                        <option value="contract">Contract</option>
                        <option value="intern">Intern</option>
                    </select>
                </div>
            </div>
        </div>
        @can('create employees')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary me-3" data-bs-toggle="modal" data-bs-target="#kt_modal_add_employee">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> New Employee
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
                                <i class="ki-duotone ki-users fs-2x text-primary">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Total Employees</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="totalEmployees">0</span>
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
                                <span class="text-gray-600 fw-semibold">Active</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="activeEmployees">0</span>
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
                            <div class="symbol symbol-50px symbol-circle bg-light-danger me-3">
                                <i class="ki-duotone ki-cross-circle fs-2x text-danger">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Inactive</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="inactiveEmployees">0</span>
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
                                <i class="ki-duotone ki-dollar fs-2x text-info">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Total Salary (UGX)</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="totalSalary">0</span>
                                    <!-- <span class="text-muted ms-2">UGX</span> -->
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
            <p class="mt-3 text-muted">Loading employees...</p>
        </div>
        
        <!-- Table Container -->
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-150px">Employee</th>
                            <th class="min-w-150px">Job Title</th>
                            <th class="min-w-120px">Department</th>
                            <th class="min-w-100px">Type</th>
                            <th class="min-w-100px">Hire Date</th>
                            <th class="min-w-120px text-end">Salary</th>
                            <th class="min-w-100px">Status</th>
                            <th class="text-end min-w-150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="employeesTableBody"></tbody>
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
            <p class="text-muted">No employees found.</p>
        </div>
    </div>
</div>

<!-- Add Employee Modal -->
<div class="modal fade" id="kt_modal_add_employee" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">New Employee</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addEmployeeForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">User</label>
                            <select class="form-select form-select-solid" name="user_id" id="add_user_id" required>
                                <option value="">Select User</option>
                            </select>
                            <div class="form-text text-muted mt-1">Select a user to convert to employee</div>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="add_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Job Title</label>
                            <input type="text" class="form-control form-control-solid" name="job_title" placeholder="e.g., Operations Officer" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Employee Type</label>
                            <select class="form-select form-select-solid" name="employee_type" id="add_employee_type" required>
                                <option value="full_time">Full Time</option>
                                <option value="part_time">Part Time</option>
                                <option value="contract">Contract</option>
                                <option value="intern">Intern</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Hire Date</label>
                            <input type="date" class="form-control form-control-solid" name="hire_date" value="{{ date('Y-m-d') }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Salary</label>
                            <div class="input-group">
                                <span class="input-group-text" id="add_currency_symbol">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="salary" placeholder="0.00" required />
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Salary Type</label>
                            <select class="form-select form-select-solid" name="salary_type" required>
                                <option value="fixed">Fixed Salary</option>
                                <option value="hourly">Hourly Rate</option>
                                <option value="commission">Commission Based</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Recurring Day</label>
                            <input type="number" class="form-control form-control-solid" name="recurring_day" placeholder="Day of month (1-31)" min="1" max="31" />
                            <div class="form-text text-muted">Day of month when salary is paid</div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_salary_recurring" checked />
                                <span class="form-check-label fw-semibold">Recurring Salary</span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info d-flex align-items-center p-3">
                                <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                                <div class="fs-7">Phantom equity units will be assigned based on job title</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addEmployeeBtn">
                            <span class="indicator-label">Create Employee</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="kt_modal_edit_employee" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Employee</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editEmployeeForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="employee_id" id="edit_employee_id">
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Employee</label>
                            <input type="text" class="form-control form-control-solid" id="edit_employee_name" disabled />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="edit_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Job Title</label>
                            <input type="text" class="form-control form-control-solid" name="job_title" id="edit_job_title" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Employee Type</label>
                            <select class="form-select form-select-solid" name="employee_type" id="edit_employee_type" required>
                                <option value="full_time">Full Time</option>
                                <option value="part_time">Part Time</option>
                                <option value="contract">Contract</option>
                                <option value="intern">Intern</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Hire Date</label>
                            <input type="date" class="form-control form-control-solid" name="hire_date" id="edit_hire_date" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="salary" id="edit_salary" required />
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Salary Type</label>
                            <select class="form-select form-select-solid" name="salary_type" id="edit_salary_type" required>
                                <option value="fixed">Fixed Salary</option>
                                <option value="hourly">Hourly Rate</option>
                                <option value="commission">Commission Based</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Recurring Day</label>
                            <input type="number" class="form-control form-control-solid" name="recurring_day" id="edit_recurring_day" placeholder="Day of month (1-31)" min="1" max="31" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_salary_recurring" id="edit_is_salary_recurring" />
                                <span class="form-check-label fw-semibold">Recurring Salary</span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info d-flex align-items-center p-3">
                                <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                                <div class="fs-7">Phantom equity units: <span id="edit_phantom_units">0</span></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editEmployeeBtn">
                            <span class="indicator-label">Update Employee</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Employee Modal -->
<div class="modal fade" id="kt_modal_view_employee" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Employee Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body p-7" id="viewEmployeeContent">
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
@endcan
@endsection

@push('scripts')
<script>
    
let currentPage = 1;
let currentSearch = '';
let currentStatus = '';
let currentDepartment = '';
let currentType = '';

// Load form data
function loadFormData() {
    fetch('{{ route("admin.employees.form-data") }}')
        .then(res => res.json())
        .then(data => {
            // Users
            const userOptions = '<option value="">Select User</option>' + 
                data.users.map(u => `<option value="${u.id}">${u.name} (${u.email})</option>`).join('');
            document.getElementById('add_user_id').innerHTML = userOptions;
            
            // Departments
            const deptOptions = '<option value="">Select Department</option>' + 
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('add_department_id').innerHTML = deptOptions;
            document.getElementById('edit_department_id').innerHTML = deptOptions;
            
            // Filter departments
            const filterOptions = '<option value="">All Departments</option>' + 
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('filterDepartment').innerHTML = filterOptions;
        })
        .catch(err => console.error('Error loading form data:', err));
}

// Load employees
function loadEmployees() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `{{ route("admin.employees.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentStatus) url += `&status=${currentStatus}`;
    if (currentDepartment) url += `&department_id=${currentDepartment}`;
    if (currentType) url += `&employee_type=${currentType}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderEmployeesTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
                updateSummary(data.summary);
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load employees');
        });
}

function updateSummary(summary) {
    document.getElementById('totalEmployees').innerHTML = summary.total || 0;
    document.getElementById('activeEmployees').innerHTML = summary.active || 0;
    document.getElementById('inactiveEmployees').innerHTML = summary.inactive || 0;
    // NO DIVISION BY 100 - the salary is already in the correct format
    document.getElementById('totalSalary').innerHTML = formatCurrency(summary.total_salary);
}

function renderEmployeesTable(employees) {
    const tbody = document.getElementById('employeesTableBody');
    tbody.innerHTML = '';
    
    employees.forEach(emp => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${emp.id}</span>`;
        row.insertCell(1).innerHTML = `
            <div class="d-flex align-items-center">
                <div class="symbol symbol-40px symbol-circle me-3">
                    <span class="symbol-label bg-light-primary text-primary fs-6 fw-bold">${emp.first_name?.[0] || 'E'}</span>
                </div>
                <div>
                    <div class="fw-bold">${escapeHtml(emp.name)}</div>
                    <div class="text-muted fs-7">${emp.email}</div>
                </div>
            </div>
        `;
        row.insertCell(2).innerHTML = `<span class="fw-bold">${escapeHtml(emp.job_title)}</span>`;
        row.insertCell(3).innerHTML = emp.department || '-';
        row.insertCell(4).innerHTML = `<span class="badge badge-light-primary">${emp.employee_type_label}</span>`;
        row.insertCell(5).innerHTML = emp.hire_date ? formatDate(emp.hire_date) : '-';
        row.insertCell(6).innerHTML = `<span class="fw-bold text-success text-end d-block">${emp.formatted_salary || '0'}</span>`;
        row.insertCell(7).innerHTML = emp.status_badge;
        row.insertCell(8).innerHTML = getActionButtons(emp);
    });
}

function getActionButtons(emp) {
    let buttons = `
        <button class="btn btn-sm btn-icon btn-light" onclick="viewEmployee(${emp.id})" title="View">
            <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
        </button>
        <button class="btn btn-sm btn-icon btn-light" onclick="editEmployee(${emp.id})" title="Edit">
            <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
        </button>
        <button class="btn btn-sm btn-icon btn-light" onclick="toggleEmployeeStatus(${emp.id}, ${emp.is_active})" title="${emp.is_active ? 'Deactivate' : 'Activate'}">
            <i class="ki-duotone ki-${emp.is_active ? 'disconnect' : 'check'} fs-3 ${emp.is_active ? 'text-warning' : 'text-success'}"><span class="path1"></span><span class="path2"></span></i>
        </button>
        <button class="btn btn-sm btn-icon btn-light" onclick="deleteEmployee(${emp.id})" title="Delete">
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
    info.innerHTML = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} entries`;
    
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
    if (page !== currentPage && page > 0) { currentPage = page; loadEmployees(); }
};

// View Employee
window.viewEmployee = function(id) {
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_employee'));
    const content = document.getElementById('viewEmployeeContent');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();
    
    fetch(`/admin/employees/${id}`)
        .then(res => res.json())
        .then(data => {
            let html = `
                <div class="d-flex align-items-center mb-5">
                    <div class="symbol symbol-60px symbol-circle me-5">
                        <span class="symbol-label bg-light-primary text-primary fs-1 fw-bold">${data.first_name?.[0] || 'E'}</span>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold">${data.name}</div>
                        <div class="text-muted">${data.email}</div>
                    </div>
                </div>
                <div class="separator my-5"></div>
                <div class="row mb-5">
                    <div class="col-md-6"><span class="text-muted">Job Title</span><div class="fw-bold">${data.job_title}</div></div>
                    <div class="col-md-6"><span class="text-muted">Employee Type</span><div class="fw-bold">${data.employee_type_label}</div></div>
                </div>
                <div class="row mb-5">
                    <div class="col-md-6"><span class="text-muted">Department</span><div class="fw-bold">${data.department || 'N/A'}</div></div>
                    <div class="col-md-6"><span class="text-muted">Status</span><div>${data.is_active ? '<span class="badge badge-light-success">Active</span>' : '<span class="badge badge-light-danger">Inactive</span>'}</div></div>
                </div>
                <div class="row mb-5">
                    <div class="col-md-6"><span class="text-muted">Hire Date</span><div class="fw-bold">${formatDate(data.hire_date)}</div></div>
                    <div class="col-md-6"><span class="text-muted">Salary</span><div class="fw-bold text-success">${data.formatted_salary || '0'}</div></div>
                </div>
            `;
            
            // Add salary details if available
            if (data.employee_salary) {
                html += `
                    <div class="separator my-5"></div>
                    <div class="row mb-5">
                        <div class="col-md-6"><span class="text-muted">Salary Type</span><div class="fw-bold">${data.salary_type}</div></div>
                        <div class="col-md-6"><span class="text-muted">Recurring</span><div class="fw-bold">${data.is_salary_recurring ? 'Yes' : 'No'}</div></div>
                    </div>
                    <div class="row mb-5">
                        <div class="col-md-4"><span class="text-muted">Phantom Equity Units</span><div class="fw-bold">${data.employee_salary.phantom_equity_units || 0}</div></div>
                        <div class="col-md-4"><span class="text-muted">Vested Units</span><div class="fw-bold">${data.employee_salary.vested_units || 0}</div></div>
                        <div class="col-md-4"><span class="text-muted">Vesting %</span><div class="fw-bold">${data.employee_salary.units_vested_percentage || 0}%</div></div>
                    </div>
                    ${data.employee_salary.salary_structure ? `
                        <div class="row mb-5">
                            <div class="col-md-12"><span class="text-muted">Salary Structure</span><div class="fw-bold">${data.employee_salary.salary_structure.job_title} (${data.employee_salary.salary_structure.role_code})</div></div>
                        </div>
                    ` : ''}
                `;
            }
            
            content.innerHTML = html;
        })
        .catch(err => {
            content.innerHTML = '<div class="text-center text-danger py-5">Failed to load employee details</div>';
        });
};

// Edit Employee - Updated
window.editEmployee = function(id) {
    fetch(`/admin/employees/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('edit_employee_id').value = data.id;
        document.getElementById('edit_employee_name').value = data.name;
        document.getElementById('edit_department_id').value = data.department_id || '';
        document.getElementById('edit_job_title').value = data.job_title;
        document.getElementById('edit_employee_type').value = data.employee_type;
        document.getElementById('edit_hire_date').value = data.hire_date || '';
        // The salary is already in the correct format (no cents conversion needed for UGX)
        document.getElementById('edit_salary').value = data.salary || 0;
        document.getElementById('edit_salary_type').value = data.salary_type || 'fixed';
        document.getElementById('edit_recurring_day').value = data.recurring_day || '';
        document.getElementById('edit_is_salary_recurring').checked = data.is_salary_recurring || false;
        document.getElementById('edit_phantom_units').innerHTML = data.employee_salary?.phantom_equity_units || 0;
        
        new bootstrap.Modal(document.getElementById('kt_modal_edit_employee')).show();
    })
    .catch(err => {
        console.error('Error loading employee:', err);
        window.showToast('error', 'Failed to load employee details');
    });
};

// Toggle Employee Status - Simpler version with POST
window.toggleEmployeeStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this employee?`)) {
        fetch(`/admin/employees/${id}/toggle-status`, {
            method: 'POST', // Use POST
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ 
                is_active: !current,
                _method: 'PUT' // Tell Laravel to treat as PUT
            })
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
                loadEmployees(); 
            } else {
                window.showToast('error', data.message || 'Failed to toggle status');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            window.showToast('error', err.message || 'Failed to toggle employee status');
        });
    }
};

// Delete Employee
window.deleteEmployee = function(id) {
    if (confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
        fetch(`/admin/employees/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadEmployees(); }
            else window.showToast('error', data.message);
        });
    }
};


// Submit Add Employee - Fixed (NO MULTIPLICATION)
document.getElementById('addEmployeeForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addEmployeeBtn');
    window.showButtonSpinner(btn);
    
    const formData = new FormData(this);
    
    // Fix checkbox value - explicitly set it
    const isRecurring = document.querySelector('input[name="is_salary_recurring"]');
    if (isRecurring) {
        formData.set('is_salary_recurring', isRecurring.checked ? '1' : '0');
    }
    
    // NO MULTIPLICATION - store exactly what the user entered
    // The salary input value is already in the correct format
    
    fetch('{{ route("admin.employees.store") }}', {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            return res.json().then(data => {
                throw data;
            });
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_employee'))?.hide();
            this.reset();
            loadEmployees();
            loadFormData();
        } else {
            window.showToast('error', data.message || 'Failed to create employee');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to create employee';
        if (err.errors) {
            errorMessage = Object.values(err.errors).flat().join('\n');
        } else if (err.message) {
            errorMessage = err.message;
        }
        window.showToast('error', errorMessage);
    })
    .finally(() => window.hideButtonSpinner(btn));
});

// Submit Edit Employee - Fixed (NO MULTIPLICATION)
document.getElementById('editEmployeeForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const btn = document.getElementById('editEmployeeBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_employee_id').value;
    
    const formData = new FormData(this);
    
    // Fix checkbox value
    const isRecurring = document.querySelector('#edit_is_salary_recurring');
    if (isRecurring) {
        formData.set('is_salary_recurring', isRecurring.checked ? '1' : '0');
    }
    
    // NO MULTIPLICATION - store exactly what the user entered
    // The salary input value is already in the correct format
    
    fetch(`/admin/employees/${id}`, {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_employee'));
            if (modal) modal.hide();
            loadEmployees();
        } else {
            window.showToast('error', data.message || 'Failed to update employee');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to update employee';
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
    loadEmployees();
    
    // Search
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadEmployees();
            }, 500);
        });
    }
    
    // Filters
    document.getElementById('filterStatus')?.addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadEmployees();
    });
    
    document.getElementById('filterDepartment')?.addEventListener('change', function() {
        currentDepartment = this.value;
        currentPage = 1;
        loadEmployees();
    });
    
    document.getElementById('filterType')?.addEventListener('change', function() {
        currentType = this.value;
        currentPage = 1;
        loadEmployees();
    });
});

// Utility functions
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
}

function formatCurrency(amount) {
    return Number(amount).toLocaleString(undefined, { 
        minimumFractionDigits: 0, 
        maximumFractionDigits: 0 
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<style>
.spinning {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
@endpush