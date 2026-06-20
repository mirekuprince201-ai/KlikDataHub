<div class="kd-delete-modal-backdrop" id="deleteCategoryModal">

    <div class="kd-delete-modal-card">

        <div class="kd-delete-modal-icon">
            <i class="fa-solid fa-trash-can"></i>
        </div>

        <h3>Delete Category</h3>

        <p>
            This action will permanently remove this category from the system.
            This action cannot be undone.
        </p>

        <form id="deleteCategoryForm" method="POST">

            @csrf
            @method('DELETE')

            <input type="hidden"
                   id="deleteCategoryId"
                   name="category_id">

            <div class="kd-delete-modal-actions">

                <button type="button"
                        id="cancelDeleteCategory"
                        class="kd-delete-cancel-btn">
                    Cancel
                </button>

                <button type="submit"
                        class="kd-delete-confirm-btn">
                    <i class="fa-solid fa-trash-can"></i>
                    Delete Category
                </button>

            </div>

        </form>

    </div>

</div>