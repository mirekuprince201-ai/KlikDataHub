$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | DataTable
    |--------------------------------------------------------------------------
    */

    const table = $('#planTable').DataTable({
        pageLength: 25,
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, 'All']
        ],
        responsive: false,
        scrollX: true,
        autoWidth: false,
        order: [[0, 'asc']],
        columnDefs: [
            {
                targets: 'no-export',
                orderable: false,
                searchable: false
            }
        ],
        dom: 'Blfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fa-solid fa-file-excel"></i> Export Excel',
                title: 'Plans',
                exportOptions: {
                    columns: ':not(.no-export)'
                }
            }
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search plans...',
            lengthMenu: 'Show _MENU_ records',
            info: 'Showing _START_ to _END_ of _TOTAL_ plans',
            emptyTable: 'No plan records found',
            zeroRecords: 'No matching plan found',
            infoEmpty: 'No plans available',
            infoFiltered: '(filtered from _MAX_ total plans)'
        }
    });

    table.buttons().container().appendTo('#planExportContainer');

    /*
    |--------------------------------------------------------------------------
    | Category Filter - Fixed
    |--------------------------------------------------------------------------
    */

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable.id !== 'planTable') {
            return true;
        }

        const selectedCategory = String($('#categoryFilter').val() || '')
            .trim()
            .toLowerCase();

        if (!selectedCategory) {
            return true;
        }

        const rowNode = table.row(dataIndex).node();

        const rowCategory = String(
            $(rowNode).find('td.kd-col-category').attr('data-category') || ''
        )
            .trim()
            .toLowerCase();

        return rowCategory === selectedCategory;
    });

    $('#categoryFilter').on('change', function () {
        table.draw();
    });

    if ($('#categoryFilter').val()) {
        table.draw();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    function lockBody() {
        $('body').addClass('kd-sidebar-open');
    }

    function unlockBody() {
        $('body').removeClass('kd-sidebar-open');
    }

    function closeAllSidebars() {
        $('.kd-plan-sidebar').removeClass('show');
        $('.kd-plan-sidebar-backdrop').removeClass('show');
        unlockBody();
    }

    function normalStatus(status) {
        return String(status || 'inactive').toLowerCase() === 'active' ? 'active' : 'inactive';
    }

    function toastError(message) {
        if (window.kdToast) {
            window.kdToast.error(message);
        } else {
            alert(message);
        }
    }

    function normalizeVolume(value) {
        let cleaned = String(value || '')
            .toUpperCase()
            .replace(/\s+/g, '')
            .replace(/GB/g, '')
            .replace(/[^0-9.]/g, '');

        if (!cleaned) {
            return '';
        }

        const parts = cleaned.split('.');

        if (parts.length > 2) {
            cleaned = parts[0] + '.' + parts.slice(1).join('');
        }

        cleaned = cleaned.replace(/^0+(\d)/, '$1');

        if (cleaned.startsWith('.')) {
            cleaned = '0' + cleaned;
        }

        return cleaned + 'GB';
    }

    function volumeNumberOnly(value) {
        return String(value || '')
            .toUpperCase()
            .replace(/\s+/g, '')
            .replace(/GB/g, '')
            .replace(/[^0-9.]/g, '');
    }

    function getNetworkFromText(value) {
        const text = String(value || '').toLowerCase();

        if (text.includes('mtn')) {
            return 'MTN';
        }

        if (text.includes('telecel') || text.includes('voda') || text.includes('vodafone')) {
            return 'TELECEL';
        }

        if (
            text.includes('airteltigo') ||
            text.includes('airtel') ||
            text.includes('tigo') ||
            text.includes('ishare') ||
            text.includes('bigtime')
        ) {
            return 'AT';
        }

        return '';
    }

    function getAddSelectedCategoryOption() {
        return $('#add_category_id').find('option:selected');
    }

    function getAddSelectedNetworkFromCategory() {
        const selected = getAddSelectedCategoryOption();
        const dataNetwork = String(selected.data('network') || '').toUpperCase();
        const categoryName = String(selected.data('category-name') || selected.text() || '');

        return dataNetwork || getNetworkFromText(categoryName);
    }

    function updateAddNetworkPreview() {
        const network = getAddSelectedNetworkFromCategory();

        $('#add_network').val(network || '');
        $('#addNetworkPreview').text(network || 'Select category');

        if (network) {
            $('#addNetworkPreview').addClass('has-network');
        } else {
            $('#addNetworkPreview').removeClass('has-network');
        }
    }

    function getEditSelectedCategoryText() {
        return $('#edit_category_id option:selected').text().trim();
    }

    function getEditSelectedNetworkFromCategory() {
        const selected = $('#edit_category_id option:selected');
        const dataNetwork = String(selected.data('network') || '').toUpperCase();
        const categoryName = String(selected.data('category-name') || selected.text() || '');

        return dataNetwork || getNetworkFromText(categoryName);
    }

    function updateEditGeneratedPlan() {
        const categoryText = getEditSelectedCategoryText();
        const network = getEditSelectedNetworkFromCategory();
        const volume = normalizeVolume($('#edit_data_volume').val());

        $('#edit_network').val(network || '');

        if (categoryText && volume) {
            $('#edit_generated_plan_name').val(categoryText + ' ' + volume);
        } else {
            $('#edit_generated_plan_name').val('');
        }
    }

    function renumberPlanRows() {
        $('#addPlanRows .kd-plan-row').each(function (index) {
            const rowNumber = index + 1;

            $(this).attr('data-row', rowNumber);
            $(this).find('.kd-plan-row-number').text(rowNumber);

            $(this).find('.kd-data-volume-input').attr('name', 'plans[' + index + '][data_volume]');
            $(this).find('.kd-selling-price-input').attr('name', 'plans[' + index + '][selling_price]');
        });

        const rowsCount = $('#addPlanRows .kd-plan-row').length;

        $('#addPlanRows .kd-remove-plan-row-btn').prop('disabled', rowsCount <= 1);
    }

    function createPlanRow(index) {
        return `
            <div class="kd-plan-row" data-row="${index + 1}">
                <div class="kd-plan-row-number">${index + 1}</div>

                <div class="kd-form-row kd-plan-row-fields">
                    <div class="kd-form-group">
                        <label>Data Size <span>*</span></label>
                        <input type="text"
                               name="plans[${index}][data_volume]"
                               class="kd-data-volume-input"
                               placeholder="Example: 1GB"
                               required>
                    </div>

                    <div class="kd-form-group">
                        <label>Selling Price <span>*</span></label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="plans[${index}][selling_price]"
                               class="kd-selling-price-input"
                               placeholder="0.00"
                               required>
                    </div>
                </div>

                <button type="button"
                        class="kd-remove-plan-row-btn"
                        title="Remove row">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        `;
    }

    function resetAddPlanRows() {
        $('#addPlanRows').html(createPlanRow(0));
        renumberPlanRows();
    }

    function resetAddPlanForm() {
        const form = $('#addPlanForm')[0];

        if (form) {
            form.reset();
        }

        $('#add_status').val('active');
        $('#add_network').val('');
        $('#addNetworkPreview').text('Select category').removeClass('has-network');

        resetAddPlanRows();

        const button = $('#addPlanForm').find('.kd-sidebar-submit-btn');
        button.prop('disabled', false);
        button.html('<i class="fa-solid fa-circle-check"></i> Save Plans');
    }

    function resetEditPlanForm() {
        const form = $('#editPlanForm')[0];

        if (form) {
            form.reset();
        }

        $('#editPlanForm').attr('action', '');
        $('#editPlanId').val('');
        $('#edit_network').val('');
        $('#edit_generated_plan_name').val('');
        $('#edit_status').val('active');

        const button = $('#editPlanForm').find('.kd-sidebar-submit-btn');
        button.prop('disabled', false);
        button.html('<i class="fa-solid fa-circle-check"></i> Update Plan');
    }

    function resetDeletePlanModal() {
        $('#deletePlanId').val('');
        $('#deletePlanForm').attr('action', '');

        const button = $('#deletePlanForm').find('.kd-delete-confirm-btn');
        button.prop('disabled', false);
        button.css({
            opacity: '',
            cursor: ''
        });
        button.html('<i class="fa-solid fa-trash-can"></i> Delete Plan');
    }

    function closeDeletePlanModal() {
        $('#deletePlanModal').removeClass('show');
        unlockBody();

        setTimeout(function () {
            resetDeletePlanModal();
        }, 250);
    }

    function loadPlanDetails(showUrl, onSuccess, onError) {
        $.ajax({
            url: showUrl,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (!response.status || !response.plan) {
                    toastError('Unable to load plan details.');

                    if (typeof onError === 'function') {
                        onError();
                    }

                    return;
                }

                onSuccess(response.plan);
            },
            error: function () {
                toastError('Plan details could not be loaded.');

                if (typeof onError === 'function') {
                    onError();
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Add Plan Sidebar
    |--------------------------------------------------------------------------
    */

    $(document).on('click', '#openAddPlanSidebar', function () {
        closeAllSidebars();

        $('#addPlanSidebar').addClass('show');
        $('#addPlanBackdrop').addClass('show');

        updateAddNetworkPreview();
        renumberPlanRows();
        lockBody();
    });

    $(document).on('click', '#closeAddPlanSidebar, #cancelAddPlan, #addPlanBackdrop', function () {
        $('#addPlanSidebar').removeClass('show');
        $('#addPlanBackdrop').removeClass('show');
        unlockBody();

        setTimeout(function () {
            resetAddPlanForm();
        }, 250);
    });

    $(document).on('change', '#add_category_id', function () {
        updateAddNetworkPreview();
    });

    /*
    |--------------------------------------------------------------------------
    | Add More Plans At Once
    |--------------------------------------------------------------------------
    */

    $(document).on('click', '#addPlanRowBtn', function () {
        const rowsCount = $('#addPlanRows .kd-plan-row').length;

        $('#addPlanRows').append(createPlanRow(rowsCount));
        renumberPlanRows();

        $('#addPlanRows .kd-plan-row').last().find('.kd-data-volume-input').focus();
    });

    $(document).on('click', '.kd-remove-plan-row-btn', function () {
        if ($('#addPlanRows .kd-plan-row').length <= 1) {
            return;
        }

        $(this).closest('.kd-plan-row').remove();
        renumberPlanRows();
    });

    /*
    |--------------------------------------------------------------------------
    | Auto Add GB To Add Sizes
    |--------------------------------------------------------------------------
    */

    $(document).on('blur', '.kd-data-volume-input', function () {
        this.value = normalizeVolume(this.value);
    });

    $(document).on('input', '.kd-data-volume-input', function () {
        this.value = volumeNumberOnly(this.value);
    });

    /*
    |--------------------------------------------------------------------------
    | Edit Plan Sidebar - Load Existing Details
    |--------------------------------------------------------------------------
    */

    $(document).on('click', '.edit-plan-btn', function () {
        const planId = $(this).data('id');
        const showUrl = $(this).data('show-url');
        const updateUrl = $(this).data('update-url');

        if (!showUrl || !updateUrl) {
            toastError('Plan route URL is missing.');
            return;
        }

        closeAllSidebars();
        resetEditPlanForm();

        $('#editPlanId').val(planId);
        $('#editPlanForm').attr('action', updateUrl);

        $('#editPlanSidebar').addClass('show');
        $('#editPlanBackdrop').addClass('show');
        lockBody();

        const button = $('#editPlanForm').find('.kd-sidebar-submit-btn');

        button.prop('disabled', true);
        button.html('<i class="fa-solid fa-spinner fa-spin"></i> Loading...');

        loadPlanDetails(showUrl, function (plan) {
            $('#editPlanId').val(plan.plan_id || planId);
            $('#edit_category_id').val(plan.category_id || '');
            $('#edit_data_volume').val(volumeNumberOnly(plan.data_volume || ''));
            $('#edit_selling_price').val(plan.selling_price || 0);
            $('#edit_status').val(normalStatus(plan.status));

            updateEditGeneratedPlan();

            button.prop('disabled', false);
            button.html('<i class="fa-solid fa-circle-check"></i> Update Plan');
        }, function () {
            button.prop('disabled', false);
            button.html('<i class="fa-solid fa-circle-check"></i> Update Plan');
        });
    });

    $(document).on('click', '#closeEditPlanSidebar, #cancelEditPlan, #editPlanBackdrop', function () {
        $('#editPlanSidebar').removeClass('show');
        $('#editPlanBackdrop').removeClass('show');
        unlockBody();

        setTimeout(function () {
            resetEditPlanForm();
        }, 250);
    });

    $(document).on('change', '#edit_category_id', function () {
        updateEditGeneratedPlan();
    });

    $(document).on('input', '#edit_data_volume', function () {
        this.value = volumeNumberOnly(this.value);
        updateEditGeneratedPlan();
    });

    $(document).on('blur', '#edit_data_volume', function () {
        this.value = volumeNumberOnly(this.value);
        updateEditGeneratedPlan();
    });

    /*
    |--------------------------------------------------------------------------
    | Submit Validation + Loading
    |--------------------------------------------------------------------------
    */

    $(document).on('submit', '#addPlanForm', function (event) {
        const categoryId = $('#add_category_id').val();
        const network = $('#add_network').val();
        let hasInvalidRow = false;

        if (!categoryId) {
            event.preventDefault();
            toastError('Please select a category.');
            return;
        }

        if (!network) {
            event.preventDefault();
            toastError('The selected category does not have a valid network.');
            return;
        }

        $('#addPlanRows .kd-plan-row').each(function () {
            const volumeInput = $(this).find('.kd-data-volume-input');
            const priceInput = $(this).find('.kd-selling-price-input');

            volumeInput.val(normalizeVolume(volumeInput.val()));

            const volume = volumeInput.val();
            const price = priceInput.val();

            if (!volume || !price || Number(price) < 0) {
                hasInvalidRow = true;
            }
        });

        if (hasInvalidRow) {
            event.preventDefault();
            toastError('Please complete all plan sizes and selling prices.');
            return;
        }

        const button = $(this).find('.kd-sidebar-submit-btn');

        button.prop('disabled', true);
        button.html('<i class="fa-solid fa-spinner fa-spin"></i> Saving Plans...');
    });

    $(document).on('submit', '#editPlanForm', function (event) {
        const categoryId = $('#edit_category_id').val();
        const network = $('#edit_network').val();
        const dataVolume = normalizeVolume($('#edit_data_volume').val());
        const price = $('#edit_selling_price').val();

        if (!categoryId) {
            event.preventDefault();
            toastError('Please select a category.');
            return;
        }

        if (!network) {
            event.preventDefault();
            toastError('The selected category does not have a valid network.');
            return;
        }

        if (!dataVolume || !price || Number(price) < 0) {
            event.preventDefault();
            toastError('Please enter valid data volume and selling price.');
            return;
        }

        $('#edit_data_volume').val(dataVolume);

        const button = $(this).find('.kd-sidebar-submit-btn');

        button.prop('disabled', true);
        button.html('<i class="fa-solid fa-spinner fa-spin"></i> Updating...');
    });

    /*
    |--------------------------------------------------------------------------
    | Status Switch Loading
    |--------------------------------------------------------------------------
    */

    $(document).on('submit', '.kd-status-switch-form', function () {
        const button = $(this).find('.kd-status-switch');

        button.prop('disabled', true);
        button.css('opacity', '.65');
    });

    /*
    |--------------------------------------------------------------------------
    | Delete Plan Modal
    |--------------------------------------------------------------------------
    */

    $(document).on('click', '.delete-plan-btn', function () {
        const planId = $(this).data('id');
        const deleteUrl = $(this).data('url');

        closeAllSidebars();

        $('#deletePlanId').val(planId);
        $('#deletePlanForm').attr('action', deleteUrl);
        $('#deletePlanModal').addClass('show');

        lockBody();
    });

    $(document).on('click', '#closeDeletePlanModal, #cancelDeletePlan', function () {
        closeDeletePlanModal();
    });

    $(document).on('click', '#deletePlanModal', function (event) {
        if (event.target.id === 'deletePlanModal') {
            closeDeletePlanModal();
        }
    });

    $(document).on('submit', '#deletePlanForm', function () {
        const button = $(this).find('.kd-delete-confirm-btn');

        button.prop('disabled', true);
        button.css({
            opacity: '.85',
            cursor: 'not-allowed'
        });

        button.html('<i class="fa-solid fa-spinner fa-spin"></i> Deleting...');
    });

    /*
    |--------------------------------------------------------------------------
    | DataTable Adjust After Sidebar Collapse / Resize
    |--------------------------------------------------------------------------
    */

    $(document).on('click', '#sidebarToggle, #collapseAdminSidebar, .kd-sidebar-collapse-btn', function () {
        setTimeout(function () {
            if ($.fn.DataTable.isDataTable('#planTable')) {
                $('#planTable').DataTable().columns.adjust().draw(false);
            }
        }, 350);
    });

    $(window).on('resize', function () {
        if ($.fn.DataTable.isDataTable('#planTable')) {
            $('#planTable').DataTable().columns.adjust();
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Init
    |--------------------------------------------------------------------------
    */

    updateAddNetworkPreview();
    renumberPlanRows();

    /*
    |--------------------------------------------------------------------------
    | Escape Key Close
    |--------------------------------------------------------------------------
    */

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllSidebars();
            closeDeletePlanModal();
        }
    });
});