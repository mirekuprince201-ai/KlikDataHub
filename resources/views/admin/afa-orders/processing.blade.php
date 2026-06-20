@extends('admin.layouts.app')

@section('title', 'Processing AFA Orders')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<link rel="stylesheet" href="{{ asset('admin/css/processing-orders.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/afa-processing-orders.css') }}?v={{ time() }}">
@endpush

@section('content')
@php
    $processingOrders = ($orders ?? collect())
        ->filter(fn ($order) =>
            strtolower($order->status ?? '') === 'processing'
            && strtolower($order->payment_status ?? '') === 'successful'
        )
        ->sortByDesc('created_at')
        ->values();

    $totalProcessingOrders = $processingOrders->count();

    $totalProcessingAmount = $processingOrders->sum(fn ($order) => (float) ($order->amount ?? 0));

    $paidOrders = $processingOrders->filter(fn ($order) => strtolower($order->payment_status ?? '') === 'successful')->count();
@endphp

<div class="kd-processing-orders-page">
    <div class="kd-orders-card">

        <div class="kd-orders-card-header">
            <div>
                <h4>Processing AFA Orders List</h4>
                <p>Only successful paid AFA orders with processing status are displayed here.</p>
            </div>

            <div class="kd-orders-header-right">
                <div class="kd-processing-summary">
                    <div class="kd-processing-summary-item">
                        <span>Total Orders</span>
                        <strong>{{ number_format($totalProcessingOrders) }}</strong>
                    </div>

                    <div class="kd-processing-summary-item">
                        <span>Total Amount</span>
                        <strong>GHS {{ number_format($totalProcessingAmount, 2) }}</strong>
                    </div>

                    <div class="kd-processing-summary-item">
                        <span>Paid Orders</span>
                        <strong>{{ number_format($paidOrders) }}</strong>
                    </div>
                </div>

                <div id="processingAfaOrdersButtons"></div>
            </div>
        </div>

        <div class="kd-orders-toolbar">
            <div class="kd-filter-box">
                <label for="occupationFilter">Filter Occupation</label>
                <select id="occupationFilter">
                    <option value="">All Occupations</option>
                    @foreach($processingOrders->pluck('occupation')->filter()->map(fn($n) => strtoupper($n))->unique()->values() as $occupation)
                        <option value="{{ $occupation }}">{{ $occupation }}</option>
                    @endforeach
                </select>
            </div>

            <div class="kd-filter-box">
                <label for="locationFilter">Filter Location</label>
                <select id="locationFilter">
                    <option value="">All Locations</option>
                    @foreach($processingOrders->pluck('location')->filter()->map(fn($n) => strtoupper($n))->unique()->values() as $location)
                        <option value="{{ $location }}">{{ $location }}</option>
                    @endforeach
                </select>
            </div>

            <div id="processingAfaOrdersLength"></div>
            <div id="processingAfaOrdersSearch"></div>
        </div>

        <div class="kd-orders-table-wrap kd-afa-scroll-table">
            <table id="processingAfaOrdersTable" class="kd-orders-table display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Ghana Card</th>
                        <th>Location</th>
                        <th>Occupation</th>
                        <th>Amount</th>
                        <th>Payment Status</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Ordered At</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($processingOrders as $order)
                    @php
                        $amount = (float) ($order->amount ?? 0);

                        $createdAt = !empty($order->ordered_at)
                            ? \Carbon\Carbon::parse($order->ordered_at)
                            : \Carbon\Carbon::parse($order->created_at ?? now());

                        $paymentMethod = strtoupper(str_replace('_', ' ', $order->payment_method ?? 'N/A'));
                        $paymentChannel = strtoupper($order->channel ?? '');
                        $paymentText = trim($paymentMethod . ' ' . $paymentChannel);
                    @endphp

                    <tr
                        data-order-id="{{ $order->order_id }}"
                        data-status="processing"
                        data-name="{{ strtoupper($order->customer_name ?? '') }}"
                        data-phone="{{ $order->phone_number ?? '' }}"
                        data-card="{{ strtoupper($order->ghana_card_number ?? '') }}"
                        data-location="{{ strtoupper($order->location ?? '') }}"
                        data-occupation="{{ strtoupper($order->occupation ?? '') }}"
                    >
                        <td></td>

                        <td>
                            <span class="kd-order-ref">{{ strtoupper($order->order_reference ?? 'N/A') }}</span>
                            <small>{{ $order->payment_reference ?? 'NO PAYMENT REF' }}</small>
                        </td>

                        <td>
                            <span class="kd-plan-name">{{ strtoupper($order->customer_name ?? 'N/A') }}</span>
                            <small>{{ $order->customer_email ?? $order->payment_customer_email ?? 'NO EMAIL' }}</small>
                        </td>

                        <td data-export="{{ $order->phone_number ?? '' }}">
                            <span class="kd-phone">{{ $order->phone_number ?? 'N/A' }}</span>
                        </td>

                        <td data-export="{{ strtoupper($order->ghana_card_number ?? '') }}">
                            <span class="kd-data-size">{{ strtoupper($order->ghana_card_number ?? 'N/A') }}</span>
                        </td>

                        <td>
                            <span class="kd-network-name">{{ strtoupper($order->location ?? 'UNKNOWN') }}</span>
                        </td>

                        <td>
                            <span class="kd-plan-name">{{ strtoupper($order->occupation ?? 'N/A') }}</span>
                        </td>

                        <td>
                            <strong class="kd-amount">GHS {{ number_format($amount, 2) }}</strong>
                        </td>

                        <td>
                            <span class="kd-status-pill paid">SUCCESSFUL</span>
                        </td>

                        <td>
                            <span class="kd-method-pill">{{ $paymentText ?: 'N/A' }}</span>
                        </td>

                        <td>
                            <span class="kd-status-pill processing">PROCESSING</span>
                        </td>

                        <td data-order="{{ $createdAt->timestamp }}">
                            <span class="kd-date-pill">{{ $createdAt->format('d M Y, h:i A') }}</span>
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
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !$.fn.DataTable) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const markUrl = "{{ route('admin.afa-orders.processing.mark-in-progress') }}";
    let exportOrderIds = [];

    const table = $('#processingAfaOrdersTable').DataTable({
        dom: 'Blfrtip',
        pageLength: -1,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[11, 'desc']],
        searching: true,
        ordering: true,
        autoWidth: false,
        scrollX: true,
        scrollY: false,
        scrollCollapse: false,
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, width: '50px' }
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search processing AFA orders...',
            lengthMenu: 'Show _MENU_ records',
            info: 'Showing _START_ to _END_ of _TOTAL_ processing AFA orders',
            zeroRecords: `
                <div class="kd-empty-orders">
                    <strong>No processing AFA orders found</strong>
                    <span>Only successful paid processing AFA orders will appear here.</span>
                </div>
            `,
            emptyTable: `
                <div class="kd-empty-orders">
                    <strong>No processing AFA orders found</strong>
                    <span>Only successful paid processing AFA orders will appear here.</span>
                </div>
            `
        },
        buttons: [
            {
                text: 'Copy AFA Details',
                className: 'kd-export-btn copy',
                action: function () {
                    const rows = getFreshRows();

                    markRowsInProgress(rows, 'copy')
                        .then(function (data) {
                            const text = buildCopyText(rows);

                            copyToClipboard(text).then(function () {
                                removeRows(rows);
                                kdToast(data.message || 'AFA details copied and moved to In Progress.');
                            });
                        })
                        .catch(function (error) {
                            kdToast(error.message || 'Copy failed.', 'error');
                        });
                }
            },
            {
                extend: 'excelHtml5',
                text: 'Export Excel',
                className: 'kd-export-btn excel',
                title: 'Processing AFA Orders',
                filename: 'processing_afa_orders',
                exportOptions: {
                    rows: function (idx, data, node) {
                        return exportOrderIds.includes(node.dataset.orderId);
                    },
                    columns: [2, 3, 4, 5, 6],
                    format: {
                        header: function (data, columnIdx) {
                            const headers = {
                                2: 'Customer Name',
                                3: 'Phone Number',
                                4: 'Ghana Card Number',
                                5: 'Location',
                                6: 'Occupation'
                            };

                            return headers[columnIdx] || data;
                        },
                        body: function (data, row, column, node) {
                            return $(node).attr('data-export') || $(node).text().replace(/\s+/g, ' ').trim();
                        }
                    }
                },
                action: function (e, dt, button, config) {
                    const rows = getFreshRows();

                    markRowsInProgress(rows, 'export')
                        .then(function (data) {
                            exportOrderIds = rows.map(row => row.dataset.orderId);

                            $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);

                            removeRows(rows);
                            kdToast(data.message || 'AFA orders exported and moved to In Progress.');
                            exportOrderIds = [];
                        }.bind(this))
                        .catch(function (error) {
                            exportOrderIds = [];
                            kdToast(error.message || 'Export failed.', 'error');
                        });
                }
            }
        ],
        drawCallback: function () {
            const api = this.api();

            api.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, index) {
                cell.innerHTML = index + 1;
            });

            fixTable();
        },
        initComplete: function () {
            fixTable();
        }
    });

    table.buttons().container().appendTo('#processingAfaOrdersButtons');
    $('#processingAfaOrdersTable_length').appendTo('#processingAfaOrdersLength');
    $('#processingAfaOrdersTable_filter').appendTo('#processingAfaOrdersSearch');

    new TomSelect('#occupationFilter', {
        create: false,
        allowEmptyOption: true,
        searchField: ['text'],
        maxOptions: 1000,
        plugins: ['clear_button']
    });

    new TomSelect('#locationFilter', {
        create: false,
        allowEmptyOption: true,
        searchField: ['text'],
        maxOptions: 1000,
        plugins: ['clear_button']
    });

    $('#occupationFilter').on('change', function () {
        const value = this.value;

        table.column(6)
            .search(value ? '^' + $.fn.dataTable.util.escapeRegex(value) + '$' : '', true, false)
            .draw();
    });

    $('#locationFilter').on('change', function () {
        const value = this.value;

        table.column(5)
            .search(value ? '^' + $.fn.dataTable.util.escapeRegex(value) + '$' : '', true, false)
            .draw();
    });

    function getFreshRows() {
        const rows = [];

        table.rows({ search: 'applied', order: 'applied' }).every(function () {
            const row = this.node();

            if (row && row.dataset.status === 'processing') {
                rows.push(row);
            }
        });

        return rows;
    }

    function buildCopyText(rows) {
        let text = 'Customer Name\tPhone Number\tGhana Card Number\tLocation\tOccupation\n';

        rows.forEach(function (row) {
            text += [
                row.dataset.name || '',
                row.dataset.phone || '',
                row.dataset.card || '',
                row.dataset.location || '',
                row.dataset.occupation || ''
            ].join('\t') + '\n';
        });

        return text.trim();
    }

    function markRowsInProgress(rows, actionType) {
        const orderIds = rows.map(row => row.dataset.orderId).filter(Boolean);

        if (!orderIds.length) {
            return Promise.reject(new Error('No new processing AFA orders available.'));
        }

        return fetch(markUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                action_type: actionType,
                order_ids: orderIds
            })
        })
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success || !data.updated) {
                throw new Error(data.message || 'AFA orders could not be moved to In Progress.');
            }

            return data;
        });
    }

    function removeRows(rows) {
        rows.forEach(function (row) {
            row.dataset.status = 'in_progress';
        });

        table.rows(rows).remove().draw(false);
        fixTable();
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';

        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        return new Promise(function (resolve, reject) {
            const copied = document.execCommand('copy');

            textarea.remove();

            copied ? resolve() : reject();
        });
    }

    function kdToast(message, type = 'success') {
        let box = document.querySelector('.kd-js-toast-wrap');

        if (!box) {
            box = document.createElement('div');
            box.className = 'kd-js-toast-wrap';
            document.body.appendChild(box);
        }

        const toast = document.createElement('div');
        toast.className = `kd-js-toast ${type}`;
        toast.innerHTML = `
            <span class="kd-js-toast-dot"></span>
            <div>
                <strong>${type === 'success' ? 'Success' : 'Notice'}</strong>
                <p>${message}</p>
            </div>
        `;

        box.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 30);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 250);
        }, 3000);
    }

    function fixTable() {
        setTimeout(function () {
            table.columns.adjust();
        }, 150);
    }

    window.addEventListener('resize', fixTable);
});
</script>
@endpush