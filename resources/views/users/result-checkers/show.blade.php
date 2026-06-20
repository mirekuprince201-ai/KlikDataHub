@extends('users.layouts.app')

@section('title', $category->category_name ?? 'Result Checkers')
@section('page-title', 'Result Checkers')

@push('styles')
<link rel="stylesheet" href="{{ asset('users/css/result-checkers.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $loggedInUser = auth('user')->user();
    $walletBalance = (float) ($stats['wallet_balance'] ?? 0);

    $loggedInName = $loggedInUser
        ? trim(
            ($loggedInUser->full_name ?? '') ?:
            (($loggedInUser->first_name ?? '') . ' ' . ($loggedInUser->last_name ?? ''))
        )
        : null;

    $availableCheckers = collect($checkers ?? [])->filter(function ($checker) {
        $isActive = strtolower((string) ($checker->status ?? 'active')) === 'active';
        $stock = (int) ($checker->available_stock ?? 0);
        return $isActive && $stock > 0;
    })->values();
@endphp

<div id="kdCheckerToast" class="kd-checker-toast" aria-live="polite"></div>

<div class="kd-checkout-overlay" id="kdCheckoutOverlay"></div>

<aside class="kd-checkout-sidebar" id="kdCheckoutSidebar" aria-hidden="true">

    <div class="kd-checkout-sticky-head">
        <div class="kd-checkout-header">
            <div class="kd-checkout-icon">
                <i class="fa-solid fa-square-poll-vertical"></i>
            </div>

            <div>
                <span id="kdSidebarType">RESULT CHECKER</span>
                <h2>Complete Purchase</h2>
            </div>
        </div>

        <button type="button" class="kd-checkout-close" id="kdCheckoutClose" aria-label="Close checkout">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <form action="javascript:void(0)" method="POST" id="kdCheckoutForm" class="kd-checkout-form">
        @csrf

        <input type="hidden" name="checker_id" id="kdCheckoutCheckerId">
        <input type="hidden" name="quantity" id="kdCheckoutQuantity">
        <input type="hidden" name="unit_price" id="kdCheckoutUnitPrice">
        <input type="hidden" name="total_amount" id="kdCheckoutTotalAmount">

        <div class="kd-checkout-scroll">

            <div class="kd-checkout-summary">
                <div>
                    <span>Checker</span>
                    <strong id="kdSidebarCheckerName">-</strong>
                </div>

                <div>
                    <span>Quantity</span>
                    <strong id="kdSidebarQuantity">1</strong>
                </div>

                <div class="kd-summary-total">
                    <span>Total</span>
                    <strong id="kdSidebarTotal">GH₵0.00</strong>
                </div>
            </div>

            @auth('user')
                <div class="kd-checkout-user-box">
                    <i class="fa-solid fa-user-check"></i>
                    <div>
                        <strong>{{ $loggedInName ?: $loggedInUser->email }}</strong>
                        <span>{{ $loggedInUser->email }}</span>
                        <small>{{ $loggedInUser->phone ?? 'No phone number found' }}</small>
                    </div>
                </div>

                <input type="hidden" name="customer_name" id="kdCustomerName" value="{{ $loggedInName }}">
                <input type="hidden" name="customer_email" id="kdCustomerEmail" value="{{ $loggedInUser->email }}">
                <input type="hidden" name="customer_phone" id="kdCustomerPhone" value="{{ $loggedInUser->phone }}">
            @else
                <div class="kd-checkout-field">
                    <label for="kdCustomerName">Full Name</label>
                    <input type="text" name="customer_name" id="kdCustomerName" placeholder="Enter your full name">
                    <small>Your name will appear on the order receipt.</small>
                    <p class="kd-field-error" id="kdNameError"></p>
                </div>

                <div class="kd-checkout-field">
                    <label for="kdCustomerEmail">Email Address</label>
                    <input type="email" name="customer_email" id="kdCustomerEmail" placeholder="Enter your email address">
                    <small>Voucher details will be sent to this email.</small>
                    <p class="kd-field-error" id="kdEmailError"></p>
                </div>

                <div class="kd-checkout-field">
                    <label for="kdCustomerPhone">Phone Number</label>
                    <input type="tel" name="customer_phone" id="kdCustomerPhone" placeholder="0241234567" maxlength="10">
                    <small>SMS delivery number. Must be exactly 10 digits.</small>
                    <p class="kd-field-error" id="kdPhoneError"></p>
                </div>
            @endauth

            <div class="kd-checkout-payment">
                <div class="kd-checkout-payment-head">
                    <label>Payment Method</label>
                    <span>Choose your preferred payment option</span>
                </div>

                <div class="kd-checkout-payment-grid">
                    <label class="kd-sidebar-payment active">
                        <input type="radio" name="payment_method" value="paystack" checked>
                        <span>
                            <i class="fa-solid fa-credit-card"></i>
                            <b>Paystack</b>
                        </span>
                    </label>

                    @auth('user')
                        <label class="kd-sidebar-payment">
                            <input type="radio" name="payment_method" value="wallet">
                            <span>
                                <i class="fa-solid fa-wallet"></i>
                                <b>Wallet</b>
                            </span>
                        </label>
                    @endauth
                </div>
            </div>

            @auth('user')
                <div class="kd-wallet-mini">
                    <span>Wallet Balance</span>
                    <strong>{{ number_format($walletBalance, 2) }}</strong>
                </div>
            @endauth

        </div>

        <div class="kd-checkout-footer">
            <div class="kd-checkout-alert" id="kdCheckoutAlert">
                Confirm your checker type and quantity before creating order.
            </div>

            <button type="submit" class="kd-checkout-pay-btn" id="kdCheckoutPayBtn">
                <i class="fa-solid fa-lock"></i>
                Pay Securely
            </button>
        </div>
    </form>
