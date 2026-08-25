<script>
    // ================================================================
// VIEW JOB - COMPLETE WITH ALL DETAILS AND STATS
// ================================================================
window.viewJob = function(id) {
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_job'));
    const content = document.getElementById('viewJobContent');
    document.getElementById('viewJobTitle').textContent = 'Job Post Details';
    content.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();

    fetch(`/admin/job-posts/${id}`)
        .then(res => res.json())
        .then(data => {
            // Helper function to get nested value safely
            const get = (obj, path, fallback = 'N/A') => {
                try {
                    return path.split('.').reduce((o, p) => o?.[p], obj) ?? fallback;
                } catch {
                    return fallback;
                }
            };

            // Build status badges
            const statusBadges = `
                ${data.is_active ? '<span class="badge badge-light-success me-1">Active</span>' : '<span class="badge badge-light-danger me-1">Inactive</span>'}
                ${data.is_verified ? '<span class="badge badge-light-success me-1">Verified</span>' : ''}
                ${data.is_featured ? '<span class="badge badge-light-primary me-1">Featured</span>' : ''}
                ${data.is_urgent ? '<span class="badge badge-light-danger me-1">Urgent</span>' : ''}
                ${data.is_pinged ? '<span class="badge badge-light-info me-1">Pinged</span>' : ''}
                ${data.is_indexed ? '<span class="badge badge-light-success me-1">Indexed</span>' : ''}
                ${data.is_simple_job ? '<span class="badge badge-light-warning me-1">Simple Job</span>' : ''}
                ${data.is_quick_gig ? '<span class="badge badge-light-warning me-1">Quick Gig</span>' : ''}
                ${data.migrated_at ? '<span class="badge badge-light-success me-1">Migrated</span>' : '<span class="badge badge-light-warning me-1">Pending Migration</span>'}
            `;

            // Build contact methods badges
            const contactMethods = `
                <div class="d-flex flex-wrap gap-1">
                    ${data.is_whatsapp_contact ? '<span class="badge badge-light-success">WhatsApp Contact</span>' : ''}
                    ${data.is_telephone_call ? '<span class="badge badge-light-info">Telephone Call</span>' : ''}
                </div>
            `;

            // Build application requirements badges
            const appRequirements = `
                <div class="d-flex flex-wrap gap-1">
                    ${data.is_application_required ? '<span class="badge badge-light-primary">Application Required</span>' : ''}
                    ${data.is_resume_required ? '<span class="badge badge-light-success">Resume Required</span>' : ''}
                    ${data.is_cover_letter_required ? '<span class="badge badge-light-warning">Cover Letter Required</span>' : ''}
                    ${data.is_academic_documents_required ? '<span class="badge badge-light-info">Academic Documents Required</span>' : ''}
                </div>
            `;

            // Job source badge
            const jobSourceBadge = data.job_source ? 
                `<span class="badge badge-light-primary">${formatJobSource(data.job_source)}</span>` : 
                '<span class="text-muted">Not specified</span>';

            let html = `
                <!-- Header Stats -->
                <div class="row g-5 mb-7">
                    <div class="col-md-3">
                        <div class="border rounded p-4 text-center h-100">
                            <span class="text-muted d-block fs-7">Job ID</span>
                            <span class="fw-bold fs-2">#${data.id || '-'}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 text-center h-100">
                            <span class="text-muted d-block fs-7">Views</span>
                            <span class="fw-bold fs-2 text-primary">${data.view_count || 0}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 text-center h-100">
                            <span class="text-muted d-block fs-7">Applications</span>
                            <span class="fw-bold fs-2 text-success">${data.application_count || 0}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 text-center h-100">
                            <span class="text-muted d-block fs-7">Clicks</span>
                            <span class="fw-bold fs-2 text-warning">${data.click_count || 0}</span>
                        </div>
                    </div>
                </div>

                <!-- Status Badges -->
                <div class="row mb-7">
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-1">
                            ${statusBadges}
                        </div>
                    </div>
                </div>

                <!-- Job Title -->
                <div class="row mb-7">
                    <div class="col-12">
                        <div class="bg-light-primary rounded p-5">
                            <span class="text-muted d-block fs-7">Job Title</span>
                            <h2 class="fw-bold mb-0">${escapeHtml(data.job_title || 'Untitled')}</h2>
                            ${data.slug ? `<div class="text-muted fs-7 mt-1">Slug: ${escapeHtml(data.slug)}</div>` : ''}
                        </div>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="row g-5 mb-7">
                    <div class="col-md-3">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Company</span>
                            <span class="fw-bold">${escapeHtml(get(data, 'company.name', 'N/A'))}</span>
                            ${data.company ? `<div class="text-muted fs-8">ID: ${data.company.id}</div>` : ''}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Poster</span>
                            <span class="fw-bold">${escapeHtml(get(data, 'poster.name', get(data, 'poster.email', 'N/A')))}</span>
                            ${data.poster ? `<div class="text-muted fs-8">${data.poster.email || ''}</div>` : ''}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Category</span>
                            <span class="fw-bold">${escapeHtml(get(data, 'job_category.name', 'N/A'))}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Industry</span>
                            <span class="fw-bold">${escapeHtml(get(data, 'industry.name', 'N/A'))}</span>
                        </div>
                    </div>
                </div>

                <!-- Location & Job Type -->
                <div class="row g-5 mb-7">
                    <div class="col-md-3">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Location</span>
                            <span class="fw-bold">${escapeHtml(get(data, 'job_location.district', 'N/A'))}</span>
                            ${data.job_location?.city ? `<div class="text-muted fs-8">${escapeHtml(data.job_location.city)}</div>` : ''}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Job Type</span>
                            <span class="fw-bold">${escapeHtml(get(data, 'job_type.name', 'N/A'))}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Experience Level</span>
                            <span class="fw-bold">${escapeHtml(get(data, 'experience_level.name', 'N/A'))}</span>
                            ${data.experience_level?.min_years !== undefined ? `<div class="text-muted fs-8">${data.experience_level.min_years || 0} - ${data.experience_level.max_years || '∞'} years</div>` : ''}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Education Level</span>
                            <span class="fw-bold">${escapeHtml(get(data, 'education_level.name', 'N/A'))}</span>
                            ${data.heighest_finished_education ? `<div class="text-muted fs-8">Highest: ${escapeHtml(data.heighest_finished_education)}</div>` : ''}
                        </div>
                    </div>
                </div>

                <!-- Salary & Employment -->
                <div class="row g-5 mb-7">
                    <div class="col-md-3">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Salary</span>
                            ${data.salary_amount ? `
                                <span class="fw-bold">${data.currency || 'AUD'} ${Number(data.salary_amount).toLocaleString()}</span>
                                ${data.payment_period ? `<div class="text-muted fs-8">${data.payment_period}</div>` : ''}
                            ` : '<span class="text-muted">Not specified</span>'}
                            ${data.salary_range ? `<div class="text-muted fs-8">${escapeHtml(data.salary_range.name)}</div>` : ''}
                            ${data.salary_range_from || data.salary_range_to ? `<div class="text-muted fs-8">Range: ${escapeHtml(data.salary_range_from || '')} - ${escapeHtml(data.salary_range_to || '')}</div>` : ''}
                            ${data.base_salary ? `<div class="text-muted fs-8">Base: ${data.currency || 'AUD'} ${Number(data.base_salary).toLocaleString()}</div>` : ''}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Employment Type</span>
                            <span class="fw-bold">${escapeHtml(data.employment_type || 'N/A')}</span>
                            ${data.location_type ? `<div class="text-muted fs-8">${escapeHtml(data.location_type)}</div>` : ''}
                            ${data.job_source ? `<div class="text-muted fs-8">Source: ${formatJobSource(data.job_source)}</div>` : ''}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Work Hours</span>
                            <span class="fw-bold">${escapeHtml(data.work_hours || 'Not specified')}</span>
                            ${data.duration ? `<div class="text-muted fs-8">Duration: ${escapeHtml(data.duration)}</div>` : ''}
                            ${data.applicant_location_requirements ? `<div class="text-muted fs-8">${escapeHtml(data.applicant_location_requirements)}</div>` : ''}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Duty Station</span>
                            <span class="fw-bold">${escapeHtml(data.duty_station || 'N/A')}</span>
                            ${data.job_reference ? `<div class="text-muted fs-8">Ref: ${escapeHtml(data.job_reference)}</div>` : ''}
                            ${data.job_type_legacy || data.job_status_legacy ? `
                                <div class="text-muted fs-8">
                                    ${data.job_type_legacy ? `Legacy Type: ${escapeHtml(data.job_type_legacy)}` : ''}
                                    ${data.job_status_legacy ? ` | Legacy Status: ${escapeHtml(data.job_status_legacy)}` : ''}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>

                <!-- Contact Details -->
                <div class="row g-5 mb-7">
                    <div class="col-md-6">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Contact Information</span>
                            ${data.email ? `<div><i class="ki-duotone ki-message fs-3 me-2"></i> ${escapeHtml(data.email)}</div>` : ''}
                            ${data.telephone ? `<div><i class="ki-duotone ki-phone fs-3 me-2"></i> ${escapeHtml(data.telephone)}</div>` : ''}
                            ${!data.email && !data.telephone ? '<span class="text-muted">No contact information provided</span>' : ''}
                            <div class="mt-2">${contactMethods}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-4 h-100">
                            <span class="text-muted d-block fs-7">Application Requirements</span>
                            ${appRequirements}
                            ${data.application_procedure ? `
                                <div class="mt-2">
                                    <span class="text-muted d-block fs-7">Procedure</span>
                                    <div class="bg-light p-2 rounded" style="max-height: 80px; overflow-y: auto;">${data.application_procedure}</div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>

                <!-- Address Details -->
                <div class="row g-5 mb-7">
                    <div class="col-md-12">
                        <div class="border rounded p-4">
                            <span class="text-muted d-block fs-7">Address</span>
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Street:</strong> ${escapeHtml(data.street_address || 'N/A')}
                                </div>
                                <div class="col-md-4">
                                    <strong>City:</strong> ${escapeHtml(data.city || 'N/A')}
                                </div>
                                <div class="col-md-4">
                                    <strong>State:</strong> ${escapeHtml(data.state || 'N/A')}
                                </div>
                                <div class="col-md-4 mt-2">
                                    <strong>Country:</strong> ${escapeHtml(data.country || 'N/A')}
                                </div>
                                <div class="col-md-4 mt-2">
                                    <strong>Zipcode:</strong> ${escapeHtml(data.zipcode || 'N/A')}
                                </div>
                                <div class="col-md-4 mt-2">
                                    <strong>Country Code:</strong> ${escapeHtml(data.country_code || 'N/A')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO & Performance Metrics -->
                <div class="row g-5 mb-7">
                    <div class="col-md-12">
                        <div class="border rounded p-4">
                            <span class="text-muted d-block fs-7 mb-3">SEO & Performance Metrics</span>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="bg-light p-3 rounded text-center">
                                        <span class="text-muted d-block fs-7">SEO Score</span>
                                        <span class="fw-bold fs-3 ${data.seo_score >= 70 ? 'text-success' : data.seo_score >= 50 ? 'text-warning' : 'text-danger'}">${data.seo_score || 0}%</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="bg-light p-3 rounded text-center">
                                        <span class="text-muted d-block fs-7">Content Quality</span>
                                        <span class="fw-bold fs-3 ${data.content_quality_score >= 70 ? 'text-success' : data.content_quality_score >= 50 ? 'text-warning' : 'text-danger'}">${data.content_quality_score || 0}%</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="bg-light p-3 rounded text-center">
                                        <span class="text-muted d-block fs-7">CTR</span>
                                        <span class="fw-bold fs-3 text-primary">${data.click_through_rate || 0}%</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="bg-light p-3 rounded text-center">
                                        <span class="text-muted d-block fs-7">Google Rank</span>
                                        <span class="fw-bold fs-3">${data.google_rank || 'N/A'}</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-3">
                                    <div class="bg-light p-3 rounded text-center">
                                        <span class="text-muted d-block fs-7">Search Impressions</span>
                                        <span class="fw-bold fs-3 text-info">${data.search_impressions || 0}</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-3">
                                    <div class="bg-light p-3 rounded text-center">
                                        <span class="text-muted d-block fs-7">Search Clicks</span>
                                        <span class="fw-bold fs-3 text-success">${data.search_clicks || 0}</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-3">
                                    <div class="bg-light p-3 rounded text-center">
                                        <span class="text-muted d-block fs-7">Social Shares</span>
                                        <span class="fw-bold fs-3 text-primary">${data.social_shares || 0}</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-3">
                                    <div class="bg-light p-3 rounded text-center">
                                        <span class="text-muted d-block fs-7">Backlinks</span>
                                        <span class="fw-bold fs-3 text-warning">${data.backlinks_count || 0}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO Meta -->
                <div class="row g-5 mb-7">
                    <div class="col-md-12">
                        <div class="border rounded p-4">
                            <span class="text-muted d-block fs-7">SEO Meta</span>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Meta Title:</strong>
                                    <div class="bg-light p-2 rounded">${escapeHtml(data.meta_title || 'Not set')}</div>
                                </div>
                                <div class="col-md-6">
                                    <strong>Meta Description:</strong>
                                    <div class="bg-light p-2 rounded">${escapeHtml(data.meta_description || 'Not set')}</div>
                                </div>
                                ${data.keywords ? `
                                    <div class="col-md-12 mt-2">
                                        <strong>Keywords:</strong>
                                        <div class="bg-light p-2 rounded">${escapeHtml(data.keywords)}</div>
                                    </div>
                                ` : ''}
                                ${data.focus_keyphrase ? `
                                    <div class="col-md-6 mt-2">
                                        <strong>Focus Keyphrase:</strong>
                                        <div class="bg-light p-2 rounded">${escapeHtml(data.focus_keyphrase)}</div>
                                    </div>
                                ` : ''}
                                ${data.canonical_url ? `
                                    <div class="col-md-6 mt-2">
                                        <strong>Canonical URL:</strong>
                                        <div class="bg-light p-2 rounded">${escapeHtml(data.canonical_url)}</div>
                                    </div>
                                ` : ''}
                                ${data.seo_synonyms ? `
                                    <div class="col-md-12 mt-2">
                                        <strong>SEO Synonyms:</strong>
                                        <div class="bg-light p-2 rounded">${escapeHtml(data.seo_synonyms)}</div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Sections -->
                ${data.job_description ? `
                    <div class="row mb-5">
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <span class="text-muted d-block fs-7 mb-2">Job Description</span>
                                <div class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">${data.job_description}</div>
                            </div>
                        </div>
                    </div>
                ` : ''}

                ${data.responsibilities ? `
                    <div class="row mb-5">
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <span class="text-muted d-block fs-7 mb-2">Responsibilities</span>
                                <div class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">${data.responsibilities}</div>
                            </div>
                        </div>
                    </div>
                ` : ''}

                ${data.skills ? `
                    <div class="row mb-5">
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <span class="text-muted d-block fs-7 mb-2">Skills</span>
                                <div class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">${data.skills}</div>
                            </div>
                        </div>
                    </div>
                ` : ''}

                ${data.qualifications ? `
                    <div class="row mb-5">
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <span class="text-muted d-block fs-7 mb-2">Qualifications</span>
                                <div class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">${data.qualifications}</div>
                            </div>
                        </div>
                    </div>
                ` : ''}

                ${data.application_procedure ? `
                    <div class="row mb-5">
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <span class="text-muted d-block fs-7 mb-2">Application Procedure</span>
                                <div class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">${data.application_procedure}</div>
                            </div>
                        </div>
                    </div>
                ` : ''}

                ${data.ai_content_analysis ? `
                    <div class="row mb-5">
                        <div class="col-12">
                            <div class="border rounded p-4">
                                <span class="text-muted d-block fs-7 mb-2">AI Content Analysis</span>
                                <div class="bg-light p-3 rounded" style="max-height: 150px; overflow-y: auto;">${escapeHtml(data.ai_content_analysis)}</div>
                            </div>
                        </div>
                    </div>
                ` : ''}

                <!-- Legacy & System Information -->
                <div class="row g-5 mb-7">
                    <div class="col-md-12">
                        <div class="border rounded p-4">
                            <span class="text-muted d-block fs-7">Legacy & System Information</span>
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Legacy ID:</strong>
                                    <span class="d-block">${data.legacy_id || 'N/A'}</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Legacy Alias:</strong>
                                    <span class="d-block">${escapeHtml(data.legacy_alias || 'N/A')}</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Legacy Company ID:</strong>
                                    <span class="d-block">${data.legacy_company_id || 'N/A'}</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Legacy UID:</strong>
                                    <span class="d-block">${data.legacy_uid || 'N/A'}</span>
                                </div>
                                <div class="col-md-3 mt-2">
                                    <strong>Experience Months:</strong>
                                    <span class="d-block">${data.experience_months || 0}</span>
                                </div>
                                <div class="col-md-3 mt-2">
                                    <strong>Job Source:</strong>
                                    <span class="d-block">${jobSourceBadge}</span>
                                </div>
                                <div class="col-md-3 mt-2">
                                    <strong>Legacy Job Type:</strong>
                                    <span class="d-block">${escapeHtml(data.job_type_legacy || 'N/A')}</span>
                                </div>
                                <div class="col-md-3 mt-2">
                                    <strong>Legacy Job Status:</strong>
                                    <span class="d-block">${escapeHtml(data.job_status_legacy || 'N/A')}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="row g-5 mb-7">
                    <div class="col-md-12">
                        <div class="border rounded p-4">
                            <span class="text-muted d-block fs-7">Timestamps</span>
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Created:</strong>
                                    <span class="d-block">${formatDate(data.created_at)}</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Updated:</strong>
                                    <span class="d-block">${formatDate(data.updated_at)}</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Published:</strong>
                                    <span class="d-block">${data.published_at ? formatDate(data.published_at) : 'Not published'}</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Published Until:</strong>
                                    <span class="d-block">${data.published_until ? formatDate(data.published_until) : 'Not set'}</span>
                                </div>
                                <div class="col-md-3 mt-2">
                                    <strong>Deadline:</strong>
                                    <span class="d-block ${data.deadline && new Date(data.deadline) < new Date() ? 'text-danger' : ''}">${formatDateOnly(data.deadline)}</span>
                                    ${data.days_remaining !== null ? `<span class="text-muted fs-8">${data.days_remaining >= 0 ? data.days_remaining + ' days remaining' : 'Expired'}</span>` : ''}
                                </div>
                                <div class="col-md-3 mt-2">
                                    <strong>Featured Until:</strong>
                                    <span class="d-block">${data.featured_until ? formatDate(data.featured_until) : 'Not featured'}</span>
                                </div>
                                <div class="col-md-3 mt-2">
                                    <strong>Migrated:</strong>
                                    <span class="d-block">${data.migrated_at ? formatDate(data.migrated_at) : 'Not migrated'}</span>
                                </div>
                                <div class="col-md-3 mt-2">
                                    <strong>Deleted:</strong>
                                    <span class="d-block">${data.deleted_at ? formatDate(data.deleted_at) : 'Not deleted'}</span>
                                </div>
                                <div class="col-md-3 mt-2">
                                    <strong>Last Pinged:</strong>
                                    <span class="d-block">${data.last_pinged_at ? formatDate(data.last_pinged_at) : 'Never'}</span>
                                </div>
                                <div class="col-md-3 mt-2">
                                    <strong>Last Indexed:</strong>
                                    <span class="d-block">${data.last_indexed_at ? formatDate(data.last_indexed_at) : 'Never'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Optimization -->
                ${data.ai_optimized_title || data.ai_optimized_description || data.ai_recommendations ? `
                    <div class="row g-5 mb-7">
                        <div class="col-md-12">
                            <div class="border rounded p-4">
                                <span class="text-muted d-block fs-7">AI Optimization</span>
                                ${data.ai_optimized_title ? `
                                    <div class="mb-2">
                                        <strong>AI Optimized Title:</strong>
                                        <div class="bg-light p-2 rounded">${escapeHtml(data.ai_optimized_title)}</div>
                                    </div>
                                ` : ''}
                                ${data.ai_optimized_description ? `
                                    <div class="mb-2">
                                        <strong>AI Optimized Description:</strong>
                                        <div class="bg-light p-2 rounded">${escapeHtml(data.ai_optimized_description)}</div>
                                    </div>
                                ` : ''}
                                ${data.ai_recommendations ? `
                                    <div>
                                        <strong>AI Recommendations:</strong>
                                        <div class="bg-light p-2 rounded">${escapeHtml(data.ai_recommendations)}</div>
                                    </div>
                                ` : ''}
                                ${data.ai_content_analysis ? `
                                    <div class="mt-2">
                                        <strong>AI Content Analysis:</strong>
                                        <div class="bg-light p-2 rounded" style="max-height: 100px; overflow-y: auto;">${escapeHtml(data.ai_content_analysis)}</div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                ` : ''}

                <!-- Structured Data -->
                ${data.structured_data ? `
                    <div class="row g-5 mb-7">
                        <div class="col-md-12">
                            <div class="border rounded p-4">
                                <span class="text-muted d-block fs-7">Structured Data</span>
                                <div class="bg-light p-2 rounded" style="max-height: 150px; overflow-y: auto; font-size: 12px;">
                                    <pre style="white-space: pre-wrap; word-wrap: break-word;">${escapeHtml(JSON.stringify(data.structured_data, null, 2))}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                ` : ''}

                <!-- Search Terms & Competitor Analysis -->
                ${data.search_terms || data.competitor_analysis ? `
                    <div class="row g-5 mb-7">
                        <div class="col-md-12">
                            <div class="border rounded p-4">
                                <span class="text-muted d-block fs-7">Search & Competitor Data</span>
                                ${data.search_terms ? `
                                    <div class="mb-2">
                                        <strong>Search Terms:</strong>
                                        <div class="bg-light p-2 rounded">${escapeHtml(JSON.stringify(data.search_terms))}</div>
                                    </div>
                                ` : ''}
                                ${data.competitor_analysis ? `
                                    <div>
                                        <strong>Competitor Analysis:</strong>
                                        <div class="bg-light p-2 rounded">${escapeHtml(JSON.stringify(data.competitor_analysis))}</div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                ` : ''}

                <!-- Ranking Keywords -->
                ${data.ranking_keywords ? `
                    <div class="row g-5 mb-7">
                        <div class="col-md-12">
                            <div class="border rounded p-4">
                                <span class="text-muted d-block fs-7">Ranking Keywords</span>
                                <div class="bg-light p-2 rounded">${escapeHtml(JSON.stringify(data.ranking_keywords))}</div>
                            </div>
                        </div>
                    </div>
                ` : ''}
            `;

            content.innerHTML = html;
        })
        .catch(err => {
            console.error('Error loading job details:', err);
            content.innerHTML = '<div class="text-center text-danger py-5"><i class="ki-duotone ki-information-5 fs-2tx d-block mb-3"></i>Failed to load job post details</div>';
        });
};
</script>