@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Route;

    $business = DB::table('business_settings')->orderByDesc('id')->first();

    $businessName = $business->business_name ?? config('app.name', 'KlikDataHub');
    $businessEmail = $business->business_email ?? 'support@klikdata.com';
    $businessPhone = $business->business_phone ?? '0240000000';
    $businessAddress = $business->business_address ?? 'Ghana';
    $supportWhatsapp = $business->support_whatsapp ?? $businessPhone;
    $supportEmail = $business->support_email ?? $businessEmail;
    $developerEmail = $business->developer_email ?? null;

    $formatPhone = function ($phone) {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($phone, '0')) {
            return '233' . substr($phone, 1);
        }

        if (str_starts_with($phone, '233')) {
            return $phone;
        }

        return $phone;
    };

    $waPhone = $formatPhone($supportWhatsapp);
    $telPhone = str_starts_with($formatPhone($businessPhone), '233')
        ? '+' . $formatPhone($businessPhone)
        : $businessPhone;

    $safeRoute = function ($name, $fallback = '#') {
        return Route::has($name) ? route($name) : $fallback;
    };
@endphp

<footer class="kd-footer">
    <div class="kd-footer-wrap">

        <div class="kd-footer-main">

            <div class="kd-footer-brand">
                <div class="kd-footer-brand-head">
                    <span class="kd-footer-logo">
                        <i class="fa-solid fa-bolt"></i>
                    </span>

                    <h3>{{ $businessName }}</h3>
                </div>

                <p>
                    {{ $businessName }} is a fast, secure and reliable digital platform for data bundles,
                    AFA registrations, wallet payments and online transactions.
                </p>

                <div class="kd-footer-socials">
                    <a href="https://wa.me/{{ $waPhone }}" target="_blank" rel="noopener" title="WhatsApp Support">
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>

                    <a href="tel:{{ $telPhone }}" title="Call Support">
                        <i class="fa-solid fa-phone"></i>
                    </a>

                    <a href="{{ $safeRoute('user.dashboard') }}" title="Dashboard">
                        <i class="fa-solid fa-house"></i>
                    </a>

                    <a href="{{ $safeRoute('user.orders.track') }}" title="Track Order">
                        <i class="fa-solid fa-truck-fast"></i>
                    </a>
                </div>
            </div>

            <div class="kd-footer-col">
                <h4>Quick Links</h4>

                <a href="{{ $safeRoute('user.dashboard') }}">Dashboard</a>
                <a href="{{ $safeRoute('user.orders.track') }}">Track Order</a>

                @auth('user')
                    <a href="{{ $safeRoute('user.orders.index') }}">Order History</a>
                    <a href="{{ $safeRoute('user.wallet.index') }}">Wallet</a>
                @else
                    @if(($business->allow_login ?? 1) == 1)
                        <a href="{{ $safeRoute('user.login') }}">Login</a>
                    @endif

                    @if(($business->allow_registration ?? 1) == 1 && ($business->registration_system_enabled ?? 1) == 1)
                        <a href="{{ $safeRoute('user.register') }}">Create Account</a>
                    @endif
                @endauth
            </div>

            <div class="kd-footer-col">
                <h4>Services</h4>

                <a href="{{ url('/packages/mtn') }}">MTN Data Bundles</a>
                <a href="{{ url('/packages/telecel') }}">Telecel Data</a>
                <a href="{{ url('/packages/airteltigo') }}">AirtelTigo Data</a>
                <a href="{{ url('/afa-history') }}">AFA Registration</a>
            </div>

            <div class="kd-footer-col">
                <h4>Support</h4>

                <a href="https://wa.me/{{ $waPhone }}" target="_blank" rel="noopener">Need Help?</a>
                <a href="tel:{{ $telPhone }}">Contact Support</a>
                <a href="mailto:{{ $supportEmail }}">Email Support</a>
                <a href="{{ url('/settings') }}">Settings</a>
            </div>

            <div class="kd-footer-contact">
                <h4>Contact Info</h4>

                <p>
                    <i class="fa-solid fa-phone"></i>
                    <a href="tel:{{ $telPhone }}">{{ $telPhone }}</a>
                </p>

                <p>
                    <i class="fa-brands fa-whatsapp"></i>
                    <a href="https://wa.me/{{ $waPhone }}" target="_blank" rel="noopener">
                        WhatsApp Support
                    </a>
                </p>

                <p>
                    <i class="fa-solid fa-envelope"></i>
                    <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
                </p>

                <p>
                    <i class="fa-solid fa-location-dot"></i>
                    {{ $businessAddress }}
                </p>
            </div>

        </div>

        <div class="kd-footer-bottom">
            <p>
                © {{ date('Y') }} {{ $businessName }}.
                All rights reserved.
            </p>

            <p>
                Powered by
                <a href="https://wa.me/233551082710" target="_blank" rel="noopener">
                    Optina Technologies
                </a>
            </p>
        </div>

    </div>
</footer>