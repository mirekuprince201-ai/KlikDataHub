@extends('admin.layouts.app')

@section('title', 'Business Settings')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/business-settings.css') }}?v={{ time() }}">
@endpush

@section('content')
@php
    $selectedDays = !empty($setting->working_days)
        ? explode(',', $setting->working_days)
        : [];

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    $announcementStatus = old('announcement_status', $setting->announcement_status ?? 'inactive');
    $announcementTemplate = old('announcement_template', $setting->announcement_template ?? 'general');

    $templates = [
        'general' => ['label' => 'General Notice', 'emoji' => '📢'],
        'christmas' => ['label' => 'Christmas Message', 'emoji' => '🎄'],
        'new_year' => ['label' => 'New Year Message', 'emoji' => '🎆'],
        'promo' => ['label' => 'Promo / Offer', 'emoji' => '🔥'],
        'maintenance' => ['label' => 'Maintenance Notice', 'emoji' => '🛠️'],
        'success' => ['label' => 'Success Message', 'emoji' => '✅'],
        'warning' => ['label' => 'Warning Alert', 'emoji' => '⚠️'],
        'info' => ['label' => 'Information', 'emoji' => 'ℹ️'],
    ];

    $boolValue = function ($field, $default = 1) use ($setting) {
        return old($field, $setting->{$field} ?? $default) ? true : false;
    };
@endphp

