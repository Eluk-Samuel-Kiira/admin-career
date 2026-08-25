<!-- Edit Job Post Modal -->
<div class="modal fade" id="kt_modal_edit_job" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Job Post</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editJobPostForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="job_post_id" id="edit_job_post_id">
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Job Title</label>
                            <input type="text" class="form-control form-control-solid" name="job_title" id="edit_job_title" required />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Deadline</label>
                            <input type="date" class="form-control form-control-solid" name="deadline" id="edit_deadline" required />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country</label>
                            <select class="form-select form-select-solid" name="country_code" id="edit_job_country_code" required>
                                <option value="AU">🇦🇺 Australia</option>
                                @foreach(\App\Helpers\CountryHelper::getCountriesWithFlags() as $country)
                                    <option value="{{ $country['code'] }}">
                                        {{ $country['flag'] }} {{ $country['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Job Source</label>
                            <select name="job_source" id="edit_job_source" class="form-select form-select-solid" required>
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

                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Company</label>
                            <div class="searchable-select" id="edit_company_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="edit_company_search" placeholder="Type to search company..." autocomplete="off">
                                <input type="hidden" name="company_id" id="edit_company" value="">
                                <div class="searchable-select-dropdown" id="edit_company_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Category</label>
                            <div class="searchable-select" id="edit_job_category_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="edit_job_category_search" placeholder="Type to search category..." autocomplete="off">
                                <input type="hidden" name="job_category_id" id="edit_job_category" value="">
                                <div class="searchable-select-dropdown" id="edit_job_category_dropdown"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Industry</label>
                            <div class="searchable-select" id="edit_industry_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="edit_industry_search" placeholder="Type to search industry..." autocomplete="off">
                                <input type="hidden" name="industry_id" id="edit_industry" value="">
                                <div class="searchable-select-dropdown" id="edit_industry_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Location</label>
                            <div class="searchable-select" id="edit_job_location_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="edit_job_location_search" placeholder="Type to search location..." autocomplete="off">
                                <input type="hidden" name="job_location_id" id="edit_job_location" value="">
                                <div class="searchable-select-dropdown" id="edit_job_location_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Job Type</label>
                            <div class="searchable-select" id="edit_job_type_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="edit_job_type_search" placeholder="Type to search job type..." autocomplete="off">
                                <input type="hidden" name="job_type_id" id="edit_job_type" value="">
                                <div class="searchable-select-dropdown" id="edit_job_type_dropdown"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Experience Level</label>
                            <div class="searchable-select" id="edit_experience_level_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="edit_experience_level_search" placeholder="Type to search experience..." autocomplete="off">
                                <input type="hidden" name="experience_level_id" id="edit_experience_level" value="">
                                <div class="searchable-select-dropdown" id="edit_experience_level_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Education Level</label>
                            <div class="searchable-select" id="edit_education_level_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="edit_education_level_search" placeholder="Type to search education..." autocomplete="off">
                                <input type="hidden" name="education_level_id" id="edit_education_level" value="">
                                <div class="searchable-select-dropdown" id="edit_education_level_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Salary Range</label>
                            <div class="searchable-select" id="edit_salary_range_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                    id="edit_salary_range_search" placeholder="Type to search salary..." autocomplete="off">
                                <input type="hidden" name="salary_range_id" id="edit_salary_range" value="">
                                <div class="searchable-select-dropdown" id="edit_salary_range_dropdown"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Salary Amount</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="salary_amount" id="edit_salary_amount" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Currency</label>
                            <input type="text" class="form-control form-control-solid" name="currency" id="edit_currency" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Duty Station</label>
                            <input type="text" class="form-control form-control-solid" name="duty_station" id="edit_duty_station" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Location Type</label>
                            <select class="form-select form-select-solid" name="location_type" id="edit_location_type">
                                <option value="on-site">On-site</option>
                                <option value="remote">Remote</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Employment Type</label>
                            <select class="form-select form-select-solid" name="employment_type" id="edit_employment_type">
                                <option value="full-time">Full-time</option>
                                <option value="part-time">Part-time</option>
                                <option value="contract">Contract</option>
                                <option value="temporary">Temporary</option>
                                <option value="internship">Internship</option>
                                <option value="freelance">Freelance</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Work Hours</label>
                            <input type="text" class="form-control form-control-solid" name="work_hours" id="edit_work_hours" placeholder="e.g., 40 hours/week" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" class="form-control form-control-solid" name="email" id="edit_email" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Telephone</label>
                            <input type="text" class="form-control form-control-solid" name="telephone" id="edit_telephone" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Job Reference</label>
                            <input type="text" class="form-control form-control-solid" name="job_reference" id="edit_job_reference" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Duration</label>
                            <input type="text" class="form-control form-control-solid" name="duration" id="edit_duration" />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Job Description</label>
                        <div id="edit_description_editor_container"></div>
                        <input type="hidden" name="job_description" id="edit_description_hidden">
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Responsibilities</label>
                        <div id="edit_responsibilities_editor_container"></div>
                        <input type="hidden" name="responsibilities" id="edit_responsibilities_hidden">
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Skills</label>
                        <div id="edit_skills_editor_container"></div>
                        <input type="hidden" name="skills" id="edit_skills_hidden">
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Qualifications</label>
                        <div id="edit_qualifications_editor_container"></div>
                        <input type="hidden" name="qualifications" id="edit_qualifications_hidden">
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Application Procedure</label>
                        <div id="edit_application_editor_container"></div>
                        <input type="hidden" name="application_procedure" id="edit_application_hidden">
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
                                <input class="form-check-input" type="checkbox" name="is_urgent" id="edit_is_urgent" />
                                <label class="form-check-label fw-semibold">Urgent</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_simple_job" id="edit_is_simple_job" />
                                <label class="form-check-label fw-semibold">Simple Job</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_quick_gig" id="edit_is_quick_gig" />
                                <label class="form-check-label fw-semibold">Quick Gig</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_whatsapp_contact" id="edit_is_whatsapp_contact" />
                                <label class="form-check-label fw-semibold">WhatsApp Contact</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_telephone_call" id="edit_is_telephone_call" />
                                <label class="form-check-label fw-semibold">Telephone Call</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_application_required" id="edit_is_application_required" />
                                <label class="form-check-label fw-semibold">Application Required</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_resume_required" id="edit_is_resume_required" checked />
                                <label class="form-check-label fw-semibold">Resume Required</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_cover_letter_required" id="edit_is_cover_letter_required" />
                                <label class="form-check-label fw-semibold">Cover Letter Required</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_academic_documents_required" id="edit_is_academic_documents_required" />
                                <label class="form-check-label fw-semibold">Academic Documents Required</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editJobBtn">
                            <span class="indicator-label">Update Job Post</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>