<!--begin::Modal - AI Job Extractor-->
<div class="modal fade" id="aiExtractModal" tabindex="-1" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header py-7 d-flex justify-content-between" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="modal-title text-white">
                        <i class="ki-duotone ki-sparkle fs-2 me-2"></i>
                        AI Job Extractor
                    </h5>
                    <span class="badge bg-white text-primary ms-2" id="modalSelectedModel">Claude</span>
                </div>
                <div class="btn btn-sm btn-icon btn-active-color-white" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1 text-white">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <!--end::Modal header-->
            
            <!--begin::Modal body-->
            <div class="modal-body scroll-y m-5">
                
                <!--begin::AI Model Selector-->
                <div class="card card-flush shadow-sm mb-5">
                    <div class="card-header border-0 pt-5">
                        <div class="d-flex align-items-center gap-3">
                            <h6 class="card-title fw-bold">
                                <i class="ki-duotone ki-robot fs-2 me-2 text-primary"></i>
                                AI Model
                            </h6>
                            <span class="badge badge-light-info fs-7 px-3 py-2" id="selectedModelDisplay">Gemini</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-2" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button='true']" id="modelSelector">
                            <div class="col-12 text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading models...</span>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="selectedModel" value="gemini">
                        <input type="hidden" id="selectedCountry" value="{{ $selectedCountry ?? 'AU' }}">
                    </div>
                </div>
                <!--end::AI Model Selector-->
                
                <!--begin::Row-->
                <div class="row g-5">
                    
                    <!--begin::Left Column-->
                    <div class="col-md-6">
                        
                        <!--begin::Source Type-->
                        <div class="fv-row mb-5" data-kt-buttons="true">
                            <label class="form-label fw-semibold">Source Type</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="sourceType" id="srcText" value="text" checked>
                                <label class="btn btn-outline btn-outline-dashed btn-active-light-primary py-3 active" for="srcText">
                                    <i class="ki-duotone ki-clipboard-text fs-3 me-1"></i> Text
                                </label>
                                <input type="radio" class="btn-check" name="sourceType" id="srcUrl" value="url">
                                <label class="btn btn-outline btn-outline-dashed btn-active-light-primary py-3" for="srcUrl">
                                    <i class="ki-duotone ki-link fs-3 me-1"></i> URL
                                </label>
                            </div>
                        </div>
                        <!--end::Source Type-->

                        <!--begin::Text Panel-->
                        <div id="textSourcePanel">
                            <div class="fv-row mb-5">
                                <label class="form-label fw-semibold">Paste Job Content</label>
                                <textarea id="aiSourceText" class="form-control form-control-solid" rows="14"
                                    placeholder="Paste job content here..."></textarea>
                                <small class="text-muted" id="textCharCount">0 characters</small>
                            </div>
                        </div>
                        <!--end::Text Panel-->

                        <!--begin::URL Panel-->
                        <div id="urlSourcePanel" style="display:none;">
                            <div class="fv-row mb-5">
                                <label class="form-label fw-semibold">Job URL</label>
                                <input type="url" id="aiSourceUrl" class="form-control form-control-solid"
                                       placeholder="https://company.com/careers/job-title">
                                <small class="text-muted">AI will extract job data from the page.</small>
                            </div>
                        </div>
                        <!--end::URL Panel-->
                        
                        <!--begin::Info-->
                        <div class="d-flex align-items-center p-4 bg-light-primary rounded-2">
                            <i class="ki-duotone ki-bulb fs-2 text-warning me-2"></i>
                            <div class="text-muted fs-7">
                                <strong class="text-gray-800">Tips:</strong> For best results, paste the full job description including requirements, responsibilities, and application instructions.
                            </div>
                        </div>
                        <!--end::Info-->
                        
                    </div>
                    <!--end::Left Column-->
                    
                    <!--begin::Right Column-->
                    <div class="col-md-6">
                        
                        <!--begin::Preview Header-->
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="fw-bold fs-6">Extracted Data Preview</span>
                            <span class="badge badge-light-info" id="extractStatus">Ready</span>
                        </div>
                        <!--end::Preview Header-->
                        
                        <!--begin::Preview Panel-->
                        <div class="border rounded-2 p-4 bg-light" style="min-height:400px;max-height:480px;overflow-y:auto;">
                            <div class="text-center text-muted py-10">
                                <i class="ki-duotone ki-robot fs-2tx d-block mb-3 opacity-25"></i>
                                <p class="mb-0 fw-semibold">No data extracted yet</p>
                                <p class="small">Extracted fields will appear here for review</p>
                            </div>
                            <div id="aiPreviewPanel"></div>
                        </div>
                        <!--end::Preview Panel-->
                        
                        <!--begin::Preview Footer-->
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <small class="text-muted" id="aiTokenInfo">
                                <i class="ki-duotone ki-information-5 fs-7 me-1"></i>
                                No data extracted yet
                            </small>
                            <div class="d-flex align-items-center gap-4">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" id="autoApplyToggle" checked>
                                    <label class="form-check-label fw-semibold text-gray-600 small" for="autoApplyToggle">Auto</label>
                                </div>
                                <button class="btn btn-sm btn-success d-none" id="applyExtractedBtn" onclick="applyExtractedData()">
                                    <i class="ki-duotone ki-check fs-3 me-1"></i>Apply
                                </button>
                            </div>
                        </div>
                        <!--end::Preview Footer-->
                        
                    </div>
                    <!--end::Right Column-->
                    
                </div>
                <!--end::Row-->
                
            </div>
            <!--end::Modal body-->
            
            <!--begin::Modal footer-->
            <div class="modal-footer py-5">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="extractJobData()" id="extractBtn">
                    <span id="extractBtnText">
                        <i class="ki-duotone ki-sparkle fs-3 me-1"></i>Extract Data
                    </span>
                    <span id="extractBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
            </div>
            <!--end::Modal footer-->
            
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>
<!--end::Modal - AI Job Extractor-->