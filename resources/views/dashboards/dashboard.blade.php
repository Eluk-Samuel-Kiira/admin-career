@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Overview</li>
@endsection

@section('content')
@can('view dashboard')
    <div class="row g-5 gx-xl-10 mb-5 mb-xl-10">
        <div class="col-md-12">
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <h3 class="card-title text-gray-800 fw-bold">Welcome, {{ auth()->user()->name }}!</h3>
                </div>
                <div class="card-body pt-5">
                    <p class="text-gray-600 fw-semibold fs-6">You're logged in and ready to go.</p>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection