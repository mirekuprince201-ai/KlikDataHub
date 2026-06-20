@extends('users.layouts.app')

@section('title', 'My Wallet')
@section('page-title', 'My Wallet')

@push('styles')
<link rel="stylesheet" href="{{ asset('users/css/wallet.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $walletBalance = (float) ($user->wallet_balance ?? 0);

    $paymentStatus = $paymentStatus ?? [
        'enabled' => true,
        'title' => 'Payment Available',
        'message' => 'Wallet top up is available.',
    ];

    $paymentEnabled = (bool) ($paymentStatus['enabled'] ?? false);
@endphp

<div class="kd-wallet-page">

    <div class="kd-wallet-hero">
        <div>
            <span class="kd-wallet-badge">
                <i class="fa-solid fa-wallet"></i>
                Secure Wallet
            </span>

            <h1>GH₵{{ number_format($walletBalance, 2) }}</h1>
            <p>Your available wallet balance for purchases and AFA payments.</p>
        </div>

        <button
            type="button"
            class="kd-wallet-topup-btn {{ !$paymentEnabled ? 'is-disabled' : '' }}"
            id="openTopupModal"
            {{ !$paymentEnabled ? 'disabled' : '' }}
        >
            <i class="fa-solid {{ $paymentEnabled ? 'fa-plus' : 'fa-ban' }}"></i>
            {{ $paymentEnabled ? 'Top Up Wallet' : 'Top Up Disabled' }}
        </button>
    </div>

    @if (!$paymentEnabled)
        <div class="kd-wallet-payment-alert">
            <div class="kd-wallet-payment-alert-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <div>
                <strong>{{ $paymentStatus['title'] ?? 'Payment Temporarily Disabled' }}</strong>
                <p>{{ $paymentStatus['message'] ?? 'Wallet top up is currently unavailable. Please try again later.' }}</p>
            </div>
        </div>
    @endif

    <div id="walletPageMessage" class="kd-wallet-message"></div>

    <div class="kd-wallet-grid kd-wallet-grid-single">

        <div class="kd-wallet-card kd-wallet-card-full">
            <div class="kd-wallet-card-header">
                <div>
                    <span>Recent Top Ups</span>
                    <h3>Top Up History</h3>
                </div>
            </div>

            <div class="kd-wallet-table-wrap">
                <table class="kd-wallet-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Charge</th>
                            <th>Total Paid</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($topups as $topup)
                            @php
                                $topupStatus = strtolower($topup->status ?? '');
                                $canPayNow = in_array($topupStatus, ['pending', 'initialized']) && !empty($topup->access_code);
                            @endphp

                            <tr>
                                <td>
                                    <strong>{{ $topup->payment_reference }}</strong>
                                    <small>{{ $topup->topup_reference }}</small>
                                </td>

                                <td>GH₵{{ number_format((float) $topup->amount, 2) }}</td>

                                <td>GH₵{{ number_format((float) ($topup->charges ?? 0), 2) }}</td>

                                <td>GH₵{{ number_format((float) $topup->total_amount, 2) }}</td>

                                <td>
                                    <span class="kd-status-pill {{ $topupStatus }}">
                                        {{ ucfirst($topup->status) }}
                                    </span>
                                </td>

                                <td>{{ \Carbon\Carbon::parse($topup->created_at)->format('d M, Y h:i A') }}</td>

                                <td>
                                    @if($canPayNow)
                                        <button
                                            type="button"
                                            class="kd-wallet-pay-later-btn"
                                            data-topup-id="{{ $topup->topup_id }}"
                                        >
                                            <i class="fa-solid fa-credit-card"></i>
                                            Pay Now
                                        </button>
                                    @else
                                        <span class="kd-wallet-no-action">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="kd-empty-state">
                                    <i class="fa-solid fa-circle-info"></i>
                                    No wallet top up yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<div class="kd-wallet-modal-backdrop" id="topupBackdrop"></div>

