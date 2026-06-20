<div class="kd-user-sidebar-backdrop" id="editUserBackdrop"></div>

<div class="kd-user-sidebar" id="editUserSidebar">

    <div class="kd-user-sidebar-header">
        <div>
            <span class="kd-sidebar-badge">
                <i class="fa-solid fa-user-pen"></i>
                Edit User
            </span>

            <h4>Edit User</h4>
            <p>Update customer account details and status.</p>
        </div>

        <button type="button" class="kd-sidebar-close" id="closeEditUserSidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <form method="POST" action="#" id="editUserForm" class="kd-user-form">
        @csrf
        @method('PUT')

        <input type="hidden" name="wallet_balance" id="edit_wallet_balance" value="0">

        <div class="kd-form-grid">

            <div class="kd-form-group">
                <label>First Name <span>*</span></label>
                <input type="text" name="first_name" id="edit_first_name" required maxlength="80">
            </div>

            <div class="kd-form-group">
                <label>Last Name <span>*</span></label>
                <input type="text" name="last_name" id="edit_last_name" required maxlength="80">
            </div>

            <div class="kd-form-group kd-full">
                <label>Phone Number <span>*</span></label>
                <input type="text" name="phone" id="edit_phone" required maxlength="20">
            </div>

            <div class="kd-form-group kd-full">
                <label>Email Address <span>*</span></label>
                <input type="email" name="email" id="edit_email" required maxlength="120">
            </div>

            <div class="kd-form-group kd-full">
                <label>New Password</label>
                <input type="password" name="password" id="edit_password" minlength="6" placeholder="Leave blank to keep current password">
            </div>

            <div class="kd-form-group kd-full">
                <label>Account Status <span>*</span></label>
                <select name="account_status" id="edit_account_status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

        </div>

        <div class="kd-sidebar-footer">
            <button type="button" class="kd-btn-cancel" id="cancelEditUserBtn">
                Cancel
            </button>

            <button type="submit" class="kd-btn-save">
                <i class="fa-solid fa-floppy-disk"></i>
                Update User
            </button>
        </div>
    </form>

</div>