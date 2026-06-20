@extends('admin.layouts.app')

@section('title', 'Users')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="{{ asset('admin/css/admin-users.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/admin-user-sidebar.css') }}">

<style>
    .kd-delete-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(2, 6, 23, .72);
        backdrop-filter: blur(8px);
        z-index: 99998;
        opacity: 0;
        visibility: hidden;
        transition: .25s ease;
    }

    .kd-delete-modal-backdrop.active {
        opacity: 1;
        visibility: visible;
    }

    .kd-delete-modal {
        position: fixed;
        left: 50%;
        top: 50%;
        width: min(440px, calc(100% - 30px));
        background: #fff;
        border-radius: 24px;
        padding: 28px;
        transform: translate(-50%, -50%) scale(.92);
        opacity: 0;
        visibility: hidden;
        z-index: 99999;
        box-shadow: 0 30px 90px rgba(15, 23, 42, .28);
        transition: .25s ease;
        text-align: center;
    }

    .kd-delete-modal.active {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, -50%) scale(1);
    }

    .kd-delete-icon {
        width: 72px;
        height: 72px;
        border-radius: 22px;
        margin: 0 auto 18px;
        display: grid;
        place-items: center;
        background: rgba(220, 38, 38, .1);
        color: #dc2626;
        font-size: 30px;
    }

    .kd-delete-modal h3 {
        margin: 0 0 8px;
        color: #0f172a;
        font-size: 22px;
        font-weight: 800;
    }

    .kd-delete-modal p {
        margin: 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
    }

    .kd-delete-user-name {
        margin-top: 14px;
        padding: 12px 14px;
        border-radius: 16px;
        background: #f8fafc;
        color: #0f172a;
        font-weight: 800;
        font-size: 14px;
    }

    .kd-delete-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }

    .kd-delete-actions button {
        flex: 1;
        border: 0;
        border-radius: 14px;
        padding: 13px 16px;
        font-weight: 800;
        cursor: pointer;
        transition: .2s ease;
    }

    .kd-cancel-delete {
        background: #f1f5f9;
        color: #334155;
    }

    .kd-confirm-delete {
        background: linear-gradient(135deg, #ef4444, #b91c1c);
        color: #fff;
        box-shadow: 0 14px 28px rgba(220, 38, 38, .25);
    }

    .kd-confirm-delete:hover {
        transform: translateY(-1px);
    }

    body.dark-mode .kd-delete-modal {
        background: #0f172a;
        border: 1px solid rgba(148, 163, 184, .18);
    }

    body.dark-mode .kd-delete-modal h3,
    body.dark-mode .kd-delete-user-name {
        color: #fff;
    }

    body.dark-mode .kd-delete-modal p {
        color: #94a3b8;
    }

    body.dark-mode .kd-delete-user-name,
    body.dark-mode .kd-cancel-delete {
        background: #111827;
        color: #cbd5e1;
    }
</style>
@endpush

@section('content')
@php
    $users = $users ?? collect();
@endphp

<div class="kd-users-page">
    <div class="kd-users-card">

        <div class="kd-users-header">
            <div>
                <span class="kd-page-badge">
                    <i class="fa-solid fa-users"></i>
                    Users
                </span>

                <h4>Users Management</h4>
                <p>Add, edit and manage registered customer accounts.</p>
            </div>

            <button type="button" class="kd-add-user-btn" id="openAddUserBtn">
                <i class="fa-solid fa-plus"></i>
                Add User
            </button>
        </div>

        <div class="kd-users-summary">
            <div class="kd-summary-item">
                <span>Total Users</span>
                <strong>{{ number_format($totalUsers ?? $users->count()) }}</strong>
            </div>

            <div class="kd-summary-item">
                <span>Active</span>
                <strong>{{ number_format($activeUsers ?? $users->where('account_status', 'active')->count()) }}</strong>
            </div>

            <div class="kd-summary-item">
                <span>Inactive</span>
                <strong>{{ number_format($inactiveUsers ?? $users->where('account_status', 'inactive')->count()) }}</strong>
            </div>

            <div class="kd-summary-item">
                <span>Total Wallet</span>
                <strong>GHS {{ number_format($totalWallet ?? $users->sum(fn($u) => (float) ($u->wallet_balance ?? 0)), 2) }}</strong>
            </div>
        </div>

        <div class="kd-users-toolbar">
            <div class="kd-filter-box">
                <label for="statusFilter">Status</label>
                <select id="statusFilter">
                    <option value="">All Status</option>
                    <option value="ACTIVE">Active</option>
                    <option value="INACTIVE">Inactive</option>
                    <option value="SUSPENDED">Suspended</option>
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
                <label for="tableSearchInput">Search Users</label>
                <input type="search" id="tableSearchInput" placeholder="Search name, phone, email..." autocomplete="off">
            </div>
        </div>

        <div class="kd-users-table-wrap">
            <table id="usersTable" class="kd-users-table display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Wallet</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Joined</th>
                        <th class="no-sort">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($users as $user)
                        @php
                            $firstName = $user->first_name ?? '';
                            $lastName = $user->last_name ?? '';
                            $fullName = strtoupper($user->full_name ?: trim($firstName . ' ' . $lastName));
                            $initials = strtoupper(substr($firstName ?: 'U', 0, 1) . substr($lastName ?: '', 0, 1));
                            $status = strtoupper($user->account_status ?? 'inactive');
                            $joinedAt = !empty($user->created_at) ? \Carbon\Carbon::parse($user->created_at) : null;
                            $lastLoginAt = !empty($user->last_login_at) ? \Carbon\Carbon::parse($user->last_login_at) : null;
                        @endphp

                        <tr>
                            <td></td>

                            <td>
                                <div class="kd-user-cell">
                                    <div class="kd-user-avatar">
                                        @if(!empty($user->profile_image))
                                            <img src="{{ asset($user->profile_image) }}" alt="{{ $fullName }}">
                                        @else
                                            <span>{{ $initials }}</span>
                                        @endif
                                    </div>

                                    <div>
                                        <strong>{{ $fullName ?: 'N/A' }}</strong>
                                    </div>
                                </div>
                            </td>

                            <td>{{ $user->phone ?? 'N/A' }}</td>
                            <td>{{ $user->email ?? 'N/A' }}</td>

                            <td>
                                <strong class="kd-wallet">
                                    GHS {{ number_format((float) ($user->wallet_balance ?? 0), 2) }}
                                </strong>
                            </td>

                            <td data-status="{{ $status }}">
                                <span class="kd-status-pill {{ strtolower($user->account_status ?? 'inactive') }}">
                                    {{ $status }}
                                </span>
                            </td>

                            <td data-order="{{ $lastLoginAt ? $lastLoginAt->timestamp : 0 }}">
                                {{ $lastLoginAt ? $lastLoginAt->format('d M Y, h:i A') : 'Never' }}
                            </td>

                            <td data-order="{{ $joinedAt ? $joinedAt->timestamp : 0 }}">
                                {{ $joinedAt ? $joinedAt->format('d M Y, h:i A') : 'N/A' }}
                            </td>

                            <td>
                                <div class="kd-action-group">
                                    <button type="button" class="kd-action-btn edit" data-user-id="{{ $user->user_id }}" title="Edit User">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="kd-action-btn delete kd-open-delete-modal"
                                        data-user-id="{{ $user->user_id }}"
                                        data-user-name="{{ $fullName ?: 'N/A' }}"
                                        title="Delete User">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>

@include('admin.users.partials.add-sidebar')
@include('admin.users.partials.edit-sidebar')

<div class="kd-delete-modal-backdrop" id="deleteUserBackdrop"></div>

<div class="kd-delete-modal" id="deleteUserModal">
    <div class="kd-delete-icon">
        <i class="fa-solid fa-trash-can"></i>
    </div>

    <h3>Delete User?</h3>

    <p>
        This action will permanently delete this user account.
        Please confirm before continuing.
    </p>

    <div class="kd-delete-user-name" id="deleteUserName">N/A</div>

    <form method="POST" id="deleteUserForm">
        @csrf
        @method('DELETE')

        <div class="kd-delete-actions">
            <button type="button" class="kd-cancel-delete" id="cancelDeleteUserBtn">
                Cancel
            </button>

            <button type="submit" class="kd-confirm-delete" id="confirmDeleteUserBtn">
                <i class="fa-solid fa-trash"></i>
                Delete
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.remove('kd-user-drawer-open', 'kd-sidebar-open');

    const usersBaseUrl = @json(url('/admin/users'));

    const addSidebar = document.getElementById('addUserSidebar');
    const addBackdrop = document.getElementById('addUserBackdrop');
    const openAddBtn = document.getElementById('openAddUserBtn');
    const closeAddBtn = document.getElementById('closeAddUserSidebar');
    const cancelAddBtn = document.getElementById('cancelAddUserBtn');
    const addForm = document.getElementById('addUserForm');

    const editSidebar = document.getElementById('editUserSidebar');
    const editBackdrop = document.getElementById('editUserBackdrop');
    const closeEditBtn = document.getElementById('closeEditUserSidebar');
    const cancelEditBtn = document.getElementById('cancelEditUserBtn');
    const editForm = document.getElementById('editUserForm');

    const deleteModal = document.getElementById('deleteUserModal');
    const deleteBackdrop = document.getElementById('deleteUserBackdrop');
    const deleteForm = document.getElementById('deleteUserForm');
    const deleteUserName = document.getElementById('deleteUserName');
    const cancelDeleteBtn = document.getElementById('cancelDeleteUserBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteUserBtn');

    function moveToBody(...elements) {
        elements.forEach(function (el) {
            if (el && el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
        });
    }

    moveToBody(addBackdrop, addSidebar, editBackdrop, editSidebar, deleteBackdrop, deleteModal);

    function openSidebar(sidebar, backdrop) {
        moveToBody(backdrop, sidebar);
        backdrop?.classList.add('active');
        sidebar?.classList.add('active');
        document.body.classList.add('kd-sidebar-open');
    }

    function closeAllSidebars() {
        [addSidebar, editSidebar].forEach(el => el?.classList.remove('active'));
        [addBackdrop, editBackdrop].forEach(el => el?.classList.remove('active'));
        document.body.classList.remove('kd-sidebar-open');
    }

    function openDeleteModal(userId, name) {
        deleteUserName.textContent = name || 'N/A';
        deleteForm.action = `${usersBaseUrl}/${userId}`;

        deleteBackdrop.classList.add('active');
        deleteModal.classList.add('active');
        document.body.classList.add('kd-sidebar-open');
    }

    function closeDeleteModal() {
        deleteBackdrop.classList.remove('active');
        deleteModal.classList.remove('active');
        document.body.classList.remove('kd-sidebar-open');

        setTimeout(function () {
            deleteForm.action = '';
            deleteUserName.textContent = 'N/A';
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.innerHTML = `<i class="fa-solid fa-trash"></i> Delete`;
        }, 200);
    }

    openAddBtn?.addEventListener('click', function () {
        openSidebar(addSidebar, addBackdrop);

        setTimeout(function () {
            addForm?.querySelector('input[name="first_name"]')?.focus();
        }, 250);
    });

    closeAddBtn?.addEventListener('click', closeAllSidebars);
    cancelAddBtn?.addEventListener('click', closeAllSidebars);
    addBackdrop?.addEventListener('click', closeAllSidebars);

    closeEditBtn?.addEventListener('click', closeAllSidebars);
    cancelEditBtn?.addEventListener('click', closeAllSidebars);
    editBackdrop?.addEventListener('click', closeAllSidebars);

    cancelDeleteBtn?.addEventListener('click', closeDeleteModal);
    deleteBackdrop?.addEventListener('click', closeDeleteModal);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllSidebars();
            closeDeleteModal();
        }
    });

    function loadUser(userId, callback) {
        fetch(`${usersBaseUrl}/${userId}`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'User not found.');
                return;
            }

            callback(data.user);
        })
        .catch(() => {
            alert('Unable to load user details.');
        });
    }

    document.querySelectorAll('.kd-action-btn.edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const userId = this.dataset.userId;

            loadUser(userId, function (user) {
                document.getElementById('edit_first_name').value = user.first_name || '';
                document.getElementById('edit_last_name').value = user.last_name || '';
                document.getElementById('edit_phone').value = user.phone || '';
                document.getElementById('edit_email').value = user.email || '';
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_wallet_balance').value = user.wallet_balance || 0;
                document.getElementById('edit_account_status').value = user.account_status || 'active';

                editForm.action = `${usersBaseUrl}/${user.user_id}`;

                openSidebar(editSidebar, editBackdrop);

                setTimeout(function () {
                    document.getElementById('edit_first_name')?.focus();
                }, 250);
            });
        });
    });

    document.querySelectorAll('.kd-open-delete-modal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openDeleteModal(this.dataset.userId, this.dataset.userName);
        });
    });

    deleteForm?.addEventListener('submit', function () {
        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Deleting...`;
    });

    [addForm, editForm].forEach(function (form) {
        form?.querySelectorAll('input[name="first_name"], input[name="last_name"]').forEach(function (input) {
            input.addEventListener('input', function () {
                const cursorStart = this.selectionStart;
                const cursorEnd = this.selectionEnd;
                this.value = this.value.toUpperCase();
                this.setSelectionRange(cursorStart, cursorEnd);
            });
        });

        form?.querySelector('input[name="phone"]')?.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9+]/g, '');
        });

        form?.addEventListener('submit', function () {
            const submitBtn = this.querySelector('button[type="submit"]');

            if (submitBtn) {
                const loadingText = this.id === 'editUserForm' ? 'Updating...' : 'Creating...';
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${loadingText}`;
            }
        });
    });

    if (!window.jQuery || !$.fn.DataTable) {
        console.error('jQuery or DataTables is not loaded.');
        return;
    }

    const table = $('#usersTable').DataTable({
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
            { targets: 0, orderable: false, searchable: false, width: '45px' },
            { targets: -1, orderable: false, searchable: false },
            { targets: 'no-sort', orderable: false }
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ users',
            infoEmpty: 'No users available',
            zeroRecords: `
                <div class="kd-empty-users">
                    <strong>No matching users found</strong>
                    <span>Try another search or filter.</span>
                </div>
            `,
            emptyTable: `
                <div class="kd-empty-users">
                    <strong>No users available</strong>
                    <span>Registered users will appear here.</span>
                </div>
            `
        },
        drawCallback: function () {
            const api = this.api();

            api.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, index) {
                cell.innerHTML = index + 1;
            });

            setTimeout(function () {
                table.columns.adjust();
            }, 80);
        },
        initComplete: function () {
            setTimeout(function () {
                table.columns.adjust();
            }, 120);
        }
    });

    document.getElementById('tableSearchInput')?.addEventListener('input', function () {
        table.search(this.value).draw();
    });

    document.getElementById('tableLengthSelect')?.addEventListener('change', function () {
        table.page.len(parseInt(this.value, 10)).draw();
    });

    const statusFilter = document.getElementById('statusFilter');

    if (statusFilter) {
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'usersTable') return true;

            const selectedStatus = statusFilter.value;

            if (!selectedStatus) return true;

            const row = table.row(dataIndex).node();
            const rowStatus = row.querySelector('td[data-status]')?.dataset.status || '';

            return rowStatus === selectedStatus;
        });

        statusFilter.addEventListener('change', function () {
            table.draw();
        });
    }

    window.addEventListener('resize', function () {
        setTimeout(function () {
            table.columns.adjust();
        }, 120);
    });
});
</script>
@endpush