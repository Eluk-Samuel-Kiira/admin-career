@extends('layouts.auth')

@section('content')

    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">Forgot Password?</h1>
        <div class="text-gray-500 fw-semibold fs-6">Enter your email to reset your password</div>
    </div>

    @if (session('status'))
        <div class="alert alert-success mb-6">{{ session('status') }}</div>
    @endif

    <form class="form w-100" method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="fv-row mb-8">
            <input type="email" name="email" placeholder="Email"
                value="{{ old('email') }}"
                class="form-control bg-transparent @error('email') is-invalid @enderror"
                autocomplete="email" autofocus required />
            @error('email')
                <div class="fv-plugins-message-container">
                    <div class="fv-help-block">{{ $message }}</div>
                </div>
            @enderror
        </div>

        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">Send Reset Link</span>
                <span class="indicator-progress">Please wait...
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </div>

        <div class="text-gray-500 text-center fw-semibold fs-6">
            Remember your password?
            <a href="{{ route('login') }}" class="link-primary">Sign in</a>
        </div>

    </form>

@endsection