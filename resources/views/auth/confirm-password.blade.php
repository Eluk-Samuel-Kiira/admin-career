@extends('layouts.auth')

@section('content')

    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">Confirm Password</h1>
        <div class="text-gray-500 fw-semibold fs-6">This is a secure area. Please confirm your password before continuing.</div>
    </div>

    <form class="form w-100" method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf

        <div class="fv-row mb-8">
            <input type="password" name="password" placeholder="Password"
                class="form-control bg-transparent @error('password') is-invalid @enderror"
                autocomplete="current-password" required />
            @error('password')
                <div class="fv-plugins-message-container">
                    <div class="fv-help-block">{{ $message }}</div>
                </div>
            @enderror
        </div>

        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">Confirm</span>
                <span class="indicator-progress">Please wait...
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </div>

    </form>

@endsection