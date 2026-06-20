$(document).ready(function () {
    /*
    |--------------------------------------------------------------------------
    | DataTable
    |--------------------------------------------------------------------------
    */

    const table = $('#categoryTable').DataTable({
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
                title: 'Categories',
                exportOptions: {
                    columns: ':not(.no-export)'
                }
            }
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search categories...',
            lengthMenu: 'Show _MENU_ records',
            info: 'Showing _START_ to _END_ of _TOTAL_ categories',
            emptyTable: 'No category records found',
            zeroRecords: 'No matching category found',
            infoEmpty: 'No categories available',
            infoFiltered: '(filtered from _MAX_ total categories)'
        }
    });

    table.buttons().container().appendTo('#categoryExportContainer');

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
        $('.kd-category-sidebar').removeClass('show');
        $('.kd-category-sidebar-backdrop').removeClass('show');
        unlockBody();
    }

    function imageUrl(path) {
        if (!path) {
            return null;
        }

        if (String(path).startsWith('http://') || String(path).startsWith('https://')) {
            return path;
        }

        return '/' + String(path).replace(/^\/+/, '');
    }

    function safeText(value, fallback = 'N/A') {
        if (value === null || value === undefined || value === '') {
            return fallback;
        }

        return value;
    }

    function formatStatus(status) {
        return String(status || 'inactive').toLowerCase() === 'active' ? 'active' : 'inactive';
    }

    function formatStatusLabel(status) {
        return formatStatus(status) === 'active' ? 'Active' : 'Inactive';
    }

    function formatDate(value) {
        if (!value) {
            return 'N/A';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return 'N/A';
        }

        return date.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }).replace(/ /g, ' ');
    }

    function resetAddCategoryForm() {
        const form = $('#addCategoryForm')[0];

        if (form) {
            form.reset();
        }

        $('#addCategoryImagePreview').html('<i class="fa-solid fa-image"></i>');
        $('#add_sort_order').val(0);
        $('#add_status').val('active');

        const button = $('#addCategoryForm').find('.kd-sidebar-submit-btn');
        button.prop('disabled', false);
        button.html('<i class="fa-solid fa-circle-check"></i> Save Category');
    }

    function resetEditCategoryForm() {
        const form = $('#editCategoryForm')[0];

        if (form) {
            form.reset();
        }

        $('#editCategoryForm').attr('action', '#');
        $('#editCategoryId').val('');
        $('#editCategoryImagePreview').html('<i class="fa-solid fa-image"></i>');
        $('#edit_sort_order').val(0);
        $('#edit_status').val('active');

        const button = $('#editCategoryForm').find('.kd-sidebar-submit-btn');
        button.prop('disabled', false);
        button.html('<i class="fa-solid fa-circle-check"></i> Update Category');
    }

    function setPreview(previewSelector, imagePath) {
        const preview = $(previewSelector);
        const src = imageUrl(imagePath);

        if (src) {
            preview.html('<img src="' + src + '" alt="Category Image">');
            return;
        }

        preview.html('<i class="fa-solid fa-image"></i>');
    }

    function previewImage(input, previewSelector) {
        const file = input.files[0];
        const preview = $(previewSelector);

        if (!file) {
            preview.html('<i class="fa-solid fa-image"></i>');
            return;
        }

        if (!file.type.match('image.*')) {
            input.value = '';
            preview.html('<i class="fa-solid fa-image"></i>');

            if (window.kdToast) {
                window.kdToast.error('Please select a valid image file.');
            }

            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            input.value = '';
            preview.html('<i class="fa-solid fa-image"></i>');

            if (window.kdToast) {
                window.kdToast.error('Image size must not exceed 2MB.');
            }

            return;
        }

        const reader = new FileReader();

        reader.onload = function (event) {
            preview.html('<img src="' + event.target.result + '" alt="Preview">');
        };

        reader.readAsDataURL(file);
    }

    function loadCategoryDetails(showUrl, onSuccess, onError) {
        $.ajax({
            url: showUrl,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (!response.status || !response.category) {
                    if (window.kdToast) {
                        window.kdToast.error('Unable to load category details.');
                    }

                    if (typeof onError === 'function') {
                        onError();
                    }

                    return;
                }

                onSuccess(response.category);
            },
            error: function () {
                if (window.kdToast) {
                    window.kdToast.error('Category details could not be loaded.');
                }

                if (typeof onError === 'function') {
                    onError();
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Add Category Sidebar
    |--------------------------------------------------------------------------
    */

    $(document).on('click', '#openAddCategorySidebar', function () {
        closeAllSidebars();

        $('#addCategorySidebar').addClass('show');
        $('#addCategoryBackdrop').addClass('show');
        lockBody();
    });

    $(document).on('click', '#closeAddCategorySidebar, #cancelAddCategory, #addCategoryBackdrop', function () {
        $('#addCategorySidebar').removeClass('show');
        $('#addCategoryBackdrop').removeClass('show');
        unlockBody();

        setTimeout(function () {
            resetAddCategoryForm();
        }, 250);
    });

    /*
    |--------------------------------------------------------------------------
    | Add Image Preview
    |--------------------------------------------------------------------------
    */

    $(document).on('change', '#add_category_image', function () {
        previewImage(this, '#addCategoryImagePreview');
    });

    /*
    |--------------------------------------------------------------------------
    | Edit Image Preview
    |--------------------------------------------------------------------------
    */

    $(document).on('change', '#edit_category_image', function () {
        previewImage(this, '#editCategoryImagePreview');
    });

    /*
    |--------------------------------------------------------------------------
    | Auto Uppercase Category Name
    |--------------------------------------------------------------------------
    */

    $(document).on('input', '#add_category_name, #edit_category_name', function () {
        this.value = this.value.toUpperCase();
    });

    /*
    |--------------------------------------------------------------------------
    | Submit Loading State
    |--------------------------------------------------------------------------
    */

    $(document).on('submit', '#addCategoryForm', function () {
        const button = $(this).find('.kd-sidebar-submit-btn');

        button.prop('disabled', true);
        button.html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...');
    });

    $(document).on('submit', '#editCategoryForm', function () {
        const button = $(this).find('.kd-sidebar-submit-btn');

        button.prop('disabled', true);
        button.html('<i class="fa-solid fa-spinner fa-spin"></i> Updating...');
    });

    /*
    |--------------------------------------------------------------------------
    | View Category Sidebar - Load Existing Details
    |--------------------------------------------------------------------------
    */

    $(document).on('click', '.view-category-btn', function () {
        const categoryId = $(this).data('id');
        const showUrl = $(this).data('show-url');

        if (!showUrl) {
            if (window.kdToast) {
                window.kdToast.error('View URL is missing.');
            }
            return;
        }

        closeAllSidebars();

        $('#viewCategorySidebar').addClass('show');
        $('#viewCategoryBackdrop').addClass('show');
        lockBody();

        if ($('#viewCategoryId').length) {
            $('#viewCategoryId').val(categoryId);
        }

        $('#viewCategoryImagePreview, #viewCategoryImageBox').html('<i class="fa-solid fa-spinner fa-spin"></i>');
        $('#viewCategoryName').text('Loading...');
        $('#viewCategorySlug').text('Loading...');
        $('#viewCategoryDescription').text('Loading...');
        $('#viewCategorySortOrder').text('Loading...');
        $('#viewCategoryStatus').removeClass('active inactive').addClass('inactive').html('<i class="fa-solid fa-spinner fa-spin"></i> Loading');
        $('#viewCategoryCreatedAt').text('Loading...');
        $('#viewCategoryUpdatedAt').text('Loading...');

        loadCategoryDetails(showUrl, function (category) {
            const status = formatStatus(category.status);
            const statusIcon = status === 'active' ? 'fa-circle-check' : 'fa-circle-xmark';

            setPreview('#viewCategoryImagePreview', category.category_image);
            setPreview('#viewCategoryImageBox', category.category_image);

            $('#viewCategoryId').val(category.category_id || '');
            $('#viewCategoryName').text(safeText(category.category_name));
            $('#viewCategorySlug').text(safeText(category.category_slug));
            $('#viewCategoryDescription').text(safeText(category.category_description, 'No description provided'));
            $('#viewCategorySortOrder').text(category.sort_order ?? 0);
            $('#viewCategoryStatus')
                .removeClass('active inactive')
                .addClass(status)
                .html('<i class="fa-solid ' + statusIcon + '"></i> ' + formatStatusLabel(status));
            $('#viewCategoryCreatedAt').text(formatDate(category.created_at));
            $('#viewCategoryUpdatedAt').text(formatDate(category.updated_at));
        }, function () {
            $('#viewCategoryImagePreview, #viewCategoryImageBox').html('<i class="fa-solid fa-image"></i>');
        });
    });

    $(document).on('click', '#closeViewCategorySidebar, #cancelViewCategory, #viewCategoryBackdrop', function () {
        $('#viewCategorySidebar').removeClass('show');
        $('#viewCategoryBackdrop').removeClass('show');
        unlockBody();
    });

    /*
    |--------------------------------------------------------------------------
    | Edit Category Sidebar - Load Existing Details
    |--------------------------------------------------------------------------
    */

    $(document).on('click', '.edit-category-btn', function () {
        const categoryId = $(this).data('id');
        const showUrl = $(this).data('show-url');
        const updateUrl = $(this).data('update-url');

        if (!showUrl) {
            if (window.kdToast) {
                window.kdToast.error('Edit URL is missing.');
            }
            return;
        }

        if (!updateUrl) {
            if (window.kdToast) {
                window.kdToast.error('Update URL is missing.');
            }
            return;
        }

        closeAllSidebars();
        resetEditCategoryForm();

        $('#editCategoryId').val(categoryId);
        $('#editCategoryForm').attr('action', updateUrl);
        $('#editCategoryImagePreview').html('<i class="fa-solid fa-spinner fa-spin"></i>');

        $('#editCategorySidebar').addClass('show');
        $('#editCategoryBackdrop').addClass('show');
        lockBody();

        loadCategoryDetails(showUrl, function (category) {
            $('#editCategoryId').val(category.category_id || categoryId);
            $('#edit_category_name').val(category.category_name || '');
            $('#edit_category_description').val(category.category_description || '');
            $('#edit_sort_order').val(category.sort_order ?? 0);
            $('#edit_status').val(formatStatus(category.status));

            setPreview('#editCategoryImagePreview', category.category_image);
        }, function () {
            $('#editCategoryImagePreview').html('<i class="fa-solid fa-image"></i>');
        });
    });

    $(document).on('click', '#closeEditCategorySidebar, #cancelEditCategory, #editCategoryBackdrop', function () {
        $('#editCategorySidebar').removeClass('show');
        $('#editCategoryBackdrop').removeClass('show');
        unlockBody();

        setTimeout(function () {
            resetEditCategoryForm();
        }, 250);
    });

    /*
    |--------------------------------------------------------------------------
    | Delete Category Modal
    |--------------------------------------------------------------------------
    */

    $(document).on('click', '.delete-category-btn', function () {
        const categoryId = $(this).data('id');
        const deleteUrl = $(this).data('url');

        closeAllSidebars();

        $('#deleteCategoryId').val(categoryId);
        $('#deleteCategoryForm').attr('action', deleteUrl);
        $('#deleteCategoryModal').addClass('show');
        lockBody();
    });

    $(document).on('click', '#closeDeleteCategoryModal, #cancelDeleteCategory', function () {
        $('#deleteCategoryModal').removeClass('show');
        unlockBody();
    });

    $(document).on('click', '#deleteCategoryModal', function (event) {
        if (event.target.id === 'deleteCategoryModal') {
            $('#deleteCategoryModal').removeClass('show');
            unlockBody();
        }
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
    | DataTable Adjust After Sidebar Collapse / Resize
    |--------------------------------------------------------------------------
    */

    $(document).on('click', '#sidebarToggle, #collapseAdminSidebar, .kd-sidebar-collapse-btn', function () {
        setTimeout(function () {
            if ($.fn.DataTable.isDataTable('#categoryTable')) {
                $('#categoryTable').DataTable().columns.adjust().draw(false);
            }
        }, 350);
    });

    $(window).on('resize', function () {
        if ($.fn.DataTable.isDataTable('#categoryTable')) {
            $('#categoryTable').DataTable().columns.adjust();
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Escape Key Close
    |--------------------------------------------------------------------------
    */

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            $('.kd-category-sidebar').removeClass('show');
            $('.kd-category-sidebar-backdrop').removeClass('show');
            $('#deleteCategoryModal').removeClass('show');
            unlockBody();
        }
    });


    /*
|--------------------------------------------------------------------------
| Delete Form Loading State
|--------------------------------------------------------------------------
*/

$(document).on('submit', '#deleteCategoryForm', function () {

    const button = $(this).find('.kd-delete-confirm-btn');

    button.prop('disabled', true);

    button.html(`
        <i class="fa-solid fa-spinner fa-spin"></i>
        Deleting Category...
    `);

});

});