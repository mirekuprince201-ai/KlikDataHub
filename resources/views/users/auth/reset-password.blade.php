@php
    use Illuminate\Support\Facades\DB;

    $business = $business ?? DB::table('business_settings')->first();
    $businessName = $business->business_name ?? 'KlikDataHub';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - {{ $businessName }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('users/css/register.css') }}?v={{ time() }}">
</head>

<body>

<main class="kd-register-page">

    <section class="kd-register-card">

        <div class="kd-register-header">
            <div class="kd-register-logo">
                <i class="fa-solid fa-lock"></i>
            </div>

            <h1>Create New Password</h1>
            <p>Your OTP has been verified. Set a strong new password for your account.</p>
        </div>

        @if (session('success'))
            <div class="kd-auth-alert kd-auth-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="kd-auth-alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ url('/reset-password') }}" class="kd-register-form" id="resetPasswordForm">
            @csrf

            <div class="kd-form-group">
                <label>New Password</label>

                <div class="kd-input-wrap">
                    <i class="fa-solid fa-lock"></i>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter new password"
                        autocomplete="new-password"
                        required
                    >

                    <button type="button" class="kd-password-toggle" data-target="password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                @error('password')
                    <small>{{ $message }}</small>
                @enderror
            </div>

            <div class="kd-form-group">
                <label>Confirm Password</label>

                <div class="kd-input-wrap">
                    <i class="fa-solid fa-shield-halved"></i>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Confirm new password"
                        autocomplete="new-password"
                        required
                    >

                    <button type="button" class="kd-password-toggle" data-target="password_confirmation">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="kd-reset-security">
                <i class="fa-solid fa-circle-info"></i>
                <span>Use at least 8 characters. For better security, combine letters, numbers, and symbols.</span>
            </div>

            <button type="submit" class="kd-auth-btn">
                <i class="fa-solid fa-key"></i>
                Reset Password
            </button>

            <div class="kd-auth-link">
                Back to
                <a href="{{ url('/login') }}">Login</a>
            </div>
        </form>

    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('resetPasswordForm');

    document.querySelectorAll('.kd-password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById(this.dataset.target);
            const icon = this.querySelector('i');

            if (!input || !icon) return;

            const hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';

            icon.classList.toggle('fa-eye', !hidden);
            icon.classList.toggle('fa-eye-slash', hidden);
        });
    });

    if (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('.kd-auth-btn');

            if (!btn || btn.disabled) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating Password...';
        });
    }
});
</script>

</body>
</html>