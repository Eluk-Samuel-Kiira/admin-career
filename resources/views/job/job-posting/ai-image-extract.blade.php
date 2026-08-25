<!--begin::Modal - Image Job Extractor-->
<div class="modal fade" id="imageExtractModal" tabindex="-1" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header py-7 d-flex justify-content-between" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="modal-title text-white">
                        <i class="ki-duotone ki-picture fs-2 me-2"></i>
                        Image Job Extractor
                    </h5>
                    <span class="badge bg-white text-primary ms-2">AI Vision</span>
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
                
                <!--begin::AI Vision Model Selector-->
                <div class="card card-flush shadow-sm mb-8">
                    <div class="card-header border-0 pt-5">
                        <div class="d-flex align-items-center gap-3">
                            <h6 class="card-title fw-bold">
                                <i class="ki-duotone ki-robot fs-2 me-2 text-primary"></i>
                                Vision Model
                            </h6>
                            <span class="badge badge-light-info fs-7 px-3 py-2" id="imgModelDisplay">Gemini</span>
                        </div>
                        <div class="card-toolbar">
                            <span class="text-muted fw-semibold fs-7">
                                <i class="ki-duotone ki-check-circle fs-2 text-success me-1"></i>
                                Image supported
                            </span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-2" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button='true']" id="imgModelSelector">
                            <div class="col-12 text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading models...</span>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="imgSelectedModel" value="gemini">
                    </div>
                </div>
                <!--end::AI Vision Model Selector-->
                
                <!--begin::Row-->
                <div class="row g-5">
                    
                    <!--begin::Left Column - Upload-->
                    <div class="col-md-5">
                        
                        <!--begin::Upload Area-->
                        <div class="fv-row mb-5">
                            <label class="form-label fw-semibold">Upload Images <span class="text-muted">(max 5)</span></label>
                            <div id="multiImgDropZone" 
                                 class="border border-2 border-dashed rounded-3 p-8 text-center bg-light cursor-pointer"
                                 style="border-style:dashed !important;min-height:180px;"
                                 onclick="document.getElementById('multiImgFileInput').click()"
                                 ondrop="handleMultiImgDrop(event)" 
                                 ondragover="event.preventDefault()">
                                <i class="ki-duotone ki-cloud-upload fs-2tx text-muted d-block mb-3"></i>
                                <p class="mb-1 fw-semibold fs-6">Drop images here or click to browse</p>
                                <p class="text-muted small mb-0">JPG, PNG, WEBP — max 5MB each • Up to 5 images</p>
                            </div>
                            <input type="file" id="multiImgFileInput" accept="image/*" multiple style="display:none"
                                   onchange="handleMultiImgSelect(event)">
                        </div>
                        <!--end::Upload Area-->
                        
                        <!--begin::Image Gallery-->
                        <div id="imageGallery" class="mb-5">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label fw-semibold small mb-0">Image Gallery</label>
                                <span class="badge badge-light-secondary" id="imageCount">0 / 5</span>
                            </div>
                            <div id="galleryContainer" class="d-flex flex-wrap gap-2" style="min-height:60px;max-height:280px;overflow-y:auto;">
                                <div class="text-muted small p-2">No images uploaded yet</div>
                            </div>
                        </div>
                        <!--end::Image Gallery-->
                        
                        <!--begin::Image Preview-->
                        <div id="imgPreviewWrap" style="display:none;" class="mb-5">
                            <label class="form-label fw-semibold small">Preview</label>
                            <div class="border rounded-2 p-2 bg-light">
                                <img id="imgPreview" class="img-fluid rounded-1" alt="Preview" style="max-height:150px;width:100%;object-fit:contain;">
                            </div>
                        </div>
                        <!--end::Image Preview-->
                        
                        <!--begin::Extract Button-->
                        <button class="btn btn-primary w-100 py-3" onclick="extractFromMultipleImages()" id="multiImgExtractBtn">
                            <span id="multiImgExtractBtnText">
                                <i class="ki-duotone ki-picture fs-3 me-2"></i>Extract from All Images
                            </span>
                            <span id="multiImgExtractBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                        </button>
                        <!--end::Extract Button-->
                        
                    </div>
                    <!--end::Left Column-->
                    
                    <!--begin::Right Column - Preview-->
                    <div class="col-md-7">
                        
                        <!--begin::Preview Header-->
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="fw-bold fs-6">Extracted Data</span>
                            <span class="badge badge-light-info" id="imgExtractStatus">Ready</span>
                        </div>
                        <!--end::Preview Header-->
                        
                        <!--begin::Preview Panel-->
                        <div class="border rounded-2 p-4 bg-light" style="min-height:400px;max-height:480px;overflow-y:auto;">
                            <div class="text-center text-muted py-10">
                                <i class="ki-duotone ki-picture fs-2tx d-block mb-3 opacity-25"></i>
                                <p class="mb-1 fw-semibold">Upload images to extract</p>
                                <p class="small">AI will extract job data from uploaded images</p>
                            </div>
                            <div id="imgPreviewPanel"></div>
                        </div>
                        <!--end::Preview Panel-->
                        
                        <!--begin::Preview Footer-->
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <small class="text-muted" id="imgTokenInfo">
                                <i class="ki-duotone ki-information-5 fs-7 me-1"></i>
                                No images processed yet
                            </small>
                            <div class="d-flex align-items-center gap-4">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" id="imgAutoApplyToggle" checked>
                                    <label class="form-check-label fw-semibold text-gray-600 small" for="imgAutoApplyToggle">Auto</label>
                                </div>
                                <button class="btn btn-sm btn-success d-none" id="applyImgBtn" onclick="applyImageData()">
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
                <button class="btn btn-primary" onclick="extractFromMultipleImages()" id="multiImgExtractBtnFooter" style="display:none;">
                    <i class="ki-duotone ki-picture fs-3 me-1"></i>Extract
                </button>
            </div>
            <!--end::Modal footer-->
            
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>
<!--end::Modal - Image Job Extractor-->
<style>
/* Drop zone hover effect */
.drop-zone-dragover {
    border-color: #009ef7 !important;
    background: #f1faff !important;
}
</style>