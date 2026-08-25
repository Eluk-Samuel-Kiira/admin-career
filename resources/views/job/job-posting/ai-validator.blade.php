<script>
    // ================================================================
    // FIELD LABELS - for turning raw validator keys into readable text
    // ================================================================
    const FIELD_LABELS = {
        job_title: 'Job Title',
        company_id: 'Company',
        job_category_id: 'Category',
        industry_id: 'Industry',
        job_location_id: 'Location',
        job_type_id: 'Job Type',
        experience_level_id: 'Experience Level',
        education_level_id: 'Education Level',
        salary_range_id: 'Salary Range',
        deadline: 'Application Deadline',
        job_description: 'Job Description',
        responsibilities: 'Responsibilities',
        qualifications: 'Qualifications',
        skills: 'Skills',
        application_procedure: 'Application Procedure',
        email: 'Contact Email',
        telephone: 'Telephone',
        employment_type: 'Employment Type',
        location_type: 'Location Type',
        salary_amount: 'Salary Amount',
        currency: 'Currency',
        payment_period: 'Payment Period',
        job_source: 'Job Source',
        meta_title: 'Meta Title',
        meta_description: 'Meta Description',
        keywords: 'Keywords',
    };

    function friendlyFieldName(key) {
        return FIELD_LABELS[key] || key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function renderValidationErrors(errors) {
        const errorDiv = document.getElementById('formErrors');
        if (!errorDiv) return;

        const items = Object.entries(errors).map(([field, messages]) => {
            const msgList = Array.isArray(messages) ? messages : [messages];
            return `<li><strong>${escapeHtml(friendlyFieldName(field))}:</strong> ${escapeHtml(msgList.join(' '))}</li>`;
        }).join('');

        errorDiv.innerHTML = `
            <div class="alert alert-danger">
                <div class="d-flex align-items-center mb-2">
                    <i class="ki-duotone ki-danger fs-3 me-2"></i>
                    <strong>Please fix the following before submitting:</strong>
                </div>
                <ul class="mb-0 ps-6">${items}</ul>
            </div>`;
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function setSubmitBusy(busy, isDraft) {
        const liveBtn = document.getElementById('submitJobBtn');
        const draftBtn = document.getElementById('submitDraftBtn');
        const activeBtn = isDraft ? draftBtn : liveBtn;
        const otherBtn = isDraft ? liveBtn : draftBtn;
        const btnText = document.getElementById(isDraft ? 'submitDraftBtnText' : 'submitJobBtnText');
        const btnSpinner = document.getElementById(isDraft ? 'submitDraftBtnSpinner' : 'submitJobBtnSpinner');

        if (activeBtn) activeBtn.disabled = busy;
        if (otherBtn) otherBtn.disabled = busy; // prevent switching mode mid-submit / double submit
        if (btnSpinner) btnSpinner.classList.toggle('d-none', !busy);
        if (btnText) btnText.innerHTML = busy
            ? (isDraft ? 'Saving…' : 'Posting…')
            : (isDraft
                ? '<i class="ki-duotone ki-save fs-3 me-2"></i>Save as Draft'
                : '<i class="ki-duotone ki-send fs-3 me-2"></i>Post Job Now');
    }

    // ================================================================
    // SUBMIT JOB POST
    // ================================================================
    async function submitJobPost(mode = 'live', forcePost = false) {
        const isDraft = mode === 'draft';

        setSubmitBusy(true, isDraft);

        const errorDiv = document.getElementById('formErrors');
        if (errorDiv) errorDiv.innerHTML = '';

        // Sync rich editors
        ['f_job_description_editor', 'f_responsibilities_editor', 'f_qualifications_editor', 'f_skills_editor', 'f_application_procedure_editor']
            .forEach(id => richEditorSync(id));

        const form = document.getElementById('aiJobForm');
        const data = {};
        
        // Read FormData
        new FormData(form).forEach((v, k) => data[k] = v);

        // --- CRITICAL FIX: Direct DOM override for experience and education ---
        // FormData sometimes doesn't capture dynamically set hidden input values
        const experienceHidden = document.getElementById('f_experience_id');
        const educationHidden = document.getElementById('f_education_id');
        const companyHidden = document.getElementById('f_company_id');
        const categoryHidden = document.getElementById('f_category_id');
        const industryHidden = document.getElementById('f_industry_id');
        const locationHidden = document.getElementById('f_location_id');
        const jobtypeHidden = document.getElementById('f_jobtype_id');
        const salaryrangeHidden = document.getElementById('f_salaryrange_id');

        // Force override with DOM values
        if (experienceHidden && experienceHidden.value) {
            data.experience_level_id = experienceHidden.value;
            // console.log('✅ FORCE SET experience_level_id:', experienceHidden.value);
        }
        if (educationHidden && educationHidden.value) {
            data.education_level_id = educationHidden.value;
            // console.log('✅ FORCE SET education_level_id:', educationHidden.value);
        }
        if (companyHidden && companyHidden.value) {
            data.company_id = companyHidden.value;
        }
        if (categoryHidden && categoryHidden.value) {
            data.job_category_id = categoryHidden.value;
        }
        if (industryHidden && industryHidden.value) {
            data.industry_id = industryHidden.value;
        }
        if (locationHidden && locationHidden.value) {
            data.job_location_id = locationHidden.value;
        }
        if (jobtypeHidden && jobtypeHidden.value) {
            data.job_type_id = jobtypeHidden.value;
        }
        if (salaryrangeHidden && salaryrangeHidden.value) {
            data.salary_range_id = salaryrangeHidden.value;
        }

        // Debug log
        // console.log('Final form data after DOM override:', {
        //     experience_level_id: data.experience_level_id,
        //     education_level_id: data.education_level_id,
        //     job_category_id: data.job_category_id,
        //     industry_id: data.industry_id,
        //     job_location_id: data.job_location_id,
        //     job_type_id: data.job_type_id,
        //     company_id: data.company_id,
        //     salary_range_id: data.salary_range_id,
        // });

        // Get rich editor content
        const editorFields = {
            job_description: 'f_job_description_editor', 
            responsibilities: 'f_responsibilities_editor',
            qualifications: 'f_qualifications_editor', 
            skills: 'f_skills_editor',
            application_procedure: 'f_application_procedure_editor',
        };
        Object.entries(editorFields).forEach(([field, editorId]) => {
            const content = richEditorGet(editorId);
            if (content) data[field] = content;
        });

        // Boolean fields
        ['is_resume_required','is_cover_letter_required','is_academic_documents_required',
        'is_application_required','is_whatsapp_contact','is_telephone_call',
        'is_featured','is_urgent','is_quick_gig','is_verified','is_simple_job',
        ].forEach(k => { data[k] = data[k] === 'on' || data[k] === true; });

        if (isDraft) data.is_active = false;
        if (forcePost) data.force_post = true;

        // Client-side validation
        const errors = {};
        if (!data.job_title) errors.job_title = ['This field is required.'];
        if (!data.company_id) errors.company_id = ['Type to search and click a company to select it.'];
        if (!data.job_category_id) errors.job_category_id = ['Type to search and click a category to select it.'];
        if (!data.industry_id) errors.industry_id = ['Type to search and click an industry to select it.'];
        if (!data.job_location_id) errors.job_location_id = ['Type to search and click a location to select it.'];
        if (!data.job_type_id) errors.job_type_id = ['Type to search and click a job type to select it.'];
        if (!data.experience_level_id) errors.experience_level_id = ['Type to search and click an experience level to select it.'];
        if (!data.education_level_id) errors.education_level_id = ['Type to search and click an education level to select it.'];
        if (!data.deadline) errors.deadline = ['This field is required.'];
        if (!richEditorGet('f_job_description_editor') && !data.job_description) errors.job_description = ['This field is required.'];

        // Check if any errors exist
        if (Object.keys(errors).length) {
            setSubmitBusy(false, isDraft);
            renderValidationErrors(errors);
            toast(Object.values(errors)[0][0], 'danger');
            return;
        }

        showBanner(isDraft ? 'Saving draft…' : 'Submitting job post…');

        try {
            const res = await apiFetch('/admin/ai/job-posts/store', { method: 'POST', body: JSON.stringify(data) });            hideBanner();
            setSubmitBusy(false, isDraft);
            toast(isDraft ? 'Draft saved!' : 'Job posted successfully!', 'success');
            if (errorDiv) {
                errorDiv.innerHTML = `<div class="alert alert-success"><i class="ki-duotone ki-check fs-3 me-2"></i><strong>${isDraft ? 'Draft saved!' : 'Job posted!'}</strong>${res.data?.slug ? `<a href="/job-posts/${res.data.slug}" class="alert-link ms-2" target="_blank">View job <i class="ki-duotone ki-exit-right fs-3 ms-1"></i></a>` : ''}</div>`;
            }
            setTimeout(() => {
                if (!isDraft) { 
                    if (confirm('Job posted! Post another?')) clearForm(); 
                    else window.location.href = '/admin/job-posts'; 
                } else { 
                    if (!confirm('Draft saved. Continue editing?')) window.location.href = '/admin/job-posts'; 
                }
            }, 1500);
        } catch (err) {
            hideBanner();
            setSubmitBusy(false, isDraft);

            if (err.is_duplicate) {
                const proceed = confirm(
                    (err.message || 'A similar job was already posted recently.') +
                    '\n\nDo you want to post it anyway?'
                );
                if (proceed) {
                    submitJobPost(mode, true);
                    return;
                }
                if (errorDiv) {
                    errorDiv.innerHTML = `<div class="alert alert-warning"><i class="ki-duotone ki-information-5 fs-3 me-2"></i>${escapeHtml(err.message || 'Duplicate job detected.')}</div>`;
                    errorDiv.scrollIntoView({ behavior: 'smooth' });
                }
                toast('Submission cancelled - duplicate detected.', 'warning');
                return;
            }

            if (err.errors && Object.keys(err.errors).length) {
                renderValidationErrors(err.errors);
                const firstMsg = Object.values(err.errors)[0];
                toast(Array.isArray(firstMsg) ? firstMsg[0] : String(firstMsg), 'danger');
                return;
            }

            const errorMsg = err.message || 'Submission failed';
            toast(errorMsg, 'danger');
            if (errorDiv) {
                errorDiv.innerHTML = `<div class="alert alert-danger"><i class="ki-duotone ki-danger fs-3 me-2"></i><strong>Error:</strong><div class="mt-1">${escapeHtml(errorMsg)}</div></div>`;
                errorDiv.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }

</script>