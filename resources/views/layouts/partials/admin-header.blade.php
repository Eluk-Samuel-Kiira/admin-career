<div id="kt_app_header" class="app-header"
    data-kt-sticky="true"
    data-kt-sticky-activate="{default: true, lg: true}"
    data-kt-sticky-name="app-header-minimize"
    data-kt-sticky-offset="{default: '200px', lg: '0'}"
    data-kt-sticky-animation="false">

    <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">

        {{-- Sidebar mobile toggle --}}
        <div class="d-flex align-items-center d-lg-none ms-n3 me-1 me-md-2" title="Show sidebar menu">
            <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_sidebar_mobile_toggle">
                <i class="ki-duotone ki-abstract-14 fs-2 fs-md-1">
                    <span class="path1"></span><span class="path2"></span>
                </i>
            </div>
        </div>

        {{-- Mobile logo --}}
        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
            <a href="{{ route('admin.dashboard') }}" class="d-lg-none">
                <img alt="Logo" src="{{ asset('careers.png') }}" class="h-30px" />
            </a>
        </div>

        {{-- Header wrapper --}}
        <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1" id="kt_app_header_wrapper">

            {{-- Menu wrapper --}}
            <div class="app-header-menu app-header-mobile-drawer align-items-stretch"
                data-kt-drawer="true"
                data-kt-drawer-name="app-header-menu"
                data-kt-drawer-activate="{default: true, lg: false}"
                data-kt-drawer-overlay="true"
                data-kt-drawer-width="250px"
                data-kt-drawer-direction="end"
                data-kt-drawer-toggle="#kt_app_header_menu_toggle"
                data-kt-swapper="true"
                data-kt-swapper-mode="{default: 'append', lg: 'prepend'}"
                data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">
                {{-- Top nav menu can be added here if needed --}}
            </div>

            {{-- Navbar --}}
            <div class="app-navbar flex-shrink-0">

                {{-- Search --}}
                <div class="app-navbar-item align-items-stretch ms-1 ms-md-4">
                    <div id="kt_header_search" class="header-search d-flex align-items-stretch"
                        data-kt-search-keypress="true"
                        data-kt-search-min-length="2"
                        data-kt-search-enter="enter"
                        data-kt-search-layout="menu"
                        data-kt-menu-trigger="auto"
                        data-kt-menu-overflow="false"
                        data-kt-menu-permanent="true"
                        data-kt-menu-placement="bottom-end">
                        <div class="d-flex align-items-center" data-kt-search-element="toggle" id="kt_header_search_toggle">
                            <div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px">
                                <i class="ki-duotone ki-magnifier fs-2">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Notifications --}}
                <div class="app-navbar-item ms-1 ms-md-4">
                    <div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px"
                        data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                        data-kt-menu-attach="parent"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-notification-status fs-2">
                            <span class="path1"></span><span class="path2"></span>
                            <span class="path3"></span><span class="path4"></span>
                        </i>
                    </div>
                </div>

                {{-- Theme mode --}}
                <div class="app-navbar-item ms-1 ms-md-4">
                    <a href="#" class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px"
                        data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                        data-kt-menu-attach="parent"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-night-day theme-light-show fs-1">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            <span class="path4"></span><span class="path5"></span><span class="path6"></span>
                            <span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span>
                        </i>
                        <i class="ki-duotone ki-moon theme-dark-show fs-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px"
                        data-kt-menu="true" data-kt-element="theme-mode-menu">
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                <span class="menu-icon"><i class="ki-duotone ki-night-day fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span></i></span>
                                <span class="menu-title">Light</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                <span class="menu-icon"><i class="ki-duotone ki-moon fs-2"><span class="path1"></span><span class="path2"></span></i></span>
                                <span class="menu-title">Dark</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                <span class="menu-icon"><i class="ki-duotone ki-screen fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i></span>
                                <span class="menu-title">System</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- User menu --}}
                <div class="app-navbar-item ms-1 ms-md-4" id="kt_header_user_menu_toggle">
                    <div class="cursor-pointer symbol symbol-35px"
                        data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                        data-kt-menu-attach="parent"
                        data-kt-menu-placement="bottom-end">
                        <img src="{{ auth()->user()->avatar ?? asset('assets/media/avatars/300-1.jpg') }}" alt="Profile Picture" />
                    </div>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                        <div class="menu-item px-3">
                            <div class="menu-content d-flex align-items-center px-3">
                                <div class="symbol symbol-50px me-5">
                                    <img src="{{ auth()->user()->avatar ?? asset('assets/media/avatars/300-1.jpg') }}" alt="Profile Picture" />
                                </div>
                                <div class="d-flex flex-column">
                                    <div class="fw-bold d-flex align-items-center fs-5">{{ auth()->user()->name ?? 'User' }}</div>
                                    <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">{{ auth()->user()->email ?? '' }}</a>
                                </div>
                            </div>
                        </div>
                        <div class="separator my-2"></div>
                        <div class="menu-item px-5">
                            <a href="{{ route('profile.edit') }}" class="menu-link px-5">
                                <i class="ki-duotone ki-user fs-3 me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                My profile
                            </a>
                        </div>
                        <div class="separator my-2"></div>
                        <div class="menu-item px-5">
                            <form method="POST" action="{{ route('logout') }}" class="m-0 w-100">
                                @csrf
                                <button type="submit" class="menu-link px-5 py-3 btn btn-link w-100 text-start p-0 text-gray-800 fs-5 fw-semibold" 
                                        style="transition: all 0.2s ease; border-radius: 6px; text-decoration: none; background: transparent; border: none; display: flex; align-items: center; gap: 8px;"
                                        onmouseover="this.style.backgroundColor='#f5f5f5';">
                                    <i class="ki-duotone ki-entrance-right fs-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Mobile menu toggle --}}
                <div class="app-navbar-item d-lg-none ms-2 me-n2">
                    <div class="btn btn-flex btn-icon btn-active-color-primary w-30px h-30px" id="kt_app_header_menu_toggle">
                        <i class="ki-duotone ki-element-4 fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>



