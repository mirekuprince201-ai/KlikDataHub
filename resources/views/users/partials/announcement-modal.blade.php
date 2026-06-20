@php
    use Illuminate\Support\Facades\DB;

    $business = DB::table('business_settings')->first();

    $announcementActive = strtolower(trim($business->announcement_status ?? 'inactive')) === 'active';

    $announcementTitle = $business->announcement_title ?? 'Announcement';
    $announcementMessage = $business->announcement_message ?? '';
    $announcementTemplate = $business->announcement_template ?? 'general';
    $announcementEmoji = $business->announcement_emoji ?? '📢';
    $announcementButtonText = $business->announcement_button_text ?? 'Got It';

    $templateClass = 'kd-template-' . $announcementTemplate;
@endphp

@if($announcementActive && filled($announcementMessage))
    <div class="kd-announcement-center" id="kdAnnouncementPopup">
        <div class="kd-announcement-backdrop"></div>

        <div class="kd-announcement-card {{ $templateClass }}">

            <button type="button" class="kd-announcement-close" id="kdAnnouncementClose">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="kd-announcement-head">
                <div class="kd-announcement-emoji">
                    {{ $announcementEmoji }}
                </div>

                <div>
                    <span class="kd-announcement-badge">
                        Official Announcement
                    </span>

                    <h3>{{ $announcementTitle }}</h3>
                </div>
            </div>

            <div class="kd-announcement-content">
                {!! nl2br(e($announcementMessage)) !!}
            </div>

            <button type="button" class="kd-announcement-btn" id="kdAnnouncementBtn">
                <i class="fa-solid fa-circle-check"></i>
                {{ $announcementButtonText }}
            </button>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('kdAnnouncementPopup');

            if (!popup) return;

            const storageKey = 'kd_announcement_{{ $business->id ?? 1 }}_{{ strtotime($business->updated_at ?? now()) }}';

            if (localStorage.getItem(storageKey) === 'closed') {
                return;
            }

            setTimeout(function () {
                popup.classList.add('show');
                document.body.classList.add('kd-announcement-is-open');
            }, 500);

            function closeAnnouncement() {
                popup.classList.remove('show');
                document.body.classList.remove('kd-announcement-is-open');
                localStorage.setItem(storageKey, 'closed');
            }

            document.getElementById('kdAnnouncementClose')?.addEventListener('click', closeAnnouncement);
            document.getElementById('kdAnnouncementBtn')?.addEventListener('click', closeAnnouncement);
        });
    </script>
@endif