<div class="kd-user-sidebar-backdrop" id="addUserBackdrop"></div>

<div class="kd-user-sidebar" id="addUserSidebar">

    <div class="kd-user-sidebar-header">
        <div>
            <span class="kd-sidebar-badge">
                <i class="fa-solid fa-user-plus"></i>
                New User
            </span>

            <h4>Add User</h4>
            <p>Create a new customer account with login access.</p>
        </div>

        <button type="button" class="kd-sidebar-close" id="closeAddUserSidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}" id="addUserForm" class="kd-user-form">
        @csrf

        <input type="hidden" name="wallet_balance" value="0">

        <div class="kd-form-grid">

            <div class="kd-form-group">
                <label>First Name <span>*</span></label>
                <input type="text" name="first_name" required maxlength="80" placeholder="Enter first name">
            </div>

            <div class="kd-form-group">
                <label>Last Name <span>*</span></label>
                <input type="text" name="last_name" required maxlength="80" placeholder="Enter last name">
            </div>

            <div class="kd-form-group kd-full">
                <label>Phone Number <span>*</span></label>
                <input type="text" name="phone" required maxlength="20" placeholder="0551234567">
            </div>

            <div class="kd-form-group kd-full">
                <label>Email Address <span>*</span></label>
                <input type="email" name="email" required maxlength="120" placeholder="user@example.com">
            </div>

            <div class="kd-form-group kd-full">
                <label>Password <span>*</span></label>
                <input type="password" name="password" required minlength="6" placeholder="Enter password">
            </div>

            <div class="kd-form-group kd-full">
                <label>Account Status <span>*</span></label>
                <select name="account_status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

        </div>

        <div class="kd-sidebar-footer">
            <button type="button" class="kd-btn-cancel" id="cancelAddUserBtn">
                Cancel
            </button>

            <button type="submit" class="kd-btn-save">
                <i class="fa-solid fa-floppy-disk"></i>
                Create User
            </button>
        </div>
    </form>

</div>