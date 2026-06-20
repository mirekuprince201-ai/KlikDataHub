document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('.kd-checker-page');

    if (!page) {
        return;
    }

    /* =====================================================
       DATATABLE
    ===================================================== */

    let resultCheckerTable = null;

    function createDataTableTools(table) {
        const wrapper = table.closest('.dataTables_wrapper');
        const tableCard = table.closest('.kd-table-card');
        const tableHead = tableCard ? tableCard.querySelector('.kd-table-head') : null;
        const tableWrap = tableCard ? tableCard.querySelector('.kd-table-wrap') : null;

        if (!wrapper || !tableCard || !tableHead || !tableWrap) {
            return;
        }

        let topTools = tableCard.querySelector('.kd-dt-tools-external');
        let bottomTools = tableCard.querySelector('.kd-dt-footer-external');

        if (!topTools) {
            topTools = document.createElement('div');
            topTools.className = 'kd-dt-tools-external';
            tableHead.after(topTools);
        }

        if (!bottomTools) {
            bottomTools = document.createElement('div');
            bottomTools.className = 'kd-dt-footer-external';
            tableWrap.after(bottomTools);
        }

        const length = wrapper.querySelector('.dataTables_length');
        const filter = wrapper.querySelector('.dataTables_filter');
        const buttons = wrapper.querySelector('.dt-buttons');
        const info = wrapper.querySelector('.dataTables_info');
        const paginate = wrapper.querySelector('.dataTables_paginate');

        if (length && !topTools.contains(length)) {
            topTools.appendChild(length);
        }

        if (buttons && !topTools.contains(buttons)) {
            topTools.appendChild(buttons);
        }

        if (filter && !topTools.contains(filter)) {
            topTools.appendChild(filter);
        }

        if (info && !bottomTools.contains(info)) {
            bottomTools.appendChild(info);
        }

        if (paginate && !bottomTools.contains(paginate)) {
            bottomTools.appendChild(paginate);
        }
    }

    if (window.jQuery && $.fn.DataTable && $('#kdResultCheckerTable').length) {
        if ($.fn.DataTable.isDataTable('#kdResultCheckerTable')) {
            $('#kdResultCheckerTable').DataTable().destroy();
        }

        resultCheckerTable = $('#kdResultCheckerTable').DataTable({
            responsive: false,
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, 'All']
            ],
            scrollX: true,
            order: [],
            autoWidth: false,
            dom: 'lBfrtip',
            buttons: [
                {
                    extend: 'copy',
                    text: '<i class="fa-solid fa-copy"></i> Copy',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    }
                },
                {
                    extend: 'excel',
                    text: '<i class="fa-solid fa-file-excel"></i> Excel',
                    title: 'Result Checker Stock',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fa-solid fa-file-pdf"></i> PDF',
                    title: 'Result Checker Stock',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fa-solid fa-print"></i> Print',
                    title: 'Result Checker Stock',
                    exportOptions: {
                        columns: ':not(:last-child)'
                    }
                }
            ],
            language: {
                lengthMenu: 'Show _MENU_ entries',
                search: '',
                searchPlaceholder: 'Search result checkers...',
                emptyTable: 'No result checkers available.',
                zeroRecords: 'No matching result checkers found.',
                info: 'Showing _START_ to _END_ of _TOTAL_ checkers',
                infoEmpty: 'Showing 0 to 0 of 0 checkers',
                infoFiltered: '(filtered from _MAX_ total checkers)'
            },
            columnDefs: [
                {
                    targets: -1,
                    orderable: false,
                    searchable: false
                }
            ],
            initComplete: function () {
                const api = this.api();
                const tableNode = api.table().node();

                createDataTableTools(tableNode);

                setTimeout(function () {
                    api.columns.adjust().draw(false);
                    createDataTableTools(tableNode);
                }, 300);
            },
            drawCallback: function () {
                const api = this.api();
                const tableNode = api.table().node();

                createDataTableTools(tableNode);
            }
        });

        setTimeout(function () {
            if (resultCheckerTable) {
                resultCheckerTable.columns.adjust().draw(false);
                createDataTableTools(resultCheckerTable.table().node());
            }
        }, 500);
    }

    /* =====================================================
       SIDEBARS
    ===================================================== */

    const sidebars = document.querySelectorAll(
        '#addCheckerSidebar, [id^="viewCheckerSidebar"], [id^="editCheckerSidebar"], [id^="importVoucherSidebar"]'
    );

    sidebars.forEach(function (sidebar) {
        sidebar.classList.add('kd-result-sidebar');

        if (sidebar.parentElement !== document.body) {
            document.body.appendChild(sidebar);
        }
    });

    function closeSidebars() {
        document.querySelectorAll('.kd-result-sidebar').forEach(function (sidebar) {
            sidebar.classList.remove('active');
        });

        document.body.classList.remove('kd-result-sidebar-open');

        if (resultCheckerTable) {
            setTimeout(function () {
                resultCheckerTable.columns.adjust().draw(false);
                createDataTableTools(resultCheckerTable.table().node());
            }, 200);
        }
    }

    document.querySelectorAll('[data-kd-open-sidebar]').forEach(function (button) {
        button.addEventListener('click', function () {
            const sidebarId = this.getAttribute('data-kd-open-sidebar');
            const sidebar = document.getElementById(sidebarId);

            if (!sidebar) {
                return;
            }

            closeSidebars();

            sidebar.classList.add('active');
            document.body.classList.add('kd-result-sidebar-open');
        });
    });

    document.addEventListener('click', function (event) {
        const closeButton = event.target.closest('[data-kd-close-sidebar]');

        if (!closeButton) {
            return;
        }

        closeSidebars();
    });

    document.querySelectorAll('.kd-result-sidebar').forEach(function (sidebar) {
        sidebar.addEventListener('click', function (event) {
            if (event.target === sidebar) {
                closeSidebars();
            }
        });
    });

    /* =====================================================
       UPPERCASE
    ===================================================== */

    function bindUppercase(scope) {
        const root = scope || document;

        root.querySelectorAll('.kd-uppercase').forEach(function (field) {
            if (field.dataset.kdUppercaseBound === 'true') {
                return;
            }

            field.dataset.kdUppercaseBound = 'true';

            field.addEventListener('input', function () {
                const start = this.selectionStart;
                const end = this.selectionEnd;

                this.value = this.value.toUpperCase();

                if (this.setSelectionRange) {
                    this.setSelectionRange(start, end);
                }
            });
        });
    }

    bindUppercase(document);

    /* =====================================================
       DELETE MODAL
    ===================================================== */

    const deleteModal = document.getElementById('kdDeleteModal');
    const deleteForm = document.getElementById('kdDeleteForm');
    const deleteText = document.getElementById('kdDeleteText');
    const cancelDelete = document.getElementById('kdCancelDelete');

    if (deleteModal && deleteModal.parentElement !== document.body) {
        document.body.appendChild(deleteModal);
    }

    function closeDeleteModal() {
        if (deleteModal) {
            deleteModal.classList.remove('active');
        }

        document.body.classList.remove('kd-result-modal-open');
    }

    document.querySelectorAll('[data-kd-delete]').forEach(function (button) {
        button.addEventListener('click', function () {
            const url = this.dataset.url;
            const title = this.dataset.title || '';

            if (!deleteModal || !deleteForm || !url) {
                return;
            }

            deleteForm.action = url;

            if (deleteText) {
                deleteText.textContent =
                    'You are about to delete "' +
                    title +
                    '". This action cannot be undone.';
            }

            deleteModal.classList.add('active');
            document.body.classList.add('kd-result-modal-open');
        });
    });

    if (cancelDelete) {
        cancelDelete.addEventListener('click', function () {
            closeDeleteModal();
        });
    }

    if (deleteModal) {
        deleteModal.addEventListener('click', function (event) {
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });
    }

    /* =====================================================
       MANUAL / EXCEL SWITCH
    ===================================================== */

    function updateVoucherMethod(form) {
        const manualArea = form.querySelector('.kd-manual-area');
        const excelArea = form.querySelector('.kd-excel-area');
        const checkedRadio = form.querySelector('input[name="voucher_entry_method"]:checked');

        if (!manualArea || !excelArea || !checkedRadio) {
            return;
        }

        if (checkedRadio.value === 'excel') {
            manualArea.style.display = 'none';
            excelArea.style.display = 'block';
        } else {
            manualArea.style.display = 'block';
            excelArea.style.display = 'none';
        }
    }

    function bindVoucherMethodSwitch(scope) {
        const root = scope || document;

        root.querySelectorAll('.kd-sidebar-form').forEach(function (form) {
            const radios = form.querySelectorAll('input[name="voucher_entry_method"]');

            if (!radios.length) {
                return;
            }

            radios.forEach(function (radio) {
                if (radio.dataset.kdVoucherMethodBound === 'true') {
                    return;
                }

                radio.dataset.kdVoucherMethodBound = 'true';

                radio.addEventListener('change', function () {
                    updateVoucherMethod(form);
                });
            });

            updateVoucherMethod(form);
        });
    }

    bindVoucherMethodSwitch(document);

    /* =====================================================
       ADD SERIAL / PIN ROW
    ===================================================== */

    document.addEventListener('click', function (event) {
        const addButton = event.target.closest('[data-kd-add-voucher]');

        if (!addButton) {
            return;
        }

        const wrapper = addButton.closest('.kd-manual-area');

        if (!wrapper) {
            return;
        }

        const repeater = wrapper.querySelector('[data-kd-voucher-repeater]');

        if (!repeater) {
            return;
        }

        const row = document.createElement('div');
        row.className = 'kd-voucher-row';

        row.innerHTML = `
            <input type="text"
                   name="serial_numbers[]"
                   class="kd-uppercase"
                   placeholder="Serial Number">

            <input type="text"
                   name="voucher_pins[]"
                   placeholder="PIN / Password">

            <button type="button"
                    class="kd-remove-voucher"
                    data-kd-remove-voucher>
                <i class="fa-solid fa-trash"></i>
            </button>
        `;

        repeater.appendChild(row);
        bindUppercase(row);
    });

    document.addEventListener('click', function (event) {
        const removeButton = event.target.closest('[data-kd-remove-voucher]');

        if (!removeButton) {
            return;
        }

        const repeater = removeButton.closest('[data-kd-voucher-repeater]');

        if (!repeater) {
            return;
        }

        const rows = repeater.querySelectorAll('.kd-voucher-row');

        if (rows.length <= 1) {
            const row = removeButton.closest('.kd-voucher-row');

            if (row) {
                row.querySelectorAll('input').forEach(function (input) {
                    input.value = '';
                });
            }

            return;
        }

        const row = removeButton.closest('.kd-voucher-row');

        if (row) {
            row.remove();
        }
    });

    /* =====================================================
       ADD BULK DISCOUNT ROW
    ===================================================== */

    document.addEventListener('click', function (event) {
        const addButton = event.target.closest('[data-kd-add-discount]');

        if (!addButton) {
            return;
        }

        const wrapper = addButton.closest('.kd-form-group');

        if (!wrapper) {
            return;
        }

        const repeater = wrapper.querySelector('[data-kd-discount-repeater]');

        if (!repeater) {
            return;
        }

        const row = document.createElement('div');
        row.className = 'kd-discount-row';

        row.innerHTML = `
            <input type="number"
                   name="discount_min_quantities[]"
                   min="1"
                   placeholder="Min Qty">

            <input type="number"
                   name="discount_max_quantities[]"
                   min="1"
                   placeholder="Max Qty / blank for above">

            <input type="number"
                   name="discount_unit_prices[]"
                   step="0.01"
                   min="0"
                   placeholder="Unit Price">

            <button type="button"
                    class="kd-remove-discount"
                    data-kd-remove-discount>
                <i class="fa-solid fa-trash"></i>
            </button>
        `;

        repeater.appendChild(row);
    });

    document.addEventListener('click', function (event) {
        const removeButton = event.target.closest('[data-kd-remove-discount]');

        if (!removeButton) {
            return;
        }

        const repeater = removeButton.closest('[data-kd-discount-repeater]');

        if (!repeater) {
            return;
        }

        const rows = repeater.querySelectorAll('.kd-discount-row');

        if (rows.length <= 1) {
            const row = removeButton.closest('.kd-discount-row');

            if (row) {
                row.querySelectorAll('input').forEach(function (input) {
                    input.value = '';
                });
            }

            return;
        }

        const row = removeButton.closest('.kd-discount-row');

        if (row) {
            row.remove();
        }
    });

    /* =====================================================
       EXCEL FILE PREVIEW
    ===================================================== */

    document.addEventListener('change', function (event) {
        const input = event.target;

        if (!input.matches('input[type="file"]')) {
            return;
        }

        if (!input.files || !input.files.length) {
            return;
        }

        const file = input.files[0];
        const label = document.querySelector(`label[for="${input.id}"] strong`);

        if (label) {
            label.textContent = file.name;
        }
    });

    /* =====================================================
       ESCAPE CLOSE
    ===================================================== */

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        closeSidebars();
        closeDeleteModal();
    });

    /* =====================================================
       WINDOW RESIZE
    ===================================================== */

    window.addEventListener('resize', function () {
        if (resultCheckerTable) {
            resultCheckerTable.columns.adjust().draw(false);
            createDataTableTools(resultCheckerTable.table().node());
        }
    });
});