@extends('admin.layouts.app')

@section('title', $title ?? 'AFA Orders')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<link rel="stylesheet" href="{{ asset('admin/css/pending-orders.css') }}">

<style>
/* ==========================================================
   AFA ORDERS PROFESSIONAL POLISH
========================================================== */

.kd-alert {
    width: 100%;
    margin: 18px 0;
    padding: 14px 16px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    font-weight: 850;
}

.kd-alert-success {
    background: rgba(22, 163, 74, .10);
    color: #15803d;
    border: 1px solid rgba(22, 163, 74, .22);
}

.kd-alert-danger {
    background: rgba(220, 38, 38, .10);
    color: #dc2626;
    border: 1px solid rgba(220, 38, 38, .22);
}

.kd-action-btn {
    border: 0;
    outline: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 10px 15px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 950;
    cursor: pointer;
    white-space: nowrap;
    transition: all .22s ease;
}

.kd-action-primary {
    background: rgba(37, 99, 235, .12);
    color: #2563eb;
}

.kd-action-primary:hover {
    background: #2563eb;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(37, 99, 235, .25);
}

.kd-action-success {
    background: rgba(22, 163, 74, .13);
    color: #16a34a;
}

.kd-action-success:hover {
    background: #16a34a;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(22, 163, 74, .25);
}

.kd-status-pill.inprogress {
    background: rgba(37, 99, 235, .12);
    color: #2563eb;
}

.kd-status-pill.processing {
    background: rgba(245, 158, 11, .14);
    color: #d97706;
}

.kd-status-pill.pending {
    background: rgba(100, 116, 139, .14);
    color: #475569;
}

.kd-status-pill.delivered {
    background: rgba(22, 163, 74, .13);
    color: #16a34a;
}

/* ==========================================================
   DARK MODE FILTERS + TABLE FIXES
========================================================== */

body.dark-mode .kd-filter-box label,
html[data-theme="dark"] .kd-filter-box label {
    color: #cbd5e1 !important;
}

body.dark-mode .kd-filter-box select,
html[data-theme="dark"] .kd-filter-box select,
body.dark-mode .kd-filter-box input,
html[data-theme="dark"] .kd-filter-box input,
body.dark-mode .ts-control,
html[data-theme="dark"] .ts-control,
body.dark-mode .ts-wrapper.single .ts-control,
html[data-theme="dark"] .ts-wrapper.single .ts-control {
    min-height: 50px !important;
    background: #0f172a !important;
    color: #f8fafc !important;
    border: 1px solid rgba(148, 163, 184, .22) !important;
    border-radius: 18px !important;
    box-shadow: none !important;
}

body.dark-mode .kd-filter-box input::placeholder,
html[data-theme="dark"] .kd-filter-box input::placeholder {
    color: #64748b !important;
}

body.dark-mode .ts-control input,
html[data-theme="dark"] .ts-control input {
    color: #f8fafc !important;
}

body.dark-mode .ts-control .item,
html[data-theme="dark"] .ts-control .item {
    color: #f8fafc !important;
    font-weight: 900 !important;
}

body.dark-mode .ts-placeholder,
html[data-theme="dark"] .ts-placeholder {
    color: #94a3b8 !important;
}

body.dark-mode .ts-dropdown,
html[data-theme="dark"] .ts-dropdown {
    background: #0f172a !important;
    border: 1px solid rgba(148, 163, 184, .22) !important;
    color: #f8fafc !important;
    border-radius: 16px !important;
    overflow: hidden !important;
}

body.dark-mode .ts-dropdown .option,
html[data-theme="dark"] .ts-dropdown .option {
    color: #e2e8f0 !important;
    padding: 10px 12px !important;
}

body.dark-mode .ts-dropdown .active,
body.dark-mode .ts-dropdown .option:hover,
html[data-theme="dark"] .ts-dropdown .active,
html[data-theme="dark"] .ts-dropdown .option:hover {
    background: rgba(37, 99, 235, .20) !important;
    color: #ffffff !important;
}

body.dark-mode .dataTables_info,
body.dark-mode .dataTables_paginate,
body.dark-mode .dataTables_paginate .paginate_button,
html[data-theme="dark"] .dataTables_info,
html[data-theme="dark"] .dataTables_paginate,
html[data-theme="dark"] .dataTables_paginate .paginate_button {
    color: #cbd5e1 !important;
}

