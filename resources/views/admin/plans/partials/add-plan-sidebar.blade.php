<div class="kd-plan-sidebar-backdrop" id="addPlanBackdrop"></div>

<aside class="kd-plan-sidebar" id="addPlanSidebar">

    <div class="kd-plan-sidebar-header">
        <div>
            <span>Plan Management</span>
            <h2>Add Plans</h2>
            <p>Create one or more data bundle plans at the same time.</p>
        </div>

        <button type="button" class="kd-plan-sidebar-close" id="closeAddPlanSidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <form action="{{ route('admin.plans.store') }}"
          method="POST"
          class="kd-plan-form"
          id="addPlanForm">

        @csrf

        <div class="kd-plan-sidebar-body">

            <div class="kd-form-group">
                <label for="add_category_id">Category <span>*</span></label>

                <select name="category_id"
                        id="add_category_id"
                        class="kd-plan-category-select"
                        required>
                    <option value="">Select Category</option>

                    @foreach($categories as $category)
                        @php
                            $name = strtoupper($category->category_name ?? '');
                            $slug = strtolower($category->category_slug ?? $category->category_name ?? '');

                            $network = '';

                            if (str_contains($slug, 'mtn')) {
                                $network = 'MTN';
                            } elseif (str_contains($slug, 'telecel') || str_contains($slug, 'voda') || str_contains($slug, 'vodafone')) {
                                $network = 'TELECEL';
                            } elseif (str_contains($slug, 'airteltigo') || str_contains($slug, 'airtel') || str_contains($slug, 'tigo') || str_contains($slug, 'ishare') || str_contains($slug, 'bigtime')) {
                                $network = 'AT';
                            }
                        @endphp

                        <option value="{{ $category->category_id }}"
                                data-category-name="{{ $name }}"
                                data-network="{{ $network }}">
                            {{ $category->category_name }}
                        </option>
                    @endforeach
                </select>

                <input type="hidden" name="network" id="add_network">
            </div>

            <div class="kd-network-preview-card">
                <div>
                    <span>Selected Network</span>
                    <strong id="addNetworkPreview">Select category</strong>
                </div>

                <i class="fa-solid fa-tower-cell"></i>
            </div>

            <div class="kd-plan-bulk-header">
                <div>
                    <h3>
                        <i class="fa-solid fa-layer-group"></i>
                        Plan Sizes & Prices
                    </h3>
                    <p>Add multiple sizes and prices before saving.</p>
                </div>

                <button type="button" class="kd-add-plan-row-btn" id="addPlanRowBtn">
                    <i class="fa-solid fa-plus"></i>
                    Add Row
                </button>
            </div>

            <div class="kd-plan-bulk-list" id="addPlanRows">

                <div class="kd-plan-row" data-row="1">
                    <div class="kd-plan-row-number">1</div>

                    <div class="kd-form-row kd-plan-row-fields">
                        <div class="kd-form-group">
                            <label>Data Size <span>*</span></label>
                            <input type="text"
                                   name="plans[0][data_volume]"
                                   class="kd-data-volume-input"
                                   placeholder="Example: 1GB"
                                   required>
                        </div>

                        <div class="kd-form-group">
                            <label>Selling Price <span>*</span></label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="plans[0][selling_price]"
                                   class="kd-selling-price-input"
                                   placeholder="0.00"
                                   required>
                        </div>
                    </div>

                    <button type="button"
                            class="kd-remove-plan-row-btn"
                            title="Remove row"
                            disabled>
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>

            </div>

            <div class="kd-form-row">
                <div class="kd-form-group">
                    <label for="add_status">Status <span>*</span></label>
                    <select name="status" id="add_status" required>
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

        </div>

        <div class="kd-plan-sidebar-footer">
            <button type="button" class="kd-sidebar-cancel-btn" id="cancelAddPlan">
                Cancel
            </button>

            <button type="submit" class="kd-sidebar-submit-btn">
                <i class="fa-solid fa-circle-check"></i>
                Save Plans
            </button>
        </div>

    </form>
</aside>