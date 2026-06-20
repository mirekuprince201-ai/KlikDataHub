@php
    use Illuminate\Support\Facades\DB;

    $user = auth('user')->user();

    $firstName = $user->first_name ?? 'Guest';
    $lastName = $user->last_name ?? '';
    $fullName = $user->full_name ?? trim($firstName . ' ' . $lastName);
    $phone = $user->phone ?? 'Login to continue';
    $walletBalance = (float) ($user->wallet_balance ?? 0);

    $initials = strtoupper(substr($firstName ?: 'U', 0, 1) . substr($lastName ?: '', 0, 1));

    if (! function_exists('kdSidebarImageUrl')) {
        function kdSidebarImageUrl($image, $fallback = 'images/category-placeholder.png') {
            if (empty($image)) {
                return asset($fallback);
            }

            $image = trim($image);

            if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
                return $image;
            }

            if (str_starts_with($image, 'storage/')) {
                return asset($image);
            }

            if (str_starts_with($image, 'public/')) {
                return asset('storage/' . substr($image, 7));
            }

            if (str_starts_with($image, 'uploads/')) {
                return asset($image);
            }

            return asset('storage/' . ltrim($image, '/'));
        }
    }

    $categories = DB::table('categories')
        ->orderBy('sort_order')
        ->orderBy('category_name')
        ->get()
        ->map(function ($category) {
            $image = $category->category_image
                ?? $category->image
                ?? $category->image_url
                ?? null;

            $category->image_url = kdSidebarImageUrl($image, 'images/category-placeholder.png');

            return $category;
        });

    $resultCheckerCategories = DB::table('result_checker_categories')
        ->orderBy('sort_order')
        ->orderBy('category_name')
        ->get()
        ->map(function ($category) {
            $image = $category->category_image
                ?? $category->image
                ?? $category->image_url
                ?? null;

            $category->image_url = kdSidebarImageUrl($image, 'images/category-placeholder.png');

            return $category;
        });

    $afas = DB::table('afa_registrations')
        ->orderBy('registration_name')
        ->get()
        ->map(function ($afa) {
            $image = $afa->registration_image
                ?? $afa->afa_image
                ?? $afa->image
                ?? $afa->image_url
                ?? null;

            $afa->image_url = kdSidebarImageUrl($image, 'images/afa-placeholder.png');

            return $afa;
        });
@endphp

<div class="kd-user-sidebar-backdrop" id="userSidebarBackdrop"></div>

