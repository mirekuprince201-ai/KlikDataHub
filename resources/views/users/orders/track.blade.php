@extends('users.layouts.app')

@section('title', 'Track Order')
@section('page-title', 'Track Order')

@push('styles')
<link rel="stylesheet" href="{{ asset('users/css/order-track.css') }}?v={{ time() }}">
@endpush

@section('content')

<div class="kd-track-page">

    <div class="kd-track-hero">
        <div>
            <span class="kd-track-badge">
                <i class="fa-solid fa-truck-fast"></i>
                Order Tracking
            </span>

            <h1>Track Your Order</h1>

            <p>
                Search with your order reference, payment reference, phone number, or email address.
            </p>
        </div>
    </div>

    <div class="kd-track-search-card">
        <form method="GET"
              action="{{ route('user.orders.track') }}"
              class="kd-track-form">

            <div class="kd-track-field">
                <label>Search Order</label>

                <div class="kd-track-search-input">
                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input type="text"
                           name="search"
                           id="trackSearchInput"
                           value="{{ $search ?? '' }}"
                           placeholder="Order ref, payment ref, phone number or email"
                           autocomplete="off">

                    @if(!empty($search))
                        <a href="{{ route('user.orders.track') }}"
                           class="kd-track-clear"
                           title="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </div>
            </div>

            <button type="submit">
                <i class="fa-solid fa-location-crosshairs"></i>
                <span>Track Order</span>
            </button>
        </form>
    </div>

    @if($searched && $orders->isEmpty())

        <div class="kd-track-empty">
            <div class="kd-track-empty-icon danger">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>

            <h3>No order found</h3>

            <p>
                We could not find any order matching
                <strong>{{ $search }}</strong>.
                Please check your details and try again.
            </p>
        </div>

    @elseif($searched && $orders->count())

        <div class="kd-track-results">
            @foreach($orders as $order)

                <div class="kd-order-card">

                    <div class="kd-order-card-top">
                        <div>
                            <span class="kd-order-ref">
                                #{{ strtoupper($order->order_reference) }}
                            </span>

                            <h2>
                                {{ strtoupper($order->network_name) }}
                                -
                                {{ strtoupper($order->data_size) }}
                            </h2>

                            <p>
                                {{ strtoupper($order->plan_name) }}
                                sent to
                                <strong>{{ $order->beneficiary_number }}</strong>
                            </p>
                        </div>

                        <span class="kd-order-status {{ $order->status_class }}">
                            {{ $order->status_label }}
                        </span>
                    </div>

                    <div class="kd-order-message {{ $order->status_class }}">
                        <div class="kd-order-message-icon">
                            @if($order->status_class === 'success')
                                <i class="fa-solid fa-circle-check"></i>
                            @elseif($order->status_class === 'danger')
                                <i class="fa-solid fa-circle-xmark"></i>
                            @elseif($order->status_class === 'progress')
                                <i class="fa-solid fa-spinner fa-spin"></i>
                            @else
                                <i class="fa-solid fa-circle-info"></i>
                            @endif
                        </div>

                        <div>
                            <h4>{{ $order->main_message }}</h4>
                            <p>{{ $order->status_description }}</p>
                        </div>
                    </div>

                    <div class="kd-order-info-grid">
                        <div>
                            <span>Order Amount</span>
                            <strong>GH₵{{ number_format((float) $order->order_amount, 2) }}</strong>
                        </div>

                        <div>
                            <span>Payment Reference</span>
                            <strong>{{ $order->payment_reference ?? 'N/A' }}</strong>
                        </div>

                        <div>
                            <span>Payment Status</span>
                            <strong>
                                <span class="kd-mini-status {{ $order->payment_status_class }}">
                                    {{ $order->payment_status_label }}
                                </span>
                            </strong>
                        </div>

                        <div>
                            <span>Payment Method</span>
                            <strong>{{ strtoupper(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}</strong>
                        </div>

                        <div>
                            <span>Channel</span>
                            <strong>{{ strtoupper(str_replace('_', ' ', $order->channel ?? 'N/A')) }}</strong>
                        </div>

                        <div>
                            <span>Customer Email</span>
                            <strong>{{ $order->customer_email }}</strong>
                        </div>
                    </div>

                    <div class="kd-order-timeline">
                        @foreach($order->timeline as $item)
                            <div class="kd-timeline-item {{ $item['done'] ? 'done' : '' }} {{ !empty($item['active']) ? 'active' : '' }}">
                                <div class="kd-timeline-dot">
                                    <i class="fa-solid {{ $item['done'] ? 'fa-check' : 'fa-clock' }}"></i>
                                </div>

                                <div class="kd-timeline-content">
                                    <h4>{{ $item['title'] }}</h4>
                                    <p>{{ $item['description'] }}</p>

                                    @if(!empty($item['time']))
                                        <small>
                                            {{ \Carbon\Carbon::parse($item['time'])->format('d M, Y h:i A') }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if(!empty($order->admin_notes))
                        <div class="kd-admin-note">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>{{ $order->admin_notes }}</span>
                        </div>
                    @endif

                </div>

            @endforeach
        </div>

    @endif

</div>

@endsection