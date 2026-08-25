
    <script>
        
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

                    <!-- Lists & indent -->
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

                    <!-- Font family & size -->
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

                    <!-- Clear / delete -->
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
                <div class="rich-editor-statusbar d-flex justify-content-between align-items-center px-3"
                    style="font-size:11px;font-family:monospace;color:#9ca3af;background:#f9fafb;border-top:1px solid #e5e7eb;min-height:24px;">
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
            const el     = document.getElementById(id);
            const hidden = document.getElementById(id + '_hidden');
            if (el && hidden) hidden.value = el.innerHTML;
        }
        
        function richEditorGet(id) {
            const el = document.getElementById(id);
            return el ? el.innerHTML : '';
        }
        
        function richEditorSet(id, html) {
            const el     = document.getElementById(id);
            const hidden = document.getElementById(id + '_hidden');
            if (el)     el.innerHTML = html ?? '';
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
            const text  = el.innerText || '';
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
            const input  = document.getElementById(inputId);
            const swatch = document.getElementById(swatchId);
            if (input && swatch) swatch.style.background = input.value;
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', e => {
            const active = document.activeElement;
            if (!active || active.getAttribute('contenteditable') !== 'true') return;
            const id = active.id;
            if (!id) return;
            if (e.ctrlKey || e.metaKey) {
                if      (e.key === 'b') { e.preventDefault(); reFmt(id, 'bold'); }
                else if (e.key === 'i') { e.preventDefault(); reFmt(id, 'italic'); }
                else if (e.key === 'u') { e.preventDefault(); reFmt(id, 'underline'); }
                else if (e.key === 'z') { e.preventDefault(); reFmt(id, 'undo'); }
                else if (e.key === 'y') { e.preventDefault(); reFmt(id, 'redo'); }
                else if (e.key === 'k') { e.preventDefault(); reInsertLink(id); }
            }
        });
        
        // Update active states on selection change
        document.addEventListener('selectionchange', () => {
            const active = document.activeElement;
            if (!active || active.getAttribute('contenteditable') !== 'true') return;
            if (active.id) updateActiveStates(active.id);
        });
        
        // Auto-sync all on form submit
        document.addEventListener('submit', () => {
            document.querySelectorAll('[contenteditable="true"][id]').forEach(el => {
                richEditorSync(el.id);
            });
        });
  
        
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[contenteditable="true"][id]').forEach(el => {
                updateStats(el.id);
            });
        });
    </script>