@extends('admin.layouts.app')

@section('title', 'Verify Payments')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="{{ asset('admin/css/verify-payments.css') }}">
@endpush

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $payments = collect($payments ?? []);
    $references = $references ?? request('payment_references', '');

    $pendingPayments = $payments
        ->filter(fn ($payment) => strtolower($payment->status ?? '') !== 'successful')
        ->values();

    $bulkPaystackRoute = Route::has('admin.finance.verify-payments.paystack.bulk')
        ? route('admin.finance.verify-payments.paystack.bulk')
        : '#';

    $bulkManualRoute = Route::has('admin.finance.verify-payments.manual.bulk')
        ? route('admin.finance.verify-payments.manual.bulk')
        : '#';
@endphp

<div class="kd-verify-page">
    <div class="kd-verify-card">

        <div class="kd-verify-header">
            <div>
                <span class="kd-page-badge">
                    <i class="fa-solid fa-shield-check"></i>
                    Verify Payments
                </span>

                <h4>Payment Reference Verification</h4>
                <p>Paste one or multiple references. Each reference can be separated by comma, space, or new line.</p>
            </div>

            <a href="{{ route('admin.finance.topups.index') }}" class="kd-topup-link">
                <i class="fa-solid fa-wallet"></i>
                Top-ups
            </a>
        </div>

        @if(session('success'))
            <div class="kd-alert kd-alert-success">
                <i class="fa-solid fa-circle-check"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="kd-alert kd-alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="kd-alert kd-alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <div class="kd-reference-box">
            <form method="GET" action="{{ route('admin.finance.verify-payments') }}" id="searchPaymentForm">
                <div>
                    <label for="payment_references">Payment / Paystack / Order References</label>
                    <textarea
                        name="payment_references"
                        id="payment_references"
                        rows="5"
                        placeholder="Paste multiple references here. Example: PAY123&#10;ORD123&#10;TOP123"
                        autocomplete="off"
                        required
                    >{{ $references }}</textarea>
                </div>

                <button type="submit" id="searchPaymentBtn">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Search Payments
                </button>
            </form>
        </div>

        @if($payments->count() < 1)
            <div class="kd-empty-search">
                <div>
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <h4>No Payment Selected</h4>
                <p>Paste one or multiple references above and search. Results will show in table format.</p>
            </div>
        @else
            <div class="kd-table-card">
                <div class="kd-table-header">
                    <div>
                        <h4>Verification Results</h4>
                        <p>
                            {{ number_format($payments->count()) }} payment record(s) found.
                            {{ number_format($pendingPayments->count()) }} pending verification.
                        </p>
                    </div>

                    @if($pendingPayments->count() > 0)
                        <div class="kd-bulk-actions">
                            <form method="POST" action="{{ $bulkPaystackRoute }}" class="kd-bulk-form">
                                @csrf

                                <input type="hidden" name="return_references" value="{{ $references }}">

                                @foreach($pendingPayments as $payment)
                                    <input type="hidden" name="payment_references[]" value="{{ $payment->payment_reference }}">
                                @endforeach

                                <button type="submit" class="kd-bulk-btn kd-bulk-paystack" {{ $bulkPaystackRoute === '#' ? 'disabled' : '' }}>
                                    <i class="fa-solid fa-shield-check"></i>
                                    Verify All With Paystack
                                </button>
                            </form>

                            <button type="button" class="kd-bulk-btn kd-bulk-manual" id="openBulkManualModalBtn">
                                <i class="fa-solid fa-user-check"></i>
                                Verify All Manually
                            </button>
                        </div>
                    @endif
                </div>

                <div class="kd-table-wrap">
                    <table id="verifyPaymentsTable" class="display nowrap kd-verify-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Scope</th>
                                <th>Payment Ref</th>
                                <th>Order / Top-up / AFA Ref</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Order Details</th>
                                <th>Status</th>
                                <th>Paid At</th>
                                <th>Verified At</th>
                                <th class="no-export">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($payments as $payment)
                                @php
                                    $scopeLabel = match($payment->payment_scope ?? null) {
                                        'wallet_topup' => 'Wallet Top-up',
                                        'afa_order' => 'AFA Order',
                                        'data_order' => 'Data Order',
                                        default => 'N/A',
                                    };

                                    $status = strtolower($payment->status ?? 'pending');

                                    $paidAt = !empty($payment->paid_at)
                                        ? \Carbon\Carbon::parse($payment->paid_at)->format('d M Y, h:i A')
                                        : 'N/A';

                                    $verifiedAt = !empty($payment->verified_at)
                                        ? \Carbon\Carbon::parse($payment->verified_at)->format('d M Y, h:i A')
                                        : 'N/A';

                                    $orderDetails = 'N/A';

                                    if (($payment->payment_scope ?? '') === 'data_order') {
                                        $orderDetails = trim(($payment->network_name ?? 'N/A') . ' - ' . ($payment->plan_name ?? 'N/A') . ' - ' . ($payment->data_size ?? 'N/A'));
                                    }

                                    if (($payment->payment_scope ?? '') === 'afa_order') {
                                        $orderDetails = trim(($payment->registration_name ?? 'N/A') . ' - ' . ($payment->location ?? 'N/A'));
                                    }

                                    if (($payment->payment_scope ?? '') === 'wallet_topup') {
                                        $orderDetails = 'Wallet top-up credit';
                                    }
                                @endphp

                                <tr>
                                    <td>{{ $loop->iteration }}</td>

                                    <td>
                                        <span class="kd-scope-pill {{ str_replace('_', '-', $payment->payment_scope ?? '') }}">
                                            {{ $scopeLabel }}
                                        </span>
                                    </td>

                                    <td>
                                        <strong>{{ $payment->payment_reference ?? 'N/A' }}</strong>
                                    </td>

                                    <td>
                                        <strong>{{ $payment->order_reference ?? 'N/A' }}</strong>
                                        <small>Order Status: {{ strtoupper($payment->order_status ?? 'N/A') }}</small>
                                    </td>

                                    <td>
                                        <strong>{{ $payment->currency ?? 'GHS' }} {{ number_format((float) ($payment->amount ?? 0), 2) }}</strong>
                                    </td>

                                    <td>
                                        <strong>{{ strtoupper($payment->provider ?? 'PAYSTACK') }}</strong>
                                        <small>{{ strtoupper($payment->channel ?? $payment->payment_method ?? 'N/A') }}</small>
                                    </td>

                                    <td>{{ $orderDetails }}</td>

                                    <td>
                                        <span class="kd-status-pill {{ $status }}">
                                            {{ strtoupper($payment->status ?? 'PENDING') }}
                                        </span>
                                    </td>

                                    <td>{{ $paidAt }}</td>
                                    <td>{{ $verifiedAt }}</td>

                                    <td>
                                        @if(($payment->status ?? '') !== 'successful')
                                            <div class="kd-table-actions">
                                                <form method="POST" action="{{ route('admin.finance.verify-payments.paystack') }}" class="kd-action-form">
                                                    @csrf
                                                    <input type="hidden" name="payment_reference" value="{{ $payment->payment_reference }}">
                                                    <input type="hidden" name="payment_scope" value="{{ $payment->payment_scope }}">
                                                    <input type="hidden" name="return_references" value="{{ $references }}">

                                                    <button type="submit" class="kd-mini-btn kd-paystack-mini">
                                                        <i class="fa-solid fa-shield-check"></i>
                                                        Paystack
                                                    </button>
                                                </form>

                                                <button
                                                    type="button"
                                                    class="kd-mini-btn kd-manual-mini openManualModalBtn"
                                                    data-payment-id="{{ $payment->payment_id }}"
                                                    data-payment-scope="{{ $payment->payment_scope }}"
                                                    data-reference="{{ $payment->payment_reference }}"
                                                >
                                                    <i class="fa-solid fa-user-check"></i>
                                                    Manual
                                                </button>
                                            </div>
                                        @else
                                            <span class="kd-done-text">
                                                <i class="fa-solid fa-circle-check"></i>
                                                Verified
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</div>

