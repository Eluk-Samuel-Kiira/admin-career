@extends('layouts.admin')

@section('title', 'AI Job Posting')
@section('page_title', 'AI Job Posting')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">AI</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Job Posting</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">{{ $countryName ?? 'Select Country' }}</li>
@endsection

@section('content')

<style>
    /* ============================================================
    AI JOB POSTING STYLES
    ============================================================ */
    .ai-banner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: #fff;
    }
    .ai-banner .btn-white {
        background: #fff;
        color: #764ba2;
        border: none;
    }
    .ai-banner .btn-white:hover {
        background: #f0f0f0;
    }

    /* Country Selection Buttons */
    .country-btn {
        transition: all 0.3s ease;
        border-radius: 10px !important;
        padding: 12px 8px !important;
        font-size: 0.85rem;
    }
    .country-btn .country-flag {
        font-size: 1.5rem;
    }
    .country-btn .country-name {
        font-weight: 600;
    }
    .country-btn.active {
        transform: scale(1.02);
        box-shadow: 0 4px 15px rgba(0, 158, 247, 0.3);
    }
    .country-btn:not(.active):hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    /* AI Model Cards */
    .model-card {
        transition: all 0.3s ease;
        border: 2px solid #e4e6ef;
        border-radius: 12px !important;
        padding: 16px 12px !important;
        cursor: pointer;
        background: #fff;
        position: relative;
    }
    .model-card:hover {
        border-color: #b5b9c9;
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }
    .model-card.active {
        border-color: #009ef7;
        background: #f1faff;
        box-shadow: 0 4px 20px rgba(0, 158, 247, 0.15);
    }
    .model-card .model-icon {
        font-size: 2rem;
        display: block;
        margin-bottom: 0.5rem;
    }
    .model-card .model-name {
        font-size: 0.8rem;
        font-weight: 600;
        color: #3f4254;
    }
    .model-card .model-badge {
        font-size: 0.55rem;
        padding: 0.15rem 0.5rem;
        position: absolute;
        top: 8px;
        right: 8px;
    }
    .model-card .model-check {
        position: absolute;
        bottom: 8px;
        right: 8px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .model-card.active .model-check {
        opacity: 1;
    }

    /* Quick Action Buttons */
    .quick-action-btn {
        border-radius: 10px !important;
        padding: 14px !important;
        transition: all 0.3s ease;
    }
    .quick-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .quick-action-btn .ki-duotone {
        font-size: 1.5rem;
    }

    /* Searchable Dropdown */
    .searchable-select {
        position: relative;
    }
    .searchable-select-dropdown {
        display: none;
        position: fixed;      /* was: absolute */
        z-index: 2000;        /* was: 1050 - bump so it clears anything else on the page */
        max-height: 220px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #e4e6ef;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,.15);
        margin-top: 2px;
    }
    .searchable-select-dropdown.show {
        display: block;
    }
    .searchable-select-option {
        padding: 8px 14px;
        cursor: pointer;
        font-size: 13px;
        color: #3f4254;
        transition: background 0.15s ease;
    }
    .searchable-select-option:hover,
    .searchable-select-option.active {
        background: #f5f8fa;
    }
    .searchable-select-empty {
        padding: 8px 14px;
        color: #a1a5b7;
        font-size: 13px;
    }

    /* Image Gallery */
    .gallery-image-item {
        position: relative;
        width: 80px;
        height: 80px;
        cursor: pointer;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid transparent;
        transition: all 0.2s;
    }
    .gallery-image-item.selected {
        border-color: #009ef7;
        box-shadow: 0 0 0 2px rgba(0,158,247,0.25);
    }
    .gallery-image-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .gallery-image-item .remove-btn {
        position: absolute;
        top: 2px;
        right: 2px;
        background: rgba(0,0,0,0.6);
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 12px;
        opacity: 0;
        transition: opacity 0.2s;
    }
    .gallery-image-item:hover .remove-btn {
        opacity: 1;
    }

    /* Drop Zone */
    .drop-zone {
        border: 2px dashed #e4e6ef;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .drop-zone:hover,
    .drop-zone.dragover {
        border-color: #009ef7;
        background: #f1faff;
    }
    .drop-zone .drop-icon {
        font-size: 2.5rem;
        color: #a1a5b7;
        margin-bottom: 0.5rem;
    }

    /* Preview Panel */
    .preview-panel {
        min-height: 400px;
        max-height: 500px;
        overflow-y: auto;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
    }
    .preview-panel .field-item {
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        background: #fff;
        margin-bottom: 0.5rem;
        border-left: 3px solid #009ef7;
    }
    .preview-panel .field-item .field-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        color: #a1a5b7;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .preview-panel .field-item .field-value {
        font-size: 0.85rem;
        font-weight: 500;
        word-break: break-word;
    }

    /* Rich Editor Status */
    .rich-editor-statusbar {
        font-size: 11px;
        font-family: monospace;
        color: #9ca3af;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        min-height: 24px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .preview-panel {
            max-height: 300px;
            min-height: 200px;
        }
        .gallery-image-item {
            width: 60px;
            height: 60px;
        }
        .country-btn {
            padding: 8px 6px !important;
            font-size: 0.75rem;
        }
        .country-btn .country-flag {
            font-size: 1.2rem;
        }
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
@can('create jobs')
{{-- AI Status Banner --}}
<div id="aiBanner" class="alert ai-banner d-none align-items-center gap-3 mb-5" role="alert">
    <div class="spinner-border spinner-border-sm text-white flex-shrink-0" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <span id="aiBannerText">AI is processing your content...</span>
    <button type="button" class="btn-close btn-close-white ms-auto" onclick="hideBanner()"></button>
</div>



{{-- Job Post Form --}}
<form id="aiJobForm">
    @csrf
    <input type="hidden" name="poster_id" value="{{ auth()->id() }}">
    <input type="hidden" name="is_simple_job" value="0">
    <input type="hidden" name="country_code" id="f_country_code" value="{{ $selectedCountry ?? 'AU' }}">
    @php
        $countryCurrency = \App\Models\Job\Country::pluck('currency', 'code')->toArray();
    @endphp

    <input type="hidden" id="countryCurrencyData" value='{{ json_encode($countryCurrency) }}'>

    {{-- MAIN ROW: 8-4 LAYOUT --}}
    <div class="row g-5">
        
        {{-- LEFT COLUMN (8) --}}
        <div class="col-xl-8">
            {{-- Country Selector - Compact --}}
            <div class="card card-flush shadow-sm mb-5">
                <div class="card-header border-0 pt-5">
                    <div class="d-flex align-items-center gap-3">
                        <h6 class="card-title fw-bold">
                            <i class="ki-duotone ki-geolocation fs-2 me-2 text-primary"></i>
                            Select Country
                        </h6>
                        <span class="badge badge-light-primary fs-6 fw-bold px-4 py-2" id="selectedCountryDisplay">
                            {{ $countryName ?? 'Select Country' }}
                        </span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-2" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button='true']">
                        @php
                            $countries = [];
                            foreach(\App\Helpers\CountryHelper::getActiveCountries() as $country) {
                                $countries[$country->code] = [
                                    'name' => $country->name,
                                    'flag' => $country->flag_emoji,
                                ];
                            }
                        @endphp
                        @foreach($countries as $code => $info)
                            <div class="col-6 col-md-3">
                                <a href="{{ route('admin.ai.job-posting', $code) }}" 
                                class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-3 w-100 {{ $selectedCountry === $code ? 'active' : '' }}"
                                data-kt-button="true"
                                style="border-width:2px;text-decoration:none;">
                                    <span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                                        <input class="form-check-input" type="radio" name="country" value="{{ $code }}" {{ $selectedCountry === $code ? 'checked' : '' }} />
                                    </span>
                                    <span class="ms-2 d-flex align-items-center gap-2">
                                        <span class="fs-2">{{ $info['flag'] }}</span>
                                        <span class="fs-7 fw-bold text-gray-800">{{ $info['name'] }}</span>
                                        @if($selectedCountry === $code)
                                            <i class="ki-duotone ki-check-circle fs-5 text-primary ms-auto"></i>
                                        @endif
                                    </span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
                        
            {{-- Basic Information --}}
            <div class="card card-flush shadow-sm mb-5">
                <div class="card-header border-0 pt-5">
                    <h6 class="card-title fw-bold">
                        <i class="ki-duotone ki-info-circle fs-2 me-2 text-primary"></i>
                        Basic Information
                    </h6>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold required">Job Title</label>
                            <input type="text" name="job_title" id="f_job_title" 
                                class="form-control form-control-lg" 
                                placeholder="e.g., Senior Software Engineer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Company</label>
                            <div class="searchable-select">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="f_company_search" placeholder="Type to search company..." autocomplete="off">
                                <input type="hidden" name="company_id" id="f_company_id" value="">
                                <div class="searchable-select-dropdown" id="f_company_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Category</label>
                            <div class="searchable-select">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="f_category_search" placeholder="Type to search category..." autocomplete="off">
                                <input type="hidden" name="job_category_id" id="f_category_id" value="">
                                <div class="searchable-select-dropdown" id="f_category_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Industry</label>
                            <div class="searchable-select">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="f_industry_search" placeholder="Type to search industry..." autocomplete="off">
                                <input type="hidden" name="industry_id" id="f_industry_id" value="">
                                <div class="searchable-select-dropdown" id="f_industry_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Location</label>
                            <div class="searchable-select">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="f_location_search" placeholder="Type to search location..." autocomplete="off">
                                <input type="hidden" name="job_location_id" id="f_location_id" value="">
                                <div class="searchable-select-dropdown" id="f_location_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold required">Job Type</label>
                            <div class="searchable-select">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="f_jobtype_search" placeholder="Type to search job type..." autocomplete="off">
                                <input type="hidden" name="job_type_id" id="f_jobtype_id" value="">
                                <div class="searchable-select-dropdown" id="f_jobtype_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold required">Employment Type</label>
                            <select name="employment_type" id="f_employment_type" class="form-select">
                                <option value="full-time">Full-time</option>
                                <option value="part-time">Part-time</option>
                                <option value="contract">Contract</option>
                                <option value="internship">Internship</option>
                                <option value="volunteer">Volunteer</option>
                                <option value="temporary">Temporary</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Location Type</label>
                            <select name="location_type" id="f_location_type" class="form-select">
                                <option value="on-site">On-site</option>
                                <option value="remote">Remote</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Duty Station</label>
                            <input type="text" name="duty_station" id="f_duty_station" 
                                class="form-control" placeholder="e.g., Kampala Head Office">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Application Deadline</label>
                            <input type="date" name="deadline" id="f_deadline" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Job Description --}}
            <div class="card card-flush shadow-sm mb-5">
                <div class="card-header border-0 pt-5">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h6 class="card-title fw-bold">
                            <i class="ki-duotone ki-file-text fs-2 me-2 text-success"></i>
                            Job Description
                        </h6>
                        <button type="button" class="btn btn-sm btn-light-primary" 
                                onclick="aiEnhanceField('job_description','Enhance and professionally rewrite this job description for SEO and clarity. Use clean HTML with <p> tags.')">
                            <i class="ki-duotone ki-sparkle fs-3 me-1"></i>AI Enhance
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div id="f_job_description_editor_container"></div>
                    <input type="hidden" name="job_description" id="f_job_description_hidden">
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted">Describe the role, company culture, and what makes this opportunity special.</small>
                        <small id="descCharCount" class="text-muted">0 chars</small>
                    </div>
                </div>
            </div>

            {{-- Responsibilities --}}
            <div class="card card-flush shadow-sm mb-5">
                <div class="card-header border-0 pt-5">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h6 class="card-title fw-bold">
                            <i class="ki-duotone ki-check-circle fs-2 me-2 text-warning"></i>
                            Key Responsibilities
                        </h6>
                        <button type="button" class="btn btn-sm btn-light-primary" 
                                onclick="aiEnhanceField('responsibilities','Rewrite as a clear, action-oriented HTML <ul><li> list of 6-8 key responsibilities. Each item should start with a strong verb.')">
                            <i class="ki-duotone ki-sparkle fs-3 me-1"></i>AI Format
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div id="f_responsibilities_editor_container"></div>
                    <input type="hidden" name="responsibilities" id="f_responsibilities_hidden">
                </div>
            </div>

            {{-- Qualifications --}}
            <div class="card card-flush shadow-sm mb-5">
                <div class="card-header border-0 pt-5">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h6 class="card-title fw-bold">
                            <i class="ki-duotone ki-shield-tick fs-2 me-2 text-info"></i>
                            Qualifications
                        </h6>
                        <button type="button" class="btn btn-sm btn-light-primary" 
                                onclick="aiEnhanceField('qualifications','Rewrite as a professional HTML <ul><li> list with Required and Preferred sections. Be specific and clear.')">
                            <i class="ki-duotone ki-sparkle fs-3 me-1"></i>AI Format
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div id="f_qualifications_editor_container"></div>
                    <input type="hidden" name="qualifications" id="f_qualifications_hidden">
                </div>
            </div>

            {{-- Skills --}}
            <div class="card card-flush shadow-sm mb-5">
                <div class="card-header border-0 pt-5">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h6 class="card-title fw-bold">
                            <i class="ki-duotone ki-tools fs-2 me-2 text-purple"></i>
                            Required Skills
                        </h6>
                        <button type="button" class="btn btn-sm btn-light-primary" 
                                onclick="aiEnhanceField('skills','Extract and list all relevant technical and soft skills as a clean comma-separated list. Include 8-12 skills total.')">
                            <i class="ki-duotone ki-sparkle fs-3 me-1"></i>AI Extract
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div id="f_skills_editor_container"></div>
                    <input type="hidden" name="skills" id="f_skills_hidden">
                    <small class="text-muted mt-1 d-block">Tip: Separate skills with commas for proper display as tags on the frontend.</small>
                </div>
            </div>

            {{-- Application Procedure --}}
            <div class="card card-flush shadow-sm mb-5">
                <div class="card-header border-0 pt-5">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h6 class="card-title fw-bold">
                            <i class="ki-duotone ki-send fs-2 me-2 text-danger"></i>
                            Application Procedure
                        </h6>
                        <button type="button" class="btn btn-sm btn-light-primary" 
                                onclick="aiEnhanceField('application_procedure','Rewrite these application instructions clearly and professionally. Include any email, URL, or deadline mentioned.')">
                            <i class="ki-duotone ki-sparkle fs-3 me-1"></i>AI Rewrite
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div id="f_application_procedure_editor_container"></div>
                    <input type="hidden" name="application_procedure" id="f_application_procedure_hidden">
                    <small class="text-muted mt-1 d-block">Include a URL where candidates should apply.</small>
                </div>
            </div>
        </div>
        {{-- END LEFT COLUMN (8) --}}

        {{-- RIGHT COLUMN (4) --}}
        <div class="col-xl-4">

            {{-- AI Assistant Card --}}
            <div class="card card-flush shadow-sm mb-5 bg-primary" style="background:linear-gradient(135deg,#7239ea 0%,#009ef7 100%);border:none;">
                <div class="card-body p-5">
                    <div class="d-flex align-items-center mb-4">
                        <div class="symbol symbol-50px symbol-circle me-3">
                            <span class="symbol-label bg-white bg-opacity-15">
                                <i class="ki-duotone ki-robot fs-2x text-white"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="text-white fw-bold mb-0">AI Assistant</h5>
                            <span class="text-white text-opacity-75 fs-7">Powered by advanced AI</span>
                        </div>
                        <span class="badge badge-light-white fw-bold px-4 py-2 rounded-pill">
                            <span class="bullet bullet-dot bullet-success me-1"></span>
                            Active
                        </span>
                    </div>
                    
                    <p class="text-white text-opacity-75 fs-7 mb-4">
                        Extract job data from any source using advanced AI models.
                    </p>
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-light btn-active-light fw-bold py-3" onclick="openAiExtractModal()">
                            <i class="ki-duotone ki-clipboard-text fs-3 me-2"></i>
                            Paste & Extract
                            <i class="ki-duotone ki-arrow-right fs-3 ms-auto"></i>
                        </button>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-light btn-active-light w-100 py-3" onclick="openImageExtractModal()">
                                    <i class="ki-duotone ki-picture fs-3 me-2"></i>
                                    Image
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-light btn-active-light w-100 py-3" onclick="aiGenerateFromUrl()">
                                    <i class="ki-duotone ki-link fs-3 me-2"></i>
                                    URL
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="separator separator-white separator-opacity-25 my-4"></div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-white text-opacity-50 fs-8">
                            <i class="ki-duotone ki-shield-tick fs-7 me-1"></i> Secure
                        </span>
                        <span class="text-white text-opacity-50 fs-8">
                            <i class="ki-duotone ki-clock fs-7 me-1"></i> Quick
                        </span>
                        <span class="text-white text-opacity-50 fs-8">
                            <i class="ki-duotone ki-check-circle fs-7 me-1"></i> 96% Accuracy
                        </span>
                    </div>
                </div>
            </div>

            {{-- Salary Information --}}
            <div class="card card-flush shadow-sm mb-5">
                <div class="card-header border-0 pt-5">
                    <h6 class="card-title fw-bold">
                        <i class="ki-duotone ki-coin fs-2 me-2 text-success"></i>
                        Salary Information
                    </h6>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Salary Range</label>
                            <div class="searchable-select">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="f_salaryrange_search" placeholder="Select salary range..." autocomplete="off">
                                <input type="hidden" name="salary_range_id" id="f_salaryrange_id" value="">
                                <div class="searchable-select-dropdown" id="f_salaryrange_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-7">
                            <label class="form-label fw-semibold">Amount</label>
                            <input type="number" name="salary_amount" id="f_salary_amount" 
                                   class="form-control" placeholder="0">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Currency</label>
                            <input type="text" name="currency" id="f_currency" 
                                   class="form-control" value="AUD">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Payment Period</label>
                            <select name="payment_period" id="f_payment_period" class="form-select">
                                <option value="">— Select —</option>
                                <option value="monthly" selected>Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="weekly">Weekly</option>
                                <option value="daily">Daily</option>
                                <option value="hourly">Hourly</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Job Source</label>
                            <select name="job_source" id="f_job_source" class="form-select" required>
                                <option value="">— Select —</option>
                                <option value="competitor_website">Competitor Website</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="newspaper">Newspaper</option>
                                <option value="employer_website">Employer Website</option>
                                <option value="linkedin">LinkedIn</option>
                                <option value="facebook">Facebook</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Experience & Education --}}
            <div class="card card-flush shadow-sm mb-5">
                <div class="card-header border-0 pt-5">
                    <h6 class="card-title fw-bold">
                        <i class="ki-duotone ki-school fs-2 me-2 text-info"></i>
                        Requirements
                    </h6>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold required">Experience Level</label>
                            <div class="searchable-select">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="f_experience_search" placeholder="Type to search..." autocomplete="off">
                                <input type="hidden" name="experience_level_id" id="f_experience_id" value="">
                                <div class="searchable-select-dropdown" id="f_experience_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold required">Education Level</label>
                            <div class="searchable-select">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="f_education_search" placeholder="Type to search..." autocomplete="off">
                                <input type="hidden" name="education_level_id" id="f_education_id" value="">
                                <div class="searchable-select-dropdown" id="f_education_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Work Hours</label>
                            <input type="text" name="work_hours" id="f_work_hours" 
                                   class="form-control" placeholder="e.g., 8am–5pm Mon–Fri">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contact Information --}}
            <div class="card card-flush shadow-sm mb-5">
                <div class="card-header border-0 pt-5">
                    <h6 class="card-title fw-bold">
                        <i class="ki-duotone ki-phone fs-2 me-2 text-primary"></i>
                        Contact Information
                    </h6>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Contact Email</label>
                            <input type="email" name="email" id="f_email" 
                                   class="form-control" placeholder="hr@company.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Telephone</label>
                            <input type="text" name="telephone" id="f_telephone" 
                                   class="form-control" placeholder="+256 700 000 000">
                        </div>
                        <div class="col-12">
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_whatsapp_contact" id="f_whatsapp">
                                    <label class="form-check-label" for="f_whatsapp">WhatsApp contact</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_telephone_call" id="f_telcall">
                                    <label class="form-check-label" for="f_telcall">Phone call OK</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Flags & Requirements --}}
            <div class="card card-flush shadow-sm mb-5">
                <div class="card-header border-0 pt-5">
                    <h6 class="card-title fw-bold">
                        <i class="ki-duotone ki-flag fs-2 me-2 text-warning"></i>
                        Flags & Requirements
                    </h6>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="f_featured">
                                <label class="form-check-label" for="f_featured">Featured</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_urgent" id="f_urgent">
                                <label class="form-check-label" for="f_urgent">Urgent</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_quick_gig" id="f_quickgig">
                                <label class="form-check-label" for="f_quickgig">Quick Gig</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_verified" id="f_verified">
                                <label class="form-check-label" for="f_verified">Pre-verify</label>
                            </div>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="row g-2">
                        <p class="small fw-semibold text-muted mb-2">Application Requirements</p>
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_resume_required" id="f_resume" checked>
                                <label class="form-check-label" for="f_resume">Resume/CV</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_cover_letter_required" id="f_cover">
                                <label class="form-check-label" for="f_cover">Cover Letter</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_academic_documents_required" id="f_academic">
                                <label class="form-check-label" for="f_academic">Academic Docs</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_application_required" id="f_appletter">
                                <label class="form-check-label" for="f_appletter">App. Letter</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SEO Metadata --}}
            <div class="card card-flush shadow-sm mb-5">
                <div class="card-header border-0 pt-5">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <h6 class="card-title fw-bold">
                            <i class="ki-duotone ki-chart-simple fs-2 me-2 text-secondary"></i>
                            SEO Metadata
                        </h6>
                        <button type="button" class="btn btn-sm btn-light" onclick="toggleSeo()">
                            <i class="ki-duotone ki-chevron-down fs-3" id="seoChevron"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0" id="seoBody" style="display:none">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Meta Title <span class="text-muted">(50-60 chars)</span></label>
                            <input type="text" name="meta_title" id="f_meta_title" 
                                   class="form-control" placeholder="Auto-generated" maxlength="60">
                            <div class="d-flex justify-content-end mt-1">
                                <small id="metaTitleCount" class="text-muted">0/60</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Meta Description <span class="text-muted">(150-160 chars)</span></label>
                            <textarea name="meta_description" id="f_meta_description" 
                                      class="form-control" rows="2" placeholder="Auto-generated" maxlength="160"></textarea>
                            <div class="d-flex justify-content-end mt-1">
                                <small id="metaDescCount" class="text-muted">0/160</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keywords</label>
                            <input type="text" name="keywords" id="f_keywords" 
                                   class="form-control" placeholder="Auto-generated keywords">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit Buttons --}}
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-primary btn-lg fw-semibold" onclick="submitJobPost('live')">
                    <span id="submitJobBtnText"><i class="ki-duotone ki-send fs-3 me-2"></i>Post Job Now</span>
                    <span id="submitJobBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="submitJobPost('draft')">
                    <span id="submitDraftBtnText"><i class="ki-duotone ki-save fs-3 me-2"></i>Save as Draft</span>
                    <span id="submitDraftBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="clearForm()">
                    <i class="ki-duotone ki-trash fs-3 me-2"></i>Clear Form
                </button>
            </div>

            <div id="formErrors" class="mt-3"></div>

            {{-- Posting Tips --}}
            <div class="card card-flush shadow-sm mt-5" style="background: #f8f9fa;">
                <div class="card-body p-4">
                    <h6 class="fw-semibold small mb-2 d-flex align-items-center gap-2">
                        <i class="ki-duotone ki-bulb text-warning fs-2"></i>
                        Posting Tips
                    </h6>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-1"><i class="ki-duotone ki-check text-success fs-2 me-2"></i>Use specific job titles for better SEO</li>
                        <li class="mb-1"><i class="ki-duotone ki-check text-success fs-2 me-2"></i>Include salary to get 3x more applicants</li>
                        <li class="mb-1"><i class="ki-duotone ki-check text-success fs-2 me-2"></i>List 5-8 key responsibilities</li>
                        <li class="mb-1"><i class="ki-duotone ki-check text-success fs-2 me-2"></i>Mention company culture and benefits</li>
                        <li class="mb-1"><i class="ki-duotone ki-check text-success fs-2 me-2"></i>Set a clear application deadline</li>
                        <li><i class="ki-duotone ki-check text-success fs-2 me-2"></i>Verify before posting for instant visibility</li>
                    </ul>
                </div>
            </div>
        </div>
        {{-- END RIGHT COLUMN (4) --}}

    </div>
    {{-- END MAIN ROW --}}
</form>

{{-- AI EXTRACT MODAL --}}
@include('job.job-posting.ai-text-extract')

{{-- IMAGE EXTRACT MODAL --}}
@include('job.job-posting.ai-image-extract')

@endcan
@endsection

@push('scripts')
@include('job.job-posting.ai-posting-scripts')
@include('job.job-posting.ai-extraction')
@include('job.job-posting.ai-validator')
@endpush