body.dark-mode .kd-alert-success,
html[data-theme="dark"] .kd-alert-success {
    background: rgba(22, 163, 74, .12);
    color: #86efac;
    border-color: rgba(34, 197, 94, .22);
}

body.dark-mode .kd-alert-danger,
html[data-theme="dark"] .kd-alert-danger {
    background: rgba(220, 38, 38, .12);
    color: #fecaca;
    border-color: rgba(248, 113, 113, .22);
}

/* ==========================
   TOM SELECT DROPDOWN CLEAN UI
========================== */

.ts-wrapper {
    width: 100% !important;
}

.ts-control {
    padding: 0 16px !important;
    min-height: 50px !important;
    border-radius: 18px !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}

.ts-control > input {
    min-width: 0 !important;
    width: 1px !important;
    flex: 1 1 auto !important;
    margin: 0 !important;
    padding: 0 !important;
    background: transparent !important;
}

.ts-control .item {
    font-size: 14px !important;
    font-weight: 900 !important;
    line-height: 1 !important;
}

.ts-wrapper.single .ts-control::after {
    right: 16px !important;
    border-color: #94a3b8 transparent transparent transparent !important;
}

.ts-dropdown {
    margin-top: 8px !important;
    border-radius: 18px !important;
    padding: 8px !important;
    z-index: 9999 !important;
}

.ts-dropdown .option {
    border-radius: 14px !important;
    padding: 12px 14px !important;
    font-size: 14px !important;
    font-weight: 850 !important;
}

.ts-wrapper.plugin-clear_button .clear-button {
    right: 38px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    color: #94a3b8 !important;
    font-size: 16px !important;
}

body.dark-mode .ts-dropdown,
html[data-theme="dark"] .ts-dropdown {
    background: #111827 !important;
    border: 1px solid rgba(148, 163, 184, .20) !important;
    box-shadow: 0 24px 70px rgba(0, 0, 0, .45) !important;
}

body.dark-mode .ts-dropdown .option,
html[data-theme="dark"] .ts-dropdown .option {
    color: #e5e7eb !important;
}

body.dark-mode .ts-dropdown .active,
body.dark-mode .ts-dropdown .option:hover,
html[data-theme="dark"] .ts-dropdown .active,
html[data-theme="dark"] .ts-dropdown .option:hover {
    background: #1d4ed8 !important;
    color: #ffffff !important;
}

body.dark-mode .ts-control,
html[data-theme="dark"] .ts-control {
    background: #0f172a !important;
    border-color: rgba(148, 163, 184, .25) !important;
}

</style>
@endpush

@section('content')
@php
    $currentStatus = strtolower($status ?? 'processing');

    $afaOrders = ($orders ?? collect())
        ->filter(fn ($order) => strtolower($order->status ?? '') === $currentStatus)
        ->sortByDesc(fn ($order) => $order->ordered_at ?? $order->created_at)
        ->values();

    $totalOrders = $totalOrders ?? $afaOrders->count();
    $totalAmount = $totalAmount ?? $afaOrders->sum(fn ($order) => (float) ($order->amount ?? 0));

    $paidOrders = $paidOrders ?? $afaOrders
        ->filter(fn ($order) => strtolower($order->payment->status ?? '') === 'successful')
        ->count();

    $unpaidOrders = $unpaidOrders ?? max($totalOrders - $paidOrders, 0);

    $pageIcon = match ($currentStatus) {
        'pending' => 'fa-clock',
        'processing' => 'fa-spinner',
        'in-progress' => 'fa-truck-fast',
        'delivered' => 'fa-circle-check',
        default => 'fa-id-card-clip',
    };

    $statusLabel = strtoupper(str_replace('-', ' ', $currentStatus));
@endphp

