<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Login | KlikDataHub</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="{{ asset('admin/css/auth.css') }}?v={{ time() }}"
    >
</head>

<body>

<div class="auth-page">

    <div class="auth-card login-card">

        <div class="auth-brand">

            <div class="auth-logo">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <h1>KlikDataHub</h1>

            <p>
                Secure admin portal login
            </p>

        </div>

        @if(!$loginEnabled)

            <div class="alert alert-danger">
                <i class="fa-solid fa-lock"></i>

                <div>
                    <strong>
                        {{ $setting->message_title ?? 'Admin Access Unavailable' }}
                    </strong>

                    <p>
                        {{ $setting->message_body ?? 'Admin access is currently disabled. Please contact the system owner.' }}
                    </p>
                </div>
            </div>

        @else

            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>

                    <div>
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('admin.auth.login.store') }}"
                id="adminLoginForm"
            >
                @csrf

                <div class="form-group">

                    <label for="login">
                        Email or Username
                    </label>

                    <div class="input-wrap">
                        <i class="fa-solid fa-user"></i>

                        <input
                            id="login"
                            type="text"
                            name="login"
                            value="{{ old('login') }}"
                            placeholder="Enter email or username"
                            required
                            autofocus
                        >
                    </div>

                </div>

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-wrap">
                        <i class="fa-solid fa-lock"></i>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Enter password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            data-target="password"
                        >
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>

                </div>

                <div class="auth-options">

                    <label class="remember-box">
                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                        >

                        <span>
                            Remember me
                        </span>
                    </label>

                </div>

                <button
                    type="submit"
                    class="auth-btn"
                    id="loginBtn"
                >
                    <span class="btn-text">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        Login to Admin Portal
                    </span>

                    <span class="btn-loader">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Logging in...
                    </span>
                </button>

                @if($registrationEnabled)
                    <div class="auth-link">
                        Don’t have an admin account?

                        <a href="{{ route('admin.auth.register') }}">
                            Create Account
                        </a>
                    </div>
                @endif

            </form>

        @endif

    </div>

</div>

<script src="{{ asset('admin/js/auth.js') }}?v={{ time() }}"></script>

</body>
</html>