@php
    use Illuminate\Support\Facades\Route;

    $safeRoute = function ($name) {
        return Route::has($name) ? route($name) : 'javascript:void(0)';
    };

    $logoutRoute = Route::has('admin.logout') ? route('admin.logout') : '#';

    $menus = [
        [
            'title' => 'Users',
            'icon' => 'fa-users',
            'active' => [
                'admin.users.*',
                'admin.afa.*',
                'admin.categories.*',
                'admin.plans.*',
            ],
            'items' => [
                ['Manage Users', 'fa-users-gear', $safeRoute('admin.users.index'), 'admin.users.index'],
                ['AFA', 'fa-layer-group', $safeRoute('admin.afa.index'), 'admin.afa.index'],
                ['Categories', 'fa-shapes', $safeRoute('admin.categories.index'), 'admin.categories.index'],
                ['Plans', 'fa-box-open', $safeRoute('admin.plans.index'), 'admin.plans.index'],
                ['Top Users', 'fa-ranking-star', $safeRoute('admin.users.top'), 'admin.users.top'],
            ],
        ],

       [
    'title' => 'Result Checkers',
    'icon' => 'fa-square-poll-vertical',
    'active' => ['admin.result-checkers.*'],
    'items' => [
        ['Checker Inventory', 'fa-boxes-stacked', $safeRoute('admin.result-checkers.index'), 'admin.result-checkers.index'],
        ['Categories', 'fa-layer-group', $safeRoute('admin.result-checkers.categories'), 'admin.result-checkers.categories'],
        ['Orders', 'fa-cart-shopping', $safeRoute('admin.result-checkers.orders'), 'admin.result-checkers.orders'],
        ['Sales Reports', 'fa-chart-line', $safeRoute('admin.result-checkers.reports'), 'admin.result-checkers.reports'],
    ],
],

        [
            'title' => 'Manage Orders',
            'icon' => 'fa-cart-shopping',
            'active' => [
                'admin.orders.*',
            ],
            'items' => [
                ['Pending Orders', 'fa-hourglass-half', $safeRoute('admin.orders.pending'), 'admin.orders.pending'],
                ['Processing Orders', 'fa-spinner', $safeRoute('admin.orders.processing'), 'admin.orders.processing'],
                ['In Progress Orders', 'fa-truck-fast', $safeRoute('admin.orders.in-progress'), 'admin.orders.in-progress'],
                ['Delivered Orders', 'fa-circle-check', $safeRoute('admin.orders.delivered'), 'admin.orders.delivered'],
            ],
        ],

        [
            'title' => 'AFA Orders',
            'icon' => 'fa-id-card-clip',
            'active' => [
                'admin.afa-orders.*',
            ],
            'items' => [
                ['Pending AFA Orders', 'fa-hourglass-half', $safeRoute('admin.afa-orders.pending'), 'admin.afa-orders.pending'],
                ['Processing AFA Orders', 'fa-spinner', $safeRoute('admin.afa-orders.processing'), 'admin.afa-orders.processing'],
                ['In Progress AFA Orders', 'fa-truck-fast', $safeRoute('admin.afa-orders.in-progress'), 'admin.afa-orders.in-progress'],
                ['Delivered AFA Orders', 'fa-circle-check', $safeRoute('admin.afa-orders.delivered'), 'admin.afa-orders.delivered'],
            ],
        ],

        [
            'title' => 'Finance',
            'icon' => 'fa-money-bill-wave',
            'active' => [
                'admin.finance.*',
                'admin.payments.*',
                'admin.wallets.*',
            ],
            'items' => [
                ['Top Ups', 'fa-circle-plus', $safeRoute('admin.finance.topups.index'), 'admin.finance.topups.index'],
                ['Transactions', 'fa-arrow-right-arrow-left', $safeRoute('admin.finance.transactions'), 'admin.finance.transactions'],
                ['Verify Payments', 'fa-circle-check', $safeRoute('admin.finance.verify-payments'), 'admin.finance.verify-payments'],
                ['Wallet Balances', 'fa-building-columns', $safeRoute('admin.finance.wallet-balances'), 'admin.finance.wallet-balances'],
                ['Financial Reports', 'fa-chart-column', $safeRoute('admin.finance.reports'), 'admin.finance.reports'],
            ],
        ],

        [
            'title' => 'Settings',
            'icon' => 'fa-gear',
            'active' => [
                'admin.settings.*',
                'admin.system.*',
            ],
            'items' => [
                ['Business Settings', 'fa-sliders', $safeRoute('admin.settings.business'), 'admin.settings.business'],
                ['Admin Auth Setup', 'fa-user-shield', $safeRoute('admin.settings.admin-auth'), 'admin.settings.admin-auth'],
                ['SMS Settings', 'fa-comment-sms', $safeRoute('admin.settings.sms'), 'admin.settings.sms'],
                ['Autocomplete Setup', 'fa-wand-magic-sparkles', $safeRoute('admin.settings.autocomplete'), 'admin.settings.autocomplete'],
                ['Activity Logs', 'fa-list-check', $safeRoute('admin.system.activity'), 'admin.system.activity'],
            ],
        ],
    ];
@endphp

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="kd-sidebar" id="adminSidebar">
    <div class="kd-sidebar-brand">
        <a href="{{ $safeRoute('admin.dashboard') }}" class="kd-brand-main">
            <div class="kd-brand-logo-img">
                <img src="{{ asset('images/optina-logo.png') }}" alt="KlikDataHub Logo">
            </div>
        </a>

        <button type="button" class="kd-sidebar-close" id="closeAdminSidebar" aria-label="Close sidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <nav class="kd-sidebar-menu">
        <a href="{{ $safeRoute('admin.dashboard') }}"
           class="kd-sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        @foreach ($menus as $menu)
            @php
                $isDropdownActive = collect($menu['active'])->contains(fn ($route) => request()->routeIs($route))
                    || collect($menu['items'])->contains(fn ($item) => request()->routeIs($item[3]));
            @endphp

            <div class="kd-sidebar-dropdown {{ $isDropdownActive ? 'open active' : '' }}">
                <button type="button" class="kd-sidebar-dropdown-btn">
                    <span>
                        <i class="fa-solid {{ $menu['icon'] }}"></i>
                        <strong>{{ $menu['title'] }}</strong>
                    </span>

                    <i class="fa-solid fa-chevron-down kd-dropdown-arrow"></i>
                </button>

                <div class="kd-sidebar-submenu">
                    @foreach ($menu['items'] as $item)
                        <a href="{{ $item[2] }}"
                           class="kd-sidebar-submenu-link {{ request()->routeIs($item[3]) ? 'active' : '' }}">
                            <i class="fa-solid {{ $item[1] }}"></i>
                            <span>{{ $item[0] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="kd-sidebar-footer">
        <form action="{{ $logoutRoute }}" method="POST">
            @csrf

            <button type="submit" class="kd-sidebar-logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Sign Out</span>
            </button>
        </form>
    </div>
</aside>