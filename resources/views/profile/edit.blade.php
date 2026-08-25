@extends('layouts.admin')

@section('title', 'My Profile')
@section('page_title', 'My Profile')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Profile</li>
@endsection

@section('content')
    <div class="row g-5 gx-xl-10 mb-5 mb-xl-10">
        <!-- Profile Header Card -->
        <div class="col-md-12">
            <div class="card card-flush">
                <div class="card-body pt-5">
                    <!--begin::Details-->
                    <div class="d-flex flex-wrap flex-sm-nowrap">
                        <!--begin: Pic-->
                        <div class="me-7 mb-4">
                            <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                                <img src="{{ auth()->user()->avatar ?? asset('assets/media/avatars/300-1.jpg') }}" alt="Profile Picture" />
                                <div class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border border-4 border-body h-20px w-20px"></div>
                            </div>
                            <div class="mt-3 text-center">
                                <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_upload_avatar">
                                    <i class="ki-duotone ki-camera fs-3"></i> Change Photo
                                </button>
                            </div>
                        </div>
                        <!--end::Pic-->
                        
                        <!--begin::Info-->
                        <div class="flex-grow-1">
                            <!--begin::Title-->
                            <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                                <!--begin::User-->
                                <div class="d-flex flex-column">
                                    <!--begin::Name-->
                                    <div class="d-flex align-items-center mb-2">
                                        <a href="#" class="text-gray-900 text-hover-primary fs-2 fw-bold me-1">{{ auth()->user()->full_name }}</a>
                                        @if(auth()->user()->email_verified_at)
                                        <a href="#">
                                            <i class="ki-duotone ki-verify fs-1 text-primary">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </a>
                                        @endif
                                    </div>
                                    <!--end::Name-->
                                    
                                    <!--begin::Info-->
                                    <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                                        <span class="d-flex align-items-center text-gray-500 me-5 mb-2">
                                            <i class="ki-duotone ki-profile-circle fs-4 me-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i>
                                            {{ ucwords(str_replace('_', ' ', auth()->user()->roles->first()->name ?? 'No Role Assigned')) }}
                                        </span>
                                        <span class="d-flex align-items-center text-gray-500 me-5 mb-2">
                                            <i class="ki-duotone ki-phone fs-4 me-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            {{ auth()->user()->phone ?? 'No phone number' }}
                                        </span>
                                        <span class="d-flex align-items-center text-gray-500 me-5 mb-2">
                                            <i class="ki-duotone ki-geolocation fs-4 me-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            {{ auth()->user()->country_code ?? 'No country' }}
                                        </span>
                                        <span class="d-flex align-items-center text-gray-500 mb-2">
                                            <i class="ki-duotone ki-sms fs-4 me-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            {{ auth()->user()->email }}
                                        </span>
                                    </div>
                                    <!--end::Info-->
                                </div>
                                <!--end::User-->
                            </div>
                            <!--end::Title-->
                            
                            <!--begin::Stats-->
                            <div class="d-flex flex-wrap flex-stack">
                                <!--begin::Wrapper-->
                                <div class="d-flex flex-column flex-grow-1 pe-8">
                                    <!--begin::Stats-->
                                    <div class="d-flex flex-wrap">
                                        <!--begin::Stat-->
                                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <i class="ki-duotone ki-calendar fs-3 text-warning me-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                                <div class="fs-2 fw-bold">{{ auth()->user()->created_at->format('M d, Y') }}</div>
                                            </div>
                                            <div class="fw-semibold fs-6 text-gray-500">Member Since</div>
                                        </div>
                                        <!--end::Stat-->
                                        
                                        <!--begin::Stat-->
                                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <i class="ki-duotone ki-clock fs-3 text-info me-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                                <div class="fs-2 fw-bold">{{ auth()->user()->last_login_at ? auth()->user()->last_login_at->diffForHumans() : 'Never' }}</div>
                                            </div>
                                            <div class="fw-semibold fs-6 text-gray-500">Last Login</div>
                                        </div>
                                        <!--end::Stat-->
                                        
                                        <!--begin::Stat-->
                                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <i class="ki-duotone ki-shield fs-3 text-success me-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                                <div class="fs-2 fw-bold">
                                                    @if(auth()->user()->is_active)
                                                        <span class="badge badge-light-success">Active</span>
                                                    @else
                                                        <span class="badge badge-light-danger">Inactive</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="fw-semibold fs-6 text-gray-500">Account Status</div>
                                        </div>
                                        <!--end::Stat-->
                                    </div>
                                    <!--end::Stats-->
                                </div>
                                <!--end::Wrapper-->
                                
                                <!--begin::Progress-->
                                <div class="d-flex align-items-center w-200px w-sm-300px flex-column mt-3">
                                    <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                                        <span class="fw-semibold fs-6 text-gray-500">Profile Completion</span>
                                        <span class="fw-bold fs-6">
                                            @php
                                                $user = auth()->user();
                                                $fields = ['first_name', 'last_name', 'email', 'phone', 'country_code'];
                                                $completed = 0;
                                                foreach ($fields as $field) {
                                                    if (!empty($user->$field)) {
                                                        $completed++;
                                                    }
                                                }
                                                if (!empty($user->bio)) $completed++;
                                                $total = count($fields) + 1;
                                                $completionPercent = round(($completed / $total) * 100);
                                            @endphp
                                            {{ $completionPercent }}%
                                        </span>
                                    </div>
                                    <div class="h-5px mx-3 w-100 bg-light mb-3">
                                        <div class="bg-success rounded h-5px" role="progressbar" style="width: {{ $completionPercent }}%;"></div>
                                    </div>
                                </div>
                                <!--end::Progress-->
                            </div>
                            <!--end::Stats-->
                        </div>
                        <!--end::Info-->
                    </div>
                    <!--end::Details-->
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Settings Sections -->
    <div class="row g-5 gx-xl-10">
        <!-- Profile Information Section -->
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header pt-7">
                    <h3 class="card-title text-gray-800 fw-bold">Profile Information</h3>
                </div>
                <div class="card-body pt-0">
                    <p class="text-muted mb-5">Update your account's profile information.</p>
                    
                    <form method="post" action="{{ route('profile.update') }}" class="form">
                        @csrf
                        @method('patch')
                        
                        <!-- First Name & Last Name Row -->
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="required fw-semibold fs-6 mb-2">First Name</label>
                                <input type="text" name="first_name" class="form-control form-control-solid @error('first_name') is-invalid @enderror" 
                                       placeholder="First name" value="{{ old('first_name', auth()->user()->first_name) }}" required />
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="required fw-semibold fs-6 mb-2">Last Name</label>
                                <input type="text" name="last_name" class="form-control form-control-solid @error('last_name') is-invalid @enderror" 
                                       placeholder="Last name" value="{{ old('last_name', auth()->user()->last_name) }}" required />
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Email Field -->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-solid @error('email') is-invalid @enderror" 
                                   placeholder="Enter your email" value="{{ old('email', auth()->user()->email) }}" required />
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            
                            @if(auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                                <div class="mt-3">
                                    <p class="text-muted fs-7 mb-2">
                                        Your email address is unverified.
                                    </p>
                                    <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light-primary">
                                            Resend Verification Email
                                        </button>
                                    </form>
                                    @if(session('status') === 'verification-link-sent')
                                        <div class="alert alert-success mt-3 py-2">
                                            A new verification link has been sent to your email address.
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        
                        <!-- Phone & Country Code Row -->
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Phone Number</label>
                                <input type="tel" name="phone" class="form-control form-control-solid @error('phone') is-invalid @enderror" 
                                       placeholder="Phone number" value="{{ old('phone', auth()->user()->phone) }}" />
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Country Code</label>
                                <select name="country_code" class="form-select form-select-solid">
                                    <option value="">Select country code</option>
                                    <option value="+1" {{ auth()->user()->country_code == '+1' ? 'selected' : '' }}>+1 (USA/Canada)</option>
                                    <option value="+44" {{ auth()->user()->country_code == '+44' ? 'selected' : '' }}>+44 (UK)</option>
                                    <option value="+254" {{ auth()->user()->country_code == '+254' ? 'selected' : '' }}>+254 (Kenya)</option>
                                    <option value="+254" {{ auth()->user()->country_code == '+256' ? 'selected' : '' }}>+256 (Uganda)</option>
                                    <option value="+234" {{ auth()->user()->country_code == '+234' ? 'selected' : '' }}>+234 (Nigeria)</option>
                                    <option value="+27" {{ auth()->user()->country_code == '+27' ? 'selected' : '' }}>+27 (South Africa)</option>
                                    <option value="+33" {{ auth()->user()->country_code == '+33' ? 'selected' : '' }}>+33 (France)</option>
                                    <option value="+49" {{ auth()->user()->country_code == '+49' ? 'selected' : '' }}>+49 (Germany)</option>
                                    <option value="+61" {{ auth()->user()->country_code == '+61' ? 'selected' : '' }}>+61 (Australia)</option>
                                </select>
                                @error('country_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Bio Field -->
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Bio / About</label>
                            <textarea name="bio" class="form-control form-control-solid" rows="3" 
                                      placeholder="Tell us a little about yourself">{{ old('bio', auth()->user()->bio ?? '') }}</textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ki-duotone ki-check-circle fs-2 me-1"></i>Save Changes
                            </button>
                        </div>
                        
                        @if(session('status') === 'profile-updated')
                            <div class="alert alert-success mt-5 py-2 d-flex align-items-center">
                                <i class="ki-duotone ki-check-circle fs-2 me-2"></i>
                                Profile updated successfully!
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Update Password Section -->
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header pt-7">
                    <h3 class="card-title text-gray-800 fw-bold">Security Settings</h3>
                </div>
                <div class="card-body pt-0">
                    <!-- Update Password -->
                    <div class="mb-10">
                        <h4 class="fw-bold mb-3">Update Password</h4>
                        <p class="text-muted mb-5">Ensure your account is using a long, random password to stay secure.</p>
                        
                        <form method="post" action="{{ route('password.update') }}" class="form">
                            @csrf
                            @method('put')
                            
                            <!-- Current Password -->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Current Password</label>
                                <input type="password" name="current_password" class="form-control form-control-solid @error('current_password', 'updatePassword') is-invalid @enderror" 
                                       placeholder="Enter current password" />
                                @error('current_password', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- New Password -->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">New Password</label>
                                <input type="password" name="password" class="form-control form-control-solid @error('password', 'updatePassword') is-invalid @enderror" 
                                       placeholder="Enter new password" />
                                @error('password', 'updatePassword')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <!-- Confirm Password -->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-control form-control-solid" 
                                       placeholder="Confirm new password" />
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ki-duotone ki-lock-2 fs-2 me-1"></i>Update Password
                                </button>
                            </div>
                            
                            @if(session('status') === 'password-updated')
                                <div class="alert alert-success mt-5 py-2 d-flex align-items-center">
                                    <i class="ki-duotone ki-check-circle fs-2 me-2"></i>
                                    Password updated successfully!
                                </div>
                            @endif
                        </form>
                    </div>
                    
                    <hr class="my-10">
                    
                    <!-- Account Status Info -->
                    <div>
                        <h4 class="fw-bold mb-3">Account Information</h4>
                        <div class="bg-light-primary rounded p-5">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-semibold">Account ID:</span>
                                <span class="text-gray-800">{{ auth()->user()->uuid ?? 'N/A' }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-semibold">Role:</span>
                                <span class="text-gray-800">{{ auth()->user()->roles->first()->name ?? 'No Role' }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-semibold">Email Verified:</span>
                                <span>
                                    @if(auth()->user()->email_verified_at)
                                        <span class="badge badge-light-success">Yes ({{ auth()->user()->email_verified_at->format('M d, Y') }})</span>
                                    @else
                                        <span class="badge badge-light-warning">No</span>
                                    @endif
                                </span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">Last Login:</span>
                                <span class="text-gray-800">{{ auth()->user()->last_login_at ? auth()->user()->last_login_at->format('M d, Y H:i:s') : 'Never' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Account Section -->
    <div class="row g-5 gx-xl-10 mt-5">
        <div class="col-12">
            <div class="card card-flush">
                <div class="card-header pt-7">
                    <h3 class="card-title text-gray-800 fw-bold text-danger">Danger Zone</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="fw-bold mb-2">Delete Account</h4>
                            <p class="text-muted mb-0">
                                Once your account is deleted, all of its resources and data will be permanently deleted.
                            </p>
                        </div>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#kt_modal_delete_account">
                            <i class="ki-duotone ki-trash fs-2 me-1"></i>Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upload Avatar Modal -->
    <div class="modal fade" id="kt_modal_upload_avatar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('post')
                    
                    <div class="modal-header">
                        <h2 class="fw-bold">Change Profile Picture</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                    </div>
                    
                    <div class="modal-body">
                        <div class="text-center mb-5">
                            <div class="symbol symbol-150px symbol-circle mb-3">
                                <img src="{{ auth()->user()->avatar ?? asset('assets/media/avatars/300-1.jpg') }}" alt="Profile Picture" id="avatar_preview" />
                            </div>
                            <p class="text-muted">Upload a new profile picture (JPEG, PNG - Max 2MB)</p>
                        </div>
                        
                        <div class="fv-row mb-7">
                            <input type="file" name="avatar" class="form-control form-control-solid" accept="image/*" id="avatar_input" required />
                            @error('avatar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Account Confirmation Modal -->
    <div class="modal fade" id="kt_modal_delete_account" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')
                    
                    <div class="modal-header">
                        <h2 class="fw-bold">Confirm Account Deletion</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                    </div>
                    
                    <div class="modal-body">
                        <div class="alert alert-danger mb-5">
                            <i class="ki-duotone ki-information-5 fs-2 me-2"></i>
                            This action is permanent and cannot be undone.
                        </div>
                        
                        <p class="text-muted fs-6 mb-5">
                            Are you sure you want to delete your account? Please enter your password to confirm.
                        </p>
                        
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Password</label>
                            <input type="password" name="password" class="form-control form-control-solid @error('password', 'userDeletion') is-invalid @enderror" 
                                   placeholder="Enter your password to confirm" />
                            @error('password', 'userDeletion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Yes, Delete My Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Auto-hide success alerts after 3 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert-success');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 3000);
        });
        
        // Avatar preview
        const avatarInput = document.getElementById('avatar_input');
        if (avatarInput) {
            avatarInput.addEventListener('change', function(e) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatar_preview').setAttribute('src', e.target.result);
                }
                reader.readAsDataURL(e.target.files[0]);
            });
        }
    });
    
    // Calculate profile completion (optional)
    function calculateProfileCompletion() {
        let completed = 0;
        let total = 0;
        
        const fields = ['first_name', 'last_name', 'email', 'phone', 'country_code'];
        fields.forEach(field => {
            total++;
            if (document.querySelector(`[name="${field}"]`)?.value) {
                completed++;
            }
        });
        
        if (document.querySelector('[name="bio"]')?.value) completed++;
        total++;
        
        return Math.round((completed / total) * 100);
    }
</script>
@endpush