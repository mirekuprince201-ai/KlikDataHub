@extends('users.layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@push('styles')
<link rel="stylesheet" href="{{ asset('users/css/settings.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $firstName = $user->first_name ?? '';
    $lastName = $user->last_name ?? '';
    $fullName = $user->full_name ?? trim($firstName . ' ' . $lastName);
    $initials = strtoupper(substr($firstName ?: 'U', 0, 1) . substr($lastName ?: '', 0, 1));

    $profileImage = $user->profile_image
        ? (str_starts_with($user->profile_image, 'http') ? $user->profile_image : asset($user->profile_image))
        : null;
@endphp

<div class="kd-settings-page">

    <div class="kd-settings-hero">
        <div>
            <span class="kd-settings-badge">
                <i class="fa-solid fa-gear"></i>
                Account Settings
            </span>

            <h1>Settings</h1>
            <p>Update your profile details and secure your account password.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="kd-settings-alert success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="kd-settings-alert error">
            <i class="fa-solid fa-circle-xmark"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="kd-settings-alert error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Please check the highlighted fields and try again.</span>
        </div>
    @endif

    <div class="kd-settings-grid">

        <div class="kd-settings-card">
            <div class="kd-settings-card-head">
                <div>
                    <span>Profile</span>
                    <h2>Personal Information</h2>
                </div>
            </div>

            <form method="POST"
                  action="{{ route('user.settings.profile.update') }}"
                  enctype="multipart/form-data"
                  class="kd-settings-form">

                @csrf

                <div class="kd-profile-preview">
                    <div class="kd-profile-avatar" id="profileAvatarPreview">
                        @if($profileImage)
                            <img src="{{ $profileImage }}" alt="{{ $fullName }}">
                        @else
                            <span>{{ $initials }}</span>
                        @endif
                    </div>

                    <div>
                        <h3>{{ $fullName ?: 'User Account' }}</h3>
                        <p>{{ $user->email }}</p>

                        <label class="kd-upload-btn">
                            <i class="fa-solid fa-camera"></i>
                            Change Photo
                            <input type="file"
                                   name="profile_image"
                                   id="profileImageInput"
                                   accept="image/png,image/jpeg,image/jpg,image/webp">
                        </label>

                        @error('profile_image')
                            <small class="kd-error-text">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="kd-form-row">
                    <div class="kd-form-group">
                        <label>First Name <span>*</span></label>
                        <input type="text"
                               name="first_name"
                               value="{{ old('first_name', $user->first_name) }}"
                               placeholder="First name"
                               required>
                        @error('first_name')
                            <small>{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="kd-form-group">
                        <label>Last Name <span>*</span></label>
                        <input type="text"
                               name="last_name"
                               value="{{ old('last_name', $user->last_name) }}"
                               placeholder="Last name"
                               required>
                        @error('last_name')
                            <small>{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="kd-form-row">
                    <div class="kd-form-group">
                        <label>Phone Number <span>*</span></label>
                        <input type="text"
                               name="phone"
                               id="phoneInput"
                               value="{{ old('phone', $user->phone) }}"
                               placeholder="0551082710"
                               maxlength="10"
                               inputmode="numeric"
                               required>
                        @error('phone')
                            <small>{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="kd-form-group">
                        <label>Email Address <span>*</span></label>
                        <input type="email"
                               name="email"
                               value="{{ old('email', $user->email) }}"
                               placeholder="Email address"
                               required>
                        @error('email')
                            <small>{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="kd-settings-btn">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Profile
                </button>
            </form>
        </div>

        <div class="kd-settings-card">
            <div class="kd-settings-card-head">
                <div>
                    <span>Security</span>
                    <h2>Change Password</h2>
                </div>
            </div>

            <form method="POST"
                  action="{{ route('user.settings.password.update') }}"
                  class="kd-settings-form">

                @csrf

                <div class="kd-form-group">
                    <label>Current Password <span>*</span></label>

                    <div class="kd-password-field">
                        <input type="password"
                               name="current_password"
                               placeholder="Enter current password"
                               required>
                        <button type="button" class="kd-password-toggle">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>

                    @error('current_password')
                        <small>{{ $message }}</small>
                    @enderror
                </div>

                <div class="kd-form-group">
                    <label>New Password <span>*</span></label>

                    <div class="kd-password-field">
                        <input type="password"
                               name="password"
                               placeholder="Enter new password"
                               required>
                        <button type="button" class="kd-password-toggle">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>

                    @error('password')
                        <small>{{ $message }}</small>
                    @enderror
                </div>

                <div class="kd-form-group">
                    <label>Confirm Password <span>*</span></label>

                    <div class="kd-password-field">
                        <input type="password"
                               name="password_confirmation"
                               placeholder="Confirm new password"
                               required>
                        <button type="button" class="kd-password-toggle">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="kd-settings-btn danger">
                    <i class="fa-solid fa-shield-halved"></i>
                    Update Password
                </button>
            </form>

            <div class="kd-security-note">
                <i class="fa-solid fa-circle-info"></i>
                <p>Use at least 8 characters. Avoid using your name, phone number, or simple passwords.</p>
            </div>
        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const phoneInput = document.getElementById('phoneInput');
    const imageInput = document.getElementById('profileImageInput');
    const avatarPreview = document.getElementById('profileAvatarPreview');

    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
        });
    }

    if (imageInput && avatarPreview) {
        imageInput.addEventListener('change', function () {
            const file = this.files && this.files[0];

            if (!file) return;

            const reader = new FileReader();

            reader.onload = function (event) {
                avatarPreview.innerHTML = `<img src="${event.target.result}" alt="Profile Preview">`;
            };

            reader.readAsDataURL(file);
        });
    }

    document.querySelectorAll('.kd-password-toggle').forEach(button => {
        button.addEventListener('click', function () {
            const field = this.closest('.kd-password-field');
            const input = field.querySelector('input');
            const icon = this.querySelector('i');

            input.type = input.type === 'password' ? 'text' : 'password';

            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });
});
</script>
@endpush