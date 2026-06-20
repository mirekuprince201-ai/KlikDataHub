document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    const notyf = new Notyf({
        duration: 5000,
        ripple: true,
        dismissible: true,
        position: {
            x: 'right',
            y: 'top'
        },
        types: [
            {
                type: 'success',
                background: '#16a34a',
                icon: {
                    className: 'fa-solid fa-circle-check',
                    tagName: 'i',
                    color: '#ffffff'
                }
            },
            {
                type: 'error',
                background: '#dc2626',
                icon: {
                    className: 'fa-solid fa-circle-xmark',
                    tagName: 'i',
                    color: '#ffffff'
                }
            },
            {
                type: 'warning',
                background: '#f59e0b',
                icon: {
                    className: 'fa-solid fa-triangle-exclamation',
                    tagName: 'i',
                    color: '#ffffff'
                }
            },
            {
                type: 'info',
                background: '#2563eb',
                icon: {
                    className: 'fa-solid fa-circle-info',
                    tagName: 'i',
                    color: '#ffffff'
                }
            }
        ]
    });

    window.kdToast = {
        success(message) {
            if (message) {
                notyf.success(message);
            }
        },

        error(message) {
            if (message) {
                notyf.error(message);
            }
        },

        warning(message) {
            if (message) {
                notyf.open({
                    type: 'warning',
                    message: message
                });
            }
        },

        info(message) {
            if (message) {
                notyf.open({
                    type: 'info',
                    message: message
                });
            }
        }
    };

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