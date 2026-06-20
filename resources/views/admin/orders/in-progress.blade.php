@extends('admin.layouts.app')

@section('title', 'In Progress Orders')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<link rel="stylesheet" href="{{ asset('admin/css/in-progress-orders.css') }}">
@endpush

@section('content')
@php
    $inProgressOrders = ($orders ?? collect())
        ->filter(fn ($order) => strtolower($order->status ?? '') === 'in_progress')
        ->sortByDesc(fn ($order) => $order->in_progress_at ?? $order->updated_at ?? $order->created_at)
        ->values();

    $nextAutoDate = !empty($nextAutoCompleteAt)
        ? \Carbon\Carbon::parse($nextAutoCompleteAt)
        : null;

    $manualCount = $inProgressOrders
        ->filter(fn ($order) => empty($order->auto_complete_at))
        ->count();
@endphp

<div class="kd-processing-orders-page">
    <div class="kd-orders-card">

        <div class="kd-orders-card-header">
            <div>
                <h4>In Progress Orders List</h4>
                <p>Orders currently being processed. Auto-timed orders move to Delivered automatically by system scheduler.</p>
            </div>

            <div class="kd-orders-header-right">
                <button
                    type="button"
                    class="kd-complete-all-manual-btn"
                    id="completeAllManualBtn"
                    {{ $manualCount <= 0 ? 'disabled' : '' }}
                >
                    <i class="fa-solid fa-circle-check"></i>
                    Complete All Manual
                </button>

                <div class="kd-processing-summary">
                    <div class="kd-processing-summary-item">
                        <span>Total Orders</span>
                        <strong id="summaryTotalOrders">{{ number_format($inProgressOrders->count()) }}</strong>
                    </div>

                    <div class="kd-processing-summary-item">
                        <span>Total Amount</span>
                        <strong id="summaryTotalAmount">
                            GHS {{ number_format($inProgressOrders->sum(fn($o) => (float) ($o->amount ?? 0)), 2) }}
                        </strong>
                    </div>

                    <div class="kd-processing-summary-item">
                        <span>Total Data</span>
                        <strong id="summaryTotalData">
                            {{ number_format($inProgressOrders->sum(fn($o) => (float) preg_replace('/[^0-9.]/', '', strtoupper($o->data_size ?? '0')))) }}GB
                        </strong>
                    </div>

                    <div class="kd-processing-summary-item">
                        <span>Manual</span>
                        <strong id="summaryManualOrders">{{ number_format($manualCount) }}</strong>
                    </div>

                    <div class="kd-processing-summary-item timer">
                        <span>Set Time</span>
                        <strong>{{ !empty($autoCompleteEnabled) ? ($timerMinutes ?? 0) . ' MINUTES' : 'OFF' }}</strong>
                    </div>

                    <div class="kd-processing-summary-item timer">
                        <span>Next Batch</span>
                        <strong
                            id="nextBatchCountdown"
                            data-next-batch-at="{{ $nextAutoDate ? $nextAutoDate->toIso8601String() : '' }}"
                        >
                            {{ $nextAutoDate ? $nextAutoDate->format('d M Y, h:i A') : 'N/A' }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="kd-orders-toolbar">
            <div class="kd-filter-box">
                <label for="planFilter">Filter Plan</label>
                <select id="planFilter">
                    <option value="">All Plans</option>
                    @foreach($inProgressOrders->pluck('plan_name')->filter()->map(fn($p) => strtoupper($p))->unique()->values() as $plan)
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
                    placeholder="Search reference, plan, phone, amount..."
                    autocomplete="off"
                >
            </div>
        </div>

        <div class="kd-orders-table-wrap">
            <table id="inProgressOrdersTable" class="kd-orders-table display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Reference</th>
                        <th>Plan</th>
                        <th>Data Size</th>
                        <th>Beneficiary</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>In Progress At</th>
                        <th>Complete Time</th>
                        <th>Countdown</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($inProgressOrders as $order)
                    @php
                        $rawDataSize = strtoupper($order->data_size ?? '');
                        $cleanDataSize = preg_replace('/[^0-9.]/', '', $rawDataSize);
                        $amount = (float) ($order->amount ?? 0);

                        $inProgressAt = !empty($order->in_progress_at)
                            ? \Carbon\Carbon::parse($order->in_progress_at)
                            : \Carbon\Carbon::parse($order->updated_at ?? $order->created_at ?? now());

                        $autoCompleteAt = !empty($order->auto_complete_at)
                            ? \Carbon\Carbon::parse($order->auto_complete_at)
                            : null;
                    @endphp

                    <tr
                        data-order-id="{{ $order->order_id }}"
                        data-status="in_progress"
                        data-amount="{{ $amount }}"
                        data-size="{{ $cleanDataSize ?: 0 }}"
                        data-auto-complete="{{ $autoCompleteAt ? '1' : '0' }}"
                    >
                        <td></td>
                        <td><span class="kd-order-ref">{{ $order->order_reference ?? 'N/A' }}</span></td>
                        <td><span class="kd-plan-name">{{ strtoupper($order->plan_name ?? 'N/A') }}</span></td>
                        <td><span class="kd-data-size">{{ $rawDataSize ?: 'N/A' }}</span></td>
                        <td><span class="kd-phone">{{ $order->beneficiary_number ?? 'N/A' }}</span></td>
                        <td><strong class="kd-amount">GHS {{ number_format($amount, 2) }}</strong></td>
                        <td><span class="kd-status-pill processing">In Progress</span></td>

                        <td data-order="{{ $inProgressAt->timestamp }}">
                            <span class="kd-date-pill">{{ $inProgressAt->format('d M Y, h:i A') }}</span>
                        </td>

                        <td data-order="{{ $autoCompleteAt ? $autoCompleteAt->timestamp : 0 }}">
                            @if($autoCompleteAt)
                                <span class="kd-date-pill">{{ $autoCompleteAt->format('d M Y, h:i A') }}</span>
                            @else
                                <span class="kd-status-pill unpaid">Manual</span>
                            @endif
                        </td>

                        <td data-order="{{ $autoCompleteAt ? $autoCompleteAt->timestamp : 0 }}">
                            @if($autoCompleteAt)
                                <span
                                    class="kd-date-pill kd-auto-countdown"
                                    data-auto-complete-at="{{ $autoCompleteAt->toIso8601String() }}"
                                >
                                    Loading...
                                </span>
                            @else
                                <span class="kd-status-pill unpaid">No Timer</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>

<div class="kd-order-modal-backdrop" id="completeOrderBackdrop"></div>

<div class="kd-order-modal" id="completeOrderModal">
    <div class="kd-order-modal-card">
        <div class="kd-order-modal-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>

        <h3>Complete Manual Orders?</h3>
        <p>You are about to mark all manual in progress orders as delivered.</p>

        <div class="kd-order-modal-actions">
            <button type="button" class="kd-modal-cancel" id="cancelCompleteOrder">Cancel</button>

            <button type="button" class="kd-modal-confirm" id="confirmCompleteOrder">
                <i class="fa-solid fa-check"></i>
                Yes, Complete All
            </button>
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

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const completeUrl = "{{ route('admin.orders.in-progress.complete') }}";
    const checkAutoCompleteUrl = "{{ route('admin.orders.in-progress.check-auto-complete') }}";

    const completeAllBtn = document.getElementById('completeAllManualBtn');
    const completeBackdrop = document.getElementById('completeOrderBackdrop');
    const completeModal = document.getElementById('completeOrderModal');
    const cancelCompleteBtn = document.getElementById('cancelCompleteOrder');
    const confirmCompleteBtn = document.getElementById('confirmCompleteOrder');
    const searchInput = document.getElementById('tableSearchInput');
    const lengthSelect = document.getElementById('tableLengthSelect');

    const table = $('#inProgressOrdersTable').DataTable({
        dom: 'rt<"kd-dt-bottom"ip>',
        pageLength: -1,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[7, 'desc']],
        searching: true,
        ordering: true,
        autoWidth: false,
        scrollX: true,
        scrollCollapse: true,
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, width: '45px' }
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'No entries available',
            zeroRecords: `
                <div class="kd-empty-orders">
                    <strong>No matching in progress orders found</strong>
                    <span>Try changing the plan filter or search keyword.</span>
                </div>
            `,
            emptyTable: `
                <div class="kd-empty-orders">
                    <strong>No in progress orders available</strong>
                    <span>Orders moved from Processing will appear here.</span>
                </div>
            `
        },
        initComplete: function () {
            initControls();
            initPlanFilter();
            updateAll();
        },
        drawCallback: function () {
            const api = this.api();

            api.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, index) {
                cell.innerHTML = index + 1;
            });

            updateAll();
        }
    });

    function initControls() {
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

    function initPlanFilter() {
        const select = document.getElementById('planFilter');

        if (!select) return;

        if (window.TomSelect && !select.tomselect) {
            new TomSelect(select, {
                create: false,
                allowEmptyOption: true,
                maxOptions: 1000,
                placeholder: 'All Plans',
                closeAfterSelect: true,
                searchField: ['text']
            });
        }

        select.addEventListener('change', function () {
            const value = this.value ? $.fn.dataTable.util.escapeRegex(this.value) : '';
            table.column(2).search(value ? '^' + value + '$' : '', true, false).draw();
        });
    }

    function updateAll() {
        setTimeout(function () {
            table.columns.adjust();
        }, 80);

        updateCountdowns();
        updateNextBatchCountdown();
        updateSummary();
    }

    function updateSummary() {
        let totalOrders = 0;
        let totalAmount = 0;
        let totalData = 0;
        let manualOrders = 0;

        table.rows().every(function () {
            const row = this.node();

            if (!row || row.dataset.status !== 'in_progress') return;

            totalOrders++;
            totalAmount += parseFloat(row.dataset.amount || 0);
            totalData += parseFloat(row.dataset.size || 0);

            if (row.dataset.autoComplete !== '1') manualOrders++;
        });

        document.getElementById('summaryTotalOrders').textContent = totalOrders.toLocaleString();

        document.getElementById('summaryTotalAmount').textContent = 'GHS ' + totalAmount.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        document.getElementById('summaryTotalData').textContent = totalData.toLocaleString() + 'GB';
        document.getElementById('summaryManualOrders').textContent = manualOrders.toLocaleString();

        if (completeAllBtn) {
            completeAllBtn.disabled = manualOrders <= 0 || totalOrders <= 0;
            completeAllBtn.classList.toggle('disabled', manualOrders <= 0 || totalOrders <= 0);
        }
    }

    function openCompleteModal() {
        if (!completeAllBtn || completeAllBtn.disabled) {
            kdToast('No manual in progress orders available to complete.', 'error');
            return;
        }

        completeBackdrop?.classList.add('show');
        completeModal?.classList.add('show');
        document.body.classList.add('kd-modal-open');
    }

    function closeCompleteModal() {
        completeBackdrop?.classList.remove('show');
        completeModal?.classList.remove('show');
        document.body.classList.remove('kd-modal-open');
    }

    completeAllBtn?.addEventListener('click', openCompleteModal);
    cancelCompleteBtn?.addEventListener('click', closeCompleteModal);
    completeBackdrop?.addEventListener('click', closeCompleteModal);

    confirmCompleteBtn?.addEventListener('click', function () {
        const button = this;
        const originalHtml = button.innerHTML;

        button.disabled = true;
        button.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Completing...`;

        fetch(completeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ complete_all_manual: true })
        })
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                throw new Error(data.message || 'Manual orders could not be completed.');
            }

            if (Array.isArray(data.order_ids)) {
                data.order_ids.forEach(orderId => removeOrderRow(orderId));
            }

            kdToast(data.message || 'Manual orders completed successfully.');
            closeCompleteModal();
        })
        .catch(error => {
            kdToast(error.message || 'Unable to complete manual orders.', 'error');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = originalHtml;
            updateSummary();
        });
    });

    function removeOrderRow(orderId) {
        const row = document.querySelector(`tr[data-order-id="${orderId}"]`);

        if (!row) return;

        row.dataset.status = 'delivered';
        table.row(row).remove().draw(false);
    }

    function checkAutoCompleteOrders() {
        fetch(checkAutoCompleteUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (!data || !data.success) return;

            if (Array.isArray(data.order_ids) && data.order_ids.length > 0) {
                data.order_ids.forEach(orderId => removeOrderRow(orderId));
                kdToast(`${data.updated || data.order_ids.length} order(s) automatically delivered.`);
            }

            const nextBatch = document.getElementById('nextBatchCountdown');

            if (nextBatch) {
                if (data.next_auto_complete_at) {
                    nextBatch.dataset.nextBatchAt = new Date(data.next_auto_complete_at).toISOString();
                } else {
                    nextBatch.dataset.nextBatchAt = '';
                    nextBatch.textContent = 'N/A';
                }
            }

            updateAll();
        })
        .catch(() => {});
    }

    function formatCountdown(diff) {
        if (diff <= 0) return 'Completing...';

        const totalSeconds = Math.floor(diff / 1000);
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        if (days > 0) return `${days}d ${hours}h ${minutes}m`;
        if (hours > 0) return `${hours}h ${minutes}m ${seconds}s`;

        return `${minutes}m ${seconds}s`;
    }

    function updateCountdowns() {
        document.querySelectorAll('.kd-auto-countdown').forEach(function (el) {
            const rawDate = el.dataset.autoCompleteAt;

            if (!rawDate) return;

            const dueDate = new Date(rawDate).getTime();
            const diff = dueDate - Date.now();

            el.textContent = formatCountdown(diff);
            el.classList.toggle('due', diff <= 0);
        });
    }

    function updateNextBatchCountdown() {
        const nextBatch = document.getElementById('nextBatchCountdown');

        if (!nextBatch) return;

        if (!nextBatch.dataset.nextBatchAt) {
            nextBatch.textContent = 'N/A';
            return;
        }

        const dueDate = new Date(nextBatch.dataset.nextBatchAt).getTime();

        if (Number.isNaN(dueDate)) {
            nextBatch.textContent = 'N/A';
            return;
        }

        nextBatch.textContent = formatCountdown(dueDate - Date.now());
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
        }, 3500);
    }

    updateAll();

    setInterval(updateCountdowns, 1000);
    setInterval(updateNextBatchCountdown, 1000);
    setInterval(checkAutoCompleteOrders, 5000);

    checkAutoCompleteOrders();

    window.addEventListener('resize', function () {
        setTimeout(() => table.columns.adjust(), 120);
    });
});
</script>
@endpush