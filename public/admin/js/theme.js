document.addEventListener('DOMContentLoaded', function () {
    const html = document.documentElement;
    const body = document.body;
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const dateEl = document.getElementById('kdCurrentDate');
    const timeEl = document.getElementById('kdCurrentTime');

    const savedTheme = localStorage.getItem('klikdata_theme') || 'light';

    applyTheme(savedTheme);
    updateClock();
    setInterval(updateClock, 1000);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function () {
            const currentTheme = html.getAttribute('data-theme') || 'light';
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

            localStorage.setItem('klikdata_theme', nextTheme);
            applyTheme(nextTheme);
        });
    }

    function applyTheme(theme) {
        html.setAttribute('data-theme', theme);

        if (theme === 'dark') {
            body.classList.add('dark-mode');
        } else {
            body.classList.remove('dark-mode');
        }

        updateThemeIcon(theme);
    }

    function updateThemeIcon(theme) {
        if (!themeToggleBtn) return;

        const icon = themeToggleBtn.querySelector('i');
        if (!icon) return;

        icon.className = theme === 'dark'
            ? 'fa-solid fa-sun'
            : 'fa-solid fa-moon';
    }

    function updateClock() {
        const now = new Date();

        if (dateEl) {
            dateEl.textContent = now.toLocaleDateString('en-GB', {
                weekday: 'short',
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            }).replace(',', '');
        }

        if (timeEl) {
            timeEl.textContent = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
        }
    }
});