@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Route;

    $admin = auth('admin')->user();

    $firstName = strtoupper($admin->first_name ?? 'ADMIN');
    $lastName = strtoupper($admin->last_name ?? '');
    $fullName = trim($firstName . ' ' . $lastName);
    $email = $admin->email ?? 'admin@klikdatahub.com';

    $initials = strtoupper(
        substr($admin->first_name ?? 'A', 0, 1) .
        substr($admin->last_name ?? '', 0, 1)
    );

    $profileRoute = Route::has('admin.profile')
        ? route('admin.profile')
        : 'javascript:void(0)';

    $settingsRoute = Route::has('admin.settings.business')
        ? route('admin.settings.business')
        : 'javascript:void(0)';

    $logoutRoute = Route::has('admin.logout')
        ? route('admin.logout')
        : '#';
@endphp

<header class="kd-topbar" id="kdTopbar">

    <div class="kd-topbar-left">

        <button type="button"
                class="kd-topbar-menu-btn"
                id="openAdminSidebar"
                aria-label="Toggle Sidebar"
                title="Toggle Sidebar">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>

        <div class="kd-topbar-date">
            <span id="kdCurrentDate">{{ Carbon::now()->format('D, d M Y') }}</span>
            <strong id="kdCurrentTime">{{ Carbon::now()->format('h:i:s A') }}</strong>
        </div>

    </div>

    <div class="kd-topbar-right">

        <button type="button"
                class="kd-topbar-icon-btn kd-theme-btn"
                id="themeToggleBtn"
                title="Change Theme"
                aria-label="Change Theme">
            <i class="fa-solid fa-sun kd-theme-light-icon"></i>
            <i class="fa-solid fa-moon kd-theme-dark-icon"></i>
        </button>

        <button type="button"
                class="kd-topbar-icon-btn"
                title="Notifications"
                aria-label="Notifications">
            <i class="fa-regular fa-bell"></i>
            <span class="kd-notification-dot"></span>
        </button>

        <div class="kd-profile-wrap" id="kdProfileWrap">

            <button type="button"
                    class="kd-topbar-user"
                    id="kdProfileToggle"
                    aria-expanded="false"
                    aria-haspopup="true"
                    aria-controls="kdProfileDropdown"
                    title="{{ $fullName }}">
                <div class="kd-user-avatar">
                    {{ $initials }}
                </div>

                <div class="kd-user-details">
                    <strong>{{ $firstName }}</strong>
                </div>

                <span class="kd-user-chevron">
                    <i class="fa-solid fa-chevron-down"></i>
                </span>
            </button>

            <div class="kd-profile-dropdown" id="kdProfileDropdown">

                <div class="kd-profile-head">
                    <div class="kd-profile-big-avatar">
                        {{ $initials }}
                    </div>

                    <div>
                        <strong>{{ $fullName }}</strong>
                        <span>{{ $email }}</span>
                    </div>
                </div>

                <div class="kd-profile-menu">
                    <a href="{{ $profileRoute }}">
                        <i class="fa-regular fa-user"></i>
                        <span>My Profile</span>
                    </a>

                    <a href="{{ $settingsRoute }}">
                        <i class="fa-solid fa-gear"></i>
                        <span>Settings</span>
                    </a>

                    <a href="javascript:void(0)">
                        <i class="fa-regular fa-bell"></i>
                        <span>Notifications</span>
                    </a>
                </div>

                <form method="POST"
                      action="{{ $logoutRoute }}"
                      class="kd-profile-logout-form">
                    @csrf

                    <button type="submit">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Sign Out</span>
                    </button>
                </form>

            </div>

        </div>

    </div>

</header>