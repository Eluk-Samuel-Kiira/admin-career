@extends('layouts.admin')

@section('title', 'Add Blog Post')
@section('page_title', 'Add Blog Post')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Content</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.blogs') }}" class="text-muted text-hover-primary">Blog Posts</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Add</li>
@endsection

@section('content')
@can('create blogs')
<form id="addBlogForm" action="{{ route('admin.blogs.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row g-6">

        {{-- ============== MAIN COLUMN ============== --}}
        <div class="col-xl-8">

            {{-- Title / Category / Tags --}}
            <div class="card card-flush mb-6">
                <div class="card-header">
                    <h3 class="card-title">Blog Details</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Blog Title</label>
                        <input type="text" class="form-control form-control-solid form-control-lg" name="title"
                               id="add_title" placeholder="Enter a compelling blog title..." required 
                               value="{{ old('title') }}" />
                        <div class="text-muted fs-7 mt-1">
                            <span id="add_title_count">0</span> / 255 characters
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Category</label>
                            <input type="text" class="form-control form-control-solid" name="category"
                                   id="add_category" placeholder="e.g., career-tips" 
                                   value="{{ old('category') }}" />
                            <div class="text-muted fs-7 mt-1">Lowercase with hyphens (auto-generated if left blank)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Tags</label>
                            <input type="text" class="form-control form-control-solid" name="tags"
                                   id="add_tags" list="tagsList" placeholder="career, interview, cv-writing"
                                   value="{{ old('tags') }}" />
                            <datalist id="tagsList"></datalist>
                            <div class="text-muted fs-7 mt-1">Comma-separated</div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="fw-semibold fs-6 mb-2">Excerpt</label>
                        <textarea class="form-control form-control-solid" name="excerpt" id="add_excerpt"
                                  rows="3" maxlength="500" placeholder="Short summary shown in blog listings...">{{ old('excerpt') }}</textarea>
                        <div class="text-muted fs-7 mt-1">
                            <span id="add_excerpt_count">0</span> / 500 characters
                        </div>
                    </div>
                </div>
            </div>

            {{-- Rich content editor --}}
            <div class="card card-flush mb-6">
                <div class="card-header">
                    <h3 class="card-title">Content</h3>
                    <div class="card-toolbar">
                        <span class="text-muted fs-7">Full formatting toolbar, inline images, links &amp; blockquotes</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="mb-3">
                        <label class="required fw-semibold fs-6 mb-2">Blog Content</label>
                        <!-- Editor Container -->
                        <div id="add_content_editor_container" 
                            style="min-height: 400px; background: white; border-radius: 8px; border: 1px solid #e5e7eb;">
                        </div>
                        <!-- Hidden field to store editor content -->
                        <textarea name="content" id="add_content_editor_hidden" style="display:none">{{ old('content') }}</textarea>
                        <div class="text-muted fs-7 mt-2">
                            <span id="add_content_word_count">0</span> words · 
                            <span id="add_content_char_count">0</span> characters
                        </div>
                    </div>
                </div>
            </div>

            {{-- SEO --}}
            <div class="card card-flush mb-6">
                <div class="card-header cursor-pointer" onclick="toggleSeoSection('add')">
                    <h3 class="card-title">
                        <i class="ki-duotone ki-search fs-2 me-2 text-secondary"><span class="path1"></span><span class="path2"></span></i>
                        SEO Metadata
                    </h3>
                    <div class="card-toolbar">
                        <i class="ki-duotone ki-chevron-down fs-3" id="add_seo_chevron"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="card-body pt-0" id="add_seo_body" style="display:none">
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="fw-semibold fs-6 mb-2">Meta Title <span class="text-muted">(50–60 chars)</span></label>
                            <input type="text" class="form-control form-control-solid" name="meta_title" 
                                   id="add_meta_title" maxlength="60" value="{{ old('meta_title') }}" />
                            <div class="text-muted fs-7 mt-1">
                                <span id="add_meta_title_count">0</span> / 60 characters
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="fw-semibold fs-6 mb-2">Meta Description <span class="text-muted">(150–160 chars)</span></label>
                            <textarea class="form-control form-control-solid" name="meta_description" 
                                      id="add_meta_description" rows="2" maxlength="160">{{ old('meta_description') }}</textarea>
                            <div class="text-muted fs-7 mt-1">
                                <span id="add_meta_description_count">0</span> / 160 characters
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="fw-semibold fs-6 mb-2">Keywords</label>
                            <input type="text" class="form-control form-control-solid" name="keywords" 
                                   id="add_keywords" placeholder="Comma separated" value="{{ old('keywords') }}" />
                        </div>
                        <div class="col-12">
                            <label class="fw-semibold fs-6 mb-2">Canonical URL</label>
                            <input type="url" class="form-control form-control-solid" name="canonical_url" 
                                   id="add_canonical_url" value="{{ old('canonical_url') }}" />
                        </div>
                        <!-- <div class="col-12">
                            <label class="fw-semibold fs-6 mb-2">Robots</label>
                            <select class="form-select form-select-solid" name="robots" id="add_robots">
                                <option value="index,follow" {{ old('robots') == 'index,follow' ? 'selected' : '' }}>index, follow</option>
                                <option value="noindex,follow" {{ old('robots') == 'noindex,follow' ? 'selected' : '' }}>noindex, follow</option>
                                <option value="noindex,nofollow" {{ old('robots') == 'noindex,nofollow' ? 'selected' : '' }}>noindex, nofollow</option>
                            </select>
                        </div> -->
                    </div>
                </div>
            </div>

            {{-- Open Graph --}}
            <div class="card card-flush mb-6">
                <div class="card-header cursor-pointer" onclick="toggleOgSection('add')">
                    <h3 class="card-title">
                        <i class="ki-duotone ki-share fs-2 me-2 text-info"><span class="path1"></span><span class="path2"></span></i>
                        Open Graph Metadata
                    </h3>
                    <div class="card-toolbar">
                        <i class="ki-duotone ki-chevron-down fs-3" id="add_og_chevron"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="card-body pt-0" id="add_og_body" style="display:none">
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="fw-semibold fs-6 mb-2">OG Image URL</label>
                            <input type="url" class="form-control form-control-solid" name="og_image" 
                                   id="add_og_image" value="{{ old('og_image') }}" />
                        </div>
                        <div class="col-12">
                            <label class="fw-semibold fs-6 mb-2">OG Title</label>
                            <input type="text" class="form-control form-control-solid" name="og_title" 
                                   id="add_og_title" value="{{ old('og_title') }}" />
                        </div>
                        <div class="col-12">
                            <label class="fw-semibold fs-6 mb-2">OG Description</label>
                            <textarea class="form-control form-control-solid" name="og_description" 
                                      id="add_og_description" rows="2">{{ old('og_description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============== SIDEBAR ============== --}}
        <div class="col-xl-4">

            {{-- Cover image — big, drag-and-drop dropzone --}}
            <div class="card card-flush mb-6">
                <div class="card-header">
                    <h3 class="card-title">Cover Image</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="cover-dropzone" id="add_cover_dropzone">
                        <input type="file" id="add_cover_image_input" accept="image/*" style="display:none"
                               onchange="uploadCoverImage(this, 'add')">

                        <div class="cover-upload-progress d-none" id="add_cover_upload_progress">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Uploading...</span>
                            </div>
                            <p class="text-muted mt-2">Uploading...</p>
                        </div>

                        <div id="add_cover_image_preview" style="display:none">
                            <div class="cover-preview-wrap">
                                <img id="add_cover_image_img" src="" alt="Cover preview" class="img-fluid rounded">
                            </div>
                        </div>

                        <div id="add_cover_image_placeholder" class="text-center py-4">
                            <i class="ki-duotone ki-picture fs-3tx text-muted mb-3 d-block">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <p class="fw-semibold mb-1">Drag &amp; drop an image here</p>
                            <p class="text-muted small mb-3">or click to browse</p>
                            <p class="text-muted small">JPG, PNG, GIF, WEBP · max 5MB</p>
                        </div>

                        <div class="mt-3 text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('add_cover_image_input').click()">
                                <i class="ki-duotone ki-cloud-upload fs-3 me-1"><span class="path1"></span><span class="path2"></span></i> Select Image
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearCoverImage('add')"
                                    style="display:none" id="add_clear_cover_btn">
                                <i class="ki-duotone ki-trash fs-3 me-1"><span class="path1"></span><span class="path2"></span></i> Remove
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="cover_image" id="add_cover_image" value="{{ old('cover_image') }}" />

                    <div class="mt-5">
                        <label class="fw-semibold fs-6 mb-2">Image Alt Text</label>
                        <input type="text" class="form-control form-control-solid" name="cover_image_alt"
                               id="add_cover_image_alt" placeholder="Descriptive text for SEO" 
                               value="{{ old('cover_image_alt') }}" />
                        <div class="text-muted fs-7 mt-1">Important for SEO and accessibility</div>
                    </div>
                    <div class="mt-5">
                        <label class="fw-semibold fs-6 mb-2">Image Caption</label>
                        <input type="text" class="form-control form-control-solid" name="cover_image_caption"
                               id="add_cover_image_caption" placeholder="Optional caption" 
                               value="{{ old('cover_image_caption') }}" />
                    </div>
                </div>
            </div>

            {{-- Publish settings --}}
            <div class="card card-flush mb-6">
                <div class="card-header"><h3 class="card-title">Publish Settings</h3></div>
                <div class="card-body pt-0">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <label class="fw-semibold fs-6 mb-0">Active</label>
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="is_active" id="add_is_active" 
                                   {{ old('is_active', true) ? 'checked' : '' }} value="1" />
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <label class="fw-semibold fs-6 mb-0">Featured</label>
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="add_is_featured" 
                                   {{ old('is_featured') ? 'checked' : '' }} value="1" />
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <label class="fw-semibold fs-6 mb-0">Published</label>
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="is_published" id="add_is_published" 
                                   {{ old('is_published') ? 'checked' : '' }} value="1" />
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="fw-semibold fs-6 mb-2">Publish Date</label>
                        <input type="datetime-local" class="form-control form-control-solid" name="published_at" 
                               id="add_published_at" value="{{ old('published_at') }}" />
                    </div>

                    <div class="mb-4">
                        <label class="required fw-semibold fs-6 mb-2">Country</label>
                        <select class="form-select form-select-solid" name="country_code" id="add_country_code" required>
                            <option value="">Select Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->code }}" {{ old('country_code') == $country->code ? 'selected' : '' }}>
                                    {{ $country->flag ?? $country->flag_emoji ?? '' }} {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="fw-semibold fs-6 mb-2">Sort Order</label>
                        <input type="number" class="form-control form-control-solid" name="sort_order" 
                               id="add_sort_order" value="{{ old('sort_order', 0) }}" min="0" />
                    </div>
                </div>
            </div>

            {{-- Author --}}
            <div class="card card-flush mb-6">
                <div class="card-header"><h3 class="card-title">Author</h3></div>
                <div class="card-body pt-0">
                    <div class="mb-4">
                        <label class="fw-semibold fs-6 mb-2">Author</label>
                        <select class="form-select form-select-solid" name="author_id" id="add_author_id">
                            <option value="">Use logged-in user</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('author_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="fw-semibold fs-6 mb-2">Author Name</label>
                        <input type="text" class="form-control form-control-solid" name="author_name" 
                               id="add_author_name" placeholder="Display name" value="{{ old('author_name') }}" />
                    </div>
                    <div class="mb-4">
                        <label class="fw-semibold fs-6 mb-2">Author Title</label>
                        <input type="text" class="form-control form-control-solid" name="author_title" 
                               id="add_author_title" placeholder="e.g., Career Expert" value="{{ old('author_title') }}" />
                    </div>
                    <div class="mb-0">
                        <label class="fw-semibold fs-6 mb-2">Author Avatar URL</label>
                        <input type="url" class="form-control form-control-solid" name="author_avatar" 
                               id="add_author_avatar" placeholder="https://example.com/avatar.jpg" 
                               value="{{ old('author_avatar') }}" />
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="d-flex flex-column gap-3 mb-10">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.blogs') }}" class="btn btn-light">Discard</a>
                    <button type="submit" class="btn btn-primary" id="addBlogBtn">
                        <span class="indicator-label">Create Blog</span>
                        <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" name="save_and_publish" value="1" class="btn btn-success" id="addPublishBtn">
                        <span class="indicator-label"><i class="ki-duotone ki-check-circle fs-2 me-1"></i> Save &amp; Publish</span>
                        <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@else
    <div class="alert alert-danger">You do not have permission to create blogs.</div>
@endcan
@endsection

@include('job.blog._editor-assets')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill editor
    const quill = initBlogRichEditor('add');
    
    // Initialize cover image dropzone
    if (typeof initCoverDropzone === 'function') {
        initCoverDropzone('add');
    }

    // Populate tag suggestions
    fetch('{{ route("admin.blogs.tags") }}')
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;
            const list = document.getElementById('tagsList');
            if (list) {
                d.tags.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t;
                    list.appendChild(opt);
                });
            }
        })
        .catch(() => {});

    // Character counters
    const titleInput = document.getElementById('add_title');
    const excerptInput = document.getElementById('add_excerpt');
    const metaTitleInput = document.getElementById('add_meta_title');
    const metaDescInput = document.getElementById('add_meta_description');

    function updateCounter(input, counterId, max) {
        if (!input) return;
        const count = input.value.length;
        const counter = document.getElementById(counterId);
        if (counter) counter.textContent = count;
        if (count > max) {
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    }

    if (titleInput) {
        titleInput.addEventListener('input', function() {
            updateCounter(this, 'add_title_count', 255);
        });
        updateCounter(titleInput, 'add_title_count', 255);
    }

    if (excerptInput) {
        excerptInput.addEventListener('input', function() {
            updateCounter(this, 'add_excerpt_count', 500);
        });
        updateCounter(excerptInput, 'add_excerpt_count', 500);
    }

    if (metaTitleInput) {
        metaTitleInput.addEventListener('input', function() {
            updateCounter(this, 'add_meta_title_count', 60);
        });
        updateCounter(metaTitleInput, 'add_meta_title_count', 60);
    }

    if (metaDescInput) {
        metaDescInput.addEventListener('input', function() {
            updateCounter(this, 'add_meta_description_count', 160);
        });
        updateCounter(metaDescInput, 'add_meta_description_count', 160);
    }

    // Word and character counter for content
    if (quill) {
        quill.on('text-change', function() {
            const text = quill.getText();
            const words = text.trim() ? text.trim().split(/\s+/).length : 0;
            const chars = text.replace(/\n/g, '').length;
            
            const wordEl = document.getElementById('add_content_word_count');
            const charEl = document.getElementById('add_content_char_count');
            if (wordEl) wordEl.textContent = words;
            if (charEl) charEl.textContent = chars;
        });
    }
});

