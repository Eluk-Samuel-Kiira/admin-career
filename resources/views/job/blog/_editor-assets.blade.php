<!-- Rich Text Editor Assets -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.min.js"></script>

<style>
    .ql-editor {
        min-height: 400px;
        font-size: 16px;
        line-height: 1.8;
    }
    .ql-toolbar {
        border-radius: 8px 8px 0 0;
        background: #f8f9fa;
    }
    .ql-container {
        border-radius: 0 0 8px 8px;
        font-family: 'Inter', sans-serif;
    }
    .ql-editor p {
        margin-bottom: 1rem;
    }
    .ql-editor h1, .ql-editor h2, .ql-editor h3 {
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }
    .ql-editor blockquote {
        border-left: 4px solid #3b82f6;
        padding-left: 1rem;
        margin-left: 0;
        color: #4b5563;
        font-style: italic;
    }
    .ql-editor img {
        max-width: 100%;
        border-radius: 8px;
        margin: 1rem 0;
    }
    .ql-editor ul, .ql-editor ol {
        padding-left: 1.5rem;
        margin-bottom: 1rem;
    }
    .ql-editor a {
        color: #3b82f6;
        text-decoration: underline;
    }
    .ql-editor .ql-video {
        width: 100%;
        height: 400px;
        border-radius: 8px;
    }
    @media (max-width: 768px) {
        .ql-editor .ql-video {
            height: 250px;
        }
        .ql-editor {
            min-height: 300px;
        }
        .ql-toolbar .ql-formats {
            margin-right: 4px;
        }
        .ql-toolbar button {
            width: 28px;
            height: 28px;
        }
    }
</style>

<script>
// Store editor instances globally
window.quillEditors = {};

// Initialize Quill editor
function initBlogRichEditor(prefix) {
    const container = document.getElementById(prefix + '_content_editor_container');
    if (!container) {
        console.error('Editor container not found:', prefix + '_content_editor_container');
        return;
    }

    // Check if editor already exists
    if (window.quillEditors[prefix]) {
        return;
    }

    // Get existing content from hidden field
    const hiddenField = document.getElementById(prefix + '_content_editor_hidden');
    const existingContent = hiddenField ? hiddenField.value : '';

    // Initialize Quill
    const quill = new Quill(container, {
        theme: 'snow',
        placeholder: 'Write your blog content here...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'font': [] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }, { 'list': 'check' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                [{ 'align': [] }],
                ['link', 'image', 'video'],
                ['clean']
            ],
            clipboard: {
                matchVisual: false
            }
        }
    });

    // Load existing content if any
    if (existingContent) {
        quill.clipboard.dangerouslyPasteHTML(existingContent);
    }

    // Store editor instance
    window.quillEditors[prefix] = quill;

    // Auto-save to hidden field on change
    quill.on('text-change', function() {
        syncQuillContent(prefix);
    });

    // Handle image uploads
    const toolbar = container.querySelector('.ql-toolbar');
    if (toolbar) {
        const imageButton = toolbar.querySelector('.ql-image');
        if (imageButton) {
            imageButton.addEventListener('click', function(e) {
                e.preventDefault();
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.setAttribute('multiple', 'multiple');
                input.click();
                
                input.onchange = function() {
                    const files = this.files;
                    for (let i = 0; i < files.length; i++) {
                        uploadImageToEditor(files[i], quill);
                    }
                };
            });
        }
    }

    // Handle Enter key to stay in paragraph mode
    quill.on('selection-change', function(range) {
        if (range) {
            const format = quill.getFormat(range);
            if (format.header) {
                // Stay in header if user wants
            }
        }
    });

    console.log('✅ Editor initialized for:', prefix);
    return quill;
}

// Sync Quill content to hidden field
function syncQuillContent(prefix) {
    const quill = window.quillEditors[prefix];
    if (!quill) return;
    
    const hiddenField = document.getElementById(prefix + '_content_editor_hidden');
    if (hiddenField) {
        const content = quill.root.innerHTML;
        hiddenField.value = content;
        // Trigger change event for any listeners
        hiddenField.dispatchEvent(new Event('change'));
    }
}

// Upload image to editor
function uploadImageToEditor(file, quill) {
    const formData = new FormData();
    formData.append('cover_image', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

    // Show loading indicator
    const range = quill.getSelection();
    const loadingIndex = range ? range.index : quill.getLength();
    quill.insertEmbed(loadingIndex, 'image', '/images/loading.gif');

    fetch('/admin/blogs/upload-cover', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Upload failed');
            });
        }
        return response.json();
    })
    .then(data => {
        // Remove loading indicator
        const range2 = quill.getSelection();
        const index = range2 ? range2.index - 1 : quill.getLength() - 1;
        quill.deleteText(index, 1);

        if (data.success && data.url) {
            const range3 = quill.getSelection();
            const insertIndex = range3 ? range3.index : quill.getLength();
            quill.insertEmbed(insertIndex, 'image', data.url);
            quill.setSelection(insertIndex + 1);
            
            if (typeof window.showToast === 'function') {
                window.showToast('success', 'Image uploaded successfully');
            }
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast('error', data.message || 'Upload failed');
            }
        }
    })
    .catch(err => {
        // Remove loading indicator
        const range2 = quill.getSelection();
        const index = range2 ? range2.index - 1 : quill.getLength() - 1;
        quill.deleteText(index, 1);
        
        console.error('Upload error:', err);
        if (typeof window.showToast === 'function') {
            window.showToast('error', 'Failed to upload image: ' + err.message);
        }
    });
}

// Sync all editors (for form submission)
function syncAllEditors() {
    for (const [prefix, quill] of Object.entries(window.quillEditors)) {
        syncQuillContent(prefix);
    }
}

// Make functions globally available
window.initBlogRichEditor = initBlogRichEditor;
window.syncQuillContent = syncQuillContent;
window.syncAllEditors = syncAllEditors;
window.uploadImageToEditor = uploadImageToEditor;
</script>