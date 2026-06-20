@extends('admin.layouts.app')

@section('title', 'Admin Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/dashboard.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $dateFilter = $dateFilter ?? '30';
    $status = $status ?? 'all';
    $fromDate = $fromDate ?? null;
    $toDate = $toDate ?? null;
@endphp

<div class="kd-dashboard-page">

    <div class="kd-dashboard-hero">
        <div>
            <span class="kd-dashboard-badge">
                <i class="fa-solid fa-chart-line"></i>
                Data Selling Control Center
            </span>

            <h1>
                Welcome back, {{ strtoupper($admin->first_name ?? 'ADMIN') }}
            </h1>

            <p>
                Monitor orders, AFA registrations, wallet topups, payments, users, and revenue performance in one professional dashboard.
            </p>
        </div>

        <form method="GET" class="kd-dashboard-filters">

            <div class="kd-filter-field">
                <label>Date Filter</label>
                <select name="date_filter" id="kdDateFilter">
                    <option value="today" {{ $dateFilter == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $dateFilter == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="7" {{ $dateFilter == '7' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30" {{ $dateFilter == '30' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90" {{ $dateFilter == '90' ? 'selected' : '' }}>Last 90 Days</option>
                    <option value="this_month" {{ $dateFilter == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ $dateFilter == 'custom' ? 'selected' : '' }}>Custom Date</option>
                </select>
            </div>

            <div class="kd-filter-field">
                <label>Status</label>
                <select name="status">
                    <option value="all" {{ $status == 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ $status == 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="in_progress" {{ $status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="delivered" {{ $status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="completed" {{ $status == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="kd-filter-field kd-custom-date-field">
                <label>From</label>
                <input type="date" name="from_date" value="{{ $fromDate }}">
            </div>

            <div class="kd-filter-field kd-custom-date-field">
                <label>To</label>
                <input type="date" name="to_date" value="{{ $toDate }}">
            </div>

            <button type="submit">
                <i class="fa-solid fa-filter"></i>
                Apply
            </button>
        </form>
    </div>

    <div class="kd-stats-grid">

        <div class="kd-stat-card kd-blue">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <div>
                <span>Total Orders</span>
                <h3>{{ number_format($stats['total_orders'] ?? 0) }}</h3>
                <p>Data + AFA orders</p>
            </div>
        </div>

        <div class="kd-stat-card kd-green">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-money-bill-trend-up"></i>
            </div>
            <div>
                <span>Total Revenue</span>
                <h3>GHS {{ number_format($stats['revenue'] ?? 0, 2) }}</h3>
                <p>Successful payments</p>
            </div>
        </div>

        <div class="kd-stat-card kd-purple">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div>
                <span>Wallet Topups</span>
                <h3>GHS {{ number_format($stats['wallet_topups'] ?? 0, 2) }}</h3>
                <p>Verified topups</p>
            </div>
        </div>

        <div class="kd-stat-card kd-orange">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <span>Total Users</span>
                <h3>{{ number_format($stats['total_users'] ?? 0) }}</h3>
                <p>{{ number_format($stats['active_users'] ?? 0) }} active users</p>
            </div>
        </div>

        <div class="kd-stat-card kd-cyan">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-signal"></i>
            </div>
            <div>
                <span>Data Orders</span>
                <h3>{{ number_format($stats['data_orders'] ?? 0) }}</h3>
                <p>Bundle purchases</p>
            </div>
        </div>

        <div class="kd-stat-card kd-indigo">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <div>
                <span>AFA Orders</span>
                <h3>{{ number_format($stats['afa_orders'] ?? 0) }}</h3>
                <p>Registration orders</p>
            </div>
        </div>

        <div class="kd-stat-card kd-slate">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div>
                <span>Pending</span>
                <h3>{{ number_format($stats['pending_orders'] ?? 0) }}</h3>
                <p>Awaiting processing</p>
            </div>
        </div>

        <div class="kd-stat-card kd-yellow">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-spinner"></i>
            </div>
            <div>
                <span>Processing</span>
                <h3>{{ number_format($stats['processing_orders'] ?? 0) }}</h3>
                <p>Being processed</p>
            </div>
        </div>

        <div class="kd-stat-card kd-teal">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-person-running"></i>
            </div>
            <div>
                <span>In Progress</span>
                <h3>{{ number_format($stats['in_progress_orders'] ?? 0) }}</h3>
                <p>Active fulfillment stage</p>
            </div>
        </div>

        <div class="kd-stat-card kd-emerald">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <span>Delivered</span>
                <h3>{{ number_format($stats['delivered_orders'] ?? 0) }}</h3>
                <p>Completed deliveries</p>
            </div>
        </div>

        <div class="kd-stat-card kd-red">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div>
                <span>Cancelled</span>
                <h3>{{ number_format($stats['cancelled_orders'] ?? 0) }}</h3>
                <p>Failed/cancelled orders</p>
            </div>
        </div>

        <div class="kd-stat-card kd-pink">
            <div class="kd-stat-icon">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div>
                <span>Payment Fees</span>
                <h3>GHS {{ number_format($stats['payment_fees'] ?? 0, 2) }}</h3>
                <p>Gateway fees recorded</p>
            </div>
        </div>

    </div>

    <div class="kd-chart-grid">
        <div class="kd-panel kd-chart-large">
            <div class="kd-panel-header">
                <div>
                    <h3>Order Performance</h3>
                    <p>Daily data orders vs AFA orders</p>
                </div>

                <span class="kd-panel-pill">
                    <i class="fa-solid fa-chart-simple"></i>
                    Live Analytics
                </span>
            </div>

            <div class="kd-chart-box">
                <canvas id="ordersChart"></canvas>
            </div>
        </div>

        <div class="kd-panel">
            <div class="kd-panel-header">
                <div>
                    <h3>Order Status</h3>
                    <p>Pending, processing, in progress and completed orders</p>
                </div>
            </div>

            <div class="kd-chart-box kd-small-chart">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="kd-chart-grid">
        <div class="kd-panel">
            <div class="kd-panel-header">
                <div>
                    <h3>Revenue Trend</h3>
                    <p>Successful payment revenue</p>
                </div>
            </div>

            <div class="kd-chart-box">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <div class="kd-panel">
            <div class="kd-panel-header">
                <div>
                    <h3>Network Orders</h3>
                    <p>Top performing networks</p>
                </div>
            </div>

            <div class="kd-chart-box">
                <canvas id="networkChart"></canvas>
            </div>
        </div>
    </div>

    <div class="kd-tables-grid">

        <div class="kd-panel">
            <div class="kd-panel-header">
                <div>
                    <h3>Recent Data Orders</h3>
                    <p>Latest bundle purchases</p>
                </div>
            </div>

            <div class="kd-table-wrap">
                <table class="kd-dashboard-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Network</th>
                            <th>Plan</th>
                            <th>Phone</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($recentDataOrders as $order)
                            <tr>
                                <td>{{ $order->order_reference }}</td>
                                <td>{{ $order->network_name }}</td>
                                <td>{{ $order->plan_name }}</td>
                                <td>{{ $order->beneficiary_number }}</td>
                                <td>GHS {{ number_format($order->amount, 2) }}</td>
                                <td>
                                    <span class="kd-status kd-status-{{ str_replace('_', '-', strtolower($order->status)) }}">
                                        {{ ucwords(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="kd-empty">No data orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="kd-panel">
            <div class="kd-panel-header">
                <div>
                    <h3>Recent AFA Orders</h3>
                    <p>Latest AFA registrations</p>
                </div>
            </div>

            <div class="kd-table-wrap">
                <table class="kd-dashboard-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Registration</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($recentAfaOrders as $order)
                            <tr>
                                <td>{{ $order->order_reference }}</td>
                                <td>{{ $order->customer_name }}</td>
                                <td>{{ $order->phone_number }}</td>
                                <td>{{ $order->registration_name }}</td>
                                <td>GHS {{ number_format($order->amount, 2) }}</td>
                                <td>
                                    <span class="kd-status kd-status-{{ str_replace('_', '-', strtolower($order->status)) }}">
                                        {{ ucwords(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="kd-empty">No AFA orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="kd-panel">
        <div class="kd-panel-header">
            <div>
                <h3>Recent Wallet Transactions</h3>
                <p>Latest wallet credits and debits</p>
            </div>
        </div>

        <div class="kd-table-wrap">
            <table class="kd-dashboard-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($recentWalletTransactions as $txn)
                        <tr>
                            <td>{{ $txn->transaction_reference }}</td>
                            <td>{{ strtoupper($txn->full_name ?? 'UNKNOWN USER') }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $txn->transaction_type)) }}</td>
                            <td>GHS {{ number_format($txn->amount, 2) }}</td>
                            <td>GHS {{ number_format($txn->balance_after, 2) }}</td>
                            <td>{{ $txn->description }}</td>
                            <td>
                                <span class="kd-status kd-status-{{ str_replace('_', '-', strtolower($txn->status)) }}">
                                    {{ ucwords(str_replace('_', ' ', $txn->status)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="kd-empty">No wallet transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const kdChartData = @json($chartData);

    const kdDateFilter = document.getElementById('kdDateFilter');
    const kdCustomDateFields = document.querySelectorAll('.kd-custom-date-field');

    function toggleCustomDates() {
        const showCustomDates = kdDateFilter.value === 'custom';

        kdCustomDateFields.forEach((field) => {
            field.style.display = showCustomDates ? 'flex' : 'none';
        });
    }

    kdDateFilter.addEventListener('change', toggleCustomDates);
    toggleCustomDates();

    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    usePointStyle: true,
                    boxWidth: 8,
                    font: {
                        size: 12,
                        weight: '600'
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 11,
                        weight: '600'
                    }
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(148, 163, 184, 0.18)'
                },
                ticks: {
                    font: {
                        size: 11,
                        weight: '600'
                    }
                }
            }
        }
    };

    new Chart(document.getElementById('ordersChart'), {
        type: 'line',
        data: {
            labels: kdChartData.labels,
            datasets: [
                {
                    label: 'Data Orders',
                    data: kdChartData.dataOrders,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                    fill: true,
                    tension: 0.45,
                    pointRadius: 3
                },
                {
                    label: 'AFA Orders',
                    data: kdChartData.afaOrders,
                    borderColor: '#7c3aed',
                    backgroundColor: 'rgba(124, 58, 237, 0.12)',
                    fill: true,
                    tension: 0.45,
                    pointRadius: 3
                }
            ]
        },
        options: defaultOptions
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: kdChartData.statusLabels,
            datasets: [{
                data: kdChartData.statusData,
                backgroundColor: [
                    '#64748b',
                    '#f59e0b',
                    '#14b8a6',
                    '#22c55e',
                    '#ef4444'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        font: {
                            size: 12,
                            weight: '700'
                        }
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: kdChartData.labels,
            datasets: [{
                label: 'Revenue GHS',
                data: kdChartData.revenue,
                backgroundColor: 'rgba(34, 197, 94, 0.78)',
                borderRadius: 12
            }]
        },
        options: defaultOptions
    });

    new Chart(document.getElementById('networkChart'), {
        type: 'bar',
        data: {
            labels: kdChartData.networkLabels,
            datasets: [{
                label: 'Orders',
                data: kdChartData.networkData,
                backgroundColor: 'rgba(14, 165, 233, 0.78)',
                borderRadius: 12
            }]
        },
        options: {
            ...defaultOptions,
            indexAxis: 'y'
        }
    });
</script>
@endpush