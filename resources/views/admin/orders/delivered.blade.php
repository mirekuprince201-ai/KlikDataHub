@extends('admin.layouts.app')

@section('title', 'Delivered Orders')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<link rel="stylesheet" href="{{ asset('admin/css/delivered-orders.css') }}">
@endpush

@section('content')
@php
    $deliveredOrders = ($orders ?? collect())
        ->filter(fn ($order) => strtolower($order->status ?? '') === 'delivered')
        ->sortByDesc(fn ($order) => $order->delivered_at ?? $order->completed_at ?? $order->updated_at ?? $order->created_at)
        ->values();
@endphp

<div class="kd-delivered-orders-page">
    <div class="kd-delivered-card">

        <div class="kd-delivered-header">
            <div>
                <span class="kd-page-badge">
                    <i class="fa-solid fa-circle-check"></i>
                    Delivered Orders
                </span>

                <h4>Delivered Orders List</h4>
                <p>All successfully completed and delivered data orders.</p>
            </div>

            <div class="kd-delivered-summary">
                <div class="kd-summary-item">
                    <span>Total Orders</span>
                    <strong>{{ number_format($totalOrders ?? $deliveredOrders->count()) }}</strong>
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
                    <span>Auto Completed</span>
                    <strong>{{ number_format($autoCompletedOrders ?? 0) }}</strong>
                </div>

                <div class="kd-summary-item">
                    <span>Manual</span>
                    <strong>{{ number_format($manualCompletedOrders ?? 0) }}</strong>
                </div>
            </div>
        </div>

        <div class="kd-delivered-toolbar">
            <div class="kd-filter-box">
                <label for="networkFilter">Network</label>
                <select id="networkFilter">
                    <option value="">All Networks</option>
                    @foreach($deliveredOrders->pluck('network_name')->filter()->map(fn($n) => strtoupper($n))->unique()->values() as $network)
                        <option value="{{ $network }}">{{ $network }}</option>
                    @endforeach
                </select>
            </div>

            <div class="kd-filter-box">
                <label for="planFilter">Plan</label>
                <select id="planFilter">
                    <option value="">All Plans</option>
                    @foreach($deliveredOrders->pluck('plan_name')->filter()->map(fn($p) => strtoupper($p))->unique()->values() as $plan)
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

        <div class="kd-delivered-table-wrap">
            <table id="deliveredOrdersTable" class="kd-delivered-table display nowrap" style="width:100%">
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
                        <th>Status</th>
                        <th>Delivered At</th>
                        <th>Completed By</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($deliveredOrders as $order)
                    @php
                        $amount = (float) ($order->amount ?? 0);

                        $deliveredAt = !empty($order->delivered_at)
                            ? \Carbon\Carbon::parse($order->delivered_at)
                            : (!empty($order->completed_at)
                                ? \Carbon\Carbon::parse($order->completed_at)
                                : \Carbon\Carbon::parse($order->updated_at ?? $order->created_at ?? now()));

                        $completedBy = ((int) ($order->auto_completed ?? 0) === 1) ? 'AUTO' : 'MANUAL';

                        $paymentText = trim(strtoupper(($order->payment_method ?? 'N/A') . ' ' . ($order->channel ?? '')));
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
                            <small>{{ strtoupper($order->payment_status ?? 'N/A') }}</small>
                        </td>

                        <td>
                            <span class="kd-status-pill delivered">Delivered</span>
                        </td>

                        <td data-order="{{ $deliveredAt->timestamp }}">
                            <span class="kd-date-pill">{{ $deliveredAt->format('d M Y, h:i A') }}</span>
                        </td>

                        <td>
                            <span class="kd-status-pill {{ $completedBy === 'AUTO' ? 'auto' : 'manual' }}">
                                {{ $completedBy }}
                            </span>
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

    const table = $('#deliveredOrdersTable').DataTable({
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
            info: 'Showing _START_ to _END_ of _TOTAL_ delivered orders',
            infoEmpty: 'No delivered orders available',
            zeroRecords: `
                <div class="kd-empty-orders">
                    <strong>No matching delivered orders found</strong>
                    <span>Try changing your search or filter.</span>
                </div>
            `,
            emptyTable: `
                <div class="kd-empty-orders">
                    <strong>No delivered orders available</strong>
                    <span>Completed orders will appear here.</span>
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