@extends('users.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('users/css/dashboard.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $loggedInUser = auth('user')->user();
    $firstName = $loggedInUser->first_name ?? 'Customer';
    $walletBalance = $stats['wallet_balance'] ?? 0;

    $toastType = null;
    $toastMessage = null;

    if (session('success')) {
        $toastType = 'success';
        $toastMessage = session('success');
    } elseif (session('error')) {
        $toastType = 'error';
        $toastMessage = session('error');
    } elseif ($errors->any()) {
        $toastType = 'error';
        $toastMessage = $errors->first();
    }

    function kdImageUrl($image, $fallback = 'images/category-placeholder.png') {
        if (empty($image)) {
            return asset($fallback);
        }

        $image = trim($image);

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        if (str_starts_with($image, 'storage/')) {
            return asset($image);
        }

        if (str_starts_with($image, 'public/')) {
            return asset('storage/' . substr($image, 7));
        }

        if (str_starts_with($image, 'uploads/')) {
            return asset($image);
        }

        return asset('storage/' . ltrim($image, '/'));
    }
@endphp

<div id="dashboardToast" class="kd-dashboard-toast" aria-live="polite"></div>

<div class="kd-dashboard">

    <section class="kd-instant-hero">

        <div class="kd-hero-content">

            @auth('user')
                <span class="kd-hero-welcome">
                    <i class="fa-solid fa-circle-check"></i>
                    Welcome back, {{ strtoupper($firstName) }}
                </span>
            @else
                <span class="kd-hero-welcome">
                    <i class="fa-solid fa-bolt"></i>
                    Buy without an account
                </span>
            @endauth

            <h1>Buy data <span>instantly.</span></h1>

            <p>
                Choose your preferred network category, AFA registration, or result checker
                service below and continue securely.
            </p>

        </div>

        @auth('user')
            <div class="kd-wallet-mini">
                <div class="kd-wallet-icon">
                    <i class="fa-solid fa-wallet"></i>
                </div>

                <span>Wallet Balance</span>
                <strong>GH₵{{ number_format($walletBalance, 2) }}</strong>
            </div>
        @endauth

        @guest('user')
            <div class="kd-login-mini-card">

                <div class="kd-login-mini-icon">
                    <i class="fa-solid fa-user-lock"></i>
                </div>

                <span>Have an Account?</span>

                <strong>Login & Track Orders</strong>

                <a href="{{ route('user.login') }}"
                   class="kd-login-mini-btn">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Login
                </a>

            </div>
        @endguest

    </section>

    <section class="kd-category-section">

        <div class="kd-section-heading">
            <span>Data Categories</span>

            <h2>
                <span class="kd-title-red">Select</span>
                <span class="kd-title-gold">Network</span>
                <span class="kd-title-green">Category</span>
            </h2>

            <p>
                Choose your preferred network category to browse available data packages.
            </p>
        </div>

        @if(isset($categories) && $categories->count() > 0)

            <div class="kd-category-grid">

                @foreach($categories as $category)

                    @php
                        $isActive = strtolower($category->status ?? '') === 'active'
                                    || (int) ($category->is_active ?? 0) === 1;

                        $categoryImage = $category->image_url
                            ?? $category->category_image
                            ?? $category->image
                            ?? null;

                        $categoryImageUrl = kdImageUrl($categoryImage, 'images/category-placeholder.png');
                    @endphp

                    @if($isActive)
                        <a href="{{ url('/packages/' . $category->category_slug) }}"
                           class="kd-category-card kd-category-active">

                            <div class="kd-category-image">
                                <img
                                    src="{{ $categoryImageUrl }}"
                                    alt="{{ $category->category_name }}"
                                    loading="lazy"
                                    onerror="this.onerror=null;this.src='{{ asset('images/category-placeholder.png') }}';">
                            </div>

                            <h3>{{ strtoupper($category->category_name) }}</h3>
                        </a>
                    @else
                        <div class="kd-category-card kd-category-inactive">

                            <div class="kd-category-image">
                                <img
                                    src="{{ $categoryImageUrl }}"
                                    alt="{{ $category->category_name }}"
                                    loading="lazy"
                                    onerror="this.onerror=null;this.src='{{ asset('images/category-placeholder.png') }}';">

                                <div class="kd-out-stock-badge">OUT OF STOCK</div>
                            </div>

                            <h3>{{ strtoupper($category->category_name) }}</h3>
                        </div>
                    @endif

                @endforeach

            </div>

        @else

            <div class="kd-empty-state">
                <i class="fa-solid fa-layer-group"></i>
                <h3>No Categories Available</h3>
                <p>Please check back later. Available categories will appear here.</p>
            </div>

        @endif

    </section>

    <section class="kd-category-section kd-afa-section">

        <div class="kd-section-heading">
            <span>AFA & Result Checker</span>

            <h2>
                <span class="kd-title-red">AFA</span>
                <span class="kd-title-gold">And</span>
                <span class="kd-title-green">Result Checker</span>
            </h2>

            <p>
                Select an available AFA registration or result checker option and continue securely.
            </p>
        </div>

        @if(
            (isset($afas) && $afas->count() > 0) ||
            (isset($resultCheckers) && $resultCheckers->count() > 0)
        )

            <div class="kd-afa-grid kd-service-grid">

                @if(isset($afas) && $afas->count() > 0)

                    @foreach($afas as $afa)

                        @php
                            $afaIsActive = strtolower($afa->status ?? '') === 'active'
                                           || (int) ($afa->is_active ?? 0) === 1;

                            $afaImage = $afa->image_url
                                ?? $afa->registration_image
                                ?? $afa->afa_image
                                ?? $afa->image
                                ?? null;

                            $afaImageUrl = kdImageUrl($afaImage, 'images/afa-placeholder.png');
                        @endphp

                        @if($afaIsActive)
                            <a href="{{ url('/afa/' . $afa->registration_slug) }}"
                               class="kd-afa-card kd-service-card kd-afa-active">

                                <div class="kd-afa-image">
                                    <img
                                        src="{{ $afaImageUrl }}"
                                        alt="{{ $afa->registration_name }}"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='{{ asset('images/afa-placeholder.png') }}';">
                                </div>

                                <h3>{{ strtoupper($afa->registration_name) }}</h3>
                            </a>
                        @else
                            <div class="kd-afa-card kd-service-card kd-afa-inactive">

                                <div class="kd-afa-image">
                                    <img
                                        src="{{ $afaImageUrl }}"
                                        alt="{{ $afa->registration_name }}"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='{{ asset('images/afa-placeholder.png') }}';">

                                    <div class="kd-unavailable-badge">UNAVAILABLE</div>
                                </div>

                                <h3>{{ strtoupper($afa->registration_name) }}</h3>
                            </div>
                        @endif

                    @endforeach

                @endif

                @if(isset($resultCheckers) && $resultCheckers->count() > 0)

                    @foreach($resultCheckers as $checker)

                        @php
                            $checkerIsActive = strtolower($checker->status ?? '') === 'active'
                                               || (int) ($checker->is_active ?? 0) === 1;

                            $checkerImage = $checker->image_url
                                ?? $checker->category_image
                                ?? $checker->image
                                ?? null;

                            $checkerImageUrl = kdImageUrl($checkerImage, 'images/result-checker-placeholder.png');
                        @endphp

                        @if($checkerIsActive)
                            <a href="{{ url('/result-checkers/' . $checker->category_slug) }}"
                               class="kd-afa-card kd-service-card kd-result-checker-card kd-afa-active">

                                <div class="kd-afa-image">
                                    <img
                                        src="{{ $checkerImageUrl }}"
                                        alt="{{ $checker->category_name }}"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='{{ asset('images/result-checker-placeholder.png') }}';">
                                </div>

                                <h3>{{ strtoupper($checker->category_name) }}</h3>
                            </a>
                        @else
                            <div class="kd-afa-card kd-service-card kd-result-checker-card kd-afa-inactive">

                                <div class="kd-afa-image">
                                    <img
                                        src="{{ $checkerImageUrl }}"
                                        alt="{{ $checker->category_name }}"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='{{ asset('images/result-checker-placeholder.png') }}';">

                                    <div class="kd-unavailable-badge">UNAVAILABLE</div>
                                </div>

                                <h3>{{ strtoupper($checker->category_name) }}</h3>
                            </div>
                        @endif

                    @endforeach

                @endif

            </div>

        @else

            <div class="kd-empty-state">
                <i class="fa-solid fa-id-card"></i>
                <h3>No AFA Or Result Checker Available</h3>
                <p>Please check back later. AFA and result checker options will appear here.</p>
            </div>

        @endif

    </section>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const toast = document.getElementById('dashboardToast');

    function showDashboardToast(message, type = 'success') {
        if (!toast || !message) return;

        const icon = type === 'success'
            ? 'fa-circle-check'
            : type === 'warning'
                ? 'fa-circle-exclamation'
                : 'fa-triangle-exclamation';

        toast.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;
        toast.className = `kd-dashboard-toast ${type} show`;

        clearTimeout(window.dashboardToastTimer);
        window.dashboardToastTimer = setTimeout(() => {
            toast.classList.remove('show');
        }, 4500);
    }

    @if($toastMessage)
        showDashboardToast(`{!! addslashes($toastMessage) !!}`, '{{ $toastType ?? "success" }}');
    @endif

});
</script>
@endpush