<div class="kd-wallet-modal" id="topupModal">
    <div class="kd-wallet-modal-card">

        <button type="button" class="kd-wallet-modal-close" id="closeTopupModal">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="kd-wallet-modal-icon">
            <i class="fa-solid fa-wallet"></i>
        </div>

        <h2>Top Up Wallet</h2>

        @if ($paymentEnabled)
            <p>Enter the amount you want to add to your wallet. Processing charge will be added during payment.</p>
        @else
            <p>Wallet top up is currently unavailable.</p>
        @endif

        <div id="topupModalMessage" class="kd-wallet-message modal-message"></div>

        @if (!$paymentEnabled)
            <div class="kd-wallet-payment-alert modal-alert">
                <div class="kd-wallet-payment-alert-icon">
                    <i class="fa-solid fa-ban"></i>
                </div>

                <div>
                    <strong>{{ $paymentStatus['title'] ?? 'Payment Disabled' }}</strong>
                    <p>{{ $paymentStatus['message'] ?? 'Payment will be back soon. Please try again later.' }}</p>
                </div>
            </div>
        @endif

        <form id="walletTopupForm" action="{{ route('user.wallet.initialize') }}" method="POST" novalidate>
            @csrf

            <div class="kd-wallet-form-group" id="amountGroup">
                <label>Top Up Amount <span>*</span></label>

                <div class="kd-wallet-money-input {{ !$paymentEnabled ? 'is-disabled' : '' }}">
                    <span>GH₵</span>
                    <input
                        type="text"
                        name="amount"
                        id="topupAmount"
                        placeholder="Example: 50"
                        {{ !$paymentEnabled ? 'disabled readonly' : '' }}
                    >
                </div>

                <small id="amountError">Please enter a valid amount.</small>
            </div>

            <div class="kd-wallet-summary {{ !$paymentEnabled ? 'is-disabled' : '' }}">
                <div>
                    <span>Wallet Credit</span>
                    <strong id="summaryCredit">GH₵0.00</strong>
                </div>

                <div>
                    <span>Processing Charge</span>
                    <strong id="summaryCharge">GH₵0.00</strong>
                </div>

                <div class="total">
                    <span>Total Payable</span>
                    <strong id="summaryTotal">GH₵0.00</strong>
                </div>
            </div>

            <button
                type="submit"
                class="kd-wallet-pay-btn {{ !$paymentEnabled ? 'is-disabled' : '' }}"
                id="walletTopupBtn"
                {{ !$paymentEnabled ? 'disabled' : '' }}
            >
                <i class="fa-solid {{ $paymentEnabled ? 'fa-lock' : 'fa-ban' }}"></i>
                {{ $paymentEnabled ? 'Continue Payment' : 'Payment Disabled' }}
            </button>
        </form>

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
    const disabledMessage = @json($paymentStatus['message'] ?? 'Wallet top up is currently unavailable.');

    const openBtn = document.getElementById('openTopupModal');
    const closeBtn = document.getElementById('closeTopupModal');
    const modal = document.getElementById('topupModal');
    const backdrop = document.getElementById('topupBackdrop');

    const form = document.getElementById('walletTopupForm');
    const amountInput = document.getElementById('topupAmount');
    const amountGroup = document.getElementById('amountGroup');
    const payBtn = document.getElementById('walletTopupBtn');

    const pageMessage = document.getElementById('walletPageMessage');
    const modalMessage = document.getElementById('topupModalMessage');

    const summaryCredit = document.getElementById('summaryCredit');
    const summaryCharge = document.getElementById('summaryCharge');
    const summaryTotal = document.getElementById('summaryTotal');

    const chargeRate = 0.025;

    function openModal() {
        modal.classList.add('show');
        backdrop.classList.add('show');
        document.body.classList.add('kd-wallet-modal-open');

        if (paymentEnabled && amountInput) {
            amountInput.focus();
        }
    }

    function closeModal() {
        modal.classList.remove('show');
        backdrop.classList.remove('show');
        document.body.classList.remove('kd-wallet-modal-open');
        clearModalMessage();
    }

    function money(value) {
        return 'GH₵' + Number(value || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function showPageMessage(type, message) {
        pageMessage.className = 'kd-wallet-message show ' + type;
        pageMessage.innerHTML = `
            <i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>
            <span>${message}</span>
        `;
        pageMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function showModalMessage(type, message) {
        modalMessage.className = 'kd-wallet-message modal-message show ' + type;
        modalMessage.innerHTML = `
            <i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>
            <span>${message}</span>
        `;
    }

    function clearModalMessage() {
        modalMessage.className = 'kd-wallet-message modal-message';
        modalMessage.innerHTML = '';
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

    function calculateSummary() {
        const amount = parseFloat(amountInput?.value || 0);
        const safeAmount = isNaN(amount) ? 0 : amount;

        const charge = +(safeAmount * chargeRate).toFixed(2);
        const total = +(safeAmount + charge).toFixed(2);

        summaryCredit.textContent = money(safeAmount);
        summaryCharge.textContent = money(charge);
        summaryTotal.textContent = money(total);
    }

    async function verifyWalletPayment(reference, button = null, oldHtml = null) {
        const token = "{{ csrf_token() }}";

        const verifyResponse = await fetch("{{ route('user.wallet.verify') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                reference: reference
            })
        });

        const verifyResult = await verifyResponse.json();

        if (verifyResponse.ok && verifyResult.status) {
            closeModal();
            showPageMessage('success', verifyResult.message || 'Wallet top up successful.');

            setTimeout(() => {
                window.location.href = verifyResult.redirect_url || "{{ route('user.wallet.index') }}";
            }, 900);

            return;
        }

        if (button && oldHtml) {
            button.disabled = false;
            button.innerHTML = oldHtml;
        }

        showPageMessage('error', verifyResult.message || 'Wallet top up verification failed.');
        setLoading(false);
    }

    if (openBtn) {
        openBtn.addEventListener('click', function () {
            if (!paymentEnabled) {
                showPageMessage('error', disabledMessage);
            }

            openModal();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeModal);
    }

    if (amountInput) {
        amountInput.addEventListener('input', function () {
            if (!paymentEnabled) {
                this.value = '';
                calculateSummary();
                return;
            }

            this.value = this.value.replace(/[^\d.]/g, '');

            const parts = this.value.split('.');

            if (parts.length > 2) {
                this.value = parts[0] + '.' + parts.slice(1).join('');
            }

            if (parts[1] && parts[1].length > 2) {
                this.value = parts[0] + '.' + parts[1].slice(0, 2);
            }

            amountGroup.classList.remove('has-error');
            clearModalMessage();
            calculateSummary();
        });
    }

    if (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!paymentEnabled) {
                showModalMessage('error', disabledMessage);
                setLoading(false);
                return;
            }

            clearModalMessage();
            amountGroup.classList.remove('has-error');

            const amount = parseFloat(amountInput.value || 0);

            if (!amount || amount < 1) {
                amountGroup.classList.add('has-error');
                showModalMessage('error', 'Please enter an amount of at least GH₵1.00.');
                return;
            }

            if (typeof PaystackPop === 'undefined') {
                showModalMessage('error', 'Payment gateway could not load. Please refresh the page and try again.');
                return;
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
                    showModalMessage('error', result.message || 'Unable to initialize wallet top up.');
                    setLoading(false);
                    return;
                }

                const popup = new PaystackPop();

                popup.resumeTransaction(result.access_code, {
                    onSuccess: async function (transaction) {
                        setLoading(true, 'Verifying...');
                        await verifyWalletPayment(transaction.reference || result.payment_reference);
                    },

                    onCancel: function () {
                        showModalMessage('error', 'Payment was cancelled. You can try again or pay later from your top up history.');
                        setLoading(false);
                    },

                    onError: function () {
                        showModalMessage('error', 'Payment failed. Please try again.');
                        setLoading(false);
                    }
                });

            } catch (error) {
                showModalMessage('error', 'Payment could not be started. Please check your connection and try again.');
                setLoading(false);
            }
        });
    }

    document.querySelectorAll('.kd-wallet-pay-later-btn').forEach(function (button) {
        button.addEventListener('click', async function () {
            if (!paymentEnabled) {
                showPageMessage('error', disabledMessage);
                return;
            }

            if (typeof PaystackPop === 'undefined') {
                showPageMessage('error', 'Payment gateway could not load. Please refresh the page and try again.');
                return;
            }

            const topupId = button.dataset.topupId;
            const oldHtml = button.innerHTML;

            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';

            try {
                const response = await fetch("{{ route('user.wallet.resume') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        topup_id: topupId
                    })
                });

                const result = await response.json();

                if (!response.ok || !result.status) {
                    showPageMessage('error', result.message || 'Unable to continue payment.');
                    button.disabled = false;
                    button.innerHTML = oldHtml;
                    return;
                }

                const popup = new PaystackPop();

                popup.resumeTransaction(result.access_code, {
                    onSuccess: async function (transaction) {
                        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying...';
                        await verifyWalletPayment(transaction.reference || result.payment_reference, button, oldHtml);
                    },

                    onCancel: function () {
                        showPageMessage('error', 'Payment was cancelled. You can pay later.');
                        button.disabled = false;
                        button.innerHTML = oldHtml;
                    },

                    onError: function () {
                        showPageMessage('error', 'Payment failed. Please try again.');
                        button.disabled = false;
                        button.innerHTML = oldHtml;
                    }
                });

            } catch (error) {
                showPageMessage('error', 'Could not continue payment. Please try again.');
                button.disabled = false;
                button.innerHTML = oldHtml;
            }
        });
    });

    if (!paymentEnabled) {
        if (amountInput) {
            amountInput.disabled = true;
            amountInput.readOnly = true;
        }

        if (payBtn) {
            payBtn.disabled = true;
            payBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Payment Disabled';
        }
    }

    calculateSummary();
});
</script>
@endpush