<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Register | KlikDataHub</title>

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

    <div class="auth-card register-card">

        <div class="auth-brand">

            <div class="auth-logo">
                <i class="fa-solid fa-user-shield"></i>
            </div>

            <h1>KlikDataHub</h1>

            <p>
                Create secure admin account
            </p>

        </div>

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
            action="{{ route('admin.auth.register.store') }}"
            id="adminRegisterForm"
        >
            @csrf

            <div class="form-grid">

                <div class="form-group">
                    <label for="first_name">First Name</label>

                    <div class="input-wrap">
                        <i class="fa-solid fa-user"></i>

                        <input
                            id="first_name"
                            type="text"
                            name="first_name"
                            value="{{ old('first_name') }}"
                            placeholder="Enter first name"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>

                    <div class="input-wrap">
                        <i class="fa-solid fa-user"></i>

                        <input
                            id="last_name"
                            type="text"
                            name="last_name"
                            value="{{ old('last_name') }}"
                            placeholder="Enter last name"
                            required
                        >
                    </div>
                </div>

            </div>

            <div class="form-group">
                <label for="other_name">Other Name</label>

                <div class="input-wrap">
                    <i class="fa-solid fa-user-pen"></i>

                    <input
                        id="other_name"
                        type="text"
                        name="other_name"
                        value="{{ old('other_name') }}"
                        placeholder="Enter other name"
                    >
                </div>
            </div>

            <div class="form-grid">

                <div class="form-group">
                    <label for="username">Username</label>

                    <div class="input-wrap">
                        <i class="fa-solid fa-at"></i>

                        <input
                            id="username"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            placeholder="Enter username"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>

                    <div class="input-wrap">
                        <i class="fa-solid fa-phone"></i>

                        <input
                            id="phone"
                            type="text"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="0240000000"
                            maxlength="20"
                            required
                        >
                    </div>
                </div>

            </div>

            <div class="form-group">
                <label for="email">Email Address</label>

                <div class="input-wrap">
                    <i class="fa-solid fa-envelope"></i>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="admin@example.com"
                        required
                    >
                </div>
            </div>

            <div class="form-grid">

                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="input-wrap">
                        <i class="fa-solid fa-lock"></i>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Minimum 8 characters"
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

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>

                    <div class="input-wrap">
                        <i class="fa-solid fa-lock"></i>

                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            placeholder="Confirm password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            data-target="password_confirmation"
                        >
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

            </div>

            <button
                type="submit"
                class="auth-btn"
                id="registerBtn"
            >
                <span class="btn-text">
                    <i class="fa-solid fa-user-plus"></i>
                    Create Admin Account
                </span>

                <span class="btn-loader">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    Creating...
                </span>
            </button>

            @if($loginEnabled)
                <div class="auth-link">
                    Already have an account?

                    <a href="{{ route('admin.auth.login') }}">
                        Login here
                    </a>
                </div>
            @endif

        </form>

    </div>

</div>

<script src="{{ asset('admin/js/auth.js') }}?v={{ time() }}"></script>

</body>
</html>