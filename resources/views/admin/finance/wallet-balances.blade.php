@extends('admin.layouts.app')

@section('title', 'Wallet Balances')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="{{ asset('admin/css/wallet-balances.css') }}">
@endpush

@section('content')

@php
    use Carbon\Carbon;
@endphp

<div class="kd-wallet-page">

    <div class="kd-wallet-header">
        <div>
            <span class="kd-page-badge">
                <i class="fa-solid fa-wallet"></i>
                Wallet Management
            </span>

            <h3>Wallet Balances</h3>

            <p>
                View all customer wallet balances, credits, debits and complete wallet transaction history.
            </p>
        </div>
    </div>

    <div class="kd-wallet-stats">

        <div class="kd-stat-card">
            <div class="kd-stat-icon blue">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <span>Total Users</span>
                <h4>{{ number_format($totalUsers ?? 0) }}</h4>
            </div>
        </div>

        <div class="kd-stat-card">
            <div class="kd-stat-icon green">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div>
                <span>Total Wallet Balance</span>
                <h4>GHS {{ number_format($totalWalletBalance ?? 0, 2) }}</h4>
            </div>
        </div>

        <div class="kd-stat-card">
            <div class="kd-stat-icon emerald">
                <i class="fa-solid fa-arrow-down"></i>
            </div>
            <div>
                <span>Total Credits</span>
                <h4>GHS {{ number_format($totalCredit ?? 0, 2) }}</h4>
            </div>
        </div>

        <div class="kd-stat-card">
            <div class="kd-stat-icon red">
                <i class="fa-solid fa-arrow-up"></i>
            </div>
            <div>
                <span>Total Debits</span>
                <h4>GHS {{ number_format($totalDebit ?? 0, 2) }}</h4>
            </div>
        </div>

        <div class="kd-stat-card">
            <div class="kd-stat-icon purple">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <span>Active Wallets</span>
                <h4>{{ number_format($activeWallets ?? 0) }}</h4>
            </div>
        </div>

        <div class="kd-stat-card">
            <div class="kd-stat-icon orange">
                <i class="fa-solid fa-circle-minus"></i>
            </div>
            <div>
                <span>Zero Balance</span>
                <h4>{{ number_format($zeroWallets ?? 0) }}</h4>
            </div>
        </div>

    </div>

    <div class="kd-wallet-card">

        <div class="kd-card-header">
            <div>
                <h4>Customer Wallet Balances</h4>
                <p>{{ number_format(($users ?? collect())->count()) }} wallet account(s)</p>
            </div>
        </div>

        <div class="table-responsive">

            <table id="walletTable" class="display nowrap kd-wallet-table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Wallet Balance</th>
                        <th>Total Credit</th>
                        <th>Total Debit</th>
                        <th>Transactions</th>
                        <th>Created</th>
                        <th class="no-export">Action</th>
                    </tr>
                </thead>

                <tbody>
                @foreach(($users ?? collect()) as $user)

                    @php
                        $displayName = strtoupper(
                            trim($user->display_name ?: (($user->first_name ?? '') . ' ' . ($user->last_name ?? '')))
                        ) ?: 'CUSTOMER';

                        $walletHistory = collect($user->wallet_history ?? [])->values();
                    @endphp

                    <tr>
                        <td>{{ $loop->iteration }}</td>

                        <td>
                            <div class="kd-user-box">
                                <div class="kd-avatar">
                                    {{ strtoupper(substr($displayName, 0, 1)) }}
                                </div>

                                <div>
                                    <strong>{{ $displayName }}</strong>
                                    {{-- <small>ID: {{ $user->user_id ?? 'N/A' }}</small> --}}
                                </div>
                            </div>
                        </td>

                        <td>{{ $user->phone ?? 'N/A' }}</td>
                        <td>{{ $user->email ?? 'N/A' }}</td>

                        <td>
                            <span class="kd-balance-pill">
                                GHS {{ number_format($user->wallet_balance ?? 0, 2) }}
                            </span>
                        </td>

                        <td class="text-success">
                            GHS {{ number_format($user->total_credit ?? 0, 2) }}
                        </td>

                        <td class="text-danger">
                            GHS {{ number_format($user->total_debit ?? 0, 2) }}
                        </td>

                        <td>{{ number_format($user->total_transactions ?? 0) }}</td>

                        <td>
                            {{ !empty($user->created_at) ? Carbon::parse($user->created_at)->format('d M Y') : 'N/A' }}
                        </td>

                        <td class="no-export">
                            <button
                                type="button"
                                class="kd-action-btn kd-view-wallet-btn"
                                data-name="{{ e($displayName) }}"
                                data-user-id="{{ e($user->user_id ?? 'N/A') }}"
                                data-phone="{{ e($user->phone ?? 'N/A') }}"
                                data-email="{{ e($user->email ?? 'N/A') }}"
                                data-balance="{{ number_format($user->wallet_balance ?? 0, 2) }}"
                                data-credit="{{ number_format($user->total_credit ?? 0, 2) }}"
                                data-debit="{{ number_format($user->total_debit ?? 0, 2) }}"
                                data-transactions="{{ number_format($user->total_transactions ?? 0) }}"
                                data-history='@json($walletHistory)'
                            >
                                <i class="fa-solid fa-eye"></i>
                                View History
                            </button>
                        </td>
                    </tr>

                @endforeach
                </tbody>
            </table>

        </div>

    </div>

