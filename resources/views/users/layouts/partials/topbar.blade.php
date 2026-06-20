@php
    use Illuminate\Support\Facades\DB;
    use Carbon\Carbon;

    $setting = DB::table('business_settings')->first();

    $now = Carbon::now();

    $openTime = $setting?->open_time
        ? Carbon::createFromFormat('H:i:s', $setting->open_time)
        : Carbon::createFromTime(0, 0, 0);

    $closeTime = $setting?->close_time
        ? Carbon::createFromFormat('H:i:s', $setting->close_time)
        : Carbon::createFromTime(23, 59, 59);

    $openDateTime = $now->copy()->setTimeFrom($openTime);
    $closeDateTime = $now->copy()->setTimeFrom($closeTime);

    if ($closeDateTime->lessThanOrEqualTo($openDateTime)) {
        $closeDateTime->addDay();
    }

    $workingDays = !empty($setting?->working_days)
        ? array_map('trim', explode(',', $setting->working_days))
        : [];

    $firstWorkingDay = $workingDays[0] ?? 'Monday';
    $lastWorkingDay = end($workingDays) ?: 'Sunday';

    $todayName = $now->format('l');
    $isWorkingDay = empty($workingDays) || in_array($todayName, $workingDays);

    $isMaintenance = (int) ($setting->maintenance_mode ?? 0) === 1;

    $isOpen = false;
    $isClosingSoon = false;
    $isOpeningSoon = false;

    if (! $isMaintenance && $isWorkingDay) {
        $isOpen = $now->between($openDateTime, $closeDateTime);
        $isClosingSoon = $isOpen && $now->diffInMinutes($closeDateTime, false) <= 30;
        $isOpeningSoon = ! $isOpen
            && $now->lessThan($openDateTime)
            && $now->diffInMinutes($openDateTime, false) <= 30;
    }

    if ($isMaintenance) {
        $statusClass = 'kd-status-maintenance';
        $statusIcon = 'fa-screwdriver-wrench';
        $statusText = $setting->maintenance_title ?? 'Maintenance Mode';
        $statusMessage = $setting->maintenance_message ?? 'We are currently updating the system.';
    } elseif (! $isWorkingDay) {
        $statusClass = 'kd-status-closed';
        $statusIcon = 'fa-circle-xmark';
        $statusText = 'We’re currently closed.';
        $statusMessage = 'Business resumes on our next working day.';
    } elseif ($isClosingSoon) {
        $statusClass = 'kd-status-closing-soon';
        $statusIcon = 'fa-clock';
        $statusText = 'Closing soon.';
        $statusMessage = 'Please complete your transactions soon.';
    } elseif ($isOpeningSoon) {
        $statusClass = 'kd-status-opening-soon';
        $statusIcon = 'fa-clock';
        $statusText = 'Opening soon.';
        $statusMessage = 'Services will be available shortly.';
    } elseif ($isOpen) {
        $statusClass = 'kd-status-open';
        $statusIcon = 'fa-circle-check';
        $statusText = 'We’re currently open.';
        $statusMessage = 'Business operations are active. All services are currently available.';
    } else {
        $statusClass = 'kd-status-closed';
        $statusIcon = 'fa-circle-xmark';
        $statusText = 'We’re currently closed.';
        $statusMessage = 'Please check back during business hours.';
    }

    $businessPhone = $setting->business_phone ?? '0240000000';
    $supportWhatsapp = $setting->support_whatsapp ?? $businessPhone;

    $cleanWhatsapp = preg_replace('/\D+/', '', $supportWhatsapp);

    if (str_starts_with($cleanWhatsapp, '0')) {
        $cleanWhatsapp = '233' . substr($cleanWhatsapp, 1);
    }

    $businessHours = $openTime->format('h:i A') . ' – ' . $closeTime->format('h:i A');
@endphp

<header class="kd-status-topbar {{ $statusClass }}">
    <div class="kd-status-track">
        <div class="kd-status-marquee">

            <span>
                <i class="fa-solid {{ $statusIcon }}"></i>
                {{ $statusText }}
            </span>

            <span>
                {{ $statusMessage }}
            </span>

            <span>
                Business Hours:
                {{ $businessHours }}
            </span>

            <span>
                Working Days:
                {{ $firstWorkingDay }} – {{ $lastWorkingDay }}
            </span>

            <span>
                Support:
                <a href="tel:{{ $businessPhone }}">Call</a>
                <a href="https://wa.me/{{ $cleanWhatsapp }}" target="_blank" rel="noopener">
                    WhatsApp
                </a>
            </span>

        </div>
    </div>
</header>

<header class="kd-user-topbar">
    <div class="kd-topbar-logo">
        <a href="{{ route('user.dashboard') }}">
            <img src="{{ asset('images/klik.png') }}" alt="KlikDataHub">
        </a>
    </div>

    <div class="kd-topbar-actions">

        <button type="button" class="kd-topbar-icon" id="themeToggle" title="Toggle Theme">
            <i class="fa-solid fa-sun"></i>
        </button>

        <a href="{{ url('/orders/track') }}"
           class="kd-topbar-icon {{ request()->is('orders/track') ? 'active' : '' }}"
           title="Track Order">
            <i class="fa-solid fa-truck-fast"></i>
        </a>

        @auth('user')

            <a href="{{ route('user.wallet.index') }}"
               class="kd-topbar-icon {{ request()->routeIs('user.wallet.*') ? 'active' : '' }}"
               title="Wallet">
                <i class="fa-solid fa-wallet"></i>
            </a>

            <a href="{{ route('user.wallet.index') }}#transactions"
               class="kd-topbar-icon kd-has-alert"
               title="Transaction History">
                <i class="fa-regular fa-bell"></i>
                <span></span>
            </a>

            @php
                $user = auth('user')->user();
                $firstInitial = strtoupper(substr($user->first_name ?? 'U', 0, 1));
                $lastInitial = strtoupper(substr($user->last_name ?? '', 0, 1));
            @endphp

            <a href="{{ url('/settings') }}"
               class="kd-topbar-profile"
               title="My Profile">
                {{ $firstInitial . $lastInitial }}
            </a>

            <button type="button" class="kd-topbar-icon" id="sidebarToggle" title="Menu">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>

        @else

            <a href="{{ route('user.login') }}" class="kd-topbar-icon" title="Login">
                <i class="fa-solid fa-circle-user"></i>
            </a>

        @endauth

    </div>
</header>