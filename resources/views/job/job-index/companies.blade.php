@extends('layouts.admin')

@section('title', 'Companies')
@section('page_title', 'Companies')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Jobs</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Companies</li>
@endsection

@section('content')

<style>
/* ===== SEARCHABLE SELECT STYLES ===== */
.searchable-select { position: relative; }
.searchable-select-dropdown {
    display: none;
    position: fixed;
    z-index: 2000;
    max-height: 220px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #e4e6ef;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
    margin-top: 2px;
    min-width: 200px;
}
.searchable-select-dropdown.show { display: block; }
.searchable-select-option {
    padding: 8px 14px;
    cursor: pointer;
    font-size: 13px;
    color: #3f4254;
}
.searchable-select-option:hover,
.searchable-select-option.active { background: #f5f8fa; }
.searchable-select-empty {
    padding: 8px 14px;
    color: #a1a5b7;
    font-size: 13px;
}

/* ===== RICH EDITOR STYLES ===== */
.rich-editor-wrapper { background: #fff; }
.rich-editor-toolbar { background: #f8f9fa !important; border-bottom: 1px solid #e5e7eb; }
.re-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 28px; padding: 0; border: 1px solid #dee2e6;
    border-radius: 4px; background: #fff; cursor: pointer; color: #495057;
    flex-shrink: 0; transition: background .1s, border-color .1s;
}
.re-btn:hover  { background: #e9ecef; border-color: #adb5bd; }
.re-btn:active { background: #dee2e6; }
.re-btn.active { background: #e9ecef; border-color: #adb5bd; }
.re-btn svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.re-btn-text { width: auto; padding: 0 8px; font-size: 12px; font-weight: 600; }
.re-btn-danger:hover { background: #fff5f5; color: #dc3545; border-color: #f5c2c7; }
.re-sep { width: 1px; height: 22px; background: #dee2e6; margin: 0 2px; flex-shrink: 0; }
.re-select {
    height: 28px; font-size: 12px; padding: 0 4px; border: 1px solid #dee2e6;
    border-radius: 4px; background: #fff; color: #495057; cursor: pointer;
}
.re-color-btn { cursor: pointer; overflow: hidden; position: relative; }
.re-color-input {
    position: absolute; opacity: 0; width: 100%; height: 100%;
    top: 0; left: 0; cursor: pointer; border: none; padding: 0;
}
.re-color-swatch {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 3px;
    border: 1px solid #dee2e6;
    margin-left: 2px;
}
.rich-editor-body:empty:before {
    content: attr(data-placeholder);
    color: #adb5bd; pointer-events: none; display: block;
}
.rich-editor-body ul { list-style-type: disc !important; padding-left: 1.5em !important; margin: 0.5em 0; }
.rich-editor-body ol { list-style-type: decimal !important; padding-left: 1.5em !important; margin: 0.5em 0; }
.rich-editor-body li { display: list-item !important; }
.rich-editor-body h1 { font-size: 2em; font-weight: 600; margin: 0.67em 0; }
.rich-editor-body h2 { font-size: 1.5em; font-weight: 600; margin: 0.75em 0; }
.rich-editor-body h3 { font-size: 1.17em; font-weight: 600; margin: 0.83em 0; }
.rich-editor-body h4 { font-size: 1em; font-weight: 600; margin: 1em 0; }
.rich-editor-body h5 { font-size: 0.83em; font-weight: 600; margin: 1.5em 0; }
.rich-editor-body h6 { font-size: 0.67em; font-weight: 600; margin: 1.67em 0; }
.rich-editor-statusbar { font-size: 11px; font-family: monospace; color: #9ca3af; background: #f9fafb; border-top: 1px solid #e5e7eb; min-height: 24px; }
</style>

@can('view company')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search companies..." />
            </div>
            <div>
                <select id="countryFilter" class="form-select form-select-solid w-120px">
                    <option value="">All Countries</option>
                    @foreach(\App\Helpers\CountryHelper::getCountriesWithFlags() as $country)
                        <option value="{{ $country['code'] }}">
                            {{ $country['flag'] }} {{ $country['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select id="statusFilter" class="form-select form-select-solid w-140px">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="verified">Verified</option>
                    <option value="gold">Gold</option>
                    <option value="featured">Featured</option>
                    <option value="migrated">Migrated</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
        </div>
        @can('create company')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_company">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> Add Company
            </button>
        </div>
        @endcan
    </div>
    
    <div class="card-body pt-0">
        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading companies...</p>
        </div>
        
        <!-- Table Container -->
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-60px">Logo</th>
                            <th class="min-w-180px">Name</th>
                            <th class="min-w-100px">Country</th>
                            <th class="min-w-150px">Contact</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-100px">Verified</th>
                            <th class="min-w-100px">Migration</th>
                            <th class="text-end min-w-160px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="companiesTableBody"></tbody>
                </table>
            </div>
            
            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav><ul class="pagination m-0" id="pagination"></ul></nav>
            </div>
        </div>
        
        <!-- No Data Message -->
        <div id="noDataMessage" class="text-center py-10 d-none">
            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <p class="text-muted">No companies found.</p>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- ADD COMPANY MODAL -->
<!-- ================================================================ -->
<div class="modal fade" id="kt_modal_add_company" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add Company</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addCompanyForm" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Company Name</label>
                            <input type="text" class="form-control form-control-solid" name="name" placeholder="e.g., Acme Corp" required />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Slug</label>
                            <input type="text" class="form-control form-control-solid" name="slug" placeholder="Auto-generated if left blank" />
                            <div class="text-muted fs-7 mt-1">URL-friendly name (auto-generated)</div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country</label>
                            <select class="form-select form-select-solid" name="country_code" id="add_country_code" required>
                                <option value="">Select Country</option>
                                @foreach(\App\Helpers\CountryHelper::getCountriesWithFlags() as $country)
                                    <option value="{{ $country['code'] }}">
                                        {{ $country['flag'] }} {{ $country['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Company Size</label>
                            <select class="form-select form-select-solid" name="company_size">
                                <option value="">Select Size</option>
                                <option value="1-10">1-10 employees</option>
                                <option value="11-50">11-50 employees</option>
                                <option value="51-200">51-200 employees</option>
                                <option value="201-500">201-500 employees</option>
                                <option value="500+">500+ employees</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Industry</label>
                            <div class="searchable-select" id="add_industry_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="add_industry_search" placeholder="Type to search industry..." autocomplete="off">
                                <input type="hidden" name="industry_id" id="add_industry" value="">
                                <div class="searchable-select-dropdown" id="add_industry_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Location</label>
                            <div class="searchable-select" id="add_location_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="add_location_search" placeholder="Type to search location..." autocomplete="off">
                                <input type="hidden" name="location_id" id="add_location" value="">
                                <div class="searchable-select-dropdown" id="add_location_dropdown"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Logo</label>
                        <div id="add_logo_preview" class="mb-3" style="display: none;">
                            <img id="add_logo_image" src="" alt="Logo Preview" style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e0e0e0;" />
                            <button type="button" class="btn btn-sm btn-light-danger ms-2" onclick="clearAddLogo()">
                                <i class="ki-duotone ki-cross fs-2"></i> Remove
                            </button>
                        </div>
                        <input type="file" class="form-control form-control-solid" name="logo" id="add_logo_input" accept="image/*" required/>
                        <div class="text-muted fs-7 mt-1">Upload company logo (JPEG, PNG, JPG, GIF, SVG, WEBP - Max 2MB)</div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Website</label>
                        <input type="url" class="form-control form-control-solid" name="website" placeholder="https://example.com" />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <div id="add_rich_editor_container"></div>
                        <input type="hidden" name="description" id="add_description_hidden">
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Contact Name</label>
                            <input type="text" class="form-control form-control-solid" name="contact_name" placeholder="John Doe" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Contact Email</label>
                            <input type="email" class="form-control form-control-solid" name="contact_email" placeholder="john@example.com" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Contact Phone</label>
                            <input type="text" class="form-control form-control-solid" name="contact_phone" placeholder="+1234567890" />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Address</label>
                        <input type="text" class="form-control form-control-solid" name="address1" placeholder="Street address" />
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_active" checked />
                                <label class="form-check-label fw-semibold">Active</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_verified" />
                                <label class="form-check-label fw-semibold">Verified</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_gold" />
                                <label class="form-check-label fw-semibold">Gold</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_featured" />
                                <label class="form-check-label fw-semibold">Featured</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Gold Start Date</label>
                            <input type="datetime-local" class="form-control form-control-solid" name="gold_start_date" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Gold End Date</label>
                            <input type="date" class="form-control form-control-solid" name="gold_end_date" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Featured Start Date</label>
                            <input type="datetime-local" class="form-control form-control-solid" name="featured_start_date" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Featured End Date</label>
                            <input type="date" class="form-control form-control-solid" name="featured_end_date" />
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addCompanyBtn">
                            <span class="indicator-label">Create Company</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- EDIT COMPANY MODAL -->
<!-- ================================================================ -->
<div class="modal fade" id="kt_modal_edit_company" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Company</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editCompanyForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="company_id" id="edit_company_id">
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Company Name</label>
                            <input type="text" class="form-control form-control-solid" name="name" id="edit_name" required />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Slug</label>
                            <input type="text" class="form-control form-control-solid" name="slug" id="edit_slug" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country</label>
                            <select class="form-select form-select-solid" name="country_code" id="edit_country_code" required>
                                <option value="">Select Country</option>
                                @foreach(\App\Helpers\CountryHelper::getCountriesWithFlags() as $country)
                                    <option value="{{ $country['code'] }}">
                                        {{ $country['flag'] }} {{ $country['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Company Size</label>
                            <select class="form-select form-select-solid" name="company_size" id="edit_company_size">
                                <option value="">Select Size</option>
                                <option value="1-10">1-10 employees</option>
                                <option value="11-50">11-50 employees</option>
                                <option value="51-200">51-200 employees</option>
                                <option value="201-500">201-500 employees</option>
                                <option value="500+">500+ employees</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Industry</label>
                            <div class="searchable-select" id="edit_industry_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="edit_industry_search" placeholder="Type to search industry..." autocomplete="off">
                                <input type="hidden" name="industry_id" id="edit_industry" value="">
                                <div class="searchable-select-dropdown" id="edit_industry_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Location</label>
                            <div class="searchable-select" id="edit_location_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="edit_location_search" placeholder="Type to search location..." autocomplete="off">
                                <input type="hidden" name="location_id" id="edit_location" value="">
                                <div class="searchable-select-dropdown" id="edit_location_dropdown"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Logo</label>
                        <div id="edit_logo_preview" class="mb-3">
                            <img id="edit_logo_image" src="" alt="Logo" style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e0e0e0; display: none;" />
                            <button type="button" class="btn btn-sm btn-light-danger ms-2" id="edit_remove_logo_btn" style="display: none;" onclick="clearEditLogo()">
                                <i class="ki-duotone ki-cross fs-2"></i> Remove
                            </button>
                        </div>
                        <input type="file" class="form-control form-control-solid" name="logo" id="edit_logo_input" accept="image/*" required/>
                        <div class="text-muted fs-7 mt-1">Upload new logo to replace existing (JPEG, PNG, JPG, GIF, SVG, WEBP - Max 2MB)</div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Website</label>
                        <input type="url" class="form-control form-control-solid" name="website" id="edit_website" />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <div id="edit_rich_editor_container"></div>
                        <input type="hidden" name="description" id="edit_description_hidden">
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Contact Name</label>
                            <input type="text" class="form-control form-control-solid" name="contact_name" id="edit_contact_name" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Contact Email</label>
                            <input type="email" class="form-control form-control-solid" name="contact_email" id="edit_contact_email" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Contact Phone</label>
                            <input type="text" class="form-control form-control-solid" name="contact_phone" id="edit_contact_phone" />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Address</label>
                        <input type="text" class="form-control form-control-solid" name="address1" id="edit_address1" />
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" />
                                <label class="form-check-label fw-semibold">Active</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_verified" id="edit_is_verified" />
                                <label class="form-check-label fw-semibold">Verified</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_gold" id="edit_is_gold" />
                                <label class="form-check-label fw-semibold">Gold</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="edit_is_featured" />
                                <label class="form-check-label fw-semibold">Featured</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Gold Start Date</label>
                            <input type="datetime-local" class="form-control form-control-solid" name="gold_start_date" id="edit_gold_start_date" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Gold End Date</label>
                            <input type="date" class="form-control form-control-solid" name="gold_end_date" id="edit_gold_end_date" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Featured Start Date</label>
                            <input type="datetime-local" class="form-control form-control-solid" name="featured_start_date" id="edit_featured_start_date" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Featured End Date</label>
                            <input type="date" class="form-control form-control-solid" name="featured_end_date" id="edit_featured_end_date" />
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editCompanyBtn">
                            <span class="indicator-label">Update Company</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')

<script>
// ================================================================
// GLOBALS
// ================================================================
let currentPage = 1;
let currentSearch = '';
let currentCountry = '';
let currentStatus = '';
const searchableSelectData = {};

// ================================================================
// DOM READY
// ================================================================
document.addEventListener('DOMContentLoaded', function() {
    // console.log('🚀 DOM Ready - Initializing...');
    loadCompanies();
    // Load form data AFTER the DOM is ready
    setTimeout(function() {
        // console.log('⏰ Loading form data after 100ms...');
        loadFormData('AU', false);
    }, 100);
    setupEventListeners();
    initRichEditors();
    // console.log('✅ Initialization complete');
});

// ================================================================
// EVENT LISTENERS
// ================================================================
function setupEventListeners() {
    // Search input
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadCompanies();
            }, 500);
        });
    }

    // Country filter
    document.getElementById('countryFilter')?.addEventListener('change', function() {
        currentCountry = this.value;
        currentPage = 1;
        loadCompanies();
    });

    // Status filter
    document.getElementById('statusFilter')?.addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadCompanies();
    });

    // Country change to load locations for ADD modal
    document.getElementById('add_country_code')?.addEventListener('change', function() {
        loadFormData(this.value, false);
    });

    // Country change to load locations for EDIT modal
    document.getElementById('edit_country_code')?.addEventListener('change', function() {
        const companyId = document.getElementById('edit_company_id').value;
        if (companyId) {
            // Get the current selected industry and location from the form
            const industryId = document.getElementById('edit_industry')?.value || null;
            const locationId = document.getElementById('edit_location')?.value || null;
            loadFormData(this.value, true, industryId, locationId);
        } else {
            loadFormData(this.value, true);
        }
    });

    // Add logo preview
    document.getElementById('add_logo_input')?.addEventListener('change', function(e) {
        const file = this.files[0];
        const previewContainer = document.getElementById('add_logo_preview');
        const previewImage = document.getElementById('add_logo_image');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = 'none';
            previewImage.src = '';
        }
    });

    // Edit logo preview
    document.getElementById('edit_logo_input')?.addEventListener('change', function(e) {
        const file = this.files[0];
        const previewImage = document.getElementById('edit_logo_image');
        const removeBtn = document.getElementById('edit_remove_logo_btn');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'inline-block';
                removeBtn.style.display = 'inline-block';
            };
            reader.readAsDataURL(file);
        } else {
            const companyId = document.getElementById('edit_company_id').value;
            if (companyId) {
                fetch(`/admin/companies/${companyId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.logo_url && data.logo_url !== '/assets/media/avatars/blank.png') {
                            previewImage.src = data.logo_url;
                            previewImage.style.display = 'inline-block';
                            removeBtn.style.display = 'inline-block';
                        } else {
                            previewImage.style.display = 'none';
                            removeBtn.style.display = 'none';
                        }
                    })
                    .catch(err => console.error('Error loading logo:', err));
            }
        }
    });

    // Reset add form on modal close
    document.getElementById('kt_modal_add_company')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('addCompanyForm')?.reset();
        clearSearchableSelect('add_industry');
        clearSearchableSelect('add_location');
        document.getElementById('add_logo_preview').style.display = 'none';
        document.getElementById('add_logo_image').src = '';
        richEditorClear('add_rich_editor');
    });
}

// ================================================================
// CLEAR LOGO FUNCTIONS
// ================================================================
function clearAddLogo() {
    document.getElementById('add_logo_input').value = '';
    document.getElementById('add_logo_preview').style.display = 'none';
    document.getElementById('add_logo_image').src = '';
}

function clearEditLogo() {
    document.getElementById('edit_logo_input').value = '';
    document.getElementById('edit_logo_image').style.display = 'none';
    document.getElementById('edit_remove_logo_btn').style.display = 'none';
    const removeFlag = document.createElement('input');
    removeFlag.type = 'hidden';
    removeFlag.name = 'remove_logo';
    removeFlag.value = '1';
    document.getElementById('editCompanyForm').appendChild(removeFlag);
}

function setSearchableSelectOptions(prefix, items, selectedId = null) {
    searchableSelectData[prefix] = items || [];

    const hiddenInput = document.getElementById(prefix);
    const searchInput = document.getElementById(`${prefix}_search`);
    const dropdown = document.getElementById(`${prefix}_dropdown`);
    
    // console.log(`🔧 Elements for ${prefix}:`, {
    //     hidden: !!hiddenInput,
    //     search: !!searchInput,
    //     dropdown: !!dropdown,
    //     selectedId: selectedId
    // });
    
    if (!hiddenInput || !searchInput || !dropdown) {
        console.warn(`⚠️ Elements not found for: ${prefix}`);
        return;
    }

    // Find selected item
    let match = null;
    if (selectedId) {
        match = (items || []).find(i => String(i.id) === String(selectedId));
    }
    
    // console.log(`🎯 Match found for ${prefix}:`, match);
    
    if (match) {
        hiddenInput.value = match.id;
        searchInput.value = match.label;
        // console.log(`✅ Set ${prefix} to:`, match.id, match.label);
    } else {
        hiddenInput.value = '';
        searchInput.value = '';
    }

    renderSearchableDropdown(prefix, items || []);
}

function renderSearchableDropdown(prefix, items) {
    const dropdown = document.getElementById(`${prefix}_dropdown`);
    if (!dropdown) return;
    
    if (!items || items.length === 0) {
        dropdown.innerHTML = '<div class="searchable-select-empty">No matches found</div>';
        return;
    }
    
    dropdown.innerHTML = items.map(item => `
        <div class="searchable-select-option" data-id="${escapeHtml(String(item.id))}" data-label="${escapeHtml(item.label)}">
            ${escapeHtml(item.label)}
        </div>
    `).join('');
}

function selectSearchableOption(prefix, id, label) {
    const hidden = document.getElementById(prefix);
    const search = document.getElementById(`${prefix}_search`);
    if (hidden) {
        hidden.value = id;
        // console.log(`✅ Selected ${prefix}:`, id, label);
    }
    if (search) search.value = label;
    closeSearchableDropdown(prefix);
}

function clearSearchableSelect(prefix) {
    const hidden = document.getElementById(prefix);
    const search = document.getElementById(`${prefix}_search`);
    if (hidden) hidden.value = '';
    if (search) search.value = '';
}

function getSearchableValue(prefix) {
    const hidden = document.getElementById(prefix);
    const value = hidden ? hidden.value : '';
    // console.log(`🔍 getSearchableValue for ${prefix}:`, value);
    return value;
}

function positionSearchableDropdown(prefix) {
    const input = document.getElementById(`${prefix}_search`);
    const dropdown = document.getElementById(`${prefix}_dropdown`);
    if (!input || !dropdown) return;
    const rect = input.getBoundingClientRect();
    dropdown.style.top = `${rect.bottom + 2}px`;
    dropdown.style.left = `${rect.left}px`;
    dropdown.style.width = `${rect.width}px`;
}

function openSearchableDropdown(prefix) {
    document.querySelectorAll('.searchable-select-dropdown.show').forEach(d => {
        if (d.id !== `${prefix}_dropdown`) d.classList.remove('show');
    });
    positionSearchableDropdown(prefix);
    document.getElementById(`${prefix}_dropdown`)?.classList.add('show');
}

function closeSearchableDropdown(prefix) {
    document.getElementById(`${prefix}_dropdown`)?.classList.remove('show');
}

// ================================================================
// SEARCHABLE SELECT EVENT HANDLERS - FIXED
// ================================================================

// Input event for filtering
document.addEventListener('input', function(e) {
    if (!e || !e.target) return;
    if (!e.target.classList || !e.target.classList.contains('searchable-select-input')) return;

    const prefix = e.target.id.replace('_search', '');
    const items = searchableSelectData[prefix] || [];
    const term = e.target.value.trim().toLowerCase();

    let filtered = items;
    if (term) filtered = items.filter(i => i.label.toLowerCase().includes(term));

    renderSearchableDropdown(prefix, filtered);
    openSearchableDropdown(prefix);

    const hidden = document.getElementById(prefix);  
    if (hidden && hidden.value) {
        const current = items.find(i => String(i.id) === String(hidden.value));
        if (!current || current.label !== e.target.value) hidden.value = '';
    }
});

// Focus event - open dropdown with all items
document.addEventListener('focus', function(e) {
    if (!e || !e.target) return;
    if (!e.target.classList || !e.target.classList.contains('searchable-select-input')) return;
    
    const prefix = e.target.id.replace('_search', '');
    const items = searchableSelectData[prefix] || [];
    renderSearchableDropdown(prefix, items);
    openSearchableDropdown(prefix);
}, true);

// Focusout event - clear if no selection
document.addEventListener('focusout', function(e) {
    if (!e || !e.target) return;
    if (!e.target.classList || !e.target.classList.contains('searchable-select-input')) return;

    const prefix = e.target.id.replace('_search', '');
    setTimeout(() => {
        const hidden = document.getElementById(prefix);   // was: document.getElementById(prefix)
        const search = document.getElementById(`${prefix}_search`);
        if (hidden && search && !hidden.value) search.value = '';
    }, 150);
}, true);

// Click event - select option or close
document.addEventListener('click', function(e) {
    if (!e || !e.target) return;
    
    const option = e.target.closest('.searchable-select-option');
    if (option) {
        const dropdown = option.closest('.searchable-select-dropdown');
        if (dropdown) {
            const prefix = dropdown.id.replace('_dropdown', '');
            selectSearchableOption(prefix, option.dataset.id, option.dataset.label);
        }
        return;
    }
    
    if (!e.target.closest('.searchable-select')) {
        document.querySelectorAll('.searchable-select-dropdown.show').forEach(d => d.classList.remove('show'));
    }
});

// Close dropdown on scroll
document.addEventListener('scroll', function() {
    document.querySelectorAll('.searchable-select-dropdown.show').forEach(d => d.classList.remove('show'));
}, true);

// Reposition dropdown on resize
window.addEventListener('resize', function() {
    document.querySelectorAll('.searchable-select-dropdown.show').forEach(d => {
        const prefix = d.id.replace('_dropdown', '');
        positionSearchableDropdown(prefix);
    });
});

// ================================================================
// LOAD FORM DATA - WITH CONSOLE LOGS
// ================================================================
function loadFormData(country, isEdit = false, selectedIndustryId = null, selectedLocationId = null) {
    const countryCode = country || 'AU';
    const endpoint = `/admin/companies/form-data?country=${countryCode}`;

    const industryPrefix = isEdit ? 'edit_industry' : 'add_industry';
    const locationPrefix = isEdit ? 'edit_location' : 'add_location';

    // console.log('🔍 loadFormData called with:', {
    //     country: countryCode,
    //     isEdit: isEdit,
    //     selectedIndustryId: selectedIndustryId,
    //     selectedLocationId: selectedLocationId,
    //     industryPrefix: industryPrefix,
    //     locationPrefix: locationPrefix
    // });

    fetch(endpoint)
        .then(res => {
            // console.log('📡 API Response Status:', res.status, res.statusText);
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            }
            return res.json();
        })
        .then(data => {
            // console.log('📦 API Response Data:', data);
            
            if (data.success) {
                // console.log('✅ API Success - Industries:', data.industries?.length || 0);
                // console.log('✅ API Success - Locations:', data.locations?.length || 0);
                
                // Build industry options
                const industries = (data.industries || []).map(i => ({ 
                    id: i.id, 
                    label: i.name 
                }));
                
                // Build location options
                const locations = (data.locations || []).map(l => ({ 
                    id: l.id, 
                    label: `${l.district} (${l.country})` 
                }));

                // console.log('🏭 Industries mapped:', industries);
                // console.log('📍 Locations mapped:', locations);

                // Get current values
                const currentIndustry = getSearchableValue(industryPrefix);
                const currentLocation = getSearchableValue(locationPrefix);
                
                // console.log('🔄 Current Industry Value:', currentIndustry);
                // console.log('🔄 Current Location Value:', currentLocation);

                const keepIndustry = selectedIndustryId ?? currentIndustry;
                const keepLocation = selectedLocationId ?? currentLocation;

                // console.log('🎯 Keeping Industry ID:', keepIndustry);
                // console.log('🎯 Keeping Location ID:', keepLocation);

                // Check if elements exist
                const industryInput = document.getElementById(industryPrefix);
                const industrySearch = document.getElementById(`${industryPrefix}_search`);
                const industryDropdown = document.getElementById(`${industryPrefix}_dropdown`);
                
                // console.log('🔧 Industry Elements:', {
                //     hidden: !!industryInput,
                //     search: !!industrySearch,
                //     dropdown: !!industryDropdown
                // });

                const locationInput = document.getElementById(locationPrefix);
                const locationSearch = document.getElementById(`${locationPrefix}_search`);
                const locationDropdown = document.getElementById(`${locationPrefix}_dropdown`);
                
                // console.log('🔧 Location Elements:', {
                //     hidden: !!locationInput,
                //     search: !!locationSearch,
                //     dropdown: !!locationDropdown
                // });

                setSearchableSelectOptions(industryPrefix, industries, keepIndustry);
                setSearchableSelectOptions(locationPrefix, locations, keepLocation);
                
                // console.log('✅ setSearchableSelectOptions called for both');
                
            } else {
                console.error('❌ API returned error:', data.message || 'Unknown error');
            }
        })
        .catch(err => {
            console.error('❌ Error loading form data:', err);
        });
}

// ================================================================
// LOAD COMPANIES TABLE
// ================================================================
function loadCompanies() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `/admin/companies/data?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentCountry) url += `&country=${encodeURIComponent(currentCountry)}`;
    if (currentStatus) url += `&status=${encodeURIComponent(currentStatus)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderCompaniesTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Failed to load companies');
            }
        });
}

function renderCompaniesTable(companies) {
    const tbody = document.getElementById('companiesTableBody');
    tbody.innerHTML = '';
    
    companies.forEach(company => {
        const row = tbody.insertRow();
        
        row.insertCell(0).innerHTML = `<span class="fw-bold">${company.id}</span>`;
        
        const logoCell = row.insertCell(1);
        if (company.logo_url && company.logo_url !== '/assets/media/avatars/blank.png' && company.logo_url !== 'http://localhost/assets/media/avatars/blank.png') {
            logoCell.innerHTML = `<img src="${company.logo_url}" alt="${escapeHtml(company.name)}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;" />`;
        } else {
            const firstLetter = company.name ? company.name.charAt(0).toUpperCase() : '?';
            logoCell.innerHTML = `<div class="symbol symbol-40px symbol-circle bg-light-primary"><span class="symbol-label fs-3 fw-bold text-primary">${firstLetter}</span></div>`;
        }
        
        row.insertCell(2).innerHTML = `<div class="fw-bold">${escapeHtml(company.name)}</div>`;
        row.insertCell(3).innerHTML = `<span class="badge badge-light-info">${company.country_code || 'N/A'}</span>`;
        row.insertCell(4).innerHTML = `
            <div class="text-muted fs-7">${escapeHtml(company.contact_name || 'N/A')}</div>
            <div class="text-muted fs-8">${escapeHtml(company.contact_email || '')}</div>
        `;
        row.insertCell(5).innerHTML = company.status_badge;
        row.insertCell(6).innerHTML = company.verified_badge;
        row.insertCell(7).innerHTML = company.migration_badge;
        row.insertCell(8).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleStatus(${company.id}, ${company.is_active})" title="${company.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${company.is_active ? 'disconnect' : 'check'} fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleVerified(${company.id}, ${company.is_verified})" title="${company.is_verified ? 'Unverify' : 'Verify'}">
                    <i class="ki-duotone ki-shield fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="editCompany(${company.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="deleteCompany(${company.id}, '${escapeHtml(company.name)}')" title="Delete">
                    <i class="ki-duotone ki-trash fs-3 text-danger">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                    </i>
                </button>
            </div>
        `;
    });
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
    if (page !== currentPage && page > 0) { currentPage = page; loadCompanies(); }
};

// ================================================================
// TOGGLE FUNCTIONS
// ================================================================
window.toggleStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this company?`)) {
        fetch(`/admin/companies/${id}/toggle-status`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof window.showToast === 'function') window.showToast('success', data.message);
                loadCompanies();
            } else {
                if (typeof window.showToast === 'function') window.showToast('error', data.message);
            }
        })
        .catch(err => {
            if (typeof window.showToast === 'function') window.showToast('error', 'Failed to toggle status');
        });
    }
};

window.toggleVerified = function(id, current) {
    const action = current ? 'unverify' : 'verify';
    if (confirm(`Are you sure you want to ${action} this company?`)) {
        fetch(`/admin/companies/${id}/toggle-verified`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof window.showToast === 'function') window.showToast('success', data.message);
                loadCompanies();
            } else {
                if (typeof window.showToast === 'function') window.showToast('error', data.message);
            }
        })
        .catch(err => {
            if (typeof window.showToast === 'function') window.showToast('error', 'Failed to toggle verification');
        });
    }
};

// ================================================================
// EDIT COMPANY - FIXED
// ================================================================
window.editCompany = function(id) {
    fetch(`/admin/companies/${id}`)
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            }
            return res.json();
        })
        .then(data => {
            document.getElementById('edit_company_id').value = data.id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_slug').value = data.slug || '';
            document.getElementById('edit_country_code').value = data.country_code || '';
            document.getElementById('edit_company_size').value = data.company_size || '';
            document.getElementById('edit_website').value = data.website || '';
            document.getElementById('edit_contact_name').value = data.contact_name || '';
            document.getElementById('edit_contact_email').value = data.contact_email || '';
            document.getElementById('edit_contact_phone').value = data.contact_phone || '';
            document.getElementById('edit_address1').value = data.address1 || '';
            document.getElementById('edit_is_active').checked = data.is_active;
            document.getElementById('edit_is_verified').checked = data.is_verified;
            document.getElementById('edit_is_gold').checked = data.is_gold;
            document.getElementById('edit_is_featured').checked = data.is_featured;
            document.getElementById('edit_gold_start_date').value = data.gold_start_date || '';
            document.getElementById('edit_gold_end_date').value = data.gold_end_date || '';
            document.getElementById('edit_featured_start_date').value = data.featured_start_date || '';
            document.getElementById('edit_featured_end_date').value = data.featured_end_date || '';
            
            // Set rich editor content
            richEditorSet('edit_rich_editor', data.description || '');
            
            // Load industries and locations - CRITICAL: pass the selected IDs
            loadFormData(data.country_code, true, data.industry_id, data.location_id);
            
            // Show logo preview
            const previewImage = document.getElementById('edit_logo_image');
            const removeBtn = document.getElementById('edit_remove_logo_btn');
            if (data.logo_url && data.logo_url !== '/assets/media/avatars/blank.png') {
                previewImage.src = data.logo_url;
                previewImage.style.display = 'inline-block';
                removeBtn.style.display = 'inline-block';
            } else {
                previewImage.style.display = 'none';
                removeBtn.style.display = 'none';
            }
            
            document.getElementById('edit_logo_input').value = '';
            const existingFlag = document.querySelector('input[name="remove_logo"]');
            if (existingFlag) existingFlag.remove();
            
            new bootstrap.Modal(document.getElementById('kt_modal_edit_company')).show();
        })
        .catch(err => {
            console.error('Error loading company:', err);
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Failed to load company details');
            }
        });
};

// ================================================================
// DELETE COMPANY
// ================================================================
window.deleteCompany = function(id, name) {
    if (confirm(`Are you sure you want to delete company "${name}"? This action cannot be undone.`)) {
        fetch(`/admin/companies/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof window.showToast === 'function') window.showToast('success', data.message);
                loadCompanies();
            } else {
                if (typeof window.showToast === 'function') window.showToast('error', data.message);
            }
        })
        .catch(err => {
            if (typeof window.showToast === 'function') window.showToast('error', 'Failed to delete company');
        });
    }
};

// ================================================================
// RICH EDITOR FUNCTIONS
// ================================================================
function initRichEditors() {
    // Initialize Add Rich Editor
    const addContainer = document.getElementById('add_rich_editor_container');
    if (addContainer) {
        addContainer.innerHTML = buildRichEditor('add_rich_editor', 'description', 'Company description...', 200);
    }
    
    // Initialize Edit Rich Editor
    const editContainer = document.getElementById('edit_rich_editor_container');
    if (editContainer) {
        editContainer.innerHTML = buildRichEditor('edit_rich_editor', 'description', 'Company description...', 200);
    }
}

function buildRichEditor(id, name, placeholder, height = 160) {
    const s = `fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"`;
    return `
    <div class="rich-editor-wrapper border rounded overflow-hidden" data-editor-id="${id}">

        <div class="rich-editor-toolbar d-flex flex-wrap align-items-center gap-1 px-2 py-1 border-bottom bg-light">

            <!-- History -->
            <button type="button" class="re-btn" onclick="reFmt('${id}','undo')" title="Undo">
                <svg viewBox="0 0 24 24" ${s}><path d="M3 7v6h6"/><path d="M3 13A9 9 0 1 0 6 6.7"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','redo')" title="Redo">
                <svg viewBox="0 0 24 24" ${s}><path d="M21 7v6h-6"/><path d="M21 13A9 9 0 1 1 18 6.7"/></svg>
            </button>

            <div class="re-sep"></div>

            <!-- Text styles -->
            <button type="button" class="re-btn" id="${id}-bold" onclick="reFmt('${id}','bold')" title="Bold (Ctrl+B)">
                <svg viewBox="0 0 24 24" ${s}><path d="M6 4h8a4 4 0 0 1 0 8H6z"/><path d="M6 12h9a4 4 0 0 1 0 8H6z"/></svg>
            </button>
            <button type="button" class="re-btn" id="${id}-italic" onclick="reFmt('${id}','italic')" title="Italic (Ctrl+I)">
                <svg viewBox="0 0 24 24" ${s}><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>
            </button>
            <button type="button" class="re-btn" id="${id}-underline" onclick="reFmt('${id}','underline')" title="Underline (Ctrl+U)">
                <svg viewBox="0 0 24 24" ${s}><path d="M6 3v7a6 6 0 0 0 12 0V3"/><line x1="4" y1="21" x2="20" y2="21"/></svg>
            </button>
            <button type="button" class="re-btn" id="${id}-strikeThrough" onclick="reFmt('${id}','strikeThrough')" title="Strikethrough">
                <svg viewBox="0 0 24 24" ${s}><line x1="4" y1="12" x2="20" y2="12"/><path d="M17.5 6.5A4.5 4 0 0 0 12 5c-2.76 0-5 1.34-5 3.5 0 1.54 1.2 2.8 3 3.5"/><path d="M6.5 17.5A4.5 4 0 0 0 12 19c2.76 0 5-1.34 5-3.5 0-1-.37-1.9-1-2.6"/></svg>
            </button>

            <div class="re-sep"></div>

            <!-- Block formats -->
            <button type="button" class="re-btn re-btn-text" onclick="reFmt('${id}','formatBlock','h2')" title="Heading 2">H2</button>
            <button type="button" class="re-btn re-btn-text" onclick="reFmt('${id}','formatBlock','h3')" title="Heading 3">H3</button>
            <button type="button" class="re-btn re-btn-text" onclick="reFmt('${id}','formatBlock','p')"  title="Paragraph">P</button>

            <div class="re-sep"></div>

            <!-- Lists -->
            <button type="button" class="re-btn" onclick="reInsertList('${id}', false)" title="Bullet list">
                <svg viewBox="0 0 24 24" ${s}><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1.5" fill="currentColor"/><circle cx="4" cy="12" r="1.5" fill="currentColor"/><circle cx="4" cy="18" r="1.5" fill="currentColor"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reInsertList('${id}', true)" title="Numbered list">
                <svg viewBox="0 0 24 24" ${s}><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','outdent')" title="Outdent">
                <svg viewBox="0 0 24 24" ${s}><line x1="21" y1="6" x2="11" y2="6"/><line x1="21" y1="12" x2="11" y2="12"/><line x1="21" y1="18" x2="11" y2="18"/><path d="M7 8l-4 4 4 4"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','indent')" title="Indent">
                <svg viewBox="0 0 24 24" ${s}><line x1="21" y1="6" x2="11" y2="6"/><line x1="21" y1="12" x2="11" y2="12"/><line x1="21" y1="18" x2="11" y2="18"/><path d="M3 8l4 4-4 4"/></svg>
            </button>

            <div class="re-sep"></div>

            <!-- Alignment -->
            <button type="button" class="re-btn" onclick="reFmt('${id}','justifyLeft')"   title="Align left">
                <svg viewBox="0 0 24 24" ${s}><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','justifyCenter')" title="Align center">
                <svg viewBox="0 0 24 24" ${s}><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','justifyRight')"  title="Align right">
                <svg viewBox="0 0 24 24" ${s}><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
            </button>

            <div class="re-sep"></div>

            <!-- Link -->
            <button type="button" class="re-btn" onclick="reInsertLink('${id}')" title="Insert link (Ctrl+K)">
                <svg viewBox="0 0 24 24" ${s}><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reUnlink('${id}')" title="Remove link">
                <svg viewBox="0 0 24 24" ${s}><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','formatBlock','blockquote')" title="Blockquote">
                <svg viewBox="0 0 24 24" ${s}><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>
            </button>

            <div class="re-sep"></div>

            <!-- Colors -->
            <label class="re-btn re-color-btn position-relative" title="Text color">
                <svg viewBox="0 0 24 24" ${s}><path d="M9 3H5l7 14 7-14h-4l-3 6-3-6z"/><line x1="3" y1="21" x2="21" y2="21" stroke-width="3"/></svg>
                <span class="re-color-swatch" id="${id}-fgSwatch" style="background:#000"></span>
                <input type="color" class="re-color-input" id="${id}_fgColor" value="#000000"
                    oninput="updateSwatch('${id}_fgColor','${id}-fgSwatch')"
                    onchange="reFmt('${id}','foreColor',this.value)">
            </label>
            <label class="re-btn re-color-btn position-relative" title="Highlight color">
                <svg viewBox="0 0 24 24" ${s}><path d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7z"/><circle cx="12" cy="9" r="2.5" fill="currentColor"/></svg>
                <span class="re-color-swatch" id="${id}-bgSwatch" style="background:#ffff00"></span>
                <input type="color" class="re-color-input" id="${id}_bgColor" value="#ffff00"
                    oninput="updateSwatch('${id}_bgColor','${id}-bgSwatch')"
                    onchange="reFmt('${id}','hiliteColor',this.value)">
            </label>

            <div class="re-sep"></div>

            <!-- Font -->
            <select class="re-select" onchange="reFmt('${id}','fontName',this.value)" title="Font" style="max-width:90px;">
                <option value="">Font</option>
                <option value="Arial">Arial</option>
                <option value="Georgia">Georgia</option>
                <option value="Verdana">Verdana</option>
                <option value="'Times New Roman'">Times NR</option>
                <option value="'Courier New'">Mono</option>
            </select>
            <select class="re-select" onchange="reFmt('${id}','fontSize',this.value)" title="Size" style="max-width:64px;">
                <option value="">Size</option>
                <option value="1">8pt</option>
                <option value="2">10pt</option>
                <option value="3">12pt</option>
                <option value="4">14pt</option>
                <option value="5">18pt</option>
                <option value="6">24pt</option>
                <option value="7">36pt</option>
            </select>

            <div class="re-sep"></div>

            <!-- Clear -->
            <button type="button" class="re-btn" onclick="reFmt('${id}','removeFormat')" title="Clear formatting">
                <svg viewBox="0 0 24 24" ${s}><path d="M4 7l4-4 12 12-4 4"/><path d="M14.5 2.5l7 7"/><line x1="2" y1="22" x2="22" y2="22"/><path d="M3 17l4-4"/></svg>
            </button>
            <button type="button" class="re-btn re-btn-danger" onclick="richEditorClear('${id}')" title="Clear all content">
                <svg viewBox="0 0 24 24" ${s}><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>

        </div>

        <!-- Editable area -->
        <div id="${id}"
            contenteditable="true"
            class="rich-editor-body p-3"
            style="min-height:${height}px;max-height:${height * 2}px;overflow-y:auto;outline:none;font-size:14px;line-height:1.7"
            data-placeholder="${placeholder}"
            oninput="richEditorSync('${id}'); updateStats('${id}')">
        </div>

        <!-- Status bar -->
        <div class="rich-editor-statusbar d-flex justify-content-between align-items-center px-3">
            <span></span>
            <div class="d-flex gap-3">
                <span id="${id}-words">0 words</span>
                <span id="${id}-chars">0 chars</span>
            </div>
        </div>

    </div>`;
}

function reFmt(id, cmd, val = null) {
    const el = document.getElementById(id);
    if (!el) return;
    el.focus();
    document.execCommand(cmd, false, val);
    richEditorSync(id);
    updateActiveStates(id);
}

function reInsertList(id, ordered) {
    const el = document.getElementById(id);
    if (!el) return;
    el.focus();
    const listTag = ordered ? 'OL' : 'UL';
    const sel = window.getSelection();
    if (sel && sel.rangeCount) {
        const anc = sel.getRangeAt(0).commonAncestorContainer;
        let node = anc.nodeType === 3 ? anc.parentNode : anc;
        while (node && node !== el) {
            if (node.tagName === listTag) {
                document.execCommand(ordered ? 'insertOrderedList' : 'insertUnorderedList', false, null);
                richEditorSync(id);
                return;
            }
            node = node.parentNode;
        }
    }
    document.execCommand(ordered ? 'insertOrderedList' : 'insertUnorderedList', false, null);
    richEditorSync(id);
}

function reInsertLink(id) {
    const el = document.getElementById(id);
    if (!el) return;
    const sel = window.getSelection();
    const txt = sel && sel.toString().trim();
    const url = prompt('Enter URL:', 'https://');
    if (!url) return;
    el.focus();
    if (txt) {
        document.execCommand('createLink', false, url);
    } else {
        document.execCommand('insertHTML', false,
            `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`);
    }
    richEditorSync(id);
}

function reUnlink(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.focus();
    document.execCommand('unlink', false, null);
    richEditorSync(id);
}

function richEditorSync(id) {
    const el = document.getElementById(id);
    const hidden = document.getElementById(id + '_hidden');
    if (el && hidden) hidden.value = el.innerHTML;
}

function richEditorGet(id) {
    const el = document.getElementById(id);
    return el ? el.innerHTML : '';
}

function richEditorSet(id, html) {
    const el = document.getElementById(id);
    const hidden = document.getElementById(id + '_hidden');
    if (el) el.innerHTML = html ?? '';
    if (hidden) hidden.value = html ?? '';
    updateStats(id);
}

function richEditorClear(id) {
    richEditorSet(id, '');
    document.getElementById(id)?.focus();
}

function updateStats(id) {
    const el = document.getElementById(id);
    if (!el) return;
    const text = el.innerText || '';
    const words = text.trim() ? text.trim().split(/\s+/).length : 0;
    const chars = text.length;
    const wEl = document.getElementById(id + '-words');
    const cEl = document.getElementById(id + '-chars');
    if (wEl) wEl.textContent = words + (words === 1 ? ' word' : ' words');
    if (cEl) cEl.textContent = chars + (chars === 1 ? ' char' : ' chars');
}

function updateActiveStates(id) {
    ['bold','italic','underline','strikeThrough'].forEach(cmd => {
        const btn = document.getElementById(`${id}-${cmd}`);
        if (btn) btn.classList.toggle('active', document.queryCommandState(cmd));
    });
}

function updateSwatch(inputId, swatchId) {
    const input = document.getElementById(inputId);
    const swatch = document.getElementById(swatchId);
    if (input && swatch) swatch.style.background = input.value;
}

// Keyboard shortcuts for rich editor
document.addEventListener('keydown', e => {
    const active = document.activeElement;
    if (!active || active.getAttribute('contenteditable') !== 'true') return;
    const id = active.id;
    if (!id) return;
    if (e.ctrlKey || e.metaKey) {
        if (e.key === 'b') { e.preventDefault(); reFmt(id, 'bold'); }
        else if (e.key === 'i') { e.preventDefault(); reFmt(id, 'italic'); }
        else if (e.key === 'u') { e.preventDefault(); reFmt(id, 'underline'); }
        else if (e.key === 'z') { e.preventDefault(); reFmt(id, 'undo'); }
        else if (e.key === 'y') { e.preventDefault(); reFmt(id, 'redo'); }
        else if (e.key === 'k') { e.preventDefault(); reInsertLink(id); }
    }
});

document.addEventListener('selectionchange', () => {
    const active = document.activeElement;
    if (!active || active.getAttribute('contenteditable') !== 'true') return;
    if (active.id) updateActiveStates(active.id);
});

document.addEventListener('submit', () => {
    document.querySelectorAll('[contenteditable="true"][id]').forEach(el => {
        richEditorSync(el.id);
    });
});

// ================================================================
// FORM SUBMISSIONS
// ================================================================
document.getElementById('addCompanyForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    if (!getSearchableValue('add_industry')) {
        if (typeof window.showToast === 'function') window.showToast('error', 'Please select an industry');
        return;
    }
    if (!getSearchableValue('add_location')) {
        if (typeof window.showToast === 'function') window.showToast('error', 'Please select a location');
        return;
    }

    const btn = document.getElementById('addCompanyBtn');
    if (typeof window.showButtonSpinner === 'function') window.showButtonSpinner(btn);
    
    richEditorSync('add_rich_editor');
    
    const formData = new FormData(this);

    fetch('/admin/companies', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (typeof window.showToast === 'function') window.showToast('success', data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_company'));
            if (modal) modal.hide();
            this.reset();
            clearSearchableSelect('add_industry');
            clearSearchableSelect('add_location');
            document.getElementById('add_logo_preview').style.display = 'none';
            document.getElementById('add_logo_image').src = '';
            richEditorClear('add_rich_editor');
            loadCompanies();
        } else {
            if (data.errors) {
                const errorMessages = Object.values(data.errors).flat().join('\n');
                if (typeof window.showToast === 'function') window.showToast('error', errorMessages);
            } else {
                if (typeof window.showToast === 'function') window.showToast('error', data.message || 'Failed to create company');
            }
        }
    })
    .catch(err => {
        console.error('Error:', err);
        if (typeof window.showToast === 'function') window.showToast('error', 'Failed to create company: ' + err.message);
    })
    .finally(() => {
        if (typeof window.hideButtonSpinner === 'function') window.hideButtonSpinner(btn);
    });
});