<div class="kd-manual-backdrop" id="manualBackdrop"></div>

<div class="kd-manual-modal" id="manualModal">
    <div class="kd-manual-header">
        <div>
            <span>
                <i class="fa-solid fa-user-check"></i>
                Manual Verification
            </span>
            <h4 id="manualModalTitle">Verify Payment Manually</h4>
            <p id="manualModalSubtitle">Use only after confirming the transaction from Paystack dashboard or bank record.</p>
        </div>

        <button type="button" id="closeManualModalBtn">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <form method="POST" action="{{ route('admin.finance.verify-payments.manual') }}" id="manualVerifyForm">
        @csrf

        <div id="manualHiddenFields">
            <input type="hidden" name="payment_id" id="manual_payment_id">
            <input type="hidden" name="payment_scope" id="manual_payment_scope">
            <input type="hidden" name="return_references" value="{{ $references }}">
        </div>

        <div class="kd-manual-info">
            <span>Payment Reference</span>
            <strong id="manual_reference">N/A</strong>
            <small id="manual_count_text">Manual verification will mark this payment as successful.</small>
        </div>

        <div class="kd-form-grid">
            <div class="kd-form-group kd-full">
                <label for="manual_note">Verification Note</label>
                <textarea
                    name="note"
                    id="manual_note"
                    rows="4"
                    maxlength="500"
                    placeholder="Example: Confirmed manually from Paystack dashboard"
                ></textarea>
            </div>
        </div>

        <div class="kd-warning-box">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Manual verification will mark payment as successful. Orders will move to processing. Wallet top-ups will credit the user wallet.
        </div>

        <div class="kd-modal-actions">
            <button type="button" class="kd-cancel-btn" id="cancelManualModalBtn">
                Cancel
            </button>

            <button type="submit" class="kd-submit-btn" id="submitManualBtn">
                <i class="fa-solid fa-check"></i>
                Confirm Manual Verification
            </button>
        </div>
    </form>
