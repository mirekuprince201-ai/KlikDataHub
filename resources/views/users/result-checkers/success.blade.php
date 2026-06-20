@extends('users.layouts.app')

@section('title', 'Result Checker Order Successful')
@section('page-title', 'Order Successful')

@push('styles')
<link rel="stylesheet" href="{{ asset('users/css/result-checkers.css') }}?v={{ time() }}">
@endpush

@section('content')

<div class="kd-result-success-page">

    <div class="kd-success-card">

        <div class="kd-success-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>

        <span class="kd-success-label">Payment Successful</span>

        <h1>Your Result Checker PIN is Ready</h1>

        <p>
            Your voucher details have been delivered by email and SMS.
            You can also copy them below.
        </p>

        <div class="kd-success-summary">
            <div>
                <span>Order Number</span>
                <strong>{{ $order->order_number }}</strong>
            </div>

            <div>
                <span>Checker</span>
                <strong>{{ $order->checker_name }}</strong>
            </div>

            <div>
                <span>Quantity</span>
                <strong>{{ $order->quantity }}</strong>
            </div>

            <div>
                <span>Total Paid</span>
                <strong>{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</strong>
            </div>
        </div>

        <div class="kd-voucher-list">
            @forelse($items as $index => $item)
                <div class="kd-voucher-card">
                    <div class="kd-voucher-head">
                        <span>Voucher {{ $index + 1 }}</span>
                        <i class="fa-solid fa-ticket"></i>
                    </div>

                    <div class="kd-voucher-row">
                        <span>Serial Number</span>
                        <strong>{{ $item->serial_number }}</strong>
                    </div>

                    <div class="kd-voucher-row">
                        <span>PIN</span>
                        <strong>{{ $item->voucher_pin }}</strong>
                    </div>
                </div>
            @empty
                <div class="kd-empty-result">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <h3>No Voucher Found</h3>
                    <p>Please contact support with your order number.</p>
                </div>
            @endforelse
        </div>

        <div class="kd-success-actions">
            <a href="https://ghana.waecdirect.org/" target="_blank" rel="noopener" class="kd-waec-link">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                Open WAECDirect
            </a>

            <a href="{{ route('user.dashboard') }}" class="kd-result-back">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

    </div>

</div>

@endsection