<aside class="kd-user-sidebar" id="userSidebar">

    <div class="kd-sidebar-header">
        <div>
            <span>Navigation</span>
            <h3>Menu</h3>
        </div>

        <button type="button" class="kd-sidebar-close" id="closeUserSidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="kd-sidebar-profile">
        <div class="kd-sidebar-avatar">{{ $initials ?: 'U' }}</div>

        <div class="kd-sidebar-profile-info">
            <h4>{{ $fullName }}</h4>
            <p>{{ $phone }}</p>
        </div>

        <i class="fa-solid fa-chevron-right"></i>
    </div>

    <nav class="kd-sidebar-nav">

        <span class="kd-sidebar-label">Main</span>

        <a href="{{ route('user.dashboard') }}"
           class="kd-sidebar-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
            <i class="fa-solid fa-chevron-right"></i>
        </a>

        @auth('user')
            <a href="{{ route('user.wallet.index') }}"
               class="kd-sidebar-link kd-wallet-link {{ request()->routeIs('user.wallet.*') ? 'active' : '' }}">
                <i class="fa-solid fa-wallet"></i>
                <span>Wallet</span>
                <strong>GH₵ {{ number_format($walletBalance, 2) }}</strong>
            </a>
        @else
            <a href="{{ route('user.login') }}" class="kd-sidebar-link kd-wallet-link">
                <i class="fa-solid fa-wallet"></i>
                <span>Wallet</span>
                <strong>Login</strong>
            </a>
        @endauth

        <a href="{{ url('/orders/track') }}"
           class="kd-sidebar-link {{ request()->is('orders/track') ? 'active' : '' }}">
            <i class="fa-solid fa-truck-fast"></i>
            <span>Track Order</span>
            <i class="fa-solid fa-chevron-right"></i>
        </a>

        <a href="{{ url('/orders') }}"
           class="kd-sidebar-link {{ request()->is('orders') ? 'active' : '' }}">
            <i class="fa-solid fa-box-archive"></i>
            <span>Order History</span>
            <i class="fa-solid fa-chevron-right"></i>
        </a>

        <a href="{{ url('/result-checker-history') }}"
           class="kd-sidebar-link {{ request()->is('result-checker-history') || request()->is('result-checkers/history') ? 'active' : '' }}">
            <i class="fa-solid fa-square-poll-vertical"></i>
            <span>Result Checker History</span>
            <i class="fa-solid fa-chevron-right"></i>
        </a>

        <a href="{{ url('/afa-history') }}"
           class="kd-sidebar-link {{ request()->is('afa-history') ? 'active' : '' }}">
            <i class="fa-solid fa-id-card-clip"></i>
            <span>AFA History</span>
            <i class="fa-solid fa-chevron-right"></i>
        </a>

        <a href="{{ url('/bulk-purchase') }}"
           class="kd-sidebar-link {{ request()->is('bulk-purchase') ? 'active' : '' }}">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Bulk Purchase</span>
            <i class="fa-solid fa-chevron-right"></i>
        </a>

        <span class="kd-sidebar-label">Buy Data</span>

        @forelse($categories as $category)
            @php
                $isActive = strtolower(trim($category->status ?? '')) === 'active';
            @endphp

            @if($isActive)
                <a href="{{ route('user.packages.index', $category->category_slug) }}"
                   class="kd-sidebar-link kd-sidebar-service {{ request()->is('packages/' . $category->category_slug) ? 'active' : '' }}">
                    <span class="kd-sidebar-service-img">
                        <img
                            src="{{ $category->image_url }}"
                            alt="{{ $category->category_name }}"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='{{ asset('images/category-placeholder.png') }}';">
                    </span>

                    <span>{{ strtoupper($category->category_name) }}</span>
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            @else
                <div class="kd-sidebar-link kd-sidebar-service kd-sidebar-inactive" aria-disabled="true">
                    <span class="kd-sidebar-service-img">
                        <img
                            src="{{ $category->image_url }}"
                            alt="{{ $category->category_name }}"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='{{ asset('images/category-placeholder.png') }}';">
                    </span>

                    <span>{{ strtoupper($category->category_name) }}</span>
                    <em>Out</em>
                    <i class="fa-solid fa-lock"></i>
                </div>
            @endif
        @empty
            <div class="kd-sidebar-empty">
                <i class="fa-solid fa-layer-group"></i>
                <span>No data categories</span>
            </div>
        @endforelse

        <span class="kd-sidebar-label">Result Checkers</span>

        @forelse($resultCheckerCategories as $checkerCategory)
            @php
                $checkerIsActive = strtolower(trim($checkerCategory->status ?? '')) === 'active';
                $checkerSlug = $checkerCategory->category_slug ?? '';
                $checkerName = $checkerCategory->category_name ?? 'Result Checker';
            @endphp

            @if($checkerIsActive)
                <a href="{{ url('/result-checkers/' . $checkerSlug) }}"
                   class="kd-sidebar-link kd-sidebar-service {{ request()->is('result-checkers/' . $checkerSlug) ? 'active' : '' }}">
                    <span class="kd-sidebar-service-img">
                        <img
                            src="{{ $checkerCategory->image_url }}"
                            alt="{{ $checkerName }}"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='{{ asset('images/category-placeholder.png') }}';">
                    </span>

                    <span>{{ strtoupper($checkerName) }}</span>
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            @else
                <div class="kd-sidebar-link kd-sidebar-service kd-sidebar-inactive" aria-disabled="true">
                    <span class="kd-sidebar-service-img">
                        <img
                            src="{{ $checkerCategory->image_url }}"
                            alt="{{ $checkerName }}"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='{{ asset('images/category-placeholder.png') }}';">
                    </span>

                    <span>{{ strtoupper($checkerName) }}</span>
                    <em>Out</em>
                    <i class="fa-solid fa-lock"></i>
                </div>
            @endif
        @empty
            <div class="kd-sidebar-empty">
                <i class="fa-solid fa-square-poll-vertical"></i>
                <span>No result checker service</span>
            </div>
        @endforelse

        <span class="kd-sidebar-label">AFA Registration</span>

        @forelse($afas as $afa)
            @php
                $afaIsActive = strtolower(trim($afa->status ?? '')) === 'active';
            @endphp

            @if($afaIsActive)
                <a href="{{ route('user.afa.show', $afa->registration_slug) }}"
                   class="kd-sidebar-link kd-sidebar-service {{ request()->is('afa/' . $afa->registration_slug) ? 'active' : '' }}">
                    <span class="kd-sidebar-service-img">
                        <img
                            src="{{ $afa->image_url }}"
                            alt="{{ $afa->registration_name }}"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='{{ asset('images/afa-placeholder.png') }}';">
                    </span>

                    <span>{{ strtoupper($afa->registration_name) }}</span>
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            @else
                <div class="kd-sidebar-link kd-sidebar-service kd-sidebar-inactive" aria-disabled="true">
                    <span class="kd-sidebar-service-img">
                        <img
                            src="{{ $afa->image_url }}"
                            alt="{{ $afa->registration_name }}"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='{{ asset('images/afa-placeholder.png') }}';">
                    </span>

                    <span>{{ strtoupper($afa->registration_name) }}</span>
                    <em>Out</em>
                    <i class="fa-solid fa-lock"></i>
                </div>
            @endif
        @empty
            <div class="kd-sidebar-empty">
                <i class="fa-solid fa-id-card"></i>
                <span>No AFA service</span>
            </div>
        @endforelse

        <span class="kd-sidebar-label">API</span>

        <div class="kd-sidebar-link kd-sidebar-service kd-sidebar-inactive" aria-disabled="true">
            <i class="fa-solid fa-code"></i>
            <span>API Documentation</span>
            <em>Off</em>
            <i class="fa-solid fa-lock"></i>
        </div>

        <div class="kd-sidebar-link kd-sidebar-service kd-sidebar-inactive" aria-disabled="true">
            <i class="fa-solid fa-key"></i>
            <span>API Keys</span>
            <em>Off</em>
            <i class="fa-solid fa-lock"></i>
        </div>

        <div class="kd-sidebar-link kd-sidebar-service kd-sidebar-inactive" aria-disabled="true">
            <i class="fa-solid fa-plug-circle-bolt"></i>
            <span>API Status</span>
            <em>Off</em>
            <i class="fa-solid fa-lock"></i>
        </div>

        <span class="kd-sidebar-label">More Services</span>

        <div class="kd-sidebar-link kd-sidebar-inactive" aria-disabled="true">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Security</span>
            <em>Off</em>
            <i class="fa-solid fa-lock"></i>
        </div>

        <a href="{{ url('/settings') }}"
           class="kd-sidebar-link {{ request()->is('settings') ? 'active' : '' }}">
            <i class="fa-solid fa-gear"></i>
            <span>Settings</span>
            <i class="fa-solid fa-chevron-right"></i>
        </a>

        <a href="https://wa.me/233243194783"
           target="_blank"
           rel="noopener"
           class="kd-sidebar-link">
            <i class="fa-solid fa-headset"></i>
            <span>Need Help?</span>
            <i class="fa-solid fa-chevron-right"></i>
        </a>

    </nav>

    <div class="kd-sidebar-footer">
        @auth('user')
            <form method="POST" action="{{ route('user.logout') }}">
                @csrf
                <button type="submit" class="kd-sidebar-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        @else
            <a href="{{ route('user.login') }}" class="kd-sidebar-login">
                <i class="fa-solid fa-circle-user"></i>
                <span>Login</span>
            </a>
        @endauth

        <p>KlikDataHub © {{ date('Y') }}</p>
    </div>

</aside>