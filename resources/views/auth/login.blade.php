@extends('layouts.auth')

@section('content')

    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">Sign In</h1>
        <div class="text-gray-500 fw-semibold fs-6">{{ config('app.name') }}</div>
    </div>

    @if (session('status'))
        <div class="alert alert-success mb-6">{{ session('status') }}</div>
    @endif

    <form class="form w-100" method="POST" action="{{ route('login.post') }}" id="loginForm" novalidate>        
        @csrf

        {{-- Email --}}
        <div class="fv-row mb-8">
            <input type="email" name="email" id="email" placeholder="Email"
                value="{{ old('email') }}"
                class="form-control bg-transparent @error('email') is-invalid @enderror"
                autocomplete="username" autofocus required />
            @error('email')
                <div class="fv-plugins-message-container">
                    <div class="fv-help-block">{{ $message }}</div>
                </div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="fv-row mb-3">
            <input type="password" name="password" id="password" placeholder="Password"
                class="form-control bg-transparent @error('password') is-invalid @enderror"
                autocomplete="current-password" required />
            @error('password')
                <div class="fv-plugins-message-container">
                    <div class="fv-help-block">{{ $message }}</div>
                </div>
            @enderror
        </div>

        {{-- Remember + Forgot --}}
        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
            <label class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="remember" id="remember_me" />
                <span class="form-check-label text-gray-700">Remember me</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link-primary">Forgot Password?</a>
            @endif
        </div>

        {{-- Submit --}}
        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary" id="signInBtn">
                <span class="indicator-label">Sign In</span>
                <span class="indicator-progress d-none">Please wait...
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </div>

        {{-- Test Login Button --}}
        <div class="d-grid mb-5">
            <button type="button" class="btn btn-light-warning" id="testLoginBtn">
                <i class="ki-duotone ki-magic-star fs-2 me-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                Test Login (Demo)
            </button>
        </div>

        {{--
        @if (Route::has('register'))
            <div class="text-gray-500 text-center fw-semibold fs-6">
                Not a Member yet?
                <a href="{{ route('register') }}" class="link-primary">Sign up</a>
            </div>
        @endif
        --}}
    </form>

@endsection

@section('scripts')
    <script src="{{ asset('assets/js/custom/authentication/sign-in/general.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const testBtn = document.getElementById('testLoginBtn');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const signInBtn = document.getElementById('signInBtn');
            const form = document.getElementById('loginForm');
            
            if (testBtn) {
                testBtn.addEventListener('click', function() {
                    // Fill credentials
                    emailInput.value = 'john.doe@lafab.com';
                    passwordInput.value = 'Admin@123';
                    
                    // Optional: Visual feedback
                    emailInput.classList.add('is-valid');
                    passwordInput.classList.add('is-valid');
                    
                    // Remove invalid classes if any
                    emailInput.classList.remove('is-invalid');
                    passwordInput.classList.remove('is-invalid');
                    
                });
            }
        });
    </script>
@endsection