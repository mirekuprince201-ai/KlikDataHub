@extends('users.layouts.app')

@section('title', $afa->registration_name)
@section('page-title', 'AFA Registration')

@push('styles')
<link rel="stylesheet" href="{{ asset('users/css/afa-order.css') }}?v={{ time() }}">

<style>
    .kd-afa-price-box .charge-row,
    .kd-afa-price-box .total-row {
        display: none !important;
    }

    .kd-afa-price-box > div:first-child strong {
        font-size: 25px;
    }

    .kd-field-error {
        display: none;
        margin-top: 7px;
        color: #ef4444;
        font-size: 12px;
        font-weight: 800;
        line-height: 1.4;
    }

    .kd-form-group.has-error .kd-field-error {
        display: block;
    }

    .kd-form-group.has-error input,
    .kd-form-group.has-error .kd-smart-select-btn {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, .12) !important;
    }

    .kd-payment-disabled-alert {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 16px 18px;
        margin-bottom: 20px;
        border-radius: 20px;
        background: rgba(239, 68, 68, .1);
        border: 1px solid rgba(239, 68, 68, .25);
        color: #991b1b;
    }

    .kd-payment-disabled-alert i {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: rgba(239, 68, 68, .15);
        color: #dc2626;
        flex-shrink: 0;
    }

    .kd-payment-disabled-alert strong {
        display: block;
        font-size: 15px;
        font-weight: 900;
        margin-bottom: 4px;
    }

    .kd-payment-disabled-alert p {
        margin: 0;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.6;
    }

    .kd-pay-btn:disabled,
    .kd-payment-option.disabled,
    .kd-payment-option.disabled span {
        cursor: not-allowed !important;
        opacity: .6;
        pointer-events: none;
    }

    .kd-ported-wrap {
        display: none;
    }

    .kd-ported-wrap.show {
        display: block;
    }

    .kd-smart-select {
        width: 100%;
        position: relative;
    }

    .kd-smart-select-btn {
        width: 100%;
        text-align: left;
        overflow: hidden;
    }

    .kd-smart-select-btn span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .kd-smart-select-panel {
        width: 100%;
        left: 0;
        right: 0;
        overflow: hidden;
    }

    .kd-smart-search {
        position: relative;
        display: flex;
        align-items: center;
    }

    .kd-smart-search i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        z-index: 2;
    }

    .kd-smart-search input {
        width: 100%;
        padding-left: 40px !important;
    }

    .kd-payment-methods {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 12px !important;
    }

    .kd-payment-option {
        width: 100%;
    }

    .kd-payment-option span {
        width: 100%;
        min-height: 58px;
        padding: 10px 12px;
        text-align: center;
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, .28);
    }

    .kd-payment-option.active span {
        background: linear-gradient(135deg, rgba(37, 99, 235, .22), rgba(37, 99, 235, .08)) !important;
        border-color: #2563eb !important;
        color: #ffffff !important;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
    }

    .kd-payment-option.active span i {
        color: #ffffff !important;
    }

    body.dark-mode .kd-payment-disabled-alert,
    html[data-theme="dark"] .kd-payment-disabled-alert {
        background: rgba(239, 68, 68, .12);
        border-color: rgba(248, 113, 113, .25);
        color: #fecaca;
    }

    body.dark-mode .kd-payment-disabled-alert i,
    html[data-theme="dark"] .kd-payment-disabled-alert i {
        background: rgba(239, 68, 68, .16);
        color: #fca5a5;
    }

    @media (max-width: 640px) {
        .kd-afa-hero h1 {
            font-size: 28px !important;
            line-height: 1.1 !important;
        }

        .kd-afa-hero p {
            font-size: 14px !important;
            line-height: 1.6 !important;
        }

        .kd-payment-methods {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 10px !important;
        }

        .kd-payment-option span {
            min-height: 56px;
            font-size: 12px !important;
            gap: 6px !important;
            padding: 8px 6px !important;
            line-height: 1.25;
        }

        .kd-payment-option span i {
            font-size: 13px;
        }

        .kd-pay-btn {
            height: 58px;
            font-size: 14px !important;
        }
    }

    @media (max-width: 390px) {
        .kd-payment-option span {
            font-size: 11px !important;
        }
    }
</style>
@endpush

@section('content')