</aside>

<div class="kd-result-page">

    <a href="{{ route('user.dashboard') }}" class="kd-result-back">
        <i class="fa-solid fa-arrow-left"></i>
        Back to Dashboard
    </a>

    <section class="kd-result-layout">

        <div class="kd-result-shell">
            <div class="kd-result-content">

                <span class="kd-result-label">
                    <i class="fa-solid fa-square-poll-vertical"></i>
                    Result Checker
                </span>

                <h1>{{ strtoupper($category->category_name ?? 'RESULT CHECKER') }}</h1>

                <p class="kd-result-subtitle">
                    Select your checker type and quantity. Click continue to complete your purchase securely.
                </p>

                @if(session('success'))
                    <div class="kd-page-alert kd-page-alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="kd-page-alert kd-page-alert-error">
                        <i class="fa-solid fa-circle-xmark"></i>
                        {{ session('error') }}
                    </div>
                @endif

                @if($availableCheckers->count() > 0)

                    <form action="javascript:void(0)" class="kd-result-form" id="kdResultCheckerForm">

                        <div class="kd-form-group">
                            <label class="kd-main-label">Select Type</label>

                            <div class="kd-type-switches">
                                @foreach($availableCheckers as $checker)
                                    @php
                                        $availableStock = (int) ($checker->available_stock ?? 0);
                                        $price = (float) ($checker->price ?? $checker->selling_price ?? 0);

                                        $discountJson = [];

                                        if (isset($checker->discounts)) {
                                            foreach ($checker->discounts as $discount) {
                                                $discountJson[] = [
                                                    'min' => (int) ($discount->min_quantity ?? $discount['min_quantity'] ?? 0),
                                                    'max' => isset($discount->max_quantity)
                                                        ? ($discount->max_quantity ? (int) $discount->max_quantity : null)
                                                        : (isset($discount['max_quantity']) ? ($discount['max_quantity'] ? (int) $discount['max_quantity'] : null) : null),
                                                    'price' => (float) ($discount->unit_price ?? $discount['unit_price'] ?? 0),
                                                ];
                                            }
                                        }

                                        $checkerLabel = $checker->checker_type ?: $checker->checker_name;
                                    @endphp

                                    <label class="kd-type-option {{ $loop->first ? 'active' : '' }}">
                                        <input
                                            type="radio"
                                            name="checker_id"
                                            value="{{ $checker->checker_id }}"
                                            data-name="{{ $checker->checker_name }}"
                                            data-type="{{ $checker->checker_type ?? 'CHECKER' }}"
                                            data-price="{{ $price }}"
                                            data-stock="{{ $availableStock }}"
                                            data-discounts='@json($discountJson)'
                                            {{ $loop->first ? 'checked' : '' }}
                                        >

                                        <span class="kd-radio-ui"></span>

                                        <span class="kd-type-text">
                                            <strong>{{ strtoupper($checkerLabel) }}</strong>
                                            <small>{{ number_format($availableStock) }} Available</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="kd-form-grid">
                            <div class="kd-form-group">
                                <label class="kd-main-label" for="quantity">Quantity</label>
                                <input
                                    type="number"
                                    name="quantity"
                                    id="quantity"
                                    min="1"
                                    value="1"
                                    required
                                >
                            </div>

                            <div class="kd-form-group">
                                <label class="kd-main-label">Available Stock</label>
                                <div class="kd-readonly-box" id="kdAvailableStock">0</div>
                            </div>
                        </div>

                        <div class="kd-amount-card">
                            <div>
                                <span>Unit Price</span>
                                <strong id="kdUnitPrice">GH₵0.00</strong>
                            </div>

                            <div>
                                <span>Total Amount</span>
                                <strong id="kdTotalAmount">GH₵0.00</strong>
                            </div>
                        </div>

                        <div class="kd-selected-note" id="kdSelectedNote">
                            Select a checker type to continue.
                        </div>

                        <button type="button" class="kd-buy-btn" id="kdOpenCheckoutBtn">
                            <i class="fa-solid fa-cart-shopping"></i>
                            Continue Purchase
                        </button>

                    </form>

                @else
                    <div class="kd-empty-result">
                        <i class="fa-solid fa-box-open"></i>
                        <h3>No Result Checker Available</h3>
                        <p>Please check back later. Available checker products will appear here once stock is added.</p>
                    </div>
                @endif

            </div>
        </div>

        <aside class="kd-result-notice">
            <div class="kd-notice-head">
                <span>
                    <i class="fa-solid fa-circle-info"></i>
                    Notice
                </span>

                <h2>How to Check Your Result</h2>

                <p>
                    After purchasing your voucher, follow these official WAECDirect steps carefully.
                </p>
            </div>

            <div class="kd-notice-steps">
                <div><b>1</b><span>Enter your Index Number.</span></div>
                <div><b>2</b><span>Select the Type of Examination, for example WASSCE.</span></div>
                <div><b>3</b><span>Select your Examination Year, for example 2003.</span></div>
                <div><b>4</b><span>Enter the Serial Number found on your voucher.</span></div>
                <div><b>5</b><span>Enter the 12-digit PIN on your voucher.</span></div>
                <div><b>6</b><span>Confirm your Index Number and Examination Year.</span></div>
                <div><b>7</b><span>Click Submit and wait for the popup window.</span></div>
            </div>

            <a href="https://ghana.waecdirect.org/" target="_blank" rel="noopener" class="kd-waec-link">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                Open WAECDirect Result Portal
            </a>
        </aside>

    </section>

</div>

@endsection

@push('scripts')
<script src="https://js.paystack.co/v2/inline.js"></script>

<script>
    window.KD_RESULT_CHECKER_CONFIG = {
        isLoggedIn: @json(auth('user')->check()),
        walletBalance: Number(@json($walletBalance)),
        paystackChargeRate: 0,
        csrfToken: @json(csrf_token()),
        initializeUrl: @json(route('user.result-checkers.payment.initialize')),
        verifyUrl: @json(route('user.result-checkers.payment.verify')),
        walletUrl: @json(route('user.result-checkers.payment.wallet'))
    };
</script>

<script src="{{ asset('users/js/result-checkers.js') }}?v={{ time() }}"></script>
@endpush