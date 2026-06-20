<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ $title ?? '403' }} | KlikDataHub</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            user-select: none;
            -webkit-user-select: none;
            -ms-user-select: none;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            background: #111827;
            color: #ffffff;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow: hidden;
        }

        .kd-error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .kd-error-content {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .kd-error-code {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: .02em;
            color: #f8fafc;
            white-space: nowrap;
        }

        .kd-error-line {
            width: 1px;
            height: 42px;
            background: rgba(255, 255, 255, .45);
            flex-shrink: 0;
        }

        .kd-error-message {
            max-width: 720px;
            font-size: 24px;
            font-weight: 500;
            letter-spacing: .04em;
            text-transform: uppercase;
            line-height: 1.55;
            color: #ffffff;
        }

        @media (max-width: 640px) {
            .kd-error-page {
                padding: 20px;
            }

            .kd-error-content {
                align-items: flex-start;
                gap: 16px;
            }

            .kd-error-code {
                font-size: 20px;
            }

            .kd-error-line {
                height: 36px;
            }

            .kd-error-message {
                font-size: 18px;
                line-height: 1.6;
            }
        }
    </style>
</head>

<body>

<div class="kd-error-page">

    <div class="kd-error-content">

        <div class="kd-error-code">
            403
        </div>

        <div class="kd-error-line"></div>

        <div class="kd-error-message">
            {{ $message ?? 'Direct access to this domain is not allowed. Please use a subdomain.' }}
        </div>

    </div>

</div>

<script>
    document.addEventListener('contextmenu', function (event) {
        event.preventDefault();
    });

    document.addEventListener('keydown', function (event) {
        const key = event.key.toUpperCase();

        if (
            event.key === 'F12' ||
            (event.ctrlKey && event.shiftKey && ['I', 'J', 'C'].includes(key)) ||
            (event.ctrlKey && key === 'U') ||
            (event.ctrlKey && key === 'S')
        ) {
            event.preventDefault();
            return false;
        }
    });

    document.addEventListener('selectstart', function (event) {
        event.preventDefault();
    });

    document.addEventListener('dragstart', function (event) {
        event.preventDefault();
    });
</script>

</body>
</html>