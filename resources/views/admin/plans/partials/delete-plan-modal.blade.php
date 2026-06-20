<div class="kd-delete-modal-backdrop" id="deletePlanModal">

    <div class="kd-delete-modal-card">

        <div class="kd-delete-modal-icon">
            <i class="fa-solid fa-trash-can"></i>
        </div>

        <h3>Delete Plan</h3>

        <p>
            This action will permanently remove this data plan from the system.
            This action cannot be undone.
        </p>

        <form id="deletePlanForm" method="POST">

            @csrf
            @method('DELETE')

            <input type="hidden"
                   id="deletePlanId"
                   name="plan_id">

            <div class="kd-delete-modal-actions">

                <button type="button"
                        id="cancelDeletePlan"
                        class="kd-delete-cancel-btn">
                    Cancel
                </button>

                <button type="submit"
                        class="kd-delete-confirm-btn">
                    <i class="fa-solid fa-trash-can"></i>
                    Delete Plan
                </button>

            </div>

        </form>

    </div>

</div>