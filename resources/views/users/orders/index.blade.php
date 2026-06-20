@extends('users.layouts.app')

@section('title', 'Order History')
@section('page-title', 'Order History')

@push('styles')
<link rel="stylesheet" href="{{ asset('users/css/order-history.css') }}?v={{ time() }}">
@endpush

@section('content')

<div class="kd-orders-page">

    <div class="kd-orders-hero">
        <div>
            <span class="kd-orders-badge">
                <i class="fa-solid fa-box-archive"></i>
                Order History
            </span>

            <h1>All Orders</h1>
            <p>View and track all your data bundle purchases in one place.</p>
        </div>
    </div>

    <div class="kd-orders-filter-card">
        <form method="GET"
              action="{{ route('user.orders.index') }}"
              class="kd-orders-filter-form"
              id="ordersFilterForm">

            <div class="kd-orders-search">
                <i class="fa-solid fa-magnifying-glass"></i>

                <input type="text"
                       name="search"
                       id="ordersSearchInput"
                       value="{{ $search }}"
                       placeholder="Search reference, network, phone, data size or email"
                       autocomplete="off">

                @if($search)
                    <a href="{{ route('user.orders.index', array_filter(['status' => $status])) }}"
                       class="kd-orders-search-clear"
                       title="Clear search">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </div>

            <input type="hidden" name="status" id="ordersStatusInput" value="{{ $status }}">

            <div class="kd-status-select" id="ordersStatusSelect">
                <button type="button" class="kd-status-select-btn">
                    <span id="ordersStatusText">
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
                            @case('delivered')
                                Delivered
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

                    <button type="button" data-value="delivered" class="{{ $status === 'delivered' ? 'selected' : '' }}">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Delivered</span>
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
                <a href="{{ route('user.orders.index') }}" class="kd-orders-clear">
                    <i class="fa-solid fa-xmark"></i>
                    Clear
                </a>
            @endif

        </form>
    </div>

    <div class="kd-orders-card">

        @if($orders->count())

            <div class="kd-orders-list">
                @foreach($orders as $order)

                    @php
                        $orderStatus = strtolower($order->status ?? 'pending');

                        $statusClass = match($orderStatus) {
                            'delivered' => 'success',
                            'processing' => 'processing',
                            'in_progress' => 'progress',
                            'failed', 'cancelled' => 'danger',
                            default => 'pending',
                        };

                        $statusLabel = match($orderStatus) {
                            'in_progress' => 'In Progress',
                            default => ucfirst($orderStatus),
                        };
                    @endphp

                    <div class="kd-order-row">

                        <div class="kd-order-main">
                            <div class="kd-order-icon">
                                <i class="fa-solid fa-wifi"></i>
                            </div>

                            <div>
                                <span>#{{ strtoupper($order->order_reference) }}</span>
                                <h3>{{ strtoupper($order->network_name) }} - {{ strtoupper($order->data_size) }}</h3>
                                <p>{{ strtoupper($order->plan_name) }} sent to {{ $order->beneficiary_number }}</p>
                            </div>
                        </div>

                        <div class="kd-order-details">
                            <div>
                                <span>Amount</span>
                                <strong>GH₵{{ number_format((float) $order->amount, 2) }}</strong>
                            </div>

                            <div>
                                <span>Payment Ref</span>
                                <strong>{{ $order->payment_reference ?? 'N/A' }}</strong>
                            </div>

                            <div>
                                <span>Date</span>
                                <strong>{{ \Carbon\Carbon::parse($order->created_at)->format('d M, Y h:i A') }}</strong>
                            </div>

                            <div>
                                <span>Status</span>
                                <strong class="kd-status-pill {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </strong>
                            </div>
                        </div>

                        <div class="kd-order-actions">
                            <a href="{{ route('user.orders.track', ['search' => $order->order_reference]) }}">
                                <i class="fa-solid fa-location-crosshairs"></i>
                                Track
                            </a>
                        </div>

                    </div>

                @endforeach
            </div>

            <div class="kd-orders-pagination">
                {{ $orders->links() }}
            </div>

        @else

            <div class="kd-orders-empty">
                <i class="fa-solid fa-box-open"></i>
                <h3>No orders found</h3>
                <p>Your orders will appear here after purchase.</p>
            </div>

        @endif

    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('ordersFilterForm');
    const searchInput = document.getElementById('ordersSearchInput');
    const statusInput = document.getElementById('ordersStatusInput');
    const statusSelect = document.getElementById('ordersStatusSelect');
    const statusText = document.getElementById('ordersStatusText');
    const statusBtn = statusSelect.querySelector('.kd-status-select-btn');
    const statusOptions = statusSelect.querySelectorAll('.kd-status-select-panel button');

    let searchTimer = null;

    function submitFilters() {
        form.submit();
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);

        searchTimer = setTimeout(function () {
            submitFilters();
        }, 700);
    });

    statusBtn.addEventListener('click', function () {
        statusSelect.classList.toggle('open');
    });

    statusOptions.forEach(option => {
        option.addEventListener('click', function () {
            const value = this.dataset.value;
            const text = this.querySelector('span').textContent.trim();

            statusInput.value = value;
            statusText.textContent = text;

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