document.getElementById('editCompanyForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    if (!getSearchableValue('edit_industry')) {
        if (typeof window.showToast === 'function') window.showToast('error', 'Please select an industry');
        return;
    }
    if (!getSearchableValue('edit_location')) {
        if (typeof window.showToast === 'function') window.showToast('error', 'Please select a location');
        return;
    }

    const btn = document.getElementById('editCompanyBtn');
    if (typeof window.showButtonSpinner === 'function') window.showButtonSpinner(btn);
    const id = document.getElementById('edit_company_id').value;

    richEditorSync('edit_rich_editor');

    const formData = new FormData(this);
    formData.append('_method', 'PUT');

    const booleanFields = ['is_active', 'is_verified', 'is_gold', 'is_featured'];
    booleanFields.forEach(field => {
        const checkbox = document.querySelector(`#editCompanyForm input[name="${field}"]`);
        if (checkbox) {
            formData.set(field, checkbox.checked ? '1' : '0');
        }
    });

    fetch(`/admin/companies/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (typeof window.showToast === 'function') window.showToast('success', data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_company'));
            if (modal) modal.hide();
            loadCompanies();
        } else {
            let errorMsg = data.message;
            if (data.errors) {
                errorMsg = Object.values(data.errors).flat().join('\n');
            }
            if (typeof window.showToast === 'function') window.showToast('error', errorMsg);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        if (typeof window.showToast === 'function') window.showToast('error', 'Failed to update company: ' + err.message);
    })
    .finally(() => {
        if (typeof window.hideButtonSpinner === 'function') window.hideButtonSpinner(btn);
    });
});

// ================================================================
// UTILITY FUNCTIONS
// ================================================================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush