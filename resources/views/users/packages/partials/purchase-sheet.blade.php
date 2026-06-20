@php
    $loggedInUser = auth('user')->user();
    $isLoggedInUser = auth('user')->check();
    $loggedInEmail = $loggedInUser->email ?? '';
    $walletBalance = (float) ($walletBalance ?? ($loggedInUser->wallet_balance ?? 0));
@endphp

<div class="kd-purchase-backdrop" id="purchaseBackdrop"></div>

<aside class="kd-purchase-sheet {{ $networkClass }}" id="purchaseSheet">

    <div class="kd-purchase-handle"></div>

    <div class="kd-purchase-header">
        <div class="kd-purchase-network">
            <img id="purchaseNetworkImage" src="{{ $categoryImage }}" alt="{{ $categoryName }}">
        </div>

        <div class="kd-purchase-heading-text">
            <span id="purchaseNetworkName">{{ $categoryName }}</span>
            <h2>Complete Purchase</h2>
        </div>

        <button type="button" class="kd-close-purchase-sheet" id="closePurchaseSheet" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="kd-purchase-summary {{ ! $isLoggedInUser ? 'kd-guest-summary' : '' }}">
        <div>
            <span>Data Size</span>
            <strong id="purchaseDataSize">--</strong>
        </div>

        <div>
            <span>Amount</span>
            <strong>GH₵ <b id="purchaseAmount">0.00</b></strong>
        </div>

        @if($isLoggedInUser)
            <div class="kd-wallet-balance-box">
                <span>Wallet Balance</span>
                <strong>GH₵ {{ number_format($walletBalance, 2) }}</strong>
            </div>
        @endif
    </div>

    <form method="POST"
          action="{{ route('user.packages.purchase') }}"
          class="kd-purchase-form"
          id="purchaseForm"
          data-is-logged-in="{{ $isLoggedInUser ? '1' : '0' }}"
          novalidate>

        @csrf

        <input type="hidden" name="plan_id" id="purchasePlanId">
        <input type="hidden" name="network_name" id="purchaseNetworkInput" value="{{ $categoryName }}">
        <input type="hidden" name="payment_method" id="paymentMethodInput" value="paystack">

        @if($isLoggedInUser)
            <input type="hidden" id="loginEmail" value="{{ $loggedInEmail }}">
        @else
            <div class="kd-form-group login-email-group">
                <label>Email Address</label>
                <input type="email"
                       id="loginEmail"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="Enter your email"
                       autocomplete="email"
                       required>
            </div>
        @endif

        <div class="kd-form-group">
            <label>Beneficiary Number</label>
            <input type="text"
                   id="beneficiaryNumber"
                   name="recipient_phone"
                   maxlength="10"
                   inputmode="numeric"
                   autocomplete="tel"
                   placeholder="Enter beneficiary number"
                   required>

            <small class="kd-field-hint" id="beneficiaryHint">Number to receive the data.</small>
            <small class="kd-field-error" id="beneficiaryError"></small>
        </div>

        <label class="kd-ported-check" id="portedWrapper">
            <input type="checkbox" id="portedNumber" name="is_ported" value="1">
            <span>
                <strong>Ported Number</strong>
                <small>Tick only if this number was ported.</small>
            </span>
        </label>

        <div class="kd-payment-method-wrap">
            <div class="kd-payment-method-title">
                <label>Payment Method</label>
                <span>{{ $isLoggedInUser ? 'Choose Paystack or Wallet' : 'Guest checkout uses MoMo' }}</span>
            </div>

            <div class="kd-payment-method-grid {{ ! $isLoggedInUser ? 'kd-guest-payment-grid' : '' }}">
                <label class="kd-payment-method-option active" data-payment-method="paystack">
                    <input type="radio" name="payment_method_choice" value="paystack" checked>
                    <span class="kd-payment-method-icon"><i class="fa-solid fa-credit-card"></i></span>
                    <span class="kd-payment-method-text">
                        <strong class="kd-paystack-desktop">{{ $isLoggedInUser ? 'Paystack' : 'MoMo' }}</strong>
                        <strong class="kd-paystack-mobile">MoMo</strong>
                    </span>
                    <span class="kd-payment-check"><i class="fa-solid fa-check"></i></span>
                </label>

                @if($isLoggedInUser)
                    <label class="kd-payment-method-option" data-payment-method="wallet">
                        <input type="radio" name="payment_method_choice" value="wallet">
                        <span class="kd-payment-method-icon"><i class="fa-solid fa-wallet"></i></span>
                        <span class="kd-payment-method-text"><strong>Wallet</strong></span>
                        <span class="kd-payment-check"><i class="fa-solid fa-check"></i></span>
                    </label>
                @endif
            </div>
        </div>

        <div class="kd-purchase-alert" id="purchaseAlert"></div>

        <button type="submit" class="kd-submit-purchase-btn" id="createOrderBtn">
            <i class="fa-solid fa-lock"></i>
            <span>Create Order</span>
        </button>

    </form>
</aside>