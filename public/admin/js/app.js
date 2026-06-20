document.addEventListener('DOMContentLoaded', function () {
    /*
    |--------------------------------------------------------------------------
    | Sidebar Fallback Toggle
    |--------------------------------------------------------------------------
    */

    const body = document.body;
    const fallbackToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    function isMobile() {
        return window.innerWidth < 992;
    }

    function openMobileSidebar() {
        if (!sidebar) return;

        sidebar.classList.add('show');
        body.classList.add('sidebar-open');
        body.classList.remove('sidebar-collapsed');

        if (backdrop) {
            backdrop.classList.add('show');
        }
    }

    function closeMobileSidebar() {
        if (sidebar) {
            sidebar.classList.remove('show');
        }

        body.classList.remove('sidebar-open');

        if (backdrop) {
            backdrop.classList.remove('show');
        }
    }

    function toggleDesktopSidebar() {
        const collapsed = !body.classList.contains('sidebar-collapsed');

        body.classList.toggle('sidebar-collapsed', collapsed);

        if (sidebar) {
            sidebar.classList.toggle('collapsed', collapsed);
        }

        localStorage.setItem('klikdata_sidebar', collapsed ? 'collapsed' : 'expanded');
    }

    if (fallbackToggle) {
        fallbackToggle.addEventListener('click', function () {
            if (isMobile()) {
                openMobileSidebar();
                return;
            }

            toggleDesktopSidebar();
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeMobileSidebar);
    }

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            closeMobileSidebar();
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Global Overlay Helpers
    |--------------------------------------------------------------------------
    */

    window.kdOverlay = {
        lock() {
            body.classList.add('kd-sidebar-open');
        },

        unlock() {
            body.classList.remove('kd-sidebar-open');
            body.classList.remove('kd-modal-open');
        },

        modalLock() {
            body.classList.add('kd-modal-open');
        }
    };

    /*
    |--------------------------------------------------------------------------
    | Professional Global Toast
    |--------------------------------------------------------------------------
    */

    if (typeof Notyf === 'undefined') {
        console.error('Notyf is not loaded. Check your Notyf CDN.');
        return;
    }

    const notyf = new Notyf({
        duration: 5000,
        ripple: false,
        dismissible: true,
        position: {
            x: 'right',
            y: 'top'
        },
        types: [
            {
                type: 'success',
                background: '#ffffff',
                className: 'kd-toast kd-toast-success',
                icon: {
                    className: 'fa-solid fa-check',
                    tagName: 'i',
                    color: '#16a34a'
                }
            },
            {
                type: 'error',
                background: '#ffffff',
                className: 'kd-toast kd-toast-error',
                icon: {
                    className: 'fa-solid fa-xmark',
                    tagName: 'i',
                    color: '#dc2626'
                }
            },
            {
                type: 'warning',
                background: '#ffffff',
                className: 'kd-toast kd-toast-warning',
                icon: {
                    className: 'fa-solid fa-triangle-exclamation',
                    tagName: 'i',
                    color: '#f59e0b'
                }
            },
            {
                type: 'info',
                background: '#ffffff',
                className: 'kd-toast kd-toast-info',
                icon: {
                    className: 'fa-solid fa-circle-info',
                    tagName: 'i',
                    color: '#2563eb'
                }
            }
        ]
    });

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function buildMessage(title, message) {
        return `
            <div class="kd-toast-content">
                <strong>${escapeHtml(title)}</strong>
                <span>${escapeHtml(message)}</span>
            </div>
        `;
    }

    window.kdToast = {
        success(message) {
            if (message) {
                notyf.open({
                    type: 'success',
                    message: buildMessage('Success', message)
                });
            }
        },

        error(message) {
            if (message) {
                notyf.open({
                    type: 'error',
                    message: buildMessage('Error', message)
                });
            }
        },

        warning(message) {
            if (message) {
                notyf.open({
                    type: 'warning',
                    message: buildMessage('Warning', message)
                });
            }
        },

        info(message) {
            if (message) {
                notyf.open({
                    type: 'info',
                    message: buildMessage('Information', message)
                });
            }
        }
    };

    /*
    |--------------------------------------------------------------------------
    | Laravel Flash Messages
    |--------------------------------------------------------------------------
    */

    const flash = window.KD_FLASH_MESSAGES || {};

    if (flash.success) {
        window.kdToast.success(flash.success);
    }

    if (flash.error) {
        window.kdToast.error(flash.error);
    }

    if (flash.warning) {
        window.kdToast.warning(flash.warning);
    }

    if (flash.info) {
        window.kdToast.info(flash.info);
    }

    if (Array.isArray(flash.validation) && flash.validation.length > 0) {
        flash.validation.forEach(function (message) {
            window.kdToast.error(message);
        });
    }
});