@php
    $loggedInUser = auth('user')->user();

    $baseAmount = (float) $afa->registration_amount;
    $chargeRate = 0.025;
    $charges = round($baseAmount * $chargeRate, 2);
    $totalAmount = round($baseAmount + $charges, 2);

    $walletBalance = (float) ($loggedInUser->wallet_balance ?? 0);

    $paymentStatus = $paymentStatus ?? [
        'enabled' => true,
        'title' => 'Payment Available',
        'message' => 'Payment is available.',
    ];

    $paymentEnabled = (bool) ($paymentStatus['enabled'] ?? false);
@endphp

<div class="kd-afa-order-page">

    <div class="kd-afa-shell">

        <div class="kd-afa-hero">
            <span class="kd-afa-badge">
                <i class="fa-solid fa-id-card"></i>
                AFA Registration
            </span>

            <h1>{{ strtoupper($afa->registration_name) }}</h1>

            <p>
                Complete your registration with accurate details and continue payment securely.
            </p>

            <div class="kd-afa-price-box">
                <div>
                    <span>Amount</span>
                    <strong>GH₵{{ number_format($baseAmount, 2) }}</strong>
                </div>

                <div class="charge-row">
                    <span>Processing Charge</span>
                    <strong>GH₵{{ number_format($charges, 2) }}</strong>
                </div>

                <div class="total total-row">
                    <span>Total Payable</span>
                    <strong>GH₵{{ number_format($totalAmount, 2) }}</strong>
                </div>
            </div>

            @auth('user')
                <div class="kd-wallet-box">
                    <i class="fa-solid fa-wallet"></i>
                    <div>
                        <span>Wallet Balance</span>
                        <strong>GH₵{{ number_format($walletBalance, 2) }}</strong>
                    </div>
                </div>
            @endauth
        </div>

        <div class="kd-afa-form-card">

            <div id="afaPageMessage" class="kd-page-message"></div>

            @if (!$paymentEnabled)
                <div class="kd-payment-disabled-alert">
                    <i class="fa-solid fa-ban"></i>
                    <div>
                        <strong>{{ $paymentStatus['title'] ?? 'Payment Disabled' }}</strong>
                        <p>{{ $paymentStatus['message'] ?? 'Payment is currently unavailable. Please try again later.' }}</p>
                    </div>
                </div>
            @endif

            <form id="afaPaymentForm"
                  method="POST"
                  action="{{ route('user.afa.payment.initialize', $afa->registration_slug) }}"
                  novalidate>

                @csrf

                @guest('user')
                    <div class="kd-form-group" data-field="customer_email">
                        <label>Email Address <span>*</span></label>
                        <input type="email"
                               name="customer_email"
                               id="customer_email"
                               placeholder="Enter your email address">
                        <small class="kd-field-error">Please enter a valid email address.</small>
                    </div>
                @else
                    <input type="hidden"
                           name="customer_email"
                           id="customer_email"
                           value="{{ $loggedInUser->email }}">
                @endguest

                <div class="kd-form-group" data-field="customer_name">
                    <label>Full Name <span>*</span></label>
                    <input type="text"
                           name="customer_name"
                           id="customer_name"
                           placeholder="Enter full name"
                           autocomplete="name">
                    <small class="kd-field-error">Please enter your full name.</small>
                </div>

                <div class="kd-form-group" data-field="ghana_card_number">
                    <label>Ghana Card Number <small>(optional)</small></label>
                    <input type="text"
                           name="ghana_card_number"
                           id="ghana_card_number"
                           placeholder="GHA-719125278-6"
                           inputmode="numeric"
                           autocomplete="off"
                           maxlength="14">
                    <small class="kd-field-error">Ghana Card number must be exactly 10 digits. Example: GHA-719125278-6</small>
                </div>

                <div class="kd-form-group" data-field="phone_number">
                    <label>MTN Phone Number <span>*</span></label>
                    <input type="text"
                           name="phone_number"
                           id="phone_number"
                           placeholder="0240000000"
                           inputmode="numeric"
                           maxlength="10"
                           autocomplete="tel">

                    <div class="kd-ported-wrap" id="portedWrap">
                        <label class="kd-check-line">
                            <input type="checkbox" name="phone_is_ported" id="phone_is_ported" value="1">
                            <span>This number is ported to MTN</span>
                        </label>
                    </div>

                    <small class="kd-field-error" id="phoneError">Please enter a valid MTN phone number.</small>
                </div>

                <div class="kd-form-row">
                    <div class="kd-form-group" data-field="location">
                        <label>Location <span>*</span></label>
                        <input type="text"
                               name="location"
                               id="location"
                               placeholder="Example: Accra">
                        <small class="kd-field-error">Please enter your location.</small>
                    </div>

                    <div class="kd-form-group" data-field="occupation">
                        <label>Occupation <span>*</span></label>

                        <div class="kd-smart-select" id="occupationSelect">
                            <input type="hidden" name="occupation" id="occupation">

                            <button type="button" class="kd-smart-select-btn">
                                <span>Select occupation</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>

                            <div class="kd-smart-select-panel">
                                <div class="kd-smart-search">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" placeholder="Search occupation..." id="occupationSearch">
                                </div>

                                <div class="kd-smart-options">
                                    <button type="button" data-value="Student">Student</button>
                                    <button type="button" data-value="Trader">Trader</button>
                                    <button type="button" data-value="Farmer">Farmer</button>
                                    <button type="button" data-value="Teacher">Teacher</button>
                                    <button type="button" data-value="Nurse">Nurse</button>
                                    <button type="button" data-value="Driver">Driver</button>
                                    <button type="button" data-value="Mechanic">Mechanic</button>
                                    <button type="button" data-value="Hairdresser">Hairdresser</button>
                                    <button type="button" data-value="Seamstress / Tailor">Seamstress / Tailor</button>
                                    <button type="button" data-value="Mason">Mason</button>
                                    <button type="button" data-value="Carpenter">Carpenter</button>
                                    <button type="button" data-value="Business Owner">Business Owner</button>
                                    <button type="button" data-value="Civil Servant">Civil Servant</button>
                                    <button type="button" data-value="Security Officer">Security Officer</button>
                                    <button type="button" data-value="Unemployed">Unemployed</button>
                                    <button type="button" data-value="Other">Other</button>
                                </div>
                            </div>
                        </div>

                        <small class="kd-field-error">Please select your occupation.</small>
                    </div>
                </div>

                @auth('user')
                    <div class="kd-payment-methods">
                        <label class="kd-payment-option active {{ !$paymentEnabled ? 'disabled' : '' }}" id="paystackOption">
                            <input type="radio"
                                   name="payment_method"
                                   value="paystack_popup"
                                   checked
                                   {{ !$paymentEnabled ? 'disabled' : '' }}>
                            <span>
                                <i class="fa-solid fa-mobile-screen"></i>
                                Pay with MoMo/Card
                            </span>
                        </label>

                        <label class="kd-payment-option {{ !$paymentEnabled ? 'disabled' : '' }}" id="walletOption">
                            <input type="radio"
                                   name="payment_method"
                                   value="wallet"
                                   {{ !$paymentEnabled ? 'disabled' : '' }}>
                            <span>
                                <i class="fa-solid fa-wallet"></i>
                                Pay with Wallet
                            </span>
                        </label>
                    </div>
                @else
                    <input type="hidden" name="payment_method" value="paystack_popup">
                @endauth

                <button type="submit" class="kd-pay-btn" id="payAfaBtn" {{ !$paymentEnabled ? 'disabled' : '' }}>
                    <i class="fa-solid {{ $paymentEnabled ? 'fa-lock' : 'fa-ban' }}"></i>
                    {{ $paymentEnabled ? 'Continue Payment' : 'Payment Disabled' }}
                </button>

            </form>
        </div>

    </div>

