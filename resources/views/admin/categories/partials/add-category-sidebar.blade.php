<div class="kd-category-sidebar-backdrop" id="addCategoryBackdrop"></div>

<aside class="kd-category-sidebar" id="addCategorySidebar">

    <div class="kd-category-sidebar-header">
        <div>
            <span>Category Management</span>
            <h2>Add Category</h2>
            <p>Create a new data category or network bundle group.</p>
        </div>

        <button type="button" class="kd-category-sidebar-close" id="closeAddCategorySidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <form action="{{ route('admin.categories.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="kd-category-form"
          id="addCategoryForm">

        @csrf

        <div class="kd-category-sidebar-body">

            <div class="kd-image-upload-box">
                <div class="kd-image-preview" id="addCategoryImagePreview">
                    <i class="fa-solid fa-image"></i>
                </div>

                <div>
                    <label for="add_category_image" class="kd-image-upload-btn">
                        <i class="fa-solid fa-upload"></i>
                        Upload Image
                    </label>

                    <input type="file"
                           name="category_image"
                           id="add_category_image"
                           accept="image/png,image/jpeg,image/jpg,image/webp"
                           hidden>

                    <p>PNG, JPG, JPEG or WEBP. Max size 2MB.</p>
                </div>
            </div>

            <div class="kd-form-group">
                <label for="add_category_name">
                    Category Name <span>*</span>
                </label>

                <input type="text"
                       name="category_name"
                       id="add_category_name"
                       value="{{ old('category_name') }}"
                       placeholder="Example: MTN"
                       required>
            </div>

            <div class="kd-form-group">
                <label for="add_category_description">
                    Description
                </label>

                <textarea name="category_description"
                          id="add_category_description"
                          rows="4"
                          placeholder="Write a short description about this category...">{{ old('category_description') }}</textarea>
            </div>

            <div class="kd-form-row">
                <div class="kd-form-group">
                    <label for="add_sort_order">
                        Sort Order
                    </label>

                    <input type="number"
                           name="sort_order"
                           id="add_sort_order"
                           value="{{ old('sort_order', 0) }}"
                           min="0"
                           placeholder="0">
                </div>

                <div class="kd-form-group">
                    <label for="add_status">
                        Status <span>*</span>
                    </label>

                    <select name="status" id="add_status" required>
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>
                            Active
                        </option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>
                    </select>
                </div>
            </div>

        </div>

        <div class="kd-category-sidebar-footer">
            <button type="button" class="kd-sidebar-cancel-btn" id="cancelAddCategory">
                Cancel
            </button>

            <button type="submit" class="kd-sidebar-submit-btn">
                <i class="fa-solid fa-circle-check"></i>
                Save Category
            </button>
        </div>

    </form>
</aside>