</div>

@if($pendingPayments->count() > 0)
    <template id="bulkManualFieldsTemplate">
        <input type="hidden" name="return_references" value="{{ $references }}">

        @foreach($pendingPayments as $index => $payment)
            <input type="hidden" name="payments[{{ $index }}][payment_id]" value="{{ $payment->payment_id }}">
            <input type="hidden" name="payments[{{ $index }}][payment_scope]" value="{{ $payment->payment_scope }}">
        @endforeach
    </template>
@endif
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchForm = document.getElementById('searchPaymentForm');
    const searchBtn = document.getElementById('searchPaymentBtn');

    searchForm?.addEventListener('submit', function () {
        searchBtn.disabled = true;
        searchBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Searching...`;
    });

    if (window.jQuery && $('#verifyPaymentsTable').length) {
        $('#verifyPaymentsTable').DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            scrollX: true,
            ordering: true,
            order: [[0, 'asc']],
            dom: '<"kd-dt-top"lfB>rt<"kd-dt-bottom"ip>',
            buttons: [
                {
                    extend: 'copyHtml5',
                    text: '<i class="fa-solid fa-copy"></i> Copy',
                    className: 'kd-dt-btn',
                    exportOptions: {
                        columns: ':not(.no-export)'
                    }
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fa-solid fa-file-excel"></i> Export Excel',
                    className: 'kd-dt-btn',
                    title: 'Verify Payments',
                    exportOptions: {
                        columns: ':not(.no-export)'
                    }
                }
            ],
            language: {
                search: '',
                searchPlaceholder: 'Search payment records...',
                lengthMenu: 'Show _MENU_ records',
                emptyTable: 'No payment records found'
            }
        });
    }

    document.querySelectorAll('.kd-action-form, .kd-bulk-form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const button = form.querySelector('button[type="submit"]');

            if (button) {
                button.disabled = true;
                button.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Verifying...`;
            }
        });
    });

    const manualModal = document.getElementById('manualModal');
    const manualBackdrop = document.getElementById('manualBackdrop');
    const closeManualModalBtn = document.getElementById('closeManualModalBtn');
    const cancelManualModalBtn = document.getElementById('cancelManualModalBtn');
    const manualForm = document.getElementById('manualVerifyForm');
    const submitManualBtn = document.getElementById('submitManualBtn');
    const openBulkManualModalBtn = document.getElementById('openBulkManualModalBtn');

    const manualModalTitle = document.getElementById('manualModalTitle');
    const manualModalSubtitle = document.getElementById('manualModalSubtitle');
    const manualHiddenFields = document.getElementById('manualHiddenFields');
    const manualReference = document.getElementById('manual_reference');
    const manualCountText = document.getElementById('manual_count_text');

    const singleManualRoute = "{{ route('admin.finance.verify-payments.manual') }}";
    const bulkManualRoute = "{{ $bulkManualRoute }}";
    const returnReferences = @json($references);
    const pendingCount = {{ $pendingPayments->count() }};

    function resetSingleHiddenFields() {
        manualHiddenFields.innerHTML = `
            <input type="hidden" name="payment_id" id="manual_payment_id">
            <input type="hidden" name="payment_scope" id="manual_payment_scope">
            <input type="hidden" name="return_references" value="${escapeHtml(returnReferences)}">
        `;
    }

    function openManualModal(button) {
        resetSingleHiddenFields();

        manualForm.action = singleManualRoute;
        manualModalTitle.textContent = 'Verify Payment Manually';
        manualModalSubtitle.textContent = 'Use only after confirming the transaction from Paystack dashboard or bank record.';
        manualReference.textContent = button.dataset.reference || 'N/A';
        manualCountText.textContent = 'Manual verification will mark this payment as successful.';

        document.getElementById('manual_payment_id').value = button.dataset.paymentId || '';
        document.getElementById('manual_payment_scope').value = button.dataset.paymentScope || '';

        manualBackdrop?.classList.add('active');
        manualModal?.classList.add('active');
        document.body.classList.add('kd-modal-open');
    }

    function openBulkManualModal() {
        const template = document.getElementById('bulkManualFieldsTemplate');

        manualForm.action = bulkManualRoute;
        manualHiddenFields.innerHTML = template ? template.innerHTML : '';

        manualModalTitle.textContent = 'Verify All Payments Manually';
        manualModalSubtitle.textContent = 'This will manually verify all pending records currently listed.';
        manualReference.textContent = `${pendingCount} pending payment(s) selected`;
        manualCountText.textContent = 'Manual bulk verification will mark all selected pending payments as successful.';

        manualBackdrop?.classList.add('active');
        manualModal?.classList.add('active');
        document.body.classList.add('kd-modal-open');
    }

    function closeManualModal() {
        manualBackdrop?.classList.remove('active');
        manualModal?.classList.remove('active');
        document.body.classList.remove('kd-modal-open');
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;');
    }

    document.querySelectorAll('.openManualModalBtn').forEach(function (button) {
        button.addEventListener('click', function () {
            openManualModal(button);
        });
    });

    openBulkManualModalBtn?.addEventListener('click', openBulkManualModal);
    closeManualModalBtn?.addEventListener('click', closeManualModal);
    cancelManualModalBtn?.addEventListener('click', closeManualModal);
    manualBackdrop?.addEventListener('click', closeManualModal);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeManualModal();
    });

    manualForm?.addEventListener('submit', function () {
        submitManualBtn.disabled = true;
        submitManualBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Saving...`;
    });
});
</script>
@endpush