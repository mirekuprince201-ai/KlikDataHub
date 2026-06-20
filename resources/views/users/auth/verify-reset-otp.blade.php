@php
    use Illuminate\Support\Facades\DB;

    $business = $business ?? DB::table('business_settings')->first();
    $businessName = $business->business_name ?? 'KlikDataHub';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - {{ $businessName }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('users/css/register.css') }}?v={{ time() }}">
</head>

<body>

<main class="kd-register-page">

    <section class="kd-register-card">

        <div class="kd-register-header">

            <div class="kd-register-logo">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <h1>OTP Verification</h1>

            <p>
                Enter the 6-digit verification code sent to your registered phone number.
            </p>

        </div>

        @if(session('success'))
            <div class="kd-auth-alert kd-auth-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="kd-auth-alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form
            method="POST"
            action="{{ url('/verify-reset-otp') }}"
            class="kd-register-form"
            id="otpForm"
        >
            @csrf

            <div class="kd-form-group">

                <label>One-Time Password (OTP)</label>

                <div class="kd-input-wrap">

                    <i class="fa-solid fa-mobile-screen"></i>

                    <input
                        type="text"
                        name="otp"
                        maxlength="6"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        placeholder="Enter 6-digit OTP"
                        class="kd-otp-input"
                        required
                    >

                </div>

                @error('otp')
                    <small>{{ $message }}</small>
                @enderror

            </div>

            <div class="kd-otp-note">
                <i class="fa-solid fa-circle-info"></i>

                <span>
                    Your OTP expires after 10 minutes.
                    If it expires, request a new OTP.
                </span>
            </div>

            <button type="submit" class="kd-auth-btn">
                <i class="fa-solid fa-check"></i>
                Verify OTP
            </button>

            <div class="kd-auth-link">
                Wrong phone number or OTP expired?
                <a href="{{ url('/forgot-password') }}">
                    Request New OTP
                </a>
            </div>

        </form>

    </section>

</main>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('otpForm');

    const otpInput = document.querySelector('input[name="otp"]');

    if (otpInput) {

        otpInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });

    }

    if (form) {

        form.addEventListener('submit', function () {

            const btn = form.querySelector('.kd-auth-btn');

            if (!btn || btn.disabled) {
                return;
            }

            btn.disabled = true;

            btn.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Verifying OTP...';

        });

    }

});

</script>

</body>
</html>