<div class="kd-plan-sidebar-backdrop" id="editPlanBackdrop"></div>

<aside class="kd-plan-sidebar" id="editPlanSidebar">

    <div class="kd-plan-sidebar-header">
        <div>
            <span>Plan Management</span>
            <h2>Edit Plan</h2>
            <p>Update plan volume, price, category and status.</p>
        </div>

        <button type="button"
                class="kd-plan-sidebar-close"
                id="closeEditPlanSidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <form method="POST"
          action=""
          id="editPlanForm"
          class="kd-plan-form">

        @csrf
        @method('PUT')

        <input type="hidden" name="plan_id" id="editPlanId">
        <input type="hidden" name="edit_data_volume" id="edit_hidden_data_volume">
        <input type="hidden" name="plan_data_volume" id="edit_plan_data_volume">

        <div class="kd-plan-sidebar-body">

            <div class="kd-form-group">
                <label for="edit_category_id">
                    Category <span>*</span>
                </label>

                <select name="category_id"
                        id="edit_category_id"
                        required>
                    <option value="">Select Category</option>

                    @foreach($categories as $category)
                        <option value="{{ $category->category_id }}"
                                data-name="{{ $category->category_name }}"
                                data-slug="{{ $category->category_slug }}">
                            {{ $category->category_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="kd-form-row">

                <div class="kd-form-group">
                    <label for="edit_network">
                        Network
                    </label>

                    <input type="text"
                           name="network"
                           id="edit_network"
                           readonly
                           tabindex="-1"
                           class="kd-readonly-field">
                </div>

                <div class="kd-form-group">
                    <label for="edit_data_volume">
                        Data Volume (GB) <span>*</span>
                    </label>

                    <input type="text"
                           name="data_volume"
                           id="edit_data_volume"
                           placeholder="Example: 1 or 1GB"
                           required>
                </div>

            </div>

            <div class="kd-form-row">

                <div class="kd-form-group">
                    <label for="edit_selling_price">
                        Selling Price <span>*</span>
                    </label>

                    <input type="number"
                           step="0.01"
                           min="0"
                           name="selling_price"
                           id="edit_selling_price"
                           placeholder="0.00"
                           required>
                </div>

                <div class="kd-form-group">
                    <label for="edit_status">
                        Status <span>*</span>
                    </label>

                    <select name="status"
                            id="edit_status"
                            required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

            </div>

            <div class="kd-form-group">
                <label for="edit_generated_plan_name">
                    Generated Plan Name
                </label>

                <input type="text"
                       id="edit_generated_plan_name"
                       readonly
                       tabindex="-1"
                       class="kd-readonly-field">
            </div>

        </div>

        <div class="kd-plan-sidebar-footer">

            <button type="button"
                    class="kd-sidebar-cancel-btn"
                    id="cancelEditPlan">
                Cancel
            </button>

            <button type="submit"
                    class="kd-sidebar-submit-btn">
                <i class="fa-solid fa-circle-check"></i>
                Update Plan
            </button>

        </div>

    </form>

</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('editPlanForm');
    const volumeInput = document.getElementById('edit_data_volume');
    const hiddenVolume = document.getElementById('edit_hidden_data_volume');
    const backupVolume = document.getElementById('edit_plan_data_volume');

    if (!form || !volumeInput) {
        return;
    }

    form.addEventListener('submit', function () {
        const volume = (volumeInput.value || '').trim();

        if (hiddenVolume) {
            hiddenVolume.value = volume;
        }

        if (backupVolume) {
            backupVolume.value = volume;
        }
    });
});
</script>