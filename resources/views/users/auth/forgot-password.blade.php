@php
    use Illuminate\Support\Facades\DB;

    $business = $business ?? DB::table('business_settings')->first();
    $businessName = $business->business_name ?? 'KlikDataHub';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - {{ $businessName }}</title>
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
                <i class="fa-solid fa-key"></i>
            </div>

            <h1>Forgot Password</h1>
            <p>Enter your email or phone number. We will send an OTP to your registered phone number.</p>
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

        <form method="POST" action="{{ url('/forgot-password') }}" class="kd-register-form" id="forgotPasswordForm">
            @csrf

            <div class="kd-form-group">
                <label>Email Address or Phone Number</label>

                <div class="kd-input-wrap">
                    <i class="fa-solid fa-user"></i>

                    <input
                        type="text"
                        name="login"
                        value="{{ old('login') }}"
                        placeholder="Enter email or phone number"
                        autocomplete="username"
                        required
                    >
                </div>

                @error('login')
                    <small>{{ $message }}</small>
                @enderror
            </div>

            <div class="kd-otp-note">
                <i class="fa-solid fa-circle-info"></i>
                <span>The OTP will be sent to the phone number connected to your account.</span>
            </div>

            <button type="submit" class="kd-auth-btn">
                <i class="fa-solid fa-paper-plane"></i>
                Send OTP
            </button>

            <div class="kd-auth-link">
                Remember your password?
                <a href="{{ url('/login') }}">Login</a>
            </div>
        </form>

    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('forgotPasswordForm');

    if (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('.kd-auth-btn');

            if (!btn || btn.disabled) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending OTP...';
        });
    }
});
</script>

</body>
</html>