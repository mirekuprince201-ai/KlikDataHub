@extends('admin.layouts.app')

@section('title', 'Pending Orders')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<link rel="stylesheet" href="{{ asset('admin/css/pending-orders.css') }}">
@endpush

@section('content')
@php
    $pendingOrders = ($orders ?? collect())
        ->filter(fn ($order) => strtolower($order->status ?? '') === 'pending')
        ->sortByDesc(fn ($order) => $order->ordered_at ?? $order->created_at)
        ->values();
@endphp

<div class="kd-pending-orders-page">
    <div class="kd-pending-card">

        <div class="kd-pending-header">
            <div>
                <span class="kd-page-badge">
                    <i class="fa-solid fa-clock"></i>
                    Pending Orders
                </span>

                <h4>Pending Orders List</h4>
                <p>Orders received but not yet moved to processing.</p>
            </div>

            <div class="kd-pending-summary">
                <div class="kd-summary-item">
                    <span>Total Orders</span>
                    <strong>{{ number_format($totalOrders ?? $pendingOrders->count()) }}</strong>
                </div>

                <div class="kd-summary-item">
                    <span>Total Amount</span>
                    <strong>GHS {{ number_format($totalAmount ?? 0, 2) }}</strong>
                </div>

                <div class="kd-summary-item">
                    <span>Total Data</span>
                    <strong>{{ number_format($totalData ?? 0) }}GB</strong>
                </div>

                <div class="kd-summary-item">
                    <span>Paid Orders</span>
                    <strong>{{ number_format($paidOrders ?? 0) }}</strong>
                </div>

                <div class="kd-summary-item">
                    <span>Unpaid Orders</span>
                    <strong>{{ number_format($unpaidOrders ?? 0) }}</strong>
                </div>
            </div>
        </div>

        <div class="kd-pending-toolbar">
            <div class="kd-filter-box">
                <label for="networkFilter">Network</label>
                <select id="networkFilter">
                    <option value="">All Networks</option>
                    @foreach($pendingOrders->pluck('network_name')->filter()->map(fn($n) => strtoupper($n))->unique()->values() as $network)
                        <option value="{{ $network }}">{{ $network }}</option>
                    @endforeach
                </select>
            </div>

            <div class="kd-filter-box">
                <label for="planFilter">Plan</label>
                <select id="planFilter">
                    <option value="">All Plans</option>
                    @foreach($pendingOrders->pluck('plan_name')->filter()->map(fn($p) => strtoupper($p))->unique()->values() as $plan)
                        <option value="{{ $plan }}">{{ $plan }}</option>
                    @endforeach
                </select>
            </div>

            <div class="kd-filter-box">
                <label for="tableLengthSelect">Show Records</label>
                <select id="tableLengthSelect">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="-1" selected>All</option>
                </select>
            </div>

            <div class="kd-filter-box kd-search-box">
                <label for="tableSearchInput">Search Orders</label>
                <input
                    type="search"
                    id="tableSearchInput"
                    placeholder="Search reference, phone, plan, payment..."
                    autocomplete="off"
                >
            </div>
        </div>

        <div class="kd-pending-table-wrap">
            <table id="pendingOrdersTable" class="kd-pending-table display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Reference</th>
                        <th>Network</th>
                        <th>Plan</th>
                        <th>Data Size</th>
                        <th>Beneficiary</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Payment Status</th>
                        <th>Ordered At</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($pendingOrders as $order)
                    @php
                        $amount = (float) ($order->amount ?? 0);

                        $orderedAt = !empty($order->ordered_at)
                            ? \Carbon\Carbon::parse($order->ordered_at)
                            : \Carbon\Carbon::parse($order->created_at ?? now());

                        $paymentText = trim(strtoupper(($order->payment_method ?? 'N/A') . ' ' . ($order->channel ?? '')));
                        $paymentStatus = strtolower($order->payment_status ?? 'pending');
                    @endphp

                    <tr>
                        <td></td>

                        <td>
                            <span class="kd-order-ref">{{ $order->order_reference ?? 'N/A' }}</span>
                            <small>{{ $order->payment_reference ?? 'NO PAYMENT REF' }}</small>
                        </td>

                        <td>
                            <span class="kd-network">{{ strtoupper($order->network_name ?? 'N/A') }}</span>
                        </td>

                        <td>
                            <span class="kd-plan-name">{{ strtoupper($order->plan_name ?? 'N/A') }}</span>
                        </td>

                        <td>
                            <span class="kd-data-size">{{ strtoupper($order->data_size ?? 'N/A') }}</span>
                        </td>

                        <td>
                            <span class="kd-phone">{{ $order->beneficiary_number ?? 'N/A' }}</span>
                            <small>{{ $order->customer_email ?? 'NO EMAIL' }}</small>
                        </td>

                        <td>
                            <strong class="kd-amount">GHS {{ number_format($amount, 2) }}</strong>
                        </td>

                        <td>
                            <span class="kd-payment-pill">{{ $paymentText ?: 'N/A' }}</span>
                        </td>

                        <td>
                            <span class="kd-status-pill {{ $paymentStatus === 'successful' ? 'paid' : 'unpaid' }}">
                                {{ strtoupper($order->payment_status ?? 'PENDING') }}
                            </span>
                        </td>

                        <td data-order="{{ $orderedAt->timestamp }}">
                            <span class="kd-date-pill">{{ $orderedAt->format('d M Y, h:i A') }}</span>
                        </td>

                        <td>
                            <span class="kd-status-pill pending">Pending</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !$.fn.DataTable) return;

    const table = $('#pendingOrdersTable').DataTable({
        dom: 'rt<"kd-dt-bottom"ip>',
        pageLength: -1,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[9, 'desc']],
        searching: true,
        ordering: true,
        autoWidth: false,
        scrollX: true,
        scrollCollapse: true,
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, width: '45px' }
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ pending orders',
            infoEmpty: 'No pending orders available',
            zeroRecords: `
                <div class="kd-empty-orders">
                    <strong>No matching pending orders found</strong>
                    <span>Try changing your search or filter.</span>
                </div>
            `,
            emptyTable: `
                <div class="kd-empty-orders">
                    <strong>No pending orders available</strong>
                    <span>New orders will appear here.</span>
                </div>
            `
        },
        initComplete: function () {
            initControls();
            initFilters();
            setTimeout(() => table.columns.adjust(), 120);
        },
        drawCallback: function () {
            const api = this.api();

            api.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, index) {
                cell.innerHTML = index + 1;
            });

            setTimeout(() => table.columns.adjust(), 80);
        }
    });

    function initControls() {
        const searchInput = document.getElementById('tableSearchInput');
        const lengthSelect = document.getElementById('tableLengthSelect');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                table.search(this.value).draw();
            });
        }

        if (lengthSelect) {
            lengthSelect.addEventListener('change', function () {
                table.page.len(parseInt(this.value, 10)).draw();
            });
        }
    }

    function initFilters() {
        const networkFilter = document.getElementById('networkFilter');
        const planFilter = document.getElementById('planFilter');

        [networkFilter, planFilter].forEach(function (select) {
            if (select && window.TomSelect && !select.tomselect) {
                new TomSelect(select, {
                    create: false,
                    allowEmptyOption: true,
                    maxOptions: 1000,
                    placeholder: 'Select option',
                    closeAfterSelect: true,
                    searchField: ['text']
                });
            }
        });

        if (networkFilter) {
            networkFilter.addEventListener('change', function () {
                const value = this.value ? $.fn.dataTable.util.escapeRegex(this.value) : '';
                table.column(2).search(value ? '^' + value + '$' : '', true, false).draw();
            });
        }

        if (planFilter) {
            planFilter.addEventListener('change', function () {
                const value = this.value ? $.fn.dataTable.util.escapeRegex(this.value) : '';
                table.column(3).search(value ? '^' + value + '$' : '', true, false).draw();
            });
        }
    }

    window.addEventListener('resize', function () {
        setTimeout(() => table.columns.adjust(), 120);
    });
});
</script>
@endpush