<style>
/* ============================================================
   USER DROPDOWN CARD ONLY — 20% smaller
   Targets only the card inside #kt_header_user_menu_toggle.
   Theme switcher and notification bell stay untouched.
   ============================================================ */

/* -------- Card container --------
   Original: w-275px, py-4 (1rem top/bottom), fs-6 (~1rem)
   20% smaller: width ~220px, py ~0.8rem, fs ~0.8rem            */
#kt_header_user_menu_toggle .menu-sub-dropdown {
    width: 220px !important;
    min-width: 220px !important;
    padding-top: 0.8rem !important;
    padding-bottom: 0.8rem !important;
    font-size: 0.8rem !important;
}

/* -------- Profile header row (avatar + name + email) -------- */
#kt_header_user_menu_toggle .menu-sub-dropdown .menu-item.px-3 > .menu-content {
    padding-left: 0.6rem !important;
    padding-right: 0.6rem !important;
}

/* Avatar: was 50×50 → 40×40 */
#kt_header_user_menu_toggle .menu-sub-dropdown .symbol.symbol-50px {
    width: 40px !important;
    height: 40px !important;
    margin-right: 0.9rem !important;   /* was me-5 (~1.25rem) */
}

#kt_header_user_menu_toggle .menu-sub-dropdown .symbol.symbol-50px > img {
    width: 40px !important;
    height: 40px !important;
    border-radius: 50% !important;
}

/* Name: was fs-5 (~1.15rem) → ~0.92rem */
#kt_header_user_menu_toggle .menu-sub-dropdown .fs-5 {
    font-size: 0.92rem !important;
    line-height: 1.3 !important;
}

/* Email: was fs-7 (~0.85rem) → ~0.7rem */
#kt_header_user_menu_toggle .menu-sub-dropdown .fs-7 {
    font-size: 0.7rem !important;
    line-height: 1.3 !important;
}

/* -------- Menu rows (My Profile link) -------- */
#kt_header_user_menu_toggle .menu-sub-dropdown .menu-item.px-5 {
    padding-left: 0.85rem !important;
    padding-right: 0.85rem !important;
}

#kt_header_user_menu_toggle .menu-sub-dropdown .menu-link {
    padding: 0.5rem 0.85rem !important;
    font-size: 0.85rem !important;
    line-height: 1.35 !important;
    border-radius: 6px !important;
}

/* -------- Sign Out button (custom-styled, so we override inline too) -------- */
#kt_header_user_menu_toggle .menu-sub-dropdown button.menu-link {
    font-size: 0.85rem !important;
    padding: 0.45rem 0.75rem !important;
    gap: 7px !important;              /* was 8px */
    border-radius: 6px !important;
}

/* Sign Out icon: was fs-3 (~1.35rem) → ~1.1rem */
#kt_header_user_menu_toggle .menu-sub-dropdown button.menu-link .ki-duotone {
    font-size: 1.1rem !important;
}

/* -------- Separators: was my-2 (~0.5rem each) → ~0.4rem -------- */
#kt_header_user_menu_toggle .menu-sub-dropdown .separator.my-2 {
    margin-top: 0.4rem !important;
    margin-bottom: 0.4rem !important;
}
</style>