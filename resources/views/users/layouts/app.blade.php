<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        (function () {
            var savedTheme = localStorage.getItem('kd_user_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'KlikDataHub')</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('images/klik-logo.jpeg') }}">
    <link rel="shortcut icon" href="{{ asset('images/klik-logo.jpeg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/klik-logo.jpeg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="{{ asset('users/css/app.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('users/css/sidebar.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('users/css/topbar.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('users/css/footer.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('users/css/whatsapp-channel.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('users/css/announcement-modal.css') }}?v={{ time() }}">

    @stack('styles')
</head>

<body>

<div class="kd-user-layout">

    @include('users.layouts.partials.sidebar')

    <div class="kd-main-wrapper">

        @include('users.layouts.partials.topbar')

        <main class="kd-main-content">
            @yield('content')
        </main>

        @include('users.layouts.partials.footer')

    </div>

</div>

{{-- Announcement Modal --}}
@include('users.partials.announcement-modal')

{{-- WhatsApp Channel Component --}}
<x-whatsapp-channel />

<script>
    window.KD_FLASH_MESSAGES = {
        success: @json(session('success')),
        error: @json(session('error')),
        warning: @json(session('warning')),
        info: @json(session('info')),
        validation: @json($errors->any() ? $errors->all() : [])
    };
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="{{ asset('users/js/app.js') }}?v={{ time() }}"></script>
<script src="{{ asset('users/js/sidebar.js') }}?v={{ time() }}"></script>
<script src="{{ asset('users/js/theme.js') }}?v={{ time() }}"></script>
<script src="{{ asset('users/js/topbar.js') }}?v={{ time() }}"></script>

@stack('scripts')

</body>
</html>