<!-- ═══════════════════════════════════════════════════════════ -->
<!-- STANDALONE LINK SUBMISSION MODAL                             -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="kt_modal_link_sub" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <div>
                    <h3 class="fw-bold mb-0">Link Job Submission</h3>
                    <div class="text-muted fs-7 mt-1" id="link_sub_job_info">—</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-4">
                    <input type="text" class="form-control form-control-solid"
                           id="link_sub_search"
                           placeholder="Search submissions by title or UUID..." />
                </div>

                <div id="link_sub_loading" class="text-center py-8 d-none">
                    <div class="spinner-border text-primary"></div>
                </div>

                <div id="link_sub_list" class="d-flex flex-column gap-2"
                     style="max-height:420px;overflow-y:auto;"></div>

                <div id="link_sub_empty" class="text-center py-8 d-none">
                    <i class="ki-duotone ki-file fs-3x text-muted mb-3 d-block"></i>
                    <p class="text-muted">No available submissions for this company.</p>
                </div>

            </div>

            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-light-danger d-none" id="link_sub_unlink_btn">
                    <i class="ki-duotone ki-cross-circle fs-4 me-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    Unlink Current
                </button>
                <button type="button" class="btn btn-light ms-auto" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<script>
    // ================================================================
// STANDALONE LINK SUBMISSION MODAL
// ================================================================
let linkModalJobId = null;

window.openLinkModal = function (jobId) {
    linkModalJobId = jobId;

    document.getElementById('link_sub_search').value = '';
    document.getElementById('link_sub_list').innerHTML = '';
    document.getElementById('link_sub_empty').classList.add('d-none');
    document.getElementById('link_sub_job_info').textContent = 'Loading...';
    document.getElementById('link_sub_unlink_btn').classList.add('d-none');

    new bootstrap.Modal(document.getElementById('kt_modal_link_sub')).show();

    loadLinkableSubmissions(jobId);
};

window.loadLinkableSubmissions = function (jobId, search = '') {
    const loading = document.getElementById('link_sub_loading');
    const list    = document.getElementById('link_sub_list');
    const empty   = document.getElementById('link_sub_empty');

    loading.classList.remove('d-none');
    list.innerHTML = '';
    empty.classList.add('d-none');

    const params = new URLSearchParams();
    if (search) params.set('search', search);
    const url = `/admin/job-posts/${jobId}/linkable?${params.toString()}`;

    // console.log('🔗 Fetching:', url);

    fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(r => {
            // console.log('🔗 Response status:', r.status);
            return r.json();
        })
        .then(data => {
            // console.log('🔗 Data:', data);
            loading.classList.add('d-none');

            if (!data.success) {
                if (typeof window.showToast === 'function') {
                    window.showToast('error', data.message || 'Failed to load.');
                }
                return;
            }

            document.getElementById('link_sub_job_info').textContent =
                data.job.job_title + ' · ' + data.job.company;

            if (data.job.linked_id) {
                document.getElementById('link_sub_unlink_btn').classList.remove('d-none');
                document.getElementById('link_sub_unlink_btn').dataset.submissionId = data.job.linked_id;
            }

            if (!data.submissions || data.submissions.length === 0) {
                empty.classList.remove('d-none');
                return;
            }

            list.innerHTML = data.submissions.map(s => {
                const linked = s.is_linked_here;
                return `
                    <div class="d-flex justify-content-between align-items-center p-3 border rounded ${linked ? 'border-success bg-light-success' : 'border-gray-300'}">
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-bold text-truncate">${escapeHtml(s.job_title)}</div>
                            <div class="text-muted fs-8">
                                #${s.uuid.substring(0, 8)}
                                · ${escapeHtml(s.service_name)}
                                · ${escapeHtml(s.status_label)}
                            </div>
                        </div>
                        ${linked
                            ? `<span class="badge badge-light-success">✓ Linked</span>`
                            : `<button type="button" class="btn btn-sm btn-primary link-this-sub-btn" data-id="${s.id}">
                                   Link
                               </button>`
                        }
                    </div>
                `;
            }).join('');
        })
        .catch(err => {
            console.error('🔗 Error:', err);
            loading.classList.add('d-none');
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Failed to load submissions: ' + err.message);
            }
        });
};

// Delegated — handles clicks on any "Link" button in the list
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.link-this-sub-btn');
    if (!btn) return;

    e.preventDefault();
    const submissionId = btn.dataset.id;

    if (!linkModalJobId || !submissionId) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch(`/admin/job-posts/${linkModalJobId}/link-submission`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ submission_id: submissionId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (typeof window.showToast === 'function') {
                window.showToast('success', data.message);
            }

            // Close modal
            const modalEl = document.getElementById('kt_modal_link_sub');
            bootstrap.Modal.getInstance(modalEl)?.hide();

            // Reload the table
            loadJobPosts();
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast('error', data.message);
            }
            btn.disabled = false;
            btn.textContent = 'Link';
        }
    })
    .catch(err => {
        console.error(err);
        if (typeof window.showToast === 'function') {
            window.showToast('error', 'Link failed: ' + err.message);
        }
        btn.disabled = false;
        btn.textContent = 'Link';
    });
});

// Search filter in the modal
document.getElementById('link_sub_search')?.addEventListener('keyup', function () {
    if (!linkModalJobId) return;
    loadLinkableSubmissions(linkModalJobId, this.value);
});

// Unlink button
document.getElementById('link_sub_unlink_btn')?.addEventListener('click', function () {
    if (!linkModalJobId) return;
    if (!confirm('Unlink the current submission from this job?')) return;

    const btn = this;
    btn.disabled = true;

    fetch(`/admin/job-posts/${linkModalJobId}/unlink-submission`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (typeof window.showToast === 'function') {
                window.showToast('success', data.message);
            }
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_link_sub'))?.hide();
            loadJobPosts();
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast('error', data.message);
            }
            btn.disabled = false;
        }
    })
    .catch(() => {
        btn.disabled = false;
    });
});

// Reset state when modal closes
document.getElementById('kt_modal_link_sub')?.addEventListener('hidden.bs.modal', function () {
    linkModalJobId = null;
});
</script>