document.addEventListener('DOMContentLoaded', function () {
    const html = document.documentElement;
    const body = document.body;
    const themeToggle = document.getElementById('themeToggle');
    const storageKey = 'kd_user_theme';

    function applyTheme(theme) {
        const selectedTheme = theme === 'dark' ? 'dark' : 'light';

        html.setAttribute('data-theme', selectedTheme);

        body.classList.remove('dark-mode', 'light-mode');
        body.classList.add(selectedTheme === 'dark' ? 'dark-mode' : 'light-mode');

        localStorage.setItem(storageKey, selectedTheme);

        if (themeToggle) {
            themeToggle.innerHTML = selectedTheme === 'dark'
                ? '<i class="fa-solid fa-moon"></i>'
                : '<i class="fa-solid fa-sun"></i>';

            themeToggle.setAttribute(
                'title',
                selectedTheme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode'
            );
        }
    }

    const savedTheme = localStorage.getItem(storageKey) || 'light';
    applyTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const currentTheme = html.getAttribute('data-theme') || 'light';
            applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });
    }
});