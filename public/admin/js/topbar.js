document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Elements
    |--------------------------------------------------------------------------
    */

    const profileWrap = document.getElementById('kdProfileWrap');
    const profileToggle = document.getElementById('kdProfileToggle');
    const profileDropdown = document.getElementById('kdProfileDropdown');

    const currentDate = document.getElementById('kdCurrentDate');
    const currentTime = document.getElementById('kdCurrentTime');

    /*
    |--------------------------------------------------------------------------
    | Live Date & Time
    |--------------------------------------------------------------------------
    */

    function updateDateTime() {

        const now = new Date();

        if (currentDate) {
            currentDate.textContent = now.toLocaleDateString('en-US', {
                weekday: 'short',
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        if (currentTime) {
            currentTime.textContent = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
        }
    }

    updateDateTime();

    setInterval(updateDateTime, 1000);

    /*
    |--------------------------------------------------------------------------
    | Profile Dropdown
    |--------------------------------------------------------------------------
    */

    function openProfileDropdown() {

        if (!profileWrap || !profileToggle) return;

        profileWrap.classList.add('show');
        profileToggle.setAttribute('aria-expanded', 'true');
    }

    function closeProfileDropdown() {

        if (!profileWrap || !profileToggle) return;

        profileWrap.classList.remove('show');
        profileToggle.setAttribute('aria-expanded', 'false');
    }

    function toggleProfileDropdown() {

        if (!profileWrap || !profileToggle) return;

        const isOpen = profileWrap.classList.contains('show');

        if (isOpen) {
            closeProfileDropdown();
        } else {
            openProfileDropdown();
        }
    }

    if (profileToggle) {

        profileToggle.addEventListener('click', function (event) {

            event.preventDefault();
            event.stopPropagation();

            toggleProfileDropdown();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Click Outside
    |--------------------------------------------------------------------------
    */

    document.addEventListener('click', function (event) {

        if (!profileWrap) return;

        if (!profileWrap.contains(event.target)) {
            closeProfileDropdown();
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Escape Key
    |--------------------------------------------------------------------------
    */

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
            closeProfileDropdown();
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Prevent Closing When Clicking Inside Dropdown
    |--------------------------------------------------------------------------
    */

    if (profileDropdown) {

        profileDropdown.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Auto Close On Resize (Mobile/Desktop)
    |--------------------------------------------------------------------------
    */

    window.addEventListener('resize', function () {

        if (window.innerWidth < 992) {
            closeProfileDropdown();
        }
    });

});