@extends('admin.layouts.app')

@section('title', 'Auto Complete Settings')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
    <link rel="stylesheet" href="{{ asset('admin/css/autocomplete-settings.css') }}">
@endpush

@section('content')
@php
    $timerMinutes = (int) ($setting->timer_minutes ?? 30);
    $isEnabled = (int) ($setting->is_enabled ?? 1) === 1;

    if ($timerMinutes >= 1440 && $timerMinutes % 1440 === 0) {
        $timerValue = $timerMinutes / 1440;
        $timerUnit = 'days';
    } elseif ($timerMinutes >= 60 && $timerMinutes % 60 === 0) {
        $timerValue = $timerMinutes / 60;
        $timerUnit = 'hours';
    } else {
        $timerValue = $timerMinutes;
        $timerUnit = 'minutes';
    }

    $hours = floor($timerMinutes / 60);
    $minutes = $timerMinutes % 60;
@endphp

<div class="kd-autocomplete-page">

    <div class="kd-auto-hero">
        <div>
            <span class="kd-auto-badge">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                Automation Settings
            </span>

            <h1>Auto Complete Orders</h1>
            <p>
                Set how long an order should stay in progress before the system automatically marks it as delivered.
            </p>
        </div>

        <div class="kd-auto-status {{ $isEnabled ? 'enabled' : 'disabled' }}" id="kdAutoStatusBadge">
            <span></span>
            <b>{{ $isEnabled ? 'Enabled' : 'Disabled' }}</b>
        </div>
    </div>

    @if(session('success'))
        <div class="kd-auto-alert success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="kd-auto-alert error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="kd-auto-grid">
        <div class="kd-auto-card kd-auto-summary-card">
            <div class="kd-auto-card-icon">
                <i class="fa-solid fa-clock"></i>
            </div>

            <div>
                <span>Current Timer</span>
                <h2 id="kdCurrentTimerText">
                    @if($hours > 0)
                        {{ $hours }}H {{ $minutes }}M
                    @else
                        {{ $timerMinutes }}M
                    @endif
                </h2>
                <p>{{ number_format($timerMinutes) }} minutes after order enters In Progress.</p>
            </div>
        </div>

        <div class="kd-auto-card kd-auto-summary-card">
            <div class="kd-auto-card-icon purple">
                <i class="fa-solid fa-truck-fast"></i>
            </div>

            <div>
                <span>Target Orders</span>
                <h2>In Progress</h2>
                <p>Only orders with in_progress status will be completed automatically.</p>
            </div>
        </div>

        <div class="kd-auto-card kd-auto-summary-card">
            <div class="kd-auto-card-icon green">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <div>
                <span>Final Status</span>
                <h2>Delivered</h2>
                <p>Completed orders will move to delivered status.</p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.settings.autocomplete.update') }}" method="POST" class="kd-auto-form-card">
        @csrf

        <input type="hidden" name="timer_minutes" id="timer_minutes" value="{{ old('timer_minutes', $timerMinutes) }}">

        <div class="kd-auto-form-header">
            <div>
                <h3>Progress Order Timer</h3>
                <p>Control the automatic delivery time for in-progress orders.</p>
            </div>

            <label class="kd-auto-switch">
                <input type="checkbox" id="kdAutoEnabledSwitch" name="is_enabled" value="1" {{ $isEnabled ? 'checked' : '' }}>
                <span></span>
            </label>
        </div>

        <div class="kd-auto-form-body">
            <div class="kd-auto-input-group">
                <label>Timer Setup</label>

                <div class="kd-auto-time-row">
                    <div class="kd-auto-input-wrap">
                        <i class="fa-solid fa-stopwatch"></i>
                        <input
                            type="number"
                            id="timer_value"
                            min="1"
                            max="10080"
                            value="{{ old('timer_value', $timerValue) }}"
                            required
                        >
                    </div>

                    <div class="kd-auto-select-wrap">
                        <select id="timer_unit">
                            <option value="minutes" {{ $timerUnit === 'minutes' ? 'selected' : '' }}>Minutes</option>
                            <option value="hours" {{ $timerUnit === 'hours' ? 'selected' : '' }}>Hours</option>
                            <option value="days" {{ $timerUnit === 'days' ? 'selected' : '' }}>Days</option>
                        </select>
                    </div>
                </div>

                <small>
                    Example: select 2 Hours and the order will auto complete after 120 minutes.
                </small>
            </div>

            <div class="kd-auto-preview-box">
                <span>Preview</span>
                <strong id="kdAutoPreviewText">
                    Orders will auto complete after {{ $timerMinutes }} minutes.
                </strong>
            </div>
        </div>

        <div class="kd-auto-actions">
            <button type="submit" class="kd-auto-save-btn">
                <i class="fa-solid fa-floppy-disk"></i>
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const timerValue = document.getElementById('timer_value');
    const timerUnit = document.getElementById('timer_unit');
    const timerMinutes = document.getElementById('timer_minutes');
    const preview = document.getElementById('kdAutoPreviewText');
    const currentTimer = document.getElementById('kdCurrentTimerText');
    const enabledSwitch = document.getElementById('kdAutoEnabledSwitch');
    const statusBadge = document.getElementById('kdAutoStatusBadge');

    if (timerUnit && window.TomSelect) {
        new TomSelect(timerUnit, {
            create: false,
            searchField: ['text'],
            maxOptions: 10,
            controlInput: null
        });
    }

    function unitToMinutes(value, unit) {
        value = parseInt(value || 0);

        if (!value || value < 1) return 0;

        if (unit === 'hours') return value * 60;
        if (unit === 'days') return value * 1440;

        return value;
    }

    function formatMinutes(totalMinutes) {
        const days = Math.floor(totalMinutes / 1440);
        const remainingAfterDays = totalMinutes % 1440;
        const hours = Math.floor(remainingAfterDays / 60);
        const minutes = remainingAfterDays % 60;

        if (days > 0) {
            return `${days}D ${hours}H ${minutes}M`;
        }

        if (hours > 0) {
            return `${hours}H ${minutes}M`;
        }

        return `${totalMinutes}M`;
    }

    function updatePreview() {
        const unit = timerUnit.value;
        const total = unitToMinutes(timerValue.value, unit);

        if (!total) {
            timerMinutes.value = '';
            preview.textContent = 'Please enter a valid timer.';
            currentTimer.textContent = '0M';
            return;
        }

        timerMinutes.value = total;
        currentTimer.textContent = formatMinutes(total);

        const unitLabel = unit.charAt(0).toUpperCase() + unit.slice(1);
        preview.textContent = `Orders will auto complete after ${timerValue.value} ${unitLabel} (${total} minutes).`;
    }

    function updateStatusBadge() {
        if (!statusBadge || !enabledSwitch) return;

        const label = statusBadge.querySelector('b');

        if (enabledSwitch.checked) {
            statusBadge.classList.remove('disabled');
            statusBadge.classList.add('enabled');
            label.textContent = 'Enabled';
        } else {
            statusBadge.classList.remove('enabled');
            statusBadge.classList.add('disabled');
            label.textContent = 'Disabled';
        }
    }

    timerValue?.addEventListener('input', updatePreview);
    timerUnit?.addEventListener('change', updatePreview);
    enabledSwitch?.addEventListener('change', updateStatusBadge);

    updatePreview();
    updateStatusBadge();
});
</script>
@endpush