// Handle form submission
document.getElementById('addBlogForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Sync editor content before submission
    syncQuillContent('add');
    
    const btn = document.getElementById('addBlogBtn');
    const publishBtn = document.getElementById('addPublishBtn');
    
    // Show spinner on the clicked button
    if (e.submitter === publishBtn) {
        if (typeof window.showButtonSpinner === 'function') {
            window.showButtonSpinner(publishBtn);
        }
        if (btn) btn.disabled = true;
    } else {
        if (typeof window.showButtonSpinner === 'function') {
            window.showButtonSpinner(btn);
        }
        if (publishBtn) publishBtn.disabled = true;
    }

    const formData = new FormData(this);

    // Handle checkboxes
    ['is_active', 'is_featured', 'is_published'].forEach(field => {
        const checkbox = document.querySelector(`#addBlogForm input[name="${field}"]`);
        if (checkbox) {
            formData.set(field, checkbox.checked ? '1' : '0');
        }
    });

    // Ensure content is included
    const contentHidden = document.getElementById('add_content_editor_hidden');
    if (contentHidden) {
        formData.set('content', contentHidden.value);
    }

    // Clean up tags
    const tags = formData.get('tags');
    if (!tags || !tags.trim()) {
        formData.delete('tags');
    }

    // Handle save and publish
    if (e.submitter && e.submitter.name === 'save_and_publish') {
        formData.set('is_published', '1');
    }

    fetch('{{ route("admin.blogs.store") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Server error');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof window.showToast === 'function') {
                window.showToast('success', data.message || 'Blog created successfully!');
            }
            window.location.href = '{{ route("admin.blogs") }}';
        } else if (data.errors) {
            const errorMessages = Object.values(data.errors).flat().join('\n');
            if (typeof window.showToast === 'function') {
                window.showToast('error', errorMessages);
            } else {
                alert(errorMessages);
            }
            resetButtons();
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast('error', data.message || 'Failed to create blog');
            } else {
                alert(data.message || 'Failed to create blog');
            }
            resetButtons();
        }
    })
    .catch(err => {
        console.error('Error:', err);
        if (typeof window.showToast === 'function') {
            window.showToast('error', 'Failed to create blog: ' + err.message);
        } else {
            alert('Failed to create blog: ' + err.message);
        }
        resetButtons();
    });

    function resetButtons() {
        if (btn && typeof window.hideButtonSpinner === 'function') {
            window.hideButtonSpinner(btn);
        }
        if (btn) btn.disabled = false;
        if (publishBtn && typeof window.hideButtonSpinner === 'function') {
            window.hideButtonSpinner(publishBtn);
        }
        if (publishBtn) publishBtn.disabled = false;
    }
});

