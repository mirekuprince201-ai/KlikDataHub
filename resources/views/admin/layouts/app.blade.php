<!DOCTYPE html>
<html lang="en">

<head>

    {{-- Prevent Theme Flash On Refresh --}}
    <script>
        (function () {
            const savedTheme = localStorage.getItem('klikdata_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Laravel CSRF Token For AJAX / Fetch Requests --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Dashboard') | KlikDataHub</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('images/klik-logo.jpeg') }}">
    <link rel="shortcut icon" href="{{ asset('images/klik-logo.jpeg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/klik-logo.jpeg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">

    <link rel="stylesheet" href="{{ asset('admin/css/sidebar.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('admin/css/topbar.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('admin/css/footer.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('admin/css/app.css') }}?v={{ time() }}">

    @stack('styles')

</head>

<body>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="admin-wrapper">

        @include('admin.layouts.partials.sidebar')

        <main class="admin-main" id="adminMain">

            @include('admin.layouts.partials.topbar')

            <section class="admin-content">
                @yield('content')
            </section>

            @include('admin.layouts.partials.footer')

        </main>

    </div>

    {{-- IMPORTANT: all page sidebars/modals must render here, outside admin-main --}}
    @stack('modals')

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
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

    <script src="{{ asset('admin/js/app.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('admin/js/sidebar.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('admin/js/theme.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('admin/js/topbar.js') }}?v={{ time() }}"></script>

    @stack('scripts')

</body>

</html>