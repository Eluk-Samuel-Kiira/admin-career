{{-- Jobs Settings --}}
<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('admin.departments*') ? 'show here' : '' }}">
    <span class="menu-link">
        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
        <span class="menu-title">Jobs</span>
        <span class="menu-arrow"></span>
    </span>
    
    <div class="menu-sub menu-sub-accordion">
        <div class="menu-item">
            <a class="menu-link {{ request()->routeIs('admin.departments*') ? 'active' : '' }}" href="{{ route('admin.departments') }}">
                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                <span class="menu-title">Industries</span>
            </a>
        </div>
    </div>
    
    <div class="menu-sub menu-sub-accordion">
        <div class="menu-item">
            <a class="menu-link {{ request()->routeIs('admin.departments*') ? 'active' : '' }}" href="{{ route('admin.departments') }}">
                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                <span class="menu-title">Job Categories</span>
            </a>
        </div>
    </div>
    
    <div class="menu-sub menu-sub-accordion">
        <div class="menu-item">
            <a class="menu-link {{ request()->routeIs('admin.departments*') ? 'active' : '' }}" href="{{ route('admin.departments') }}">
                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                <span class="menu-title">Locations</span>
            </a>
        </div>
    </div>
    
    <div class="menu-sub menu-sub-accordion">
        <div class="menu-item">
            <a class="menu-link {{ request()->routeIs('admin.departments*') ? 'active' : '' }}" href="{{ route('admin.departments') }}">
                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                <span class="menu-title">Experience Levels</span>
            </a>
        </div>
    </div>

    <div class="menu-sub menu-sub-accordion">
        <div class="menu-item">
            <a class="menu-link {{ request()->routeIs('admin.departments*') ? 'active' : '' }}" href="{{ route('admin.departments') }}">
                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                <span class="menu-title">Education Levels</span>
            </a>
        </div>
    </div>
    
    <div class="menu-sub menu-sub-accordion">
        <div class="menu-item">
            <a class="menu-link {{ request()->routeIs('admin.departments*') ? 'active' : '' }}" href="{{ route('admin.departments') }}">
                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                <span class="menu-title">Salary Ranges</span>
            </a>
        </div>
    </div>
    
</div>