// Toggle SEO section
function toggleSeoSection(prefix) {
    const body = document.getElementById(prefix + '_seo_body');
    const chevron = document.getElementById(prefix + '_seo_chevron');
    if (body && chevron) {
        if (body.style.display === 'none') {
            body.style.display = 'block';
            chevron.style.transform = 'rotate(180deg)';
        } else {
            body.style.display = 'none';
            chevron.style.transform = 'rotate(0deg)';
        }
    }
}

// Toggle OG section
function toggleOgSection(prefix) {
    const body = document.getElementById(prefix + '_og_body');
    const chevron = document.getElementById(prefix + '_og_chevron');
    if (body && chevron) {
        if (body.style.display === 'none') {
            body.style.display = 'block';
            chevron.style.transform = 'rotate(180deg)';
        } else {
            body.style.display = 'none';
            chevron.style.transform = 'rotate(0deg)';
        }
    }
}

// Cover image upload function
function uploadCoverImage(input, prefix) {
    const file = input.files[0];
    if (!file) return;

    const progressDiv = document.getElementById(prefix + '_cover_upload_progress');
    const previewDiv = document.getElementById(prefix + '_cover_image_preview');
    const placeholderDiv = document.getElementById(prefix + '_cover_image_placeholder');
    const img = document.getElementById(prefix + '_cover_image_img');
    const hidden = document.getElementById(prefix + '_cover_image');
    const clearBtn = document.getElementById(prefix + '_clear_cover_btn');

    if (progressDiv) progressDiv.classList.remove('d-none');
    if (placeholderDiv) placeholderDiv.style.display = 'none';
    if (previewDiv) previewDiv.style.display = 'none';

    const formData = new FormData();
    formData.append('cover_image', file);

    fetch('{{ route("admin.blogs.upload-cover") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (progressDiv) progressDiv.classList.add('d-none');
        if (data.success) {
            if (img) img.src = data.url;
            if (hidden) hidden.value = data.url;
            if (previewDiv) previewDiv.style.display = 'block';
            if (clearBtn) clearBtn.style.display = 'inline-block';
            if (typeof window.showToast === 'function') {
                window.showToast('success', 'Image uploaded successfully');
            }
        } else {
            if (placeholderDiv) placeholderDiv.style.display = 'block';
            if (typeof window.showToast === 'function') {
                window.showToast('error', data.message || 'Upload failed');
            }
        }
    })
    .catch(err => {
        if (progressDiv) progressDiv.classList.add('d-none');
        if (placeholderDiv) placeholderDiv.style.display = 'block';
        console.error('Upload error:', err);
        if (typeof window.showToast === 'function') {
            window.showToast('error', 'Upload failed: ' + err.message);
        }
    });
}

