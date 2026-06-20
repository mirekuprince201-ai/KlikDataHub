<div class="kd-afa-sidebar-backdrop" id="editAfaBackdrop"></div>

<aside class="kd-afa-sidebar" id="editAfaSidebar">

    <div class="kd-afa-sidebar-header">
        <div>
            <span>AFA Management</span>
            <h2>Edit Registration</h2>
            <p>Update registration details, image, amount, and current status.</p>
        </div>

        <button type="button" class="kd-afa-sidebar-close" id="closeEditAfaSidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <form action="#"
          method="POST"
          enctype="multipart/form-data"
          class="kd-afa-form"
          id="editAfaForm">

        @csrf
        @method('PUT')

        <input type="hidden" name="afa_registration_id" id="editAfaId">

        <div class="kd-afa-sidebar-body">

            <div class="kd-image-upload-box">
                <div class="kd-image-preview" id="editAfaImagePreview">
                    <i class="fa-solid fa-image"></i>
                </div>

                <div>
                    <label for="edit_afa_image" class="kd-image-upload-btn">
                        <i class="fa-solid fa-upload"></i>
                        Change Image
                    </label>

                    <input type="file"
                           name="registration_image"
                           id="edit_afa_image"
                           accept="image/png,image/jpeg,image/jpg,image/webp"
                           hidden>

                    <p>PNG, JPG, JPEG or WEBP. Max size 2MB.</p>
                </div>
            </div>

            <div class="kd-form-group">
                <label for="edit_afa_name">
                    Registration Name <span>*</span>
                </label>

                <input type="text"
                       name="registration_name"
                       id="edit_afa_name"
                       placeholder="Example: MTN AFA Registration"
                       required>
            </div>

            <div class="kd-form-group">
                <label for="edit_afa_amount">
                    Amount <span>*</span>
                </label>

                <input type="number"
                       name="registration_amount"
                       id="edit_afa_amount"
                       min="0"
                       step="0.01"
                       placeholder="Example: 50.00"
                       required>
            </div>

            <div class="kd-form-group">
                <label for="edit_afa_status">
                    Status <span>*</span>
                </label>

                <select name="status" id="edit_afa_status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

        </div>

        <div class="kd-afa-sidebar-footer">
            <button type="button" class="kd-sidebar-cancel-btn" id="cancelEditAfa">
                Cancel
            </button>

            <button type="submit" class="kd-sidebar-submit-btn">
                <i class="fa-solid fa-circle-check"></i>
                Update Registration
            </button>
        </div>

    </form>
</aside>