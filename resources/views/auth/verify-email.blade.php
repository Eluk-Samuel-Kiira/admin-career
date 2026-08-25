@extends('layouts.auth')

@section('content')

    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">Verify Email</h1>
        <div class="text-gray-500 fw-semibold fs-6">Thanks for signing up! Please verify your email address.</div>
    </div>

    <div class="text-gray-500 fw-semibold fs-6 text-center mb-8">
        Before getting started, could you verify your email address by clicking on the link we just emailed to you?
        If you didn't receive the email, we will gladly send you another.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success mb-8">
            A new verification link has been sent to the email address you provided during registration.
        </div>
    @endif

    <div class="d-grid mb-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary w-100">
                <span class="indicator-label">Resend Verification Email</span>
                <span class="indicator-progress">Please wait...
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </form>
    </div>

    <div class="text-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link text-gray-500 fw-semibold fs-6">
                Log Out
            </button>
        </form>
    </div>

@endsection