// Clear cover image
function clearCoverImage(prefix) {
    const previewDiv = document.getElementById(prefix + '_cover_image_preview');
    const placeholderDiv = document.getElementById(prefix + '_cover_image_placeholder');
    const hidden = document.getElementById(prefix + '_cover_image');
    const clearBtn = document.getElementById(prefix + '_clear_cover_btn');
    const input = document.getElementById(prefix + '_cover_image_input');

    if (previewDiv) previewDiv.style.display = 'none';
    if (placeholderDiv) placeholderDiv.style.display = 'block';
    if (hidden) hidden.value = '';
    if (clearBtn) clearBtn.style.display = 'none';
    if (input) input.value = '';
}

// Initialize cover dropzone
function initCoverDropzone(prefix) {
    const dropzone = document.getElementById(prefix + '_cover_dropzone');
    if (!dropzone) return;

    let dragCounter = 0;

    dropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dragCounter++;
        this.classList.add('border-primary', 'bg-primary-light');
    });

    dropzone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dragCounter--;
        if (dragCounter === 0) {
            this.classList.remove('border-primary', 'bg-primary-light');
        }
    });

    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dragCounter = 0;
        this.classList.remove('border-primary', 'bg-primary-light');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const input = document.getElementById(prefix + '_cover_image_input');
            if (input) {
                input.files = files;
                uploadCoverImage(input, prefix);
            }
        }
    });

    dropzone.addEventListener('click', function(e) {
        // Don't trigger if clicking on buttons inside
        if (e.target.closest('button')) return;
        const input = document.getElementById(prefix + '_cover_image_input');
        if (input) input.click();
    });
}

// Make functions globally accessible
window.toggleSeoSection = toggleSeoSection;
window.toggleOgSection = toggleOgSection;
window.uploadCoverImage = uploadCoverImage;
window.clearCoverImage = clearCoverImage;
window.initCoverDropzone = initCoverDropzone;
</script>
@endpush