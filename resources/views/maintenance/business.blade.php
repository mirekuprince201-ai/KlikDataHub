<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        {{ $title ?? 'Maintenance Mode' }}
    </title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        referrerpolicy="no-referrer"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/maintenance.css') }}?v={{ time() }}"
    >
</head>

<body>

<main class="kd-maintenance-page">

    <section
        class="kd-maintenance-card"
        id="maintenanceCard"
    >

        <div class="kd-brand">
            KLIKDATAHUB
        </div>

        <div class="kd-main-icon">
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>

        <div class="kd-badge">
            <i class="fa-solid fa-circle-info"></i>
            <span>System Maintenance</span>
        </div>

        <h1>
            {{ $title ?? 'Scheduled Maintenance' }}
        </h1>

        <p>
            {{ $message ?? 'We are currently performing scheduled maintenance and system improvements. Our team is working to restore service as quickly as possible.' }}
        </p>

        <div class="kd-status">
            <span></span>
            Update in progress
        </div>

        <div class="kd-support">
            Need assistance?

            <a href="mailto:support@klikdata.com">
                support@klikdata.com
            </a>
        </div>

        <footer>
            © {{ date('Y') }} KLIKDATAHUB. All Rights Reserved.
        </footer>

    </section>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const card = document.getElementById('maintenanceCard');

        setTimeout(() => {
            card.classList.add('kd-show');
        }, 150);
    });
</script>

</body>
</html>