@extends('users.layouts.app')

@section('title', 'Packages')
@section('page-title', 'Packages')

@push('styles')
<link rel="stylesheet" href="{{ asset('users/css/packages.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('users/css/purchase-sheet.css') }}?v={{ time() }}">

@endpush

@section('content')

@php
    $user = auth('user')->user();
    $categoryName = strtoupper($category->category_name ?? 'DATA');
    $networkName = strtolower($category->category_name ?? '');

    if (str_contains($networkName, 'mtn')) {
        $networkClass = 'network-mtn';
    } elseif (str_contains($networkName, 'telecel') || str_contains($networkName, 'vodafone')) {
        $networkClass = 'network-telecel';
    } elseif (str_contains($networkName, 'at') || str_contains($networkName, 'airteltigo') || str_contains($networkName, 'airtel')) {
        $networkClass = 'network-at';
    } else {
        $networkClass = 'network-default';
    }

    $categoryImage = $category->image_url ?? asset('images/category-placeholder.png');

    $paymentStatus = $paymentStatus ?? [
        'enabled' => true,
        'title' => 'Payment Available',
        'message' => 'Payment is available.',
    ];

    $paymentEnabled = (bool) ($paymentStatus['enabled'] ?? false);
@endphp

<div class="kd-packages-page {{ $networkClass }}">

    <div class="kd-package-header">

        <a href="{{ route('user.dashboard') }}" class="kd-back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Back
        </a>

        <div class="kd-package-title-wrap">
            <div class="kd-package-title-logo">
                <img src="{{ $categoryImage }}" alt="{{ $categoryName }}">
            </div>

            <div>
                <h1>{{ $categoryName }} BUNDLES</h1>
                <p>Choose your preferred data package and continue securely.</p>
            </div>
        </div>

    </div>

    <div id="packageAlert" class="kd-package-alert" style="display:none;"></div>

    @if (!$paymentEnabled)
        <div class="kd-payment-disabled-alert">
            <i class="fa-solid fa-ban"></i>
            <div>
                <strong>{{ $paymentStatus['title'] ?? 'Payment Disabled' }}</strong>
                <p>{{ $paymentStatus['message'] ?? 'Payments are currently unavailable. Please try again later.' }}</p>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="kd-package-alert success">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="kd-package-alert error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ $errors->first() }}
        </div>
    @endif

    @if(! $categoryIsActive)

        <div class="kd-out-stock-state">
            <i class="fa-solid fa-circle-exclamation"></i>
            <h3>{{ $categoryName }} Is Currently Out Of Stock</h3>
            <p>Please check back later. Packages for this category are currently unavailable.</p>
        </div>

    @elseif(isset($packages) && $packages->count() > 0)

        <div class="kd-package-divider"></div>

        <div class="kd-package-grid">
            @foreach($packages as $package)
                <button type="button"
                        class="kd-package-card kd-open-purchase-sheet {{ !$paymentEnabled ? 'is-payment-disabled' : '' }}"
                        data-payment-enabled="{{ $paymentEnabled ? '1' : '0' }}"
                        data-payment-message="{{ $paymentStatus['message'] ?? 'Payments are currently unavailable. Please try again later.' }}"
                        data-package-id="{{ $package->plan_id }}"
                        data-package-volume="{{ strtoupper($package->data_volume ?? $package->plan_name) }}"
                        data-package-price="{{ number_format((float) $package->selling_price, 2, '.', '') }}"
                        data-package-network="{{ strtoupper($package->network ?? $categoryName) }}"
                        data-package-image="{{ $categoryImage }}">

                    <div class="kd-package-card-top">
                        <div class="kd-package-logo">
                            <img src="{{ $categoryImage }}" alt="{{ $categoryName }}">
                        </div>

                        <span>{{ strtoupper($package->network ?? $categoryName) }}</span>
                    </div>

                    <div class="kd-package-card-body">
                        <h2>{{ strtoupper($package->data_volume ?? $package->plan_name) }}</h2>
                        <strong>GH₵ {{ number_format((float) $package->selling_price, 2) }}</strong>
                    </div>

                </button>
            @endforeach
        </div>

    @else

        <div class="kd-empty-state">
            <i class="fa-solid fa-box-open"></i>
            <h3>No Packages Available</h3>
            <p>No active packages were found for {{ $categoryName }}.</p>
        </div>

    @endif

</div>

@include('users.packages.partials.purchase-sheet', [
    'networkClass' => $networkClass,
    'categoryName' => $categoryName,
    'categoryImage' => $categoryImage,
    'paymentStatus' => $paymentStatus,
    'paymentEnabled' => $paymentEnabled,
])

@endsection

@push('scripts')
@if ($paymentEnabled)
<script src="https://js.paystack.co/v1/inline.js"></script>
@endif

<script>
window.KD_PACKAGE_PAYMENT = {
    paymentEnabled: @json($paymentEnabled),
    paymentDisabledTitle: @json($paymentStatus['title'] ?? 'Payment Disabled'),
    paymentDisabledMessage: @json($paymentStatus['message'] ?? 'Payments are currently unavailable. Please try again later.'),
    purchaseUrl: "{{ route('user.packages.purchase') }}",
    paystackInitializeUrl: "{{ route('user.payments.initialize') }}",
    paystackVerifyUrl: "{{ route('user.payments.verify') }}",
    walletPaymentUrl: "{{ route('user.payments.wallet') }}",
    dashboardUrl: "{{ route('user.dashboard') }}",
    csrfToken: "{{ csrf_token() }}"
};
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const config = window.KD_PACKAGE_PAYMENT || {};
    const paymentEnabled = !!config.paymentEnabled;

    function showPackageAlert(type, message) {
        const alert = document.getElementById('packageAlert');

        if (!alert) {
            return;
        }

        alert.style.display = 'flex';
        alert.className = 'kd-package-alert ' + type;
        alert.innerHTML = `
            <i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'}"></i>
            ${message}
        `;

        alert.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    if (!paymentEnabled) {
        document.querySelectorAll('.kd-open-purchase-sheet').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();

                showPackageAlert('error', config.paymentDisabledMessage || 'Payments are currently unavailable. Please try again later.');
            }, true);
        });
    }
});
</script>

<script src="{{ asset('users/js/packages.js') }}?v={{ time() }}"></script>
@endpush