<div class="kd-pending-orders-page">
    <div class="kd-pending-card">

        <div class="kd-pending-header">
            <div>
                <span class="kd-page-badge">
                    <i class="fa-solid {{ $pageIcon }}"></i>
                    AFA Orders
                </span>

                <h4>{{ $title ?? $statusLabel . ' AFA Orders' }}</h4>

                <p>
                    Manage AFA registration orders, customer details, payment confirmation and progress.
                </p>
            </div>

            <div class="kd-pending-summary">
                <div class="kd-summary-item">
                    <span>Total Orders</span>
                    <strong>{{ number_format($totalOrders) }}</strong>
                </div>

                <div class="kd-summary-item">
                    <span>Total Amount</span>
                    <strong>GHS {{ number_format($totalAmount, 2) }}</strong>
                </div>

                <div class="kd-summary-item">
                    <span>Paid Orders</span>
                    <strong>{{ number_format($paidOrders) }}</strong>
                </div>

                <div class="kd-summary-item">
                    <span>Unpaid Orders</span>
                    <strong>{{ number_format($unpaidOrders) }}</strong>
                </div>

                <div class="kd-summary-item">
                    <span>Status</span>
                    <strong>{{ $statusLabel }}</strong>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="kd-alert kd-alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="kd-alert kd-alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <div class="kd-pending-toolbar">
            <div class="kd-filter-box">
                <label for="occupationFilter">Occupation</label>
                <select id="occupationFilter">
                    <option value="">All Occupations</option>
                    @foreach($afaOrders->pluck('occupation')->filter()->map(fn ($n) => strtoupper($n))->unique()->values() as $occupation)
                        <option value="{{ $occupation }}">{{ $occupation }}</option>
                    @endforeach
                </select>
            </div>

            <div class="kd-filter-box">
                <label for="locationFilter">Location</label>
                <select id="locationFilter">
                    <option value="">All Locations</option>
                    @foreach($afaOrders->pluck('location')->filter()->map(fn ($n) => strtoupper($n))->unique()->values() as $location)
                        <option value="{{ $location }}">{{ $location }}</option>
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
                <label for="tableSearchInput">Search AFA Orders</label>
                <input
                    type="search"
                    id="tableSearchInput"
                    placeholder="Search reference, name, phone, Ghana card..."
                    autocomplete="off"
                >
            </div>
        </div>

        <div class="kd-pending-table-wrap">
            <table id="afaOrdersTable" class="kd-pending-table display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Reference</th>
                        <th>Registration</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Ghana Card</th>
                        <th>Location</th>
                        <th>Occupation</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Ordered At</th>
                        <th>Status</th>

                        @if(in_array($currentStatus, ['processing', 'in-progress']))
                            <th>Action</th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                @foreach($afaOrders as $order)
                    @php
                        $amount = (float) ($order->amount ?? 0);

                        $orderedAt = !empty($order->ordered_at)
                            ? \Carbon\Carbon::parse($order->ordered_at)
                            : \Carbon\Carbon::parse($order->created_at ?? now());

                        $paymentStatus = strtolower($order->payment->status ?? 'pending');
                        $paymentReference = $order->payment->payment_reference ?? 'NO PAYMENT REF';
                        $paymentMethod = strtoupper($order->payment->payment_method ?? 'N/A');
                        $paymentChannel = strtoupper($order->payment->channel ?? '');
                        $paymentText = trim($paymentMethod . ' ' . $paymentChannel);
                    @endphp

                    <tr>
                        <td></td>

                        <td>
                            <span class="kd-order-ref">{{ strtoupper($order->order_reference ?? 'N/A') }}</span>
                            <small>{{ $paymentReference }}</small>
                        </td>

                        <td>
                            <span class="kd-network">
                                {{ strtoupper($order->registration_name ?? 'AFA REGISTRATION') }}
                            </span>
                        </td>

                        <td>
                            <span class="kd-plan-name">{{ strtoupper($order->customer_name ?? 'N/A') }}</span>
                            <small>{{ $order->customer_email ?? 'NO EMAIL' }}</small>
                        </td>

                        <td>
                            <span class="kd-phone">{{ $order->phone_number ?? 'N/A' }}</span>
                        </td>

                        <td>
                            <span class="kd-data-size">{{ strtoupper($order->ghana_card_number ?? 'N/A') }}</span>
                        </td>

                        <td>
                            <span class="kd-network">{{ strtoupper($order->location ?? 'UNKNOWN') }}</span>
                        </td>

                        <td>
                            <span class="kd-plan-name">{{ strtoupper($order->occupation ?? 'N/A') }}</span>
                        </td>

                        <td>
                            <strong class="kd-amount">GHS {{ number_format($amount, 2) }}</strong>
                        </td>

                        <td>
                            <span class="kd-payment-pill">
                                {{ $paymentText ?: 'N/A' }}
                            </span>

                            <span class="kd-status-pill {{ $paymentStatus === 'successful' ? 'paid' : 'unpaid' }}">
                                {{ strtoupper($paymentStatus) }}
                            </span>
                        </td>

                        <td data-order="{{ $orderedAt->timestamp }}">
                            <span class="kd-date-pill">{{ $orderedAt->format('d M Y, h:i A') }}</span>
                        </td>

                        <td>
                            <span class="kd-status-pill {{ str_replace('-', '', $currentStatus) }}">
                                {{ $statusLabel }}
                            </span>
                        </td>

                        @if($currentStatus === 'processing')
                            <td>
                                <form method="POST" action="{{ route('admin.afa-orders.processing.mark-in-progress') }}">
                                    @csrf

                                    <input type="hidden" name="order_id" value="{{ $order->order_id }}">

                                    <button type="submit" class="kd-action-btn kd-action-primary">
                                        <i class="fa-solid fa-truck-fast"></i>
                                        <span>In Progress</span>
                                    </button>
                                </form>
                            </td>
                        @endif

                        @if($currentStatus === 'in-progress')
                            <td>
                                <form method="POST" action="{{ route('admin.afa-orders.in-progress.complete') }}">
                                    @csrf

                                    <input type="hidden" name="order_id" value="{{ $order->order_id }}">

                                    <button type="submit" class="kd-action-btn kd-action-success">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span>Complete</span>
                                    </button>
                                </form>
                            </td>
                        @endif
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

    const hasActionColumn = {{ in_array($currentStatus, ['processing', 'in-progress']) ? 'true' : 'false' }};

    const table = $('#afaOrdersTable').DataTable({
        dom: 'rt<"kd-dt-bottom"ip>',
        pageLength: -1,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[10, 'desc']],
        searching: true,
        ordering: true,
        autoWidth: false,
        scrollX: true,
        scrollCollapse: true,
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                searchable: false,
                width: '45px'
            },
            ...(hasActionColumn ? [{
                targets: -1,
                orderable: false,
                searchable: false
            }] : [])
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ AFA orders',
            infoEmpty: 'No AFA orders available',
            zeroRecords: `
                <div class="kd-empty-orders">
                    <strong>No matching AFA orders found</strong>
                    <span>Try changing your search or filter.</span>
                </div>
            `,
            emptyTable: `
                <div class="kd-empty-orders">
                    <strong>No AFA orders available</strong>
                    <span>AFA orders will appear here.</span>
                </div>
            `
        },
        initComplete: function () {
            initControls();
            initFilters();

            setTimeout(() => {
                table.columns.adjust();
            }, 120);
        },
        drawCallback: function () {
            const api = this.api();

            api.column(0, {
                search: 'applied',
                order: 'applied'
            }).nodes().each(function (cell, index) {
                cell.innerHTML = index + 1;
            });

            setTimeout(() => {
                table.columns.adjust();
            }, 80);
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
        const occupationFilter = document.getElementById('occupationFilter');
        const locationFilter = document.getElementById('locationFilter');

        [occupationFilter, locationFilter].forEach(function (select) {
            if (select && window.TomSelect && !select.tomselect) {
                new TomSelect(select, {
                    create: false,
                    allowEmptyOption: true,
                    maxOptions: 1000,
                    placeholder: 'Select option',
                    closeAfterSelect: true,
                    searchField: ['text'],
                    plugins: {
                        clear_button: {
                            title: 'Clear selection'
                        }
                    }
                });
            }
        });

        if (locationFilter) {
            locationFilter.addEventListener('change', function () {
                const value = this.value ? $.fn.dataTable.util.escapeRegex(this.value) : '';

                table
                    .column(6)
                    .search(value ? '^' + value + '$' : '', true, false)
                    .draw();
            });
        }

        if (occupationFilter) {
            occupationFilter.addEventListener('change', function () {
                const value = this.value ? $.fn.dataTable.util.escapeRegex(this.value) : '';

                table
                    .column(7)
                    .search(value ? '^' + value + '$' : '', true, false)
                    .draw();
            });
        }
    }

    window.addEventListener('resize', function () {
        setTimeout(() => {
            table.columns.adjust();
        }, 120);
    });
});
</script>
@endpush