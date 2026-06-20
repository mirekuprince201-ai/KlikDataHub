@extends('users.layouts.app')

@section('title', 'AFA History')
@section('page-title', 'AFA History')

@push('styles')
<link rel="stylesheet" href="{{ asset('users/css/afa-history.css') }}?v={{ time() }}">
@endpush

@section('content')

<div class="kd-afa-history-page">

    <div class="kd-afa-history-hero">
        <div>
            <span class="kd-afa-history-badge">
                <i class="fa-solid fa-id-card-clip"></i>
                AFA History
            </span>

            <h1>AFA Registrations</h1>
            <p>View all your AFA registration requests and payment status.</p>
        </div>
    </div>

    <div class="kd-afa-filter-card">
        <form method="GET"
              action="{{ route('user.afa.history') }}"
              class="kd-afa-filter-form"
              id="afaFilterForm">

            <div class="kd-afa-search">
                <i class="fa-solid fa-magnifying-glass"></i>

                <input type="text"
                       name="search"
                       id="afaSearchInput"
                       value="{{ $search }}"
                       placeholder="Search reference, name, phone, Ghana Card or email"
                       autocomplete="off">

                @if($search)
                    <a href="{{ route('user.afa.history', array_filter(['status' => $status])) }}"
                       class="kd-afa-search-clear">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </div>

            <input type="hidden" name="status" id="afaStatusInput" value="{{ $status }}">

            <div class="kd-status-select" id="afaStatusSelect">
                <button type="button" class="kd-status-select-btn">
                    <span id="afaStatusText">
                        @switch($status)
                            @case('pending')
                                Pending
                                @break
                            @case('processing')
                                Processing
                                @break
                            @case('in_progress')
                                In Progress
                                @break
                            @case('completed')
                                Completed
                                @break
                            @case('cancelled')
                                Cancelled
                                @break
                            @case('failed')
                                Failed
                                @break
                            @default
                                All Status
                        @endswitch
                    </span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>

                <div class="kd-status-select-panel">
                    <button type="button" data-value="" class="{{ $status === '' ? 'selected' : '' }}">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>All Status</span>
                    </button>

                    <button type="button" data-value="pending" class="{{ $status === 'pending' ? 'selected' : '' }}">
                        <i class="fa-solid fa-clock"></i>
                        <span>Pending</span>
                    </button>

                    <button type="button" data-value="processing" class="{{ $status === 'processing' ? 'selected' : '' }}">
                        <i class="fa-solid fa-rotate"></i>
                        <span>Processing</span>
                    </button>

                    <button type="button" data-value="in_progress" class="{{ $status === 'in_progress' ? 'selected' : '' }}">
                        <i class="fa-solid fa-spinner"></i>
                        <span>In Progress</span>
                    </button>

                    <button type="button" data-value="completed" class="{{ $status === 'completed' ? 'selected' : '' }}">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Completed</span>
                    </button>

                    <button type="button" data-value="cancelled" class="{{ $status === 'cancelled' ? 'selected' : '' }}">
                        <i class="fa-solid fa-ban"></i>
                        <span>Cancelled</span>
                    </button>

                    <button type="button" data-value="failed" class="{{ $status === 'failed' ? 'selected' : '' }}">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Failed</span>
                    </button>
                </div>
            </div>

            @if($search || $status)
                <a href="{{ route('user.afa.history') }}" class="kd-afa-clear">
                    <i class="fa-solid fa-xmark"></i>
                    Clear
                </a>
            @endif

        </form>
    </div>

    <div class="kd-afa-history-card">

        @if($afaOrders->count())

            <div class="kd-afa-list">
                @foreach($afaOrders as $afaOrder)

                    @php
                        $orderStatus = strtolower($afaOrder->status ?? 'pending');

                        $statusClass = match($orderStatus) {
                            'completed' => 'success',
                            'processing' => 'processing',
                            'in_progress' => 'progress',
                            'failed', 'cancelled' => 'danger',
                            default => 'pending',
                        };

                        $statusLabel = match($orderStatus) {
                            'in_progress' => 'In Progress',
                            default => ucfirst($orderStatus),
                        };

                        $paymentStatus = strtolower($afaOrder->payment_status ?? 'pending');

                        $paymentClass = match($paymentStatus) {
                            'successful' => 'success',
                            'initialized' => 'processing',
                            'failed', 'cancelled' => 'danger',
                            default => 'pending',
                        };

                        $paymentLabel = match($paymentStatus) {
                            'successful' => 'Successful',
                            'initialized' => 'Initialized',
                            default => ucfirst($paymentStatus),
                        };

                        $displayAmount = (float) ($afaOrder->actual_amount ?? $afaOrder->order_amount ?? 0);
                    @endphp

                    <div class="kd-afa-row">

                        <div class="kd-afa-main">
                            <div class="kd-afa-icon">
                                <i class="fa-solid fa-id-card"></i>
                            </div>

                            <div>
                                <span>#{{ strtoupper($afaOrder->order_reference) }}</span>
                                <h3>{{ strtoupper($afaOrder->registration_name) }}</h3>
                                <p>{{ strtoupper($afaOrder->customer_name) }} • {{ $afaOrder->phone_number }}</p>
                            </div>
                        </div>

                        <div class="kd-afa-details">
                            <div>
                                <span>Amount</span>
                                <strong>GH₵{{ number_format($displayAmount, 2) }}</strong>
                            </div>

                            <div>
                                <span>Payment Ref</span>
                                <strong>{{ $afaOrder->payment_reference ?? 'N/A' }}</strong>
                            </div>

                            <div>
                                <span>Payment</span>
                                <strong class="kd-status-pill {{ $paymentClass }}">
                                    {{ $paymentLabel }}
                                </strong>
                            </div>

                            <div>
                                <span>Status</span>
                                <strong class="kd-status-pill {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </strong>
                            </div>

                            <div>
                                <span>Ghana Card</span>
                                <strong>{{ $afaOrder->ghana_card_number ?? 'N/A' }}</strong>
                            </div>

                            <div>
                                <span>Date</span>
                                <strong>{{ \Carbon\Carbon::parse($afaOrder->created_at)->format('d M, Y h:i A') }}</strong>
                            </div>
                        </div>

                    </div>

                @endforeach
            </div>

            <div class="kd-afa-pagination">
                {{ $afaOrders->links() }}
            </div>

        @else

            <div class="kd-afa-empty">
                <i class="fa-solid fa-id-card-clip"></i>
                <h3>No AFA registration found</h3>
                <p>Your AFA registration history will appear here after registration.</p>
            </div>

        @endif

    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('afaFilterForm');
    const searchInput = document.getElementById('afaSearchInput');
    const statusInput = document.getElementById('afaStatusInput');
    const statusSelect = document.getElementById('afaStatusSelect');
    const statusText = document.getElementById('afaStatusText');
    const statusBtn = statusSelect.querySelector('.kd-status-select-btn');
    const statusOptions = statusSelect.querySelectorAll('.kd-status-select-panel button');

    let searchTimer = null;

    function submitFilters() {
        form.submit();
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(submitFilters, 700);
    });

    statusBtn.addEventListener('click', function () {
        statusSelect.classList.toggle('open');
    });

    statusOptions.forEach(option => {
        option.addEventListener('click', function () {
            statusInput.value = this.dataset.value;
            statusText.textContent = this.querySelector('span').textContent.trim();

            statusOptions.forEach(btn => btn.classList.remove('selected'));
            this.classList.add('selected');

            statusSelect.classList.remove('open');
            submitFilters();
        });
    });

    document.addEventListener('click', function (event) {
        if (!statusSelect.contains(event.target)) {
            statusSelect.classList.remove('open');
        }
    });
});
</script>
@endpush