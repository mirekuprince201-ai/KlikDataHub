<div class="kd-afa-sidebar-backdrop" id="addAfaBackdrop"></div>

<aside class="kd-afa-sidebar" id="addAfaSidebar">
    <div class="kd-afa-sidebar-header">
        <div>
            <span>AFA Management</span>
            <h2>Add Registration</h2>
            <p>Create a new AFA registration with image, amount, and status.</p>
        </div>
        <button type="button" class="kd-afa-sidebar-close" id="closeAddAfaSidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <form action="{{ route('admin.afa.store') }}" method="POST" enctype="multipart/form-data" class="kd-afa-form" id="addAfaForm">
        @csrf
        <div class="kd-afa-sidebar-body">
            <div class="kd-image-upload-box">
                <div class="kd-image-preview" id="addAfaImagePreview"><i class="fa-solid fa-image"></i></div>
                <div>
                    <label for="add_afa_image" class="kd-image-upload-btn"><i class="fa-solid fa-upload"></i> Upload Image</label>
                    <input type="file" name="registration_image" id="add_afa_image" accept="image/png,image/jpeg,image/jpg,image/webp" hidden>
                    <p>PNG, JPG, JPEG or WEBP. Max size 2MB.</p>
                </div>
            </div>

            <div class="kd-form-group">
                <label for="add_afa_name">Registration Name <span>*</span></label>
                <input type="text" name="registration_name" id="add_afa_name" placeholder="Example: MTN AFA Registration" required>
            </div>

            <div class="kd-form-group">
                <label for="add_afa_amount">Amount <span>*</span></label>
                <input type="number" name="registration_amount" id="add_afa_amount" value="0" min="0" step="0.01" required>
            </div>

            <div class="kd-form-group">
                <label for="add_afa_status">Status <span>*</span></label>
                <select name="status" id="add_afa_status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="kd-afa-sidebar-footer">
            <button type="button" class="kd-sidebar-cancel-btn" id="cancelAddAfa">Cancel</button>
            <button type="submit" class="kd-sidebar-submit-btn"><i class="fa-solid fa-circle-check"></i> Save Registration</button>
        </div>
    </form>
</aside>