<div class="kd-business-settings-page">

    <div class="kd-settings-header">
        <div class="kd-settings-header-left">
            <div class="kd-settings-header-icon">
                <i class="fa-solid fa-sliders"></i>
            </div>

            <div>
                <span class="kd-page-badge">
                    <i class="fa-solid fa-shield-halved"></i>
                    Admin Control Center
                </span>

                <h3>Business Settings</h3>

                <p>
                    Control business information, announcements, maintenance mode,
                    access permissions, payment systems and support details.
                </p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="kd-alert kd-alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="kd-alert kd-alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>

            <div>
                <strong>Please fix the following errors:</strong>

                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.business.update') }}" class="kd-settings-form">
        @csrf

        <div class="kd-settings-grid">

            {{-- BUSINESS INFORMATION --}}
            <div class="kd-settings-card">
                <div class="kd-card-title">
                    <div class="kd-card-icon">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>

                    <div>
                        <h4>Business Information</h4>
                        <p>Primary company details shown across the platform.</p>
                    </div>
                </div>

                <div class="kd-form-group">
                    <label>Business Name</label>
                    <input
                        type="text"
                        name="business_name"
                        value="{{ old('business_name', $setting->business_name ?? '') }}"
                        placeholder="Enter business name"
                    >
                </div>

                <div class="kd-form-row">
                    <div class="kd-form-group">
                        <label>Business Email</label>
                        <input
                            type="email"
                            name="business_email"
                            value="{{ old('business_email', $setting->business_email ?? '') }}"
                            placeholder="support@example.com"
                        >
                    </div>

                    <div class="kd-form-group">
                        <label>Business Phone</label>
                        <input
                            type="text"
                            name="business_phone"
                            value="{{ old('business_phone', $setting->business_phone ?? '') }}"
                            placeholder="0240000000"
                        >
                    </div>
                </div>

                <div class="kd-form-group">
                    <label>Business Address</label>
                    <textarea name="business_address" placeholder="Enter business address">{{ old('business_address', $setting->business_address ?? '') }}</textarea>
                </div>
            </div>

            {{-- BUSINESS HOURS --}}
            <div class="kd-settings-card">
                <div class="kd-card-title">
                    <div class="kd-card-icon">
                        <i class="fa-solid fa-business-time"></i>
                    </div>

                    <div>
                        <h4>Business Hours</h4>
                        <p>Set opening time, closing time and working days.</p>
                    </div>
                </div>

                <div class="kd-form-row">
                    <div class="kd-form-group">
                        <label>Opening Time</label>
                        <input
                            type="time"
                            name="open_time"
                            value="{{ old('open_time', !empty($setting->open_time) ? substr($setting->open_time, 0, 5) : '') }}"
                        >
                    </div>

                    <div class="kd-form-group">
                        <label>Closing Time</label>
                        <input
                            type="time"
                            name="close_time"
                            value="{{ old('close_time', !empty($setting->close_time) ? substr($setting->close_time, 0, 5) : '') }}"
                        >
                    </div>
                </div>

                <div class="kd-days-box">
                    <label class="kd-main-label">Working Days</label>

                    <div class="kd-days-inline">
                        @foreach($days as $day)
                            <label class="kd-check-pill">
                                <input
                                    type="checkbox"
                                    name="working_days[]"
                                    value="{{ $day }}"
                                    {{ in_array($day, old('working_days', $selectedDays)) ? 'checked' : '' }}
                                >

                                <span>
                                    <i class="fa-solid fa-calendar-check"></i>
                                    {{ $day }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- PROFESSIONAL SWITCH CONTROL CENTER --}}
            <div class="kd-settings-card kd-wide-card">
                <div class="kd-card-title">
                    <div class="kd-card-icon">
                        <i class="fa-solid fa-toggle-on"></i>
                    </div>

                    <div>
                        <h4>System Control Switches</h4>
                        <p>Grouped professional switches for announcement, maintenance, access and payment controls.</p>
                    </div>
                </div>

                <div class="kd-switch-master-grid">

                    {{-- Announcement --}}
                    <div class="kd-switch-group">
                        <div class="kd-switch-group-head">
                            <span>
                                <i class="fa-solid fa-bullhorn"></i>
                            </span>

                            <div>
                                <h5>Announcement Control</h5>
                                <p>Show or hide announcement popup for users.</p>
                            </div>
                        </div>

                        <input
                            type="hidden"
                            name="announcement_status"
                            id="announcementStatusValue"
                            value="{{ $announcementStatus }}"
                        >

                        <label class="kd-pro-switch-row">
                            <span>
                                <strong>Announcement Display</strong>
                                <small id="announcementStatusText">
                                    {{ $announcementStatus === 'active'
                                        ? 'Active - users will see this announcement.'
                                        : 'Inactive - announcement is hidden from users.' }}
                                </small>
                            </span>

                            <input
                                type="checkbox"
                                id="announcementStatusSwitch"
                                data-auto-toggle="true"
                                data-field="announcement_status"
                                {{ $announcementStatus === 'active' ? 'checked' : '' }}
                            >

                            <em></em>
                        </label>
                    </div>

                    {{-- Maintenance --}}
                    <div class="kd-switch-group">
                        <div class="kd-switch-group-head">
                            <span>
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </span>

                            <div>
                                <h5>Maintenance Control</h5>
                                <p>Pause user access while working on updates.</p>
                            </div>
                        </div>

                        <input type="hidden" name="maintenance_mode" value="0">

                        <label class="kd-pro-switch-row">
                            <span>
                                <strong>Maintenance Mode</strong>
                                <small>When enabled, normal users cannot use the system.</small>
                            </span>

                            <input
                                type="checkbox"
                                name="maintenance_mode"
                                value="1"
                                data-auto-toggle="true"
                                data-field="maintenance_mode"
                                {{ $boolValue('maintenance_mode', 0) ? 'checked' : '' }}
                            >

                            <em></em>
                        </label>
                    </div>

                    {{-- Access --}}
                    <div class="kd-switch-group">
                        <div class="kd-switch-group-head">
                            <span>
                                <i class="fa-solid fa-user-shield"></i>
                            </span>

                            <div>
                                <h5>Access Control</h5>
                                <p>Control registration and login permissions.</p>
                            </div>
                        </div>

                        <input type="hidden" name="allow_registration" value="0">
                        <input type="hidden" name="allow_login" value="0">
                        <input type="hidden" name="registration_system_enabled" value="0">

                        <label class="kd-pro-switch-row">
                            <span>
                                <strong>Allow Registration</strong>
                                <small>Users can create new accounts.</small>
                            </span>

                            <input
                                type="checkbox"
                                name="allow_registration"
                                value="1"
                                data-auto-toggle="true"
                                data-field="allow_registration"
                                {{ $boolValue('allow_registration', 1) ? 'checked' : '' }}
                            >

                            <em></em>
                        </label>

                        <label class="kd-pro-switch-row">
                            <span>
                                <strong>Allow Login</strong>
                                <small>Users can login to their accounts.</small>
                            </span>

                            <input
                                type="checkbox"
                                name="allow_login"
                                value="1"
                                data-auto-toggle="true"
                                data-field="allow_login"
                                {{ $boolValue('allow_login', 1) ? 'checked' : '' }}
                            >

                            <em></em>
                        </label>

                        <label class="kd-pro-switch-row">
                            <span>
                                <strong>Registration System</strong>
                                <small>Enable or disable registration module globally.</small>
                            </span>

                            <input
                                type="checkbox"
                                name="registration_system_enabled"
                                value="1"
                                data-auto-toggle="true"
                                data-field="registration_system_enabled"
                                {{ $boolValue('registration_system_enabled', 1) ? 'checked' : '' }}
                            >

                            <em></em>
                        </label>
                    </div>

                    {{-- Payment --}}
                    <div class="kd-switch-group">
                        <div class="kd-switch-group-head">
                            <span>
                                <i class="fa-solid fa-money-check-dollar"></i>
                            </span>

                            <div>
                                <h5>Payment Control</h5>
                                <p>Control all Paystack and wallet funding buttons.</p>
                            </div>
                        </div>

                        <input type="hidden" name="payment_system_enabled" value="0">
                        <input type="hidden" name="paystack_enabled" value="0">

                        <label class="kd-pro-switch-row">
                            <span>
                                <strong>Payment System</strong>
                                <small>Enable or disable all payment buttons globally.</small>
                            </span>

                            <input
                                type="checkbox"
                                name="payment_system_enabled"
                                value="1"
                                data-auto-toggle="true"
                                data-field="payment_system_enabled"
                                {{ $boolValue('payment_system_enabled', 1) ? 'checked' : '' }}
                            >

                            <em></em>
                        </label>

                        <label class="kd-pro-switch-row">
                            <span>
                                <strong>Paystack Payments</strong>
                                <small>Controls Paystack card, MoMo, topup and online payments.</small>
                            </span>

                            <input
                                type="checkbox"
                                name="paystack_enabled"
                                value="1"
                                data-auto-toggle="true"
                                data-field="paystack_enabled"
                                {{ $boolValue('paystack_enabled', 1) ? 'checked' : '' }}
                            >

                            <em></em>
                        </label>
                    </div>

                </div>
            </div>

            {{-- ANNOUNCEMENT CENTER --}}
            <div class="kd-settings-card kd-wide-card">
                <div class="kd-card-title">
                    <div class="kd-card-icon">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>

                    <div>
                        <h4>Announcement Center</h4>
                        <p>Create professional pop-up messages for users.</p>
                    </div>
                </div>

                <div class="kd-form-row">
                    <div class="kd-form-group">
                        <label>Announcement Template</label>

                        <select name="announcement_template" id="announcementTemplate">
                            @foreach($templates as $key => $template)
                                <option
                                    value="{{ $key }}"
                                    data-emoji="{{ $template['emoji'] }}"
                                    data-label="{{ $template['label'] }}"
                                    {{ $announcementTemplate === $key ? 'selected' : '' }}
                                >
                                    {{ $template['emoji'] }} {{ $template['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="kd-form-group">
                        <label>Announcement Emoji</label>
                        <input
                            type="text"
                            name="announcement_emoji"
                            id="announcementEmoji"
                            value="{{ old('announcement_emoji', $setting->announcement_emoji ?? ($templates[$announcementTemplate]['emoji'] ?? '📢')) }}"
                            placeholder="📢"
                        >
                    </div>
                </div>

                <div class="kd-form-group">
                    <label>Announcement Title</label>
                    <input
                        type="text"
                        name="announcement_title"
                        id="announcementTitle"
                        value="{{ old('announcement_title', $setting->announcement_title ?? '') }}"
                        placeholder="Enter announcement title"
                    >
                </div>

                <div class="kd-form-group">
                    <label>Announcement Message</label>
                    <textarea
                        name="announcement_message"
                        id="announcementMessage"
                        placeholder="Enter announcement message"
                    >{{ old('announcement_message', $setting->announcement_message ?? '') }}</textarea>
                </div>

                <div class="kd-form-group">
                    <label>Button Text</label>
                    <input
                        type="text"
                        name="announcement_button_text"
                        id="announcementButtonText"
                        value="{{ old('announcement_button_text', $setting->announcement_button_text ?? 'Okay, I Understand') }}"
                        placeholder="Okay, I Understand"
                    >
                </div>

                <div class="kd-announcement-preview">
                    <div class="kd-ann-preview-icon" id="announcementPreviewEmoji">
                        {{ old('announcement_emoji', $setting->announcement_emoji ?? ($templates[$announcementTemplate]['emoji'] ?? '📢')) }}
                    </div>

                    <div>
                        <span>Live Preview</span>

                        <h5 id="announcementPreviewTitle">
                            {{ old('announcement_title', $setting->announcement_title ?? 'Announcement Title') }}
                        </h5>

                        <p id="announcementPreviewMessage">
                            {{ old('announcement_message', $setting->announcement_message ?? 'Your announcement message will appear here.') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- MAINTENANCE DETAILS --}}
            <div class="kd-settings-card">
                <div class="kd-card-title">
                    <div class="kd-card-icon">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>

                    <div>
                        <h4>Maintenance Details</h4>
                        <p>Message shown when maintenance mode is active.</p>
                    </div>
                </div>

                <div class="kd-form-group">
                    <label>Maintenance Title</label>
                    <input
                        type="text"
                        name="maintenance_title"
                        value="{{ old('maintenance_title', $setting->maintenance_title ?? '') }}"
                        placeholder="Maintenance Mode"
                    >
                </div>

                <div class="kd-form-group">
                    <label>Maintenance Message</label>
                    <textarea name="maintenance_message" placeholder="Enter maintenance message">{{ old('maintenance_message', $setting->maintenance_message ?? '') }}</textarea>
                </div>
            </div>

            {{-- DEVELOPER ACCESS --}}
            <div class="kd-settings-card">
                <div class="kd-card-title">
                    <div class="kd-card-icon">
                        <i class="fa-solid fa-code"></i>
                    </div>

                    <div>
                        <h4>Developer Access</h4>
                        <p>This email can bypass maintenance mode when logged in.</p>
                    </div>
                </div>

                <div class="kd-form-group">
                    <label>Developer Email</label>
                    <input
                        type="email"
                        name="developer_email"
                        value="{{ old('developer_email', $setting->developer_email ?? '') }}"
                        placeholder="developer@klikdatahub.com"
                    >
                </div>
            </div>

            {{-- PAYMENT NOTICE --}}
            <div class="kd-settings-card kd-wide-card">
                <div class="kd-card-title">
                    <div class="kd-card-icon">
                        <i class="fa-solid fa-wallet"></i>
                    </div>

                    <div>
                        <h4>Payment Disabled Message</h4>
                        <p>This message appears when payments are disabled.</p>
                    </div>
                </div>

                <div class="kd-form-group">
                    <label>Payment Disabled Message</label>

                    <textarea
                        name="payment_notice"
                        placeholder="Payments are temporarily unavailable. Please try again later."
                    >{{ old('payment_notice', $setting->payment_notice ?? '') }}</textarea>
                </div>
            </div>

            {{-- SUPPORT --}}
            <div class="kd-settings-card kd-wide-card">
                <div class="kd-card-title">
                    <div class="kd-card-icon">
                        <i class="fa-solid fa-headset"></i>
                    </div>

                    <div>
                        <h4>Support Details</h4>
                        <p>Customer support contact information.</p>
                    </div>
                </div>

                <div class="kd-form-row">
                    <div class="kd-form-group">
                        <label>Support WhatsApp</label>
                        <input
                            type="text"
                            name="support_whatsapp"
                            value="{{ old('support_whatsapp', $setting->support_whatsapp ?? '') }}"
                            placeholder="0240000000"
                        >
                    </div>

                    <div class="kd-form-group">
                        <label>Support Email</label>
                        <input
                            type="email"
                            name="support_email"
                            value="{{ old('support_email', $setting->support_email ?? '') }}"
                            placeholder="support@example.com"
                        >
                    </div>
                </div>
            </div>

        </div>

        <div class="kd-settings-actions">
            <button type="submit">
                <i class="fa-solid fa-floppy-disk"></i>
                Save Business Settings
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = "{{ csrf_token() }}";

    const toggleUrl = "{{ \Illuminate\Support\Facades\Route::has('admin.settings.business.toggle')
        ? route('admin.settings.business.toggle')
        : (\Illuminate\Support\Facades\Route::has('settings.business.toggle')
            ? route('settings.business.toggle')
            : url('/admin/settings/business/toggle')) }}";

    const announcementSwitch = document.getElementById('announcementStatusSwitch');
    const announcementStatusValue = document.getElementById('announcementStatusValue');
    const announcementStatusText = document.getElementById('announcementStatusText');

    const templateSelect = document.getElementById('announcementTemplate');
    const emojiInput = document.getElementById('announcementEmoji');
    const titleInput = document.getElementById('announcementTitle');
    const messageInput = document.getElementById('announcementMessage');
    const buttonInput = document.getElementById('announcementButtonText');

    const previewEmoji = document.getElementById('announcementPreviewEmoji');
    const previewTitle = document.getElementById('announcementPreviewTitle');
    const previewMessage = document.getElementById('announcementPreviewMessage');

    const templateDefaults = {
        general: {
            emoji: '📢',
            title: 'Important Announcement',
            message: 'Please take note of this important update from KLIKDATA.',
            button: 'Okay, I Understand'
        },
        christmas: {
            emoji: '🎄',
            title: 'Merry Christmas',
            message: 'Wishing you joy, peace and happiness this Christmas season. Thank you for choosing KLIKDATA.',
            button: 'Merry Christmas 🎁'
        },
        new_year: {
            emoji: '🎆',
            title: 'Happy New Year',
            message: 'Welcome to a new year. We appreciate your trust and support. More exciting offers are coming your way.',
            button: 'Celebrate 🎉'
        },
        promo: {
            emoji: '🔥',
            title: 'Special Offer',
            message: 'Enjoy amazing data bundle deals today. Top up your wallet and place your order instantly.',
            button: 'View Offers'
        },
        maintenance: {
            emoji: '🛠️',
            title: 'Scheduled Maintenance',
            message: 'We are improving our services. Some features may be temporarily unavailable. Thank you for your patience.',
            button: 'Noted ✅'
        },
        success: {
            emoji: '✅',
            title: 'Good News',
            message: 'Everything is working smoothly. Thank you for staying connected with KLIKDATA.',
            button: 'Great'
        },
        warning: {
            emoji: '⚠️',
            title: 'Important Notice',
            message: 'Please read this update carefully before continuing to use the platform.',
            button: 'I Understand'
        },
        info: {
            emoji: 'ℹ️',
            title: 'Information',
            message: 'Here is an important update for all users.',
            button: 'Got It'
        }
    };

    function showToast(message, type = 'success') {
        if (window.Notyf) {
            const notyf = new Notyf({
                duration: 2800,
                position: { x: 'right', y: 'top' },
                ripple: true,
                dismissible: true
            });

            type === 'success' ? notyf.success(message) : notyf.error(message);
            return;
        }

        console[type === 'success' ? 'log' : 'error'](message);
    }

    function updateAnnouncementStatusText() {
        if (!announcementSwitch || !announcementStatusValue || !announcementStatusText) return;

        const isActive = announcementSwitch.checked;

        announcementStatusValue.value = isActive ? 'active' : 'inactive';

        announcementStatusText.textContent = isActive
            ? 'Active - users will see this announcement.'
            : 'Inactive - announcement is hidden from users.';
    }

    function updatePreview() {
        if (previewEmoji) {
            previewEmoji.textContent = emojiInput?.value.trim() || '📢';
        }

        if (previewTitle) {
            previewTitle.textContent = titleInput?.value.trim() || 'Announcement Title';
        }

        if (previewMessage) {
            previewMessage.textContent = messageInput?.value.trim() || 'Your announcement message will appear here.';
        }
    }

    function applyTemplateDefaults() {
        const selected = templateSelect?.value || 'general';
        const data = templateDefaults[selected] || templateDefaults.general;

        if (emojiInput) emojiInput.value = data.emoji;
        if (titleInput) titleInput.value = data.title;
        if (messageInput) messageInput.value = data.message;
        if (buttonInput) buttonInput.value = data.button;

        updatePreview();
    }

    async function readResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return await response.json();
        }

        const text = await response.text();

        return {
            success: false,
            message: text ? text.substring(0, 250) : 'Server returned an invalid response.'
        };
    }

    async function saveSwitchInstantly(input) {
        const field = input.dataset.field;

        if (!field) return;

        const previousState = !input.checked;

        let value;

        if (field === 'announcement_status') {
            value = input.checked ? 'active' : 'inactive';
            updateAnnouncementStatusText();
        } else {
            value = input.checked ? 1 : 0;
        }

        input.disabled = true;
        input.closest('label')?.classList.add('kd-switch-saving');

        try {
            const response = await fetch(toggleUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    field: field,
                    value: value,
                    _token: csrfToken
                })
            });

            const data = await readResponse(response);

            if (!response.ok || data.success !== true) {
                throw new Error(data.message || 'Update failed.');
            }

            showToast('Setting updated automatically.', 'success');
        } catch (error) {
            input.checked = previousState;

            if (field === 'announcement_status') {
                updateAnnouncementStatusText();
            }

            showToast(error.message || 'Could not update setting. Please try again.', 'error');
        } finally {
            input.disabled = false;
            input.closest('label')?.classList.remove('kd-switch-saving');
        }
    }

    document.querySelectorAll('[data-auto-toggle="true"]').forEach(function (input) {
        input.addEventListener('change', function () {
            saveSwitchInstantly(input);
        });
    });

    templateSelect?.addEventListener('change', applyTemplateDefaults);
    emojiInput?.addEventListener('input', updatePreview);
    titleInput?.addEventListener('input', updatePreview);
    messageInput?.addEventListener('input', updatePreview);

    updateAnnouncementStatusText();
    updatePreview();
});
</script>
@endpush