</div>

<div id="kdWalletPortal">
    <div class="kd-wallet-overlay" id="walletOverlay"></div>

    <aside class="kd-wallet-sidebar" id="walletSidebar">

        <div class="kd-sidebar-header">
            <div>
                <span>Wallet Profile</span>
                <h4 id="sidebarName">Customer</h4>
                {{-- <p id="sidebarUserId">ID: N/A</p> --}}
            </div>

            <button type="button" class="kd-sidebar-close" id="closeWalletSidebar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="kd-sidebar-body">

            <div class="kd-profile-card">
                <div class="kd-profile-avatar" id="sidebarAvatar">C</div>

                <div>
                    <h5 id="profileName">Customer</h5>
                    <p id="profilePhone">N/A</p>
                    <p id="profileEmail">N/A</p>
                </div>
            </div>

            <div class="kd-wallet-mini-stats">
                <div>
                    <span>Balance</span>
                    <strong id="sidebarBalance">GHS 0.00</strong>
                </div>

                <div>
                    <span>Total Credit</span>
                    <strong class="green" id="sidebarCredit">GHS 0.00</strong>
                </div>

                <div>
                    <span>Total Debit</span>
                    <strong class="red" id="sidebarDebit">GHS 0.00</strong>
                </div>

                <div>
                    <span>Transactions</span>
                    <strong id="sidebarTransactions">0</strong>
                </div>
            </div>

            <div class="kd-history-section">
                <div class="kd-history-title">
                    <h5>Wallet History</h5>
                    <span id="historyCount">0 record(s)</span>
                </div>

                <div class="kd-history-list" id="walletHistoryList">
                    <div class="kd-empty-history">
                        <i class="fa-solid fa-receipt"></i>
                        <p>No wallet history found.</p>
                    </div>
                </div>
            </div>

        </div>

    </aside>
</div>

@endsection

