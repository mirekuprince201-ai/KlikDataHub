document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const sidebar = document.getElementById('adminSidebar');
    const openBtn = document.getElementById('openAdminSidebar');
    const closeBtn = document.getElementById('closeAdminSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const dropdowns = document.querySelectorAll('.kd-sidebar-dropdown');

    const DESKTOP_WIDTH = 992;
    const STORAGE_KEY = 'klikdata_sidebar';

    function isDesktop() {
        return window.innerWidth >= DESKTOP_WIDTH;
    }

    function isMobile() {
        return window.innerWidth < DESKTOP_WIDTH;
    }

    function setCollapsed(collapsed) {
        body.classList.toggle('sidebar-collapsed', collapsed);

        if (sidebar) {
            sidebar.classList.toggle('collapsed', collapsed);
        }

        if (isDesktop()) {
            localStorage.setItem(STORAGE_KEY, collapsed ? 'collapsed' : 'expanded');
        }

        closeAllDropdowns();
    }

    function openMobileSidebar() {
        if (!sidebar) return;

        sidebar.classList.add('show');

        if (backdrop) {
            backdrop.classList.add('show');
        }

        body.classList.add('sidebar-open');
        body.classList.remove('sidebar-collapsed');
    }

    function closeMobileSidebar() {
        if (sidebar) {
            sidebar.classList.remove('show');
        }

        if (backdrop) {
            backdrop.classList.remove('show');
        }

        body.classList.remove('sidebar-open');

        if (isMobile()) {
            body.classList.remove('sidebar-collapsed');
        }
    }

    function closeAllDropdowns() {
        dropdowns.forEach(function (dropdown) {
            dropdown.classList.remove('open');
        });
    }

    function syncSidebarOnLoadAndResize() {
        if (isDesktop()) {
            closeMobileSidebar();

            const savedSidebar = localStorage.getItem(STORAGE_KEY);
            setCollapsed(savedSidebar === 'collapsed');
        } else {
            body.classList.remove('sidebar-collapsed');

            if (sidebar) {
                sidebar.classList.remove('collapsed');
            }
        }
    }

    dropdowns.forEach(function (dropdown) {
        const button = dropdown.querySelector('.kd-sidebar-dropdown-btn');

        if (!button) return;

        button.addEventListener('click', function () {
            if (isDesktop() && body.classList.contains('sidebar-collapsed')) {
                return;
            }

            dropdowns.forEach(function (item) {
                if (item !== dropdown) {
                    item.classList.remove('open');
                }
            });

            dropdown.classList.toggle('open');
        });
    });

    if (openBtn) {
        openBtn.addEventListener('click', function () {
            if (isMobile()) {
                openMobileSidebar();
                return;
            }

            setCollapsed(!body.classList.contains('sidebar-collapsed'));
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeMobileSidebar);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeMobileSidebar);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMobileSidebar();
        }
    });

    window.addEventListener('resize', syncSidebarOnLoadAndResize);

    syncSidebarOnLoadAndResize();
});