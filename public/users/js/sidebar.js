document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('userSidebar');
    const backdrop = document.getElementById('userSidebarBackdrop');
    const openBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('closeUserSidebar');

    function openSidebar() {
        if (!sidebar || !backdrop) return;

        sidebar.classList.add('show');
        backdrop.classList.add('show');
        document.body.classList.add('kd-sidebar-open');
    }

    function closeSidebar() {
        if (!sidebar || !backdrop) return;

        sidebar.classList.remove('show');
        backdrop.classList.remove('show');
        document.body.classList.remove('kd-sidebar-open');
    }

    if (openBtn) {
        openBtn.addEventListener('click', openSidebar);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });
});