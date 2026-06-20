@php
    use Illuminate\Support\Facades\DB;

    $business = $business ?? DB::table('business_settings')->first();

    $businessName = $business->business_name ?? 'KlikDataHub';
    $registrationEnabled = (int) ($business->registration_system_enabled ?? $business->allow_registration ?? 1) === 1;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - {{ $businessName }}</title>
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
                <i class="fa-solid fa-bolt"></i>
            </div>

            <h1>Login</h1>
            <p>Access your {{ $businessName }} account securely.</p>
        </div>

        @if (session('success'))
            <div class="kd-auth-alert kd-auth-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('status'))
            <div class="kd-auth-alert kd-auth-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="kd-auth-alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ url('/login') }}" class="kd-register-form" id="kdLoginForm">
            @csrf

            <div class="kd-form-group">
                <label>Email Address or Phone Number</label>
                <div class="kd-input-wrap">
                    <i class="fa-solid fa-user"></i>
                    <input
                        type="text"
                        name="login"
                        value="{{ old('login') }}"
                        placeholder="Email or phone number"
                        autocomplete="username"
                        required
                    >
                </div>
                @error('login') <small>{{ $message }}</small> @enderror
            </div>

            <div class="kd-form-group">
                <label>Password</label>
                <div class="kd-input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Password"
                        autocomplete="current-password"
                        required
                    >

                    <button type="button" class="kd-password-toggle" data-target="password" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                @error('password') <small>{{ $message }}</small> @enderror
            </div>

            <div class="kd-auth-options">
                <label>
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>

                <a href="{{ url('/forgot-password') }}" class="kd-forgot-link">
                    Forgot password?
                </a>
            </div>

            <button type="submit" class="kd-auth-btn">
                <i class="fa-solid fa-right-to-bracket"></i>
                Login
            </button>

            @if ($registrationEnabled)
                <div class="kd-auth-link">
                    Don’t have an account?
                    <a href="{{ url('/register') }}">Create Account</a>
                </div>
            @endif
        </form>

    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('kdLoginForm');

    document.querySelectorAll('.kd-password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById(this.dataset.target);
            const icon = this.querySelector('i');

            if (!input || !icon) return;

            const hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';

            icon.classList.toggle('fa-eye', !hidden);
            icon.classList.toggle('fa-eye-slash', hidden);

            button.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
        });
    });

    if (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('.kd-auth-btn');

            if (!btn || btn.disabled) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Logging in...';
        });
    }
});
</script>

</body>
</html>