@php
    $businessName = $business->business_name ?? 'KlikDataHub';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - {{ $businessName }}</title>
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

            <h1>Create Account</h1>
            <p>Register to continue with {{ $businessName }}.</p>
        </div>

        @if ($errors->any())
            <div class="kd-auth-alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ url('/register') }}" class="kd-register-form" id="kdRegisterForm">
            @csrf

            <div class="kd-form-row">
                <div class="kd-form-group">
                    <label>First Name</label>
                    <div class="kd-input-wrap">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="First name" required>
                    </div>
                    @error('first_name') <small>{{ $message }}</small> @enderror
                </div>

                <div class="kd-form-group">
                    <label>Last Name</label>
                    <div class="kd-input-wrap">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Last name" required>
                    </div>
                    @error('last_name') <small>{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="kd-form-group">
                <label>Phone Number</label>
                <div class="kd-input-wrap">
                    <i class="fa-solid fa-phone"></i>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="0240000000" maxlength="10" required>
                </div>
                @error('phone') <small>{{ $message }}</small> @enderror
            </div>

            <div class="kd-form-group">
                <label>Email Address</label>
                <div class="kd-input-wrap">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Email address" required>
                </div>
                @error('email') <small>{{ $message }}</small> @enderror
            </div>

            <div class="kd-form-group">
                <label>Password</label>
                <div class="kd-input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <button type="button" class="kd-password-toggle" data-target="password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                @error('password') <small>{{ $message }}</small> @enderror
            </div>

            <div class="kd-form-group">
                <label>Confirm Password</label>
                <div class="kd-input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password_confirmation" id="confirmPassword" placeholder="Confirm password" required>
                    <button type="button" class="kd-password-toggle" data-target="confirmPassword">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="kd-auth-btn">
                <i class="fa-solid fa-user-plus"></i>
                Create Account
            </button>

            @if ($loginEnabled)
                <div class="kd-auth-link">
                    Already have an account?
                    <a href="{{ url('/login') }}">Login</a>
                </div>
            @endif
        </form>

    </section>

</main>

<script src="{{ asset('users/js/register.js') }}?v={{ time() }}"></script>

</body>
</html>