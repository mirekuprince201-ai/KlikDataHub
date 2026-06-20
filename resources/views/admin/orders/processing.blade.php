@extends('admin.layouts.app')

@section('title', 'Processing Orders')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<link rel="stylesheet" href="{{ asset('admin/css/processing-orders.css') }}">
@endpush

@section('content')
@php
    $processingOrders = ($orders ?? collect())
        ->filter(fn ($order) => strtolower($order->status ?? '') === 'processing')
        ->sortByDesc('created_at')
        ->values();

    $totalProcessingOrders = $processingOrders->count();
    $totalProcessingAmount = $processingOrders->sum(fn ($order) => (float) ($order->amount ?? 0));
    $totalProcessingData = $processingOrders->sum(fn ($order) => (float) preg_replace('/[^0-9.]/', '', strtoupper($order->data_size ?? '')));
@endphp

<div class="kd-processing-orders-page">
    <div class="kd-orders-card">

        <div class="kd-orders-card-header">
            <div>
                <h4>Processing Orders List</h4>
                <p>Only orders with processing status are displayed here.</p>
            </div>

            <div class="kd-orders-header-right">
                <div class="kd-processing-summary">
                    <div class="kd-processing-summary-item">
                        <span>Total Orders</span>
                        <strong id="summaryTotalOrders">{{ number_format($totalProcessingOrders) }}</strong>
                    </div>

                    <div class="kd-processing-summary-item">
                        <span>Total Amount</span>
                        <strong id="summaryTotalAmount">GHS {{ number_format($totalProcessingAmount, 2) }}</strong>
                    </div>

                    <div class="kd-processing-summary-item">
                        <span>Total Data</span>
                        <strong id="summaryTotalData">{{ number_format($totalProcessingData) }}GB</strong>
                    </div>
                </div>

                <div id="processingOrdersButtons"></div>
            </div>
        </div>

        <div class="kd-orders-toolbar">
            <div class="kd-filter-box">
                <label for="networkFilter">Filter Network</label>
                <select id="networkFilter" placeholder="All Networks">
                    <option value="">All Networks</option>
                    @foreach($processingOrders->pluck('network_name')->filter()->unique()->values() as $network)
                        <option value="{{ strtoupper($network) }}">{{ strtoupper($network) }}</option>
                    @endforeach
                </select>
            </div>

            <div id="processingOrdersLength"></div>
            <div id="processingOrdersSearch"></div>
        </div>

        <div class="kd-orders-table-wrap">
            <table id="processingOrdersTable" class="kd-orders-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Reference</th>
                        <th>Network</th>
                        <th>Plan</th>
                        <th>Data Size</th>
                        <th>Beneficiary</th>
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
                        $rawDataSize = strtoupper($order->data_size ?? '');
                        $cleanDataSize = preg_replace('/[^0-9.]/', '', $rawDataSize);
                        $amount = (float) ($order->amount ?? 0);
                        $createdAt = $order->created_at ? \Carbon\Carbon::parse($order->created_at) : now();
                        $paymentStatus = strtolower($order->payment_status ?? '');
                    @endphp

                    <tr
                        data-order-id="{{ $order->order_id }}"
                        data-status="processing"
                        data-amount="{{ $amount }}"
                        data-size="{{ $cleanDataSize }}"
                    >
                        <td></td>

                        <td>
                            <span class="kd-order-ref">{{ $order->order_reference ?? 'N/A' }}</span>
                        </td>

                        <td>
                            <span class="kd-network-name">{{ strtoupper($order->network_name ?? 'N/A') }}</span>
                        </td>

                        <td>
                            <span class="kd-plan-name">{{ strtoupper($order->plan_name ?? 'N/A') }}</span>
                        </td>

                        <td data-export="{{ $cleanDataSize }}">
                            <span class="kd-data-size">{{ $rawDataSize ?: 'N/A' }}</span>
                        </td>

                        <td data-export="{{ $order->beneficiary_number ?? '' }}">
                            <span class="kd-phone">{{ $order->beneficiary_number ?? 'N/A' }}</span>
                        </td>

                        <td>
                            <strong class="kd-amount">GHS {{ number_format($amount, 2) }}</strong>
                        </td>

                        <td>
                            @if($paymentStatus === 'successful')
                                <span class="kd-status-pill paid">Successful</span>
                            @elseif($paymentStatus === 'failed')
                                <span class="kd-status-pill failed">Failed</span>
                            @else
                                <span class="kd-status-pill unpaid">Pending</span>
                            @endif
                        </td>

                        <td>
                            <span class="kd-method-pill">
                                {{ strtoupper(str_replace('_', ' ', $order->payment_method ?? $order->channel ?? 'N/A')) }}
                            </span>
                        </td>

                        <td>
                            <span class="kd-status-pill processing">Processing</span>
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
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !$.fn.DataTable) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const markUrl = "{{ route('admin.orders.processing.mark-in-progress') }}";
    let exportOrderIds = [];

    const table = $('#processingOrdersTable').DataTable({
        dom: 'Blfrtip',
        pageLength: -1,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[10, 'desc']],
        searching: true,
        ordering: true,
        autoWidth: false,
        scrollX: true,
        scrollCollapse: true,
        columnDefs: [
            { targets: 0, orderable: false, searchable: false }
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search processing orders...',
            lengthMenu: 'Show _MENU_ records',
            info: 'Showing _START_ to _END_ of _TOTAL_ processing orders',
            zeroRecords: `
                <div class="kd-empty-orders">
                    <strong>No processing orders found</strong>
                    <span>Processing orders will appear here once available.</span>
                </div>
            `,
            emptyTable: `
                <div class="kd-empty-orders">
                    <strong>No processing orders found</strong>
                    <span>Processing orders will appear here once available.</span>
                </div>
            `
        },
        buttons: [
            {
                text: 'Copy Phone + Data',
                className: 'kd-export-btn copy',
                action: function () {
                    const rows = getFreshRows();

                    markRowsInProgress(rows, 'copy').then(function (data) {
                        let text = 'Phone Number\tData Size\n';

                        rows.forEach(function (row) {
                            const phone = $(row).find('td').eq(5).attr('data-export') || $(row).find('td').eq(5).text().trim();
                            const size = $(row).find('td').eq(4).attr('data-export') || $(row).find('td').eq(4).text().trim();
                            text += `${phone}\t${cleanDataSize(size)}\n`;
                        });

                        navigator.clipboard.writeText(text.trim()).finally(function () {
                            removeRows(rows);
                            kdToast(data.message || 'Orders copied and moved to In Progress.');
                        });
                    }).catch(function (error) {
                        kdToast(error.message || 'Copy failed.', 'error');
                    });
                }
            },
            {
                extend: 'excelHtml5',
                text: 'Export Excel',
                className: 'kd-export-btn excel',
                title: 'Processing Orders Phone And Data Size',
                filename: 'processing_orders_phone_data_size',
                exportOptions: {
                    rows: function (idx, data, node) {
                        return exportOrderIds.includes(node.dataset.orderId);
                    },
                    columns: [5, 4],
                    format: {
                        header: function (data, columnIdx) {
                            return columnIdx === 5 ? 'Phone Number' : 'Data Size';
                        },
                        body: function (data, row, column, node) {
                            let value = $(node).attr('data-export') || $(node).text().trim();
                            return column === 4 ? cleanDataSize(value) : value;
                        }
                    }
                },
                action: function (e, dt, button, config) {
                    const rows = getFreshRows();

                    markRowsInProgress(rows, 'export').then(function (data) {
                        exportOrderIds = rows.map(row => row.dataset.orderId);

                        $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);

                        removeRows(rows);
                        kdToast(data.message || 'Orders exported and moved to In Progress.');
                        exportOrderIds = [];
                    }.bind(this)).catch(function (error) {
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

            fixProcessingOrdersTable();
        },
        initComplete: function () {
            fixProcessingOrdersTable();
        }
    });

    table.buttons().container().appendTo('#processingOrdersButtons');
    $('#processingOrdersTable_length').appendTo('#processingOrdersLength');
    $('#processingOrdersTable_filter').appendTo('#processingOrdersSearch');

    const networkSelect = new TomSelect('#networkFilter', {
        create: false,
        allowEmptyOption: true,
        searchField: ['text'],
        maxOptions: 1000,
        placeholder: 'All Networks',
        plugins: ['clear_button'],
        render: {
            option: function (data, escape) {
                return `<div class="kd-ts-option">${escape(data.text)}</div>`;
            },
            item: function (data, escape) {
                return `<div class="kd-ts-item">${escape(data.text)}</div>`;
            }
        }
    });

    $('#networkFilter').on('change', function () {
        const value = this.value;
        table.column(2).search(value ? '^' + value + '$' : '', true, false).draw();
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

    function markRowsInProgress(rows, actionType) {
        const orderIds = rows.map(row => row.dataset.orderId).filter(Boolean);

        if (!orderIds.length) {
            return Promise.reject(new Error('No new processing orders available.'));
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
                throw new Error(data.message || 'Orders could not be moved to In Progress.');
            }

            return data;
        });
    }

    function removeRows(rows) {
        rows.forEach(row => row.dataset.status = 'in_progress');
        table.rows(rows).remove().draw(false);
        fixProcessingOrdersTable();
    }

    function cleanDataSize(value) {
        return String(value || '')
            .replace(/<[^>]*>/g, '')
            .replace(/GB/gi, '')
            .replace(/[^\d.]/g, '')
            .trim();
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

    function fixProcessingOrdersTable() {
        setTimeout(function () {
            table.columns.adjust();
        }, 150);
    }

    window.addEventListener('resize', fixProcessingOrdersTable);
});
</script>
@endpush