@extends('admin.layouts.app')

@section('title', 'Admin Auth Setup')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/admin-auth-settings.css') }}?v={{ time() }}">
@endpush

@section('content')

<div class="kd-admin-auth-page">

    <div class="kd-admin-auth-header">

        <div class="kd-admin-auth-header-left">

            <div class="kd-admin-auth-icon">
                <i class="fa-solid fa-user-shield"></i>
            </div>

            <div>

                <span class="kd-page-badge">
                    <i class="fa-solid fa-lock"></i>
                    Security Controls
                </span>

                <h3>Admin Authentication Settings</h3>

                <p>
                    Control administrator login and registration access
                    across the platform.
                </p>

            </div>

        </div>

    </div>

    @if(session('success'))
        <div class="kd-alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <form
        action="{{ route('admin.settings.admin-auth.update') }}"
        method="POST"
    >
        @csrf

        <div class="kd-auth-grid">

            <label class="kd-auth-card">

                <input
                    type="checkbox"
                    name="allow_admin_login"
                    {{ ($setting->allow_admin_login ?? 1) ? 'checked' : '' }}
                >

                <div class="kd-auth-card-icon kd-blue">
                    <i class="fa-solid fa-right-to-bracket"></i>
                </div>

                <div class="kd-auth-card-content">
                    <h4>Allow Admin Login</h4>

                    <p>
                        Enable or disable administrator login access.
                    </p>
                </div>

                <div class="kd-switch"></div>

            </label>

            <label class="kd-auth-card">

                <input
                    type="checkbox"
                    name="allow_admin_registration"
                    {{ ($setting->allow_admin_registration ?? 1) ? 'checked' : '' }}
                >

                <div class="kd-auth-card-icon kd-purple">
                    <i class="fa-solid fa-user-plus"></i>
                </div>

                <div class="kd-auth-card-content">
                    <h4>Allow Admin Registration</h4>

                    <p>
                        Enable or disable new administrator registration.
                    </p>
                </div>

                <div class="kd-switch"></div>

            </label>

        </div>

        <div class="kd-auth-message-card">

            <div class="kd-card-title">
                <i class="fa-solid fa-message"></i>
                Disabled Access Message
            </div>

            <div class="kd-form-group">
                <label>Message Title</label>

                <input
                    type="text"
                    name="message_title"
                    value="{{ old('message_title', $setting->message_title ?? '') }}"
                    placeholder="Admin Access Unavailable"
                >
            </div>

            <div class="kd-form-group">
                <label>Message Body</label>

                <textarea
                    name="message_body"
                    rows="5"
                    placeholder="Message shown when admin login or registration is disabled."
                >{{ old('message_body', $setting->message_body ?? '') }}</textarea>
            </div>

        </div>

        <div class="kd-auth-actions">

            <button type="submit">

                <i class="fa-solid fa-floppy-disk"></i>

                Save Settings

            </button>

        </div>

    </form>

</div>

@endsection