</div>

@endsection

@push('scripts')
@if ($paymentEnabled)
<script src="https://js.paystack.co/v2/inline.js"></script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const paymentEnabled = @json($paymentEnabled);
    const disabledMessage = @json($paymentStatus['message'] ?? 'Payment is currently unavailable. Please try again later.');

    const form = document.getElementById('afaPaymentForm');
    const payBtn = document.getElementById('payAfaBtn');
    const pageMessage = document.getElementById('afaPageMessage');

    const emailInput = document.getElementById('customer_email');
    const nameInput = document.getElementById('customer_name');
    const ghanaCardInput = document.getElementById('ghana_card_number');
    const phoneInput = document.getElementById('phone_number');
    const portedInput = document.getElementById('phone_is_ported');
    const portedWrap = document.getElementById('portedWrap');
    const locationInput = document.getElementById('location');

    const mtnPrefixes = ['024', '025', '053', '054', '055', '059'];

    const defaultPayment = document.querySelector('input[name="payment_method"][value="paystack_popup"]');

    if (defaultPayment && paymentEnabled) {
        defaultPayment.checked = true;
        document.querySelectorAll('.kd-payment-option').forEach(label => label.classList.remove('active'));
        defaultPayment.closest('.kd-payment-option').classList.add('active');
    }

    function groupOf(inputOrFieldName) {
        if (typeof inputOrFieldName === 'string') {
            return document.querySelector(`[data-field="${inputOrFieldName}"]`);
        }

        return inputOrFieldName ? inputOrFieldName.closest('.kd-form-group') : null;
    }

    function setFieldError(inputOrFieldName, message = null) {
        const group = groupOf(inputOrFieldName);
        if (!group) return;

        group.classList.add('has-error');

        const error = group.querySelector('.kd-field-error');
        if (error && message) {
            error.textContent = message;
        }
    }

    function clearFieldError(inputOrFieldName) {
        const group = groupOf(inputOrFieldName);
        if (!group) return;

        group.classList.remove('has-error');
    }

    function showMessage(type, message) {
        pageMessage.className = 'kd-page-message show ' + type;
        pageMessage.innerHTML = `
            <i class="fa-solid ${type === 'success' ? 'fa-circle-check' : type === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark'}"></i>
            <span>${message}</span>
        `;
        pageMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function clearMessage() {
        pageMessage.className = 'kd-page-message';
        pageMessage.innerHTML = '';
    }

    function setLoading(isLoading, text = 'Continue Payment') {
        if (!paymentEnabled) {
            payBtn.disabled = true;
            payBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Payment Disabled';
            return;
        }

        payBtn.disabled = isLoading;
        payBtn.innerHTML = isLoading
            ? '<i class="fa-solid fa-spinner fa-spin"></i> ' + text
            : '<i class="fa-solid fa-lock"></i> Continue Payment';
    }

    function getPhonePrefix(phone) {
        return phone.substring(0, 3);
    }

    function hasMtnPrefix(phone) {
        return /^0\d{9}$/.test(phone) && mtnPrefixes.includes(getPhonePrefix(phone));
    }

    function updatePortedVisibility() {
        const phone = phoneInput.value.trim();

        if (phone.length >= 3 && /^0\d{2}/.test(phone) && !mtnPrefixes.includes(getPhonePrefix(phone))) {
            portedWrap.classList.add('show');
        } else {
            portedWrap.classList.remove('show');
            portedInput.checked = false;
        }
    }

    function formatGhanaCard(value) {
        const digits = value.replace(/\D/g, '').slice(0, 10);

        if (!digits.length) {
            return '';
        }

        if (digits.length <= 9) {
            return 'GHA-' + digits;
        }

        return 'GHA-' + digits.slice(0, 9) + '-' + digits.slice(9);
    }

    phoneInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 10);
        updatePortedVisibility();
        clearFieldError(phoneInput);
    });

    if (portedInput) {
        portedInput.addEventListener('change', function () {
            clearFieldError(phoneInput);
        });
    }

    ghanaCardInput.addEventListener('input', function () {
        this.value = formatGhanaCard(this.value);
        clearFieldError(ghanaCardInput);
    });

    ghanaCardInput.addEventListener('paste', function (event) {
        event.preventDefault();

        const pasted = (event.clipboardData || window.clipboardData).getData('text');
        this.value = formatGhanaCard(pasted);
        clearFieldError(ghanaCardInput);
    });

    [emailInput, nameInput, locationInput].forEach(input => {
        if (!input) return;

        input.addEventListener('input', function () {
            clearFieldError(this);
        });
    });

    document.querySelectorAll('.kd-payment-option input').forEach(input => {
        input.addEventListener('change', function () {
            if (!paymentEnabled) return;

            document.querySelectorAll('.kd-payment-option').forEach(label => label.classList.remove('active'));
            this.closest('.kd-payment-option').classList.add('active');
        });
    });

    const smartSelect = document.getElementById('occupationSelect');
    const smartBtn = smartSelect.querySelector('.kd-smart-select-btn');
    const smartBtnText = smartBtn.querySelector('span');
    const occupationInput = document.getElementById('occupation');
    const occupationSearch = document.getElementById('occupationSearch');
    const occupationOptions = smartSelect.querySelectorAll('.kd-smart-options button');

    smartBtn.addEventListener('click', function () {
        smartSelect.classList.toggle('open');
        occupationSearch.value = '';
        occupationOptions.forEach(option => option.style.display = 'flex');

        setTimeout(() => {
            occupationSearch.focus();
        }, 120);
    });

    occupationOptions.forEach(option => {
        option.addEventListener('click', function () {
            occupationInput.value = this.dataset.value;
            smartBtnText.textContent = this.dataset.value;
            smartSelect.classList.remove('open');

            occupationOptions.forEach(btn => btn.classList.remove('selected'));
            this.classList.add('selected');

            clearFieldError('occupation');
        });
    });

    occupationSearch.addEventListener('input', function () {
        const search = this.value.toLowerCase();

        occupationOptions.forEach(option => {
            const text = option.dataset.value.toLowerCase();
            option.style.display = text.includes(search) ? 'flex' : 'none';
        });
    });

    document.addEventListener('click', function (event) {
        if (!smartSelect.contains(event.target)) {
            smartSelect.classList.remove('open');
        }
    });

    function validateForm() {
        let valid = true;

        clearMessage();

        document.querySelectorAll('.kd-form-group').forEach(group => {
            group.classList.remove('has-error');
        });

        if (emailInput && emailInput.type !== 'hidden') {
            const email = emailInput.value.trim();

            if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
                setFieldError(emailInput, 'Please enter a valid email address.');
                valid = false;
            }
        }

        if (!nameInput.value.trim()) {
            setFieldError(nameInput, 'Please enter your full name.');
            valid = false;
        }

        if (ghanaCardInput.value.trim()) {
            const digits = ghanaCardInput.value.replace(/\D/g, '');

            if (digits.length !== 10) {
                setFieldError(ghanaCardInput, 'Ghana Card number must be exactly 10 digits. Example: GHA-719125278-6');
                valid = false;
            }
        }

        const phone = phoneInput.value.trim();

        if (!phone) {
            setFieldError(phoneInput, 'Please enter your MTN phone number.');
            valid = false;
        } else if (!/^0\d{9}$/.test(phone)) {
            setFieldError(phoneInput, 'Phone number must be exactly 10 digits and start with 0.');
            valid = false;
        } else if (!hasMtnPrefix(phone) && portedInput && !portedInput.checked) {
            portedWrap.classList.add('show');
            setFieldError(phoneInput, 'This is not an MTN prefix. Tick ported to MTN if the number is ported.');
            valid = false;
        }

        if (!locationInput.value.trim()) {
            setFieldError(locationInput, 'Please enter your location.');
            valid = false;
        }

        if (!occupationInput.value.trim()) {
            setFieldError('occupation', 'Please select your occupation.');
            valid = false;
        }

        return valid;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (!paymentEnabled) {
            showMessage('error', disabledMessage);
            setLoading(false);
            return;
        }

        if (!validateForm()) {
            showMessage('error', 'Please correct the highlighted fields and try again.');
            return;
        }

        if (typeof PaystackPop === 'undefined') {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'paystack_popup';

            if (selectedMethod === 'paystack_popup') {
                showMessage('error', 'Payment gateway could not load. Please refresh the page and try again.');
                return;
            }
        }

        setLoading(true, 'Initializing...');

        try {
            const formData = new FormData(form);

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const result = await response.json();

            if (!response.ok || !result.status) {
                showMessage('error', result.message || 'Unable to initialize payment.');
                setLoading(false);
                return;
            }

            if (result.payment_method === 'wallet') {
                showMessage('success', result.message || 'Wallet payment successful.');

                setTimeout(() => {
                    window.location.href = result.redirect_url || "{{ route('user.dashboard') }}";
                }, 900);

                return;
            }

            const popup = new PaystackPop();

            popup.resumeTransaction(result.access_code, {
                onSuccess: async function (transaction) {
                    setLoading(true, 'Verifying...');

                    const verifyResponse = await fetch("{{ route('user.afa.payment.verify') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            reference: transaction.reference || result.payment_reference
                        })
                    });

                    const verifyResult = await verifyResponse.json();

                    if (verifyResponse.ok && verifyResult.status) {
                        showMessage('success', verifyResult.message || 'Payment successful.');

                        setTimeout(() => {
                            window.location.href = verifyResult.redirect_url || "{{ route('user.dashboard') }}";
                        }, 900);

                        return;
                    }

                    showMessage('error', verifyResult.message || 'Payment verification failed.');
                    setLoading(false);
                },

                onCancel: function () {
                    showMessage('warning', 'Payment was cancelled. You can try again when ready.');
                    setLoading(false);
                },

                onError: function () {
                    showMessage('error', 'Payment failed. Please try again.');
                    setLoading(false);
                }
            });

        } catch (error) {
            showMessage('error', 'Payment could not be started. Please check your connection and try again.');
            setLoading(false);
        }
    });

    if (!paymentEnabled) {
        payBtn.disabled = true;
        payBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Payment Disabled';

        document.querySelectorAll('.kd-payment-option input').forEach(input => {
            input.disabled = true;
        });
    }
});
</script>
@endpush