<div class="kd-delete-modal-backdrop" id="deleteAfaModal">

    <div class="kd-delete-modal-card">

        <div class="kd-delete-modal-icon">
            <i class="fa-solid fa-trash-can"></i>
        </div>

        <h3>Delete AFA Registration</h3>

        <p>
            This action will permanently remove this AFA registration from the
            system. This action cannot be undone.
        </p>

        <form id="deleteAfaForm" method="POST">

            @csrf
            @method('DELETE')

            <input type="hidden"
                   id="deleteAfaId"
                   name="afa_registration_id">

            <div class="kd-delete-modal-actions">

                <button type="button"
                        id="cancelDeleteAfa"
                        class="kd-delete-cancel-btn">
                    Cancel
                </button>

                <button type="submit"
                        class="kd-delete-confirm-btn">
                    <i class="fa-solid fa-trash-can"></i>
                    Delete Registration
                </button>

            </div>

        </form>

    </div>

</div>