$(document).ready(function () {

    // ==============================
    // Move AFA drawers to body
    // FIX: drawer/backdrop/modal stay above topbar and footer
    // ==============================
    function moveAfaSidebarsToBody() {
        $('#addAfaBackdrop, #addAfaSidebar, #editAfaBackdrop, #editAfaSidebar, #deleteAfaModal').each(function () {
            if (this && this.parentNode !== document.body) {
                document.body.appendChild(this);
            }
        });
    }

    moveAfaSidebarsToBody();

    // ==============================
    // DataTable setup
    // ==============================
    const table = $('#afaTable').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        responsive: false,
        scrollX: true,
        autoWidth: false,
        order: [[0, 'asc']],
        columnDefs: [{ targets: 'no-export', orderable: false, searchable: false }],
        dom: 'Blfrtip',
        buttons: [{
            extend: 'excelHtml5',
            text: '<i class="fa-solid fa-file-excel"></i> Export Excel',
            title: 'AFA Registrations',
            exportOptions: { columns: ':not(.no-export)' }
        }],
        language: {
            search: '',
            searchPlaceholder: 'Search AFA registrations...',
            lengthMenu: 'Show _MENU_ records',
            info: 'Showing _START_ to _END_ of _TOTAL_ registrations',
            emptyTable: 'No AFA registrations found',
            zeroRecords: 'No matching AFA registration found',
            infoEmpty: 'No registrations available',
            infoFiltered: '(filtered from _MAX_ total registrations)'
        }
    });

    table.buttons().container().appendTo('#afaExportContainer');

    // ==============================
    // Body lock/unlock
    // ==============================
    function lockBody() {
        moveAfaSidebarsToBody();
        $('html, body').addClass('kd-sidebar-open');
    }

    function unlockBody() {
        $('html, body').removeClass('kd-sidebar-open');
    }

    function closeAllSidebars() {
        $('#addAfaSidebar, #addAfaBackdrop').removeClass('show');
        $('#editAfaSidebar, #editAfaBackdrop').removeClass('show');
        $('#deleteAfaModal').removeClass('show');
        unlockBody();
    }

    // ==============================
    // Image preview helpers
    // ==============================
    function imageUrl(path) {
        if (!path) return null;

        path = String(path);

        if (path.startsWith('http://') || path.startsWith('https://')) {
            return path;
        }

        return '/' + path.replace(/^\/+/, '');
    }

    function formatStatus(status) {
        return String(status || 'inactive').toLowerCase() === 'active' ? 'active' : 'inactive';
    }

    function setPreview(selector, path) {
        const preview = $(selector);
        const src = imageUrl(path);

        if (src) {
            preview.html('<img src="' + src + '" alt="Preview">');
        } else {
            preview.html('<i class="fa-solid fa-image"></i>');
        }
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

    // ==============================
    // Form resets
    // ==============================
    function resetAddAfaForm() {
        const form = $('#addAfaForm')[0];

        if (form) {
            form.reset();
        }

        $('#addAfaImagePreview').html('<i class="fa-solid fa-image"></i>');
        $('#add_afa_amount').val(0);
        $('#add_afa_status').val('active');

        $('#addAfaForm .kd-sidebar-submit-btn')
            .prop('disabled', false)
            .html('<i class="fa-solid fa-circle-check"></i> Save Registration');
    }

    function resetEditAfaForm() {
        const form = $('#editAfaForm')[0];

        if (form) {
            form.reset();
        }

        $('#editAfaForm').attr('action', '#');
        $('#editAfaId').val('');
        $('#editAfaImagePreview').html('<i class="fa-solid fa-image"></i>');
        $('#edit_afa_amount').val(0);
        $('#edit_afa_status').val('active');

        $('#editAfaForm .kd-sidebar-submit-btn')
            .prop('disabled', false)
            .html('<i class="fa-solid fa-circle-check"></i> Update Registration');
    }

    // ==============================
    // AJAX load AFA details
    // ==============================
    function loadAfaDetails(url, onSuccess, onError) {
        $.getJSON(url)
            .done(function (response) {
                const afa = response.afa || response.registration || response.data || response;

                if (!afa || !afa.afa_registration_id) {
                    if (window.kdToast) {
                        window.kdToast.error('Unable to load AFA details.');
                    }

                    if (typeof onError === 'function') {
                        onError();
                    }

                    return;
                }

                onSuccess(afa);
            })
            .fail(function () {
                if (window.kdToast) {
                    window.kdToast.error('AFA details could not be loaded.');
                }

                if (typeof onError === 'function') {
                    onError();
                }
            });
    }

    // ==============================
    // Add sidebar open/close
    // ==============================
    $(document).on('click', '#openAddAfaSidebar', function () {
        closeAllSidebars();
        moveAfaSidebarsToBody();

        $('#addAfaSidebar, #addAfaBackdrop').addClass('show');
        lockBody();
    });

    $(document).on('click', '#closeAddAfaSidebar, #cancelAddAfa, #addAfaBackdrop', function () {
        $('#addAfaSidebar, #addAfaBackdrop').removeClass('show');
        unlockBody();

        setTimeout(function () {
            resetAddAfaForm();
        }, 250);
    });

    $(document).on('change', '#add_afa_image', function () {
        previewImage(this, '#addAfaImagePreview');
    });

    // ==============================
    // Uppercase names
    // ==============================
    $(document).on('input', '#add_afa_name, #edit_afa_name', function () {
        this.value = this.value.toUpperCase();
    });

    // ==============================
    // Add submit loading
    // ==============================
    $(document).on('submit', '#addAfaForm', function () {
        $(this).find('.kd-sidebar-submit-btn')
            .prop('disabled', true)
            .html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...');
    });

    // ==============================
    // Edit sidebar open/close
    // ==============================
    $(document).on('click', '.edit-afa-btn', function () {
        const afaId = $(this).data('id');
        const showUrl = $(this).data('show-url');
        const updateUrl = $(this).data('update-url');

        if (!showUrl || !updateUrl) {
            if (window.kdToast) {
                window.kdToast.error('AFA edit URL is missing.');
            }
            return;
        }

        closeAllSidebars();
        moveAfaSidebarsToBody();
        resetEditAfaForm();

        $('#editAfaId').val(afaId);
        $('#editAfaForm').attr('action', updateUrl);
        $('#editAfaImagePreview').html('<i class="fa-solid fa-spinner fa-spin"></i>');

        $('#editAfaSidebar, #editAfaBackdrop').addClass('show');
        lockBody();

        loadAfaDetails(showUrl, function (afa) {
            $('#editAfaId').val(afa.afa_registration_id || afaId);
            $('#edit_afa_name').val(afa.registration_name || '');
            $('#edit_afa_amount').val(afa.registration_amount || 0);
            $('#edit_afa_status').val(formatStatus(afa.status));

            setPreview('#editAfaImagePreview', afa.registration_image);
        }, function () {
            $('#editAfaImagePreview').html('<i class="fa-solid fa-image"></i>');
        });
    });

    $(document).on('click', '#closeEditAfaSidebar, #cancelEditAfa, #editAfaBackdrop', function () {
        $('#editAfaSidebar, #editAfaBackdrop').removeClass('show');
        unlockBody();

        setTimeout(function () {
            resetEditAfaForm();
        }, 250);
    });

    $(document).on('change', '#edit_afa_image', function () {
        previewImage(this, '#editAfaImagePreview');
    });

    $(document).on('submit', '#editAfaForm', function () {
        $(this).find('.kd-sidebar-submit-btn')
            .prop('disabled', true)
            .html('<i class="fa-solid fa-spinner fa-spin"></i> Updating...');
    });

    // ==============================
    // Delete modal
    // ==============================
    $(document).on('click', '.delete-afa-btn', function () {
        const afaId = $(this).data('id');
        const deleteUrl = $(this).data('url');

        if (!deleteUrl) {
            if (window.kdToast) {
                window.kdToast.error('AFA delete URL is missing.');
            }
            return;
        }

        closeAllSidebars();
        moveAfaSidebarsToBody();

        $('#deleteAfaId').val(afaId);
        $('#deleteAfaForm').attr('action', deleteUrl);
        $('#deleteAfaModal').addClass('show');

        lockBody();
    });

    $(document).on('click', '#cancelDeleteAfa, #closeDeleteAfaModal', function () {
        $('#deleteAfaModal').removeClass('show');
        unlockBody();
    });

    $(document).on('click', '#deleteAfaModal', function (event) {
        if (event.target.id === 'deleteAfaModal') {
            $('#deleteAfaModal').removeClass('show');
            unlockBody();
        }
    });

    $(document).on('submit', '#deleteAfaForm', function () {
        $(this).find('.kd-delete-confirm-btn')
            .prop('disabled', true)
            .html('<i class="fa-solid fa-spinner fa-spin"></i> Deleting...');
    });

    // ==============================
    // Status switch
    // ==============================
    $(document).on('submit', '.kd-status-switch-form', function () {
        const btn = $(this).find('.kd-status-switch');

        btn.prop('disabled', true);
        btn.css('opacity', '.65');
    });

    // ==============================
    // Recalculate DataTable
    // ==============================
    $(document).on('click', '#sidebarToggle, #collapseAdminSidebar, .kd-sidebar-collapse-btn', function () {
        setTimeout(function () {
            if ($.fn.DataTable.isDataTable('#afaTable')) {
                $('#afaTable').DataTable().columns.adjust().draw(false);
            }
        }, 350);
    });

    $(window).on('resize', function () {
        if ($.fn.DataTable.isDataTable('#afaTable')) {
            $('#afaTable').DataTable().columns.adjust();
        }
    });

    // ==============================
    // Escape key closes all
    // ==============================
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllSidebars();
        }
    });

});