@push('scripts')

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
$(function () {

    const portal = $('#kdWalletPortal');

    if (portal.length) {
        portal.appendTo('body');
    }

    $('#walletTable').DataTable({
        pageLength: 25,
        scrollX: true,
        order: [[4, 'desc']],

        dom:
            '<"kd-dt-top"lfB>' +
            'rt' +
            '<"kd-dt-bottom"ip>',

        buttons: [
            {
                extend: 'copyHtml5',
                text: '<i class="fa-solid fa-copy"></i> Copy',
                className: 'kd-dt-btn',
                exportOptions: {
                    columns: ':not(.no-export)'
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fa-solid fa-file-excel"></i> Export Excel',
                className: 'kd-dt-btn',
                title: 'Wallet Balances',
                exportOptions: {
                    columns: ':not(.no-export)'
                }
            }
        ],

        language: {
            search: '',
            searchPlaceholder: 'Search wallets...',
            lengthMenu: 'Show _MENU_ records'
        }
    });

    const sidebar = $('#walletSidebar');
    const overlay = $('#walletOverlay');
    const historyList = $('#walletHistoryList');

    function openSidebar() {
        sidebar.addClass('show');
        overlay.addClass('show');
        $('body').addClass('kd-sidebar-open');
    }

    function closeSidebar() {
        sidebar.removeClass('show');
        overlay.removeClass('show');
        $('body').removeClass('kd-sidebar-open');
    }

    function money(value) {
        const amount = parseFloat(value || 0);

        return 'GHS ' + amount.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function cleanText(value) {
        return $('<div>').text(value || 'N/A').html();
    }

    function badgeClass(type) {
        type = String(type || '').toLowerCase();

        if (type.includes('credit') || type.includes('topup') || type.includes('top-up')) {
            return 'credit';
        }

        if (type.includes('debit') || type.includes('payment') || type.includes('withdraw')) {
            return 'debit';
        }

        return 'neutral';
    }

    $(document).on('click', '.kd-view-wallet-btn', function () {
        const btn = $(this);

        const name = btn.data('name') || 'Customer';
        const phone = btn.data('phone') || 'N/A';
        const email = btn.data('email') || 'N/A';
        const userId = btn.data('user-id') || 'N/A';
        const balance = btn.data('balance') || '0.00';
        const credit = btn.data('credit') || '0.00';
        const debit = btn.data('debit') || '0.00';
        const transactions = btn.data('transactions') || '0';

        let history = btn.attr('data-history') || '[]';

        try {
            history = JSON.parse(history);
        } catch (e) {
            history = [];
        }

        $('#sidebarName').text(name);
        $('#profileName').text(name);
        $('#profilePhone').text(phone);
        $('#profileEmail').text(email);
        $('#sidebarUserId').text('ID: ' + userId);
        $('#sidebarAvatar').text(String(name).charAt(0).toUpperCase());

        $('#sidebarBalance').text('GHS ' + balance);
        $('#sidebarCredit').text('GHS ' + credit);
        $('#sidebarDebit').text('GHS ' + debit);
        $('#sidebarTransactions').text(transactions);

        $('#historyCount').text(history.length + ' record(s)');

        historyList.empty();

        if (!history.length) {
            historyList.html(`
                <div class="kd-empty-history">
                    <i class="fa-solid fa-receipt"></i>
                    <p>No wallet history found for this customer.</p>
                </div>
            `);
        } else {
            history.forEach(function (item) {
                const type = cleanText(item.type || 'N/A');
                const recordType = cleanText(item.record_type || 'Wallet Record');
                const source = cleanText(item.source || 'N/A');
                const amount = money(item.amount);
                const charges = money(item.charges);
                const totalAmount = money(item.total_amount);
                const before = money(item.balance_before);
                const after = money(item.balance_after);
                const reference = cleanText(item.reference || 'N/A');
                const description = cleanText(item.description || 'Wallet transaction');
                const status = cleanText(item.status || 'N/A');
                const paymentMethod = cleanText(item.payment_method || 'N/A');
                const provider = cleanText(item.provider || 'N/A');
                const channel = cleanText(item.channel || 'N/A');
                const gatewayResponse = cleanText(item.gateway_response || 'N/A');
                const createdAt = cleanText(item.created_at || 'N/A');
                const cls = badgeClass(type);

                historyList.append(`
                    <div class="kd-history-item">
                        <div class="kd-history-top">
                            <span class="kd-history-badge ${cls}">${type}</span>
                            <strong>${amount}</strong>
                        </div>

                        <p>${description}</p>

                        <div class="kd-history-grid">
                            <span><small>Record Type</small>${recordType}</span>
                            <span><small>Source</small>${source}</span>
                            <span><small>Before</small>${before}</span>
                            <span><small>After</small>${after}</span>
                            <span><small>Charges</small>${charges}</span>
                            <span><small>Total Amount</small>${totalAmount}</span>
                            <span><small>Payment Method</small>${paymentMethod}</span>
                            <span><small>Provider / Channel</small>${provider} / ${channel}</span>
                        </div>

                        <div class="kd-history-bottom">
                            <span>
                                <i class="fa-solid fa-hashtag"></i>
                                ${reference}
                            </span>

                            <span>
                                <i class="fa-regular fa-clock"></i>
                                ${createdAt}
                            </span>
                        </div>

                        <div class="kd-history-response">
                            <small>Gateway Response</small>
                            <span>${gatewayResponse}</span>
                        </div>

                        <div class="kd-history-status">
                            ${status}
                        </div>
                    </div>
                `);
            });
        }

        openSidebar();
    });

    $(document).on('click', '#closeWalletSidebar, #walletOverlay', closeSidebar);

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });

});
</script>

@endpush