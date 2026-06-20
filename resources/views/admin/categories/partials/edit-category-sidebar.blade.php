<div class="kd-category-sidebar-backdrop" id="editCategoryBackdrop"></div>

<aside class="kd-category-sidebar" id="editCategorySidebar">

    <div class="kd-category-sidebar-header">
        <div>
            <span>Category Management</span>
            <h2>Edit Category</h2>
            <p>Update category details, image, sorting, and current status.</p>
        </div>

        <button type="button" class="kd-category-sidebar-close" id="closeEditCategorySidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <form action="#"
          method="POST"
          enctype="multipart/form-data"
          class="kd-category-form"
          id="editCategoryForm">

        @csrf
        @method('PUT')

        <input type="hidden" name="category_id" id="editCategoryId">

        <div class="kd-category-sidebar-body">

            <div class="kd-image-upload-box">
                <div class="kd-image-preview" id="editCategoryImagePreview">
                    <i class="fa-solid fa-image"></i>
                </div>

                <div>
                    <label for="edit_category_image" class="kd-image-upload-btn">
                        <i class="fa-solid fa-upload"></i>
                        Change Image
                    </label>

                    <input type="file"
                           name="category_image"
                           id="edit_category_image"
                           accept="image/png,image/jpeg,image/jpg,image/webp"
                           hidden>

                    <p>PNG, JPG, JPEG or WEBP. Max size 2MB.</p>
                </div>
            </div>

            <div class="kd-form-group">
                <label for="edit_category_name">
                    Category Name <span>*</span>
                </label>

                <input type="text"
                       name="category_name"
                       id="edit_category_name"
                       placeholder="Example: MTN"
                       required>
            </div>

            <div class="kd-form-group">
                <label for="edit_category_description">
                    Description
                </label>

                <textarea name="category_description"
                          id="edit_category_description"
                          rows="4"
                          placeholder="Write a short description about this category..."></textarea>
            </div>

            <div class="kd-form-row">
                <div class="kd-form-group">
                    <label for="edit_sort_order">
                        Sort Order
                    </label>

                    <input type="number"
                           name="sort_order"
                           id="edit_sort_order"
                           min="0"
                           placeholder="0">
                </div>

                <div class="kd-form-group">
                    <label for="edit_status">
                        Status <span>*</span>
                    </label>

                    <select name="status" id="edit_status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

        </div>

        <div class="kd-category-sidebar-footer">
            <button type="button" class="kd-sidebar-cancel-btn" id="cancelEditCategory">
                Cancel
            </button>

            <button type="submit" class="kd-sidebar-submit-btn">
                <i class="fa-solid fa-circle-check"></i>
                Update Category
            </button>
        </div>

    </form>
</aside>