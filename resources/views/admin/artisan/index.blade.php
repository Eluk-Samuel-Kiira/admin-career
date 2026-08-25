@extends('layouts.admin')

@section('title', 'Artisan Commands')
@section('page_title', 'Artisan Commands')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Tools</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Artisan</li>
@endsection

@section('content')
@role('super_admin')
<div class="row g-5 g-xl-8">
    {{-- Command Form Card --}}
    <div class="col-xl-5">
        <div class="card card-flush h-100">
            <div class="card-header pt-7">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900">Run Artisan Command</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">Execute Laravel CLI commands</span>
                </h3>
            </div>

            <div class="card-body pt-5">
                {{-- Command Select --}}
                <div class="fv-row mb-8">
                    <label class="form-label fw-semibold text-gray-900 fs-6 required">Select Command</label>
                    <select id="artisan_command" class="form-select form-select-solid" data-control="select2" data-placeholder="Select a command...">
                        <option></option>
                        @foreach($commands as $cmd => $description)
                            <option value="{{ $cmd }}">php artisan {{ $cmd }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Description --}}
                <div id="command_description" class="mb-8 d-none">
                    <div class="d-flex align-items-center bg-light-info rounded p-5">
                        <i class="ki-duotone ki-document fs-2x text-info me-3">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <div>
                            <div class="fs-7 text-muted fw-semibold">Description</div>
                            <div id="description_text" class="fs-6 text-gray-800 fw-bold"></div>
                        </div>
                    </div>
                </div>

                {{-- Run Button --}}
                <button id="run_btn" class="btn btn-primary w-100 py-4" disabled>
                    <span class="indicator-label">
                        <i class="ki-duotone ki-rocket fs-3 me-2">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Run Command
                    </span>
                    <span class="indicator-progress">
                        Processing...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Output Terminal Card --}}
    <div class="col-xl-7">
        <div class="card card-flush h-100">
            <div class="card-header pt-7">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900">Terminal Output</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">Command execution results</span>
                </h3>
                <div class="card-toolbar">
                    <button id="clear_output" class="btn btn-sm btn-light-danger btn-flex">
                        <i class="ki-duotone ki-trash fs-3 me-2">
                            <span class="path1"></span><span class="path2"></span>
                            <span class="path3"></span><span class="path4"></span><span class="path5"></span>
                        </i>
                        Clear Output
                    </button>
                </div>
            </div>

            <div class="card-body pt-5">
                {{-- Terminal Window --}}
                <div class="terminal-wrapper bg-dark rounded-4 overflow-hidden mb-5" style="min-height: 380px;">
                    {{-- Terminal Top Bar --}}
                    <div class="terminal-header d-flex align-items-center px-5 py-3" style="background: #1e1e2e; border-bottom: 1px solid #313244;">
                        <div class="d-flex gap-2 me-4">
                            <span class="terminal-dot bg-danger rounded-circle" style="width: 12px; height: 12px;"></span>
                            <span class="terminal-dot bg-warning rounded-circle" style="width: 12px; height: 12px;"></span>
                            <span class="terminal-dot bg-success rounded-circle" style="width: 12px; height: 12px;"></span>
                        </div>
                        <span class="terminal-title text-gray-500 fs-8 fw-semibold" id="terminal_title">Terminal</span>
                    </div>
                    {{-- Terminal Body --}}
                    <div id="terminal_output" class="terminal-body p-5" 
                         style="background: #1e1e2e; font-family: 'JetBrains Mono', 'Courier New', monospace; font-size: 13px; color: #cdd6f4; min-height: 330px; white-space: pre-wrap; word-break: break-word;">
                        <span class="text-gray-600">Select a command and click "Run Command" to execute it.</span>
                    </div>
                </div>

                {{-- Status Badge --}}
                <div id="status_badge" class="d-none">
                    <!-- Dynamic content -->
                </div>
            </div>
        </div>
    </div>
