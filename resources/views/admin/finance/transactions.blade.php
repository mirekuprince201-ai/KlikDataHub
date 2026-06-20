@extends('admin.layouts.app')

@section('title', 'Finance Transactions')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('admin/css/finance-transactions.css') }}">
@endpush

@section('content')
@php
    $fromDate = request('from_date', '');
    $toDate = request('to_date', '');
    $typeFilter = request('type', 'all');
    $statusFilter = request('status', 'all');

    $summary = $summary ?? [
        'total_transactions' => 0,
        'total_amount' => 0,
        'successful' => 0,
        'pending' => 0,
    ];
@endphp

<div class="kd-transactions-page">

    <div class="kd-transactions-header">
        <div>
            <span class="kd-page-badge">
                <i class="fa-solid fa-arrow-right-arrow-left"></i>
                Finance Center
            </span>

            <h3>All Transactions</h3>
            <p>
                Filter AFA payments, order payments and wallet top-ups by date, type or status.
            </p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.finance.transactions') }}" class="kd-filter-card" id="transactionFilterForm">
        <div class="kd-filter-group">
            <label for="from_date">From Date</label>
            <input
                type="text"
                name="from_date"
                id="from_date"
                value="{{ $fromDate }}"
                placeholder="Select start date"
                autocomplete="off"
            >
        </div>

        <div class="kd-filter-group">
            <label for="to_date">To Date</label>
            <input
                type="text"
                name="to_date"
                id="to_date"
                value="{{ $toDate }}"
                placeholder="Select end date"
                autocomplete="off"
            >
        </div>

        <div class="kd-filter-group">
            <label for="type">Transaction Type</label>
            <select name="type" id="type">
                <option value="all" {{ $typeFilter === 'all' ? 'selected' : '' }}>All Types</option>
                <option value="wallet" {{ $typeFilter === 'wallet' ? 'selected' : '' }}>Wallet</option>
                <option value="topup" {{ $typeFilter === 'topup' ? 'selected' : '' }}>Top Ups</option>
                <option value="afa" {{ $typeFilter === 'afa' ? 'selected' : '' }}>AFA</option>
                <option value="order" {{ $typeFilter === 'order' ? 'selected' : '' }}>Orders</option>
            </select>
        </div>

        <div class="kd-filter-group">
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Status</option>
                <option value="successful" {{ $statusFilter === 'successful' ? 'selected' : '' }}>Successful</option>
                <option value="success" {{ $statusFilter === 'success' ? 'selected' : '' }}>Success</option>
                <option value="paid" {{ $statusFilter === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="processing" {{ $statusFilter === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="failed" {{ $statusFilter === 'failed' ? 'selected' : '' }}>Failed</option>
                <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        <div class="kd-filter-actions">
            <button type="submit" class="kd-filter-btn">
                <i class="fa-solid fa-filter"></i>
                Filter
            </button>

            <a href="{{ route('admin.finance.transactions') }}" class="kd-reset-btn">
                <i class="fa-solid fa-rotate-right"></i>
                Reset
            </a>
        </div>
    </form>

    <div class="kd-stats-grid">
        <div class="kd-stat-card">
            <span>Total Transactions</span>
            <h4 id="summaryTotalTransactions">{{ number_format($summary['total_transactions'] ?? 0) }}</h4>
        </div>

        <div class="kd-stat-card">
            <span>Total Amount</span>
            <h4 id="summaryTotalAmount">GHS {{ number_format((float) ($summary['total_amount'] ?? 0), 2) }}</h4>
        </div>

        <div class="kd-stat-card">
            <span>Successful</span>
            <h4 id="summarySuccessful">{{ number_format($summary['successful'] ?? 0) }}</h4>
        </div>

        <div class="kd-stat-card">
            <span>Pending</span>
            <h4 id="summaryPending">{{ number_format($summary['pending'] ?? 0) }}</h4>
        </div>
    </div>

    <div class="kd-table-card">
        <div class="kd-table-header">
            <div>
                <h4>Transaction Records</h4>
                <p>Records are loaded safely using server-side pagination for large transaction history.</p>
            </div>
        </div>

        <div class="kd-table-responsive">
            <table id="transactionsTable" class="display kd-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody></tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    $(function () {
        flatpickr('#from_date', {
            dateFormat: 'Y-m-d',
            allowInput: true
        });

        flatpickr('#to_date', {
            dateFormat: 'Y-m-d',
            allowInput: true
        });

        const table = $('#transactionsTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            order: [[7, 'desc']],
            autoWidth: false,
            scrollX: true,
            ajax: {
                url: "{{ route('admin.finance.transactions.data') }}",
                type: "GET",
                data: function (d) {
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                    d.type = $('#type').val();
                    d.status = $('#status').val();
                }
            },
            columns: [
                {
                    data: 'row_number',
                    name: 'row_number',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'user',
                    name: 'user',
                    orderable: false
                },
                {
                    data: 'type',
                    name: 'type'
                },
                {
                    data: 'reference',
                    name: 'reference'
                },
                {
                    data: 'method',
                    name: 'method'
                },
                {
                    data: 'amount',
                    name: 'amount'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'date',
                    name: 'created_at'
                }
            ],
            language: {
                search: '',
                searchPlaceholder: 'Search transactions...',
                emptyTable: 'No transactions found.',
                zeroRecords: 'No matching transactions found.',
                processing: 'Loading transactions...'
            }
        });

        $('#transactionFilterForm').on('submit', function (e) {
            e.preventDefault();
            table.ajax.reload();
        });

        $('#type, #status').on('change', function () {
            table.ajax.reload();
        });
    });
</script>
@endpush