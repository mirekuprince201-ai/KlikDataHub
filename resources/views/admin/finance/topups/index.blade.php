@extends('admin.layouts.app')

@section('title', 'Wallet Top Ups')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<link rel="stylesheet" href="{{ asset('admin/css/topups.css') }}">
@endpush

@section('content')
@php
    $users = $users ?? collect();
    $transactions = $transactions ?? collect();
@endphp

<div class="kd-topups-page">
    <div class="kd-topups-card">

        <div class="kd-topups-header">
            <div>
                <span class="kd-page-badge">
                    <i class="fa-solid fa-circle-plus"></i>
                    Top Ups
                </span>

                <h4>Wallet Credit & Debit</h4>
                <p>Credit or debit customer wallet balances securely with SMS alerts.</p>
            </div>

            <button type="button" class="kd-topup-btn" id="openTopupModalBtn">
                <i class="fa-solid fa-wallet"></i>
                New Adjustment
            </button>
        </div>

        <div class="kd-topups-summary">
            <div class="kd-summary-item">
                <span>Total Users</span>
                <strong>{{ number_format($totalUsers ?? $users->count()) }}</strong>
            </div>

            <div class="kd-summary-item">
                <span>Total Wallet</span>
                <strong>GHS {{ number_format((float) ($totalWallet ?? 0), 2) }}</strong>
            </div>

            <div class="kd-summary-item">
                <span>Admin Credits</span>
                <strong>GHS {{ number_format((float) ($totalCredits ?? 0), 2) }}</strong>
            </div>

            <div class="kd-summary-item">
                <span>Admin Debits</span>
                <strong>GHS {{ number_format((float) ($totalDebits ?? 0), 2) }}</strong>
            </div>
        </div>

        <div class="kd-topups-toolbar">
            <div class="kd-filter-box">
                <label for="typeFilter">Type</label>
                <select id="typeFilter">
                    <option value="">All Types</option>
                    <option value="CREDIT">Credit</option>
                    <option value="DEBIT">Debit</option>
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
                <label for="tableSearchInput">Search</label>
                <input type="search" id="tableSearchInput" placeholder="Search user, phone, reference..." autocomplete="off">
            </div>
        </div>

        <div class="kd-topups-table-wrap">
            <table id="topupsTable" class="kd-topups-table display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Before</th>
                        <th>After</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($transactions as $txn)
                        @php
                            $type = strtoupper($txn->transaction_type ?? 'N/A');
                            $createdAt = !empty($txn->created_at) ? \Carbon\Carbon::parse($txn->created_at) : null;
                            $name = strtoupper($txn->full_name ?? 'N/A');
                        @endphp

                        <tr>
                            <td></td>

                            <td>
                                <div class="kd-user-cell">
                                    <div class="kd-user-avatar">{{ substr($name, 0, 1) }}</div>
                                    <div>
                                        <strong>{{ $name }}</strong>
                                        <span>{{ $txn->phone ?? 'N/A' }} · {{ $txn->email ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td data-type="{{ $type }}">
                                <span class="kd-type-pill {{ strtolower($type) }}">{{ $type }}</span>
                            </td>

                            <td><strong>GHS {{ number_format((float) ($txn->amount ?? 0), 2) }}</strong></td>
                            <td>GHS {{ number_format((float) ($txn->balance_before ?? 0), 2) }}</td>

                            <td>
                                <strong class="kd-balance-after">
                                    GHS {{ number_format((float) ($txn->balance_after ?? 0), 2) }}
                                </strong>
                            </td>

                            <td><span class="kd-ref">{{ $txn->transaction_reference ?? 'N/A' }}</span></td>
                            <td>{{ $txn->description ?? 'N/A' }}</td>

                            <td data-order="{{ $createdAt ? $createdAt->timestamp : 0 }}">
                                {{ $createdAt ? $createdAt->format('d M Y, h:i A') : 'N/A' }}
                            </td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>

<div class="kd-topup-backdrop" id="topupBackdrop"></div>

<div class="kd-topup-modal" id="topupModal">
    <div class="kd-topup-modal-header">
        <div>
            <span>
                <i class="fa-solid fa-wallet"></i>
                Wallet Adjustment
            </span>
            <h4>Credit / Debit User Wallet</h4>
            <p>Select a user, choose transaction type and enter the amount.</p>
        </div>

        <button type="button" id="closeTopupModalBtn">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <form method="POST" action="{{ route('admin.finance.topups.store') }}" id="topupForm" autocomplete="off">
        @csrf

        <div class="kd-form-grid">
            <div class="kd-form-group kd-full">
                <label for="user_id">User</label>

                <select name="user_id" id="user_id" required>
                    <option value="" disabled selected hidden>Search and select user</option>

                    @foreach($users as $user)
                        @php
                            $userName = strtoupper($user->full_name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));
                            $userPhone = $user->phone ?? 'N/A';
                            $userEmail = $user->email ?? 'N/A';
                            $userBalance = (float) ($user->wallet_balance ?? 0);
                            $userStatus = strtoupper($user->account_status ?? 'N/A');
                        @endphp

                        <option
                            value="{{ $user->user_id }}"
                            data-name="{{ $userName }}"
                            data-phone="{{ $userPhone }}"
                            data-email="{{ $userEmail }}"
                            data-balance="{{ $userBalance }}"
                            data-status="{{ $userStatus }}"
                        >
                            {{ $userName }} | {{ $userPhone }} | GHS {{ number_format($userBalance, 2) }}
                        </option>
                    @endforeach
                </select>

                <small class="kd-field-hint">
                    Search by name, phone, email or wallet balance.
                </small>
            </div>

            <div class="kd-form-group">
                <label for="transaction_type">Transaction Type</label>
                <select name="transaction_type" id="transaction_type" required>
                    <option value="">Select type</option>
                    <option value="credit">Credit Wallet</option>
                    <option value="debit">Debit Wallet</option>
                </select>
            </div>

            <div class="kd-form-group">
                <label for="amount">Amount</label>
                <input type="number" name="amount" id="amount" min="0.01" step="0.01" placeholder="0.00" required>
                <small class="kd-field-hint" id="amountHint">Enter the adjustment amount.</small>
            </div>

            <div class="kd-form-group kd-full">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="4" maxlength="500" placeholder="Example: Manual wallet correction by admin"></textarea>
            </div>
        </div>

        <div class="kd-modal-actions">
            <button type="button" class="kd-cancel-btn" id="cancelTopupModalBtn">
                Cancel
            </button>

            <button type="submit" class="kd-submit-btn" id="submitTopupBtn">
                <i class="fa-solid fa-check"></i>
                Save Adjustment
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('topupModal');
    const backdrop = document.getElementById('topupBackdrop');
    const openBtn = document.getElementById('openTopupModalBtn');
    const closeBtn = document.getElementById('closeTopupModalBtn');
    const cancelBtn = document.getElementById('cancelTopupModalBtn');
    const form = document.getElementById('topupForm');
    const submitBtn = document.getElementById('submitTopupBtn');

    const userSelect = document.getElementById('user_id');
    const typeSelect = document.getElementById('transaction_type');
    const amountInput = document.getElementById('amount');
    const amountHint = document.getElementById('amountHint');

    let topupUserSelect = null;
    let isSubmitting = false;

    function money(value) {
        return 'GHS ' + Number(value || 0).toLocaleString('en-GH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function getSelectedOption() {
        if (!userSelect || !userSelect.value) return null;
        return userSelect.querySelector(`option[value="${CSS.escape(userSelect.value)}"]`);
    }

    function getSelectedBalance() {
        const option = getSelectedOption();
        return Number(option?.dataset?.balance || 0);
    }

    function updateAmountMessage() {
        const type = typeSelect?.value || '';
        const amount = Number(amountInput?.value || 0);
        const balance = getSelectedBalance();

        amountHint.classList.remove('kd-danger-text');

        if (!type) {
            amountHint.textContent = 'Enter the adjustment amount.';
            submitBtn.disabled = false;
            return;
        }

        if (type === 'credit') {
            amountHint.textContent = 'Credit amount will be added to the wallet.';
            submitBtn.disabled = false;
            return;
        }

        if (type === 'debit' && amount > balance) {
            amountHint.textContent = 'Insufficient balance for this debit.';
            amountHint.classList.add('kd-danger-text');
            submitBtn.disabled = true;
            return;
        }

        if (type === 'debit') {
            amountHint.textContent = 'Debit amount cannot be more than current balance.';
            submitBtn.disabled = false;
        }
    }

    function openModal() {
        backdrop?.classList.add('active');
        modal?.classList.add('active');
        document.body.classList.add('kd-modal-open');

        setTimeout(function () {
            topupUserSelect ? topupUserSelect.focus() : userSelect?.focus();
        }, 180);
    }

    function closeModal() {
        backdrop?.classList.remove('active');
        modal?.classList.remove('active');
        document.body.classList.remove('kd-modal-open');
    }

    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeModal();
    });

    if (window.TomSelect && userSelect) {
        topupUserSelect = new TomSelect('#user_id', {
            create: false,
            allowEmptyOption: false,
            maxItems: 1,
            plugins: ['dropdown_input'],
            placeholder: 'Search user by name, phone or email...',
            searchField: ['text', 'name', 'phone', 'email'],
            sortField: [{ field: 'name', direction: 'asc' }],
            render: {
                option: function (data, escape) {
                    const name = escape(data.name || data.text || 'N/A');
                    const phone = escape(data.phone || 'N/A');
                    const email = escape(data.email || 'N/A');
                    const status = escape(data.status || 'N/A');
                    const balance = money(data.balance || 0);

                    return `
                        <div class="kd-ts-user-option">
                            <div class="kd-ts-avatar">${name.charAt(0)}</div>
                            <div class="kd-ts-info">
                                <strong>${name}</strong>
                                <span>${phone} · ${email}</span>
                            </div>
                            <div class="kd-ts-right">
                                <b>${balance}</b>
                                <small>${status}</small>
                            </div>
                        </div>
                    `;
                },
                item: function (data, escape) {
                    const name = escape(data.name || data.text || 'N/A');
                    const phone = escape(data.phone || 'N/A');
                    const balance = money(data.balance || 0);

                    return `
                        <div class="kd-ts-user-item">
                            <strong>${name}</strong>
                            <span>${phone} · ${balance}</span>
                        </div>
                    `;
                },
                no_results: function () {
                    return `
                        <div class="kd-ts-empty">
                            <strong>No user found</strong>
                            <span>Try searching with another name, phone or email.</span>
                        </div>
                    `;
                }
            },
            onChange: updateAmountMessage
        });
    }

    userSelect?.addEventListener('change', updateAmountMessage);
    typeSelect?.addEventListener('change', updateAmountMessage);
    amountInput?.addEventListener('input', updateAmountMessage);

    form?.addEventListener('submit', function (event) {
        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        const balance = getSelectedBalance();
        const type = typeSelect?.value || '';
        const amount = Number(amountInput?.value || 0);

        if (!userSelect?.value || !type || amount <= 0) {
            event.preventDefault();
            alert('Please select user, transaction type and valid amount.');
            return;
        }

        if (type === 'debit' && amount > balance) {
            event.preventDefault();
            amountHint.textContent = 'Insufficient balance for this debit.';
            amountHint.classList.add('kd-danger-text');
            return;
        }

        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Saving...`;
    });

    if (window.jQuery && $.fn.DataTable) {
        const table = $('#topupsTable').DataTable({
            dom: 'rt<"kd-dt-bottom"ip>',
            pageLength: -1,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            order: [[8, 'desc']],
            searching: true,
            ordering: true,
            autoWidth: false,
            scrollX: true,
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, width: '45px' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ adjustments',
                infoEmpty: 'No wallet adjustments available',
                zeroRecords: `
                    <div class="kd-empty-state">
                        <strong>No matching records found</strong>
                        <span>Try another search or filter.</span>
                    </div>
                `,
                emptyTable: `
                    <div class="kd-empty-state">
                        <strong>No wallet adjustments yet</strong>
                        <span>Admin credits and debits will appear here.</span>
                    </div>
                `
            },
            drawCallback: function () {
                const api = this.api();
                api.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, index) {
                    cell.innerHTML = index + 1;
                });
            }
        });

        document.getElementById('tableSearchInput')?.addEventListener('input', function () {
            table.search(this.value).draw();
        });

        document.getElementById('tableLengthSelect')?.addEventListener('change', function () {
            table.page.len(parseInt(this.value, 10)).draw();
        });

        const typeFilter = document.getElementById('typeFilter');

        if (typeFilter) {
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'topupsTable') return true;

                const selectedType = typeFilter.value;
                if (!selectedType) return true;

                const row = table.row(dataIndex).node();
                const rowType = row.querySelector('td[data-type]')?.dataset.type || '';

                return rowType === selectedType;
            });

            typeFilter.addEventListener('change', function () {
                table.draw();
            });
        }
    }

    updateAmountMessage();
});
</script>
@endpush