@extends('layouts.auth')

@section('content')

    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">Reset Password</h1>
        <div class="text-gray-500 fw-semibold fs-6">Enter your new password below</div>
    </div>

    <form class="form w-100" method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        {{-- Email --}}
        <div class="fv-row mb-8">
            <input type="email" name="email" placeholder="Email"
                value="{{ old('email', $request->email) }}"
                class="form-control bg-transparent @error('email') is-invalid @enderror"
                autocomplete="username" autofocus required />
            @error('email')
                <div class="fv-plugins-message-container">
                    <div class="fv-help-block">{{ $message }}</div>
                </div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="fv-row mb-8">
            <input type="password" name="password" placeholder="New Password"
                class="form-control bg-transparent @error('password') is-invalid @enderror"
                autocomplete="new-password" required />
            @error('password')
                <div class="fv-plugins-message-container">
                    <div class="fv-help-block">{{ $message }}</div>
                </div>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="fv-row mb-8">
            <input type="password" name="password_confirmation" placeholder="Confirm New Password"
                class="form-control bg-transparent @error('password_confirmation') is-invalid @enderror"
                autocomplete="new-password" required />
            @error('password_confirmation')
                <div class="fv-plugins-message-container">
                    <div class="fv-help-block">{{ $message }}</div>
                </div>
            @enderror
        </div>

        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">Reset Password</span>
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