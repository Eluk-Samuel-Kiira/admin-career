<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ config('app.name') }} - @yield('title', 'Dashboard')</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="{{ asset('fav.png') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" />
    @stack('styles')
    <script>if (window.top != window.self) { window.top.location.replace(window.self.location.href); }</script>
</head>
<body id="kt_app_body"
    data-kt-app-layout="dark-sidebar"
    data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true"
    data-kt-app-sidebar-fixed="true"
    data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true"
    data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true"
    data-kt-app-toolbar-enabled="true"
    class="app-default">

    <script>
        var defaultThemeMode = "light"; var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                themeMode = localStorage.getItem("data-bs-theme") ?? defaultThemeMode;
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">

            {{-- Header --}}
            @include('layouts.partials.admin-header')

            {{-- Wrapper --}}
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">

                {{-- Sidebar --}}
                @include('layouts.partials.admin-sidebar')

                {{-- Main --}}
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">

                        {{-- Toolbar --}}
                        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                            <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
                                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                                    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                        @yield('page_title', 'Dashboard')
                                    </h1>
                                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                        <li class="breadcrumb-item text-muted">
                                            <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
                                        </li>
                                        @yield('breadcrumb')
                                    </ul>
                                </div>
                                <div class="d-flex align-items-center gap-2 gap-lg-3">
                                    @yield('toolbar_actions')
                                </div>
                            </div>
                        </div>

                        {{-- Content --}}
                        <div id="kt_app_content" class="app-content flex-column-fluid">
                            <div id="kt_app_content_container" class="app-container container-fluid">
                                @yield('content')
                            </div>
                        </div>

                    </div>

                    {{-- Footer --}}
                    <div id="kt_app_footer" class="app-footer">
                        <div class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3">
                            <div class="text-gray-900 order-2 order-md-1">
                                <span class="text-muted fw-semibold me-1">{{ date('Y') }}&copy;</span>
                                <a href="#" class="text-gray-800 text-hover-primary">{{ config('app.name') }}</a>
                            </div>
                        </div>
                    </div>

                </div>
                {{-- End Main --}}
            </div>
        </div>
    </div>

    <!--
        // Success toast
        showToast('success', 'Operation completed successfully!');

        // Error toast
        showToast('error', 'Something went wrong!');

        // Warning toast
        showToast('warning', 'Please check your input!');

        // Info toast with title
        showToast('info', 'New update available', 'Information');

        // For buttons with spinners
        const myButton = document.getElementById('myButton');
        showButtonSpinner(myButton);
        // ... do async operation
        hideButtonSpinner(myButton);
    -->
        
    <!-- Toast Container (top-right with high z-index to overlay all content) -->
    <div id="toastStackContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

    <!-- Hidden template used for cloning -->
    <div id="toastTemplate" class="toast d-none" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-white">
            <i class="ki-duotone ki-abstract-23 fs-2 me-3"></i>
            <strong class="me-auto">Title</strong>
            <small class="text-muted">Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body bg-white">
            Message goes here.
        </div>
    </div>

    <script>
        (function() {
            // ----- DOM elements -----
            const container = document.getElementById('toastStackContainer');
            const template = document.getElementById('toastTemplate');

            if (!container || !template) return;

            // Remove template from live DOM (keep as clone source)
            template.remove();

            // Ensure container has highest z-index
            container.style.zIndex = '9999';

            // ----- helper: update icon and text colours based on type -----
            function styleToastByType(toastElement, type) {
                const icon = toastElement.querySelector('.toast-header i');
                const titleEl = toastElement.querySelector('.toast-header strong');
                const messageEl = toastElement.querySelector('.toast-body');
                const smallEl = toastElement.querySelector('.toast-header small');
                const closeBtn = toastElement.querySelector('.btn-close');

                // Reset any previous status classes from all elements
                const elementsToReset = [icon, titleEl, messageEl, smallEl];
                elementsToReset.forEach(el => {
                    if (el) {
                        el.classList.remove('text-success', 'text-danger', 'text-warning', 'text-info', 'text-muted');
                    }
                });

                // Apply new colours based on type
                switch (type) {
                    case 'success':
                        if (icon) {
                            icon.classList.add('text-success');
                            icon.className = 'ki-duotone ki-check-circle fs-2 me-3 text-success';
                        }
                        if (titleEl) titleEl.classList.add('text-success');
                        if (messageEl) messageEl.classList.add('text-success');
                        if (smallEl) smallEl.classList.add('text-success');
                        break;
                    case 'error':
                    case 'danger':
                        if (icon) {
                            icon.classList.add('text-danger');
                            icon.className = 'ki-duotone ki-cross-circle fs-2 me-3 text-danger';
                        }
                        if (titleEl) titleEl.classList.add('text-danger');
                        if (messageEl) messageEl.classList.add('text-danger');
                        if (smallEl) smallEl.classList.add('text-danger');
                        break;
                    case 'warning':
                        if (icon) {
                            icon.classList.add('text-warning');
                            icon.className = 'ki-duotone ki-information-5 fs-2 me-3 text-warning';
                        }
                        if (titleEl) titleEl.classList.add('text-warning');
                        if (messageEl) messageEl.classList.add('text-warning');
                        if (smallEl) smallEl.classList.add('text-warning');
                        break;
                    case 'info':
                    default:
                        if (icon) {
                            icon.classList.add('text-info');
                            icon.className = 'ki-duotone ki-information-4 fs-2 me-3 text-info';
                        }
                        if (titleEl) titleEl.classList.add('text-info');
                        if (messageEl) messageEl.classList.add('text-info');
                        if (smallEl) smallEl.classList.add('text-info');
                        break;
                }

                // Close button always visible on white background
                if (closeBtn) closeBtn.classList.add('btn-close');
            }

            // ----- Global showToast function -----
            window.showToast = function(type, message, title = '', duration = 5000) {
                // Clone template
                const newToast = template.cloneNode(true);
                newToast.classList.remove('d-none');
                
                // Add shadow for better visibility over content
                newToast.style.boxShadow = '0 0.5rem 1rem rgba(0, 0, 0, 0.15)';

                // Fill content
                const titleElem = newToast.querySelector('.toast-header strong');
                const bodyElem = newToast.querySelector('.toast-body');
                const timeElem = newToast.querySelector('.toast-header small');

                if (titleElem) titleElem.textContent = title || (type.charAt(0).toUpperCase() + type.slice(1));
                if (bodyElem) bodyElem.textContent = message;
                if (timeElem) {
                    const now = new Date();
                    timeElem.textContent = `${now.getHours().toString().padStart(2,'0')}:${now.getMinutes().toString().padStart(2,'0')}`;
                }

                // Apply status colours to ALL text elements (title, message, time)
                styleToastByType(newToast, type);

                // Append, initialise and show
                container.appendChild(newToast);
                const bsToast = new bootstrap.Toast(newToast, { autohide: true, delay: duration });
                bsToast.show();

                // Auto‑remove after hidden
                newToast.addEventListener('hidden.bs.toast', () => newToast.remove());
            };
        })();
    </script>

    <!-- spinner button -->
    <script>
        // Global Spinner Functions for Metronic Buttons
        window.showButtonSpinner = function(button) {
            if (!button) return;
            button.setAttribute('data-kt-indicator', 'on');
            button.disabled = true;
        };

        window.hideButtonSpinner = function(button) {
            if (!button) return;
            button.removeAttribute('data-kt-indicator');
            button.disabled = false;
        };
    </script>


    {{-- Scripts --}}
    <script>var hostUrl = "{{ asset('assets/') }}";</script>
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/widgets.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/custom/widgets.js') }}"></script>
    <script src="{{ asset('assets/js/custom/apps/chat/chat.js') }}"></script>
    @stack('scripts')
</body>
</html>