</div>
@endrole
@endsection

@push('scripts')
<script>
const commands = @json($commands);

document.addEventListener('DOMContentLoaded', function() {
    const selectEl      = document.getElementById('artisan_command');
    const runBtn        = document.getElementById('run_btn');
    const terminalOut   = document.getElementById('terminal_output');
    const terminalTitle = document.getElementById('terminal_title');
    const statusBadge   = document.getElementById('status_badge');
    const cmdDesc       = document.getElementById('command_description');
    const descText      = document.getElementById('description_text');
    const clearBtn      = document.getElementById('clear_output');

    // Initialize Select2 if available
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $(selectEl).select2({
            placeholder: "Select a command...",
            allowClear: true
        });
    }

    // On command select
    $(selectEl).on('change', function() {
        const val = $(this).val();
        if (val) {
            runBtn.removeAttribute('disabled');
            descText.textContent = commands[val] ?? '';
            cmdDesc.classList.remove('d-none');
        } else {
            runBtn.setAttribute('disabled', true);
            cmdDesc.classList.add('d-none');
        }
    });

    // Run command
    runBtn.addEventListener('click', function() {
        const command = $(selectEl).val();
        if (!command) return;

        // Loading state
        runBtn.setAttribute('data-kt-indicator', 'on');
        runBtn.disabled = true;
        terminalTitle.textContent = 'Running: php artisan ' + command;
        terminalOut.innerHTML = '<span style="color:#89b4fa;">$ php artisan ' + command + '</span>\n<span class="text-gray-600">Executing command...</span>';
        statusBadge.classList.add('d-none');

        // If it's a dangerous command, show warning
        if (command === 'migrate:fresh --seed') {
            if (!confirm('⚠️ WARNING: This will delete all data and reseed your database. Are you sure?')) {
                terminalOut.innerHTML = '<span style="color:#f38ba8;">❌ Command cancelled by user</span>';
                runBtn.removeAttribute('data-kt-indicator');
                runBtn.disabled = false;
                return;
            }
        }

        fetch('{{ route("artisan.run") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ command: command })
        })
        .then(res => res.json())
        .then(data => {
            const color   = data.success ? '#a6e3a1' : '#f38ba8';
            const prompt  = data.success ? '✅' : '❌';

            terminalOut.innerHTML = 
                '<span style="color:#89b4fa;">$ php artisan ' + (data.command || command) + '</span>\n\n' +
                '<span style="color:' + color + ';">' + escapeHtml(data.output) + '</span>';

            terminalTitle.textContent = prompt + ' php artisan ' + (data.command || command);

            statusBadge.classList.remove('d-none');
            statusBadge.innerHTML = data.success
                ? '<span class="badge badge-light-success fs-7 fw-bold px-5 py-3"><i class="ki-duotone ki-check-circle fs-3 me-2"><span class="path1"></span><span class="path2"></span></i> Success</span>'
                : '<span class="badge badge-light-danger fs-7 fw-bold px-5 py-3"><i class="ki-duotone ki-cross-circle fs-3 me-2"><span class="path1"></span><span class="path2"></span></i> Failed</span>';
        })
        .catch(err => {
            terminalOut.innerHTML = 
                '<span style="color:#89b4fa;">$ php artisan ' + command + '</span>\n\n' +
                '<span style="color:#f38ba8;">❌ Error: ' + escapeHtml(err.message) + '</span>';
            terminalTitle.textContent = '❌ php artisan ' + command;
        })
        .finally(() => {
            runBtn.removeAttribute('data-kt-indicator');
            runBtn.disabled = false;
        });
    });

    // Clear output
    clearBtn.addEventListener('click', function() {
        terminalOut.innerHTML = '<span class="text-gray-600">Select a command and click "Run Command" to execute it.</span>';
        terminalTitle.textContent = 'Terminal';
        statusBadge.classList.add('d-none');
    });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }
});
</script>
@endpush