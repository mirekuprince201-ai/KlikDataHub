@php
    use Illuminate\Support\Facades\DB;

    $business = DB::table('business_settings')->first();

    $businessName = $business->business_name ?? 'KlikDataHub';

    $displayMessage = strtoupper(
        $message
        ?? 'DIRECT ACCESS TO THIS DOMAIN IS NOT ALLOWED. PLEASE USE A SUBDOMAIN.'
    );
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ $title ?? '403' }} - {{ $businessName }}</title>

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            user-select:none;
            -webkit-user-select:none;
            -moz-user-select:none;
            -ms-user-select:none;
        }

        html,
        body{
            width:100%;
            min-height:100%;
        }

        body{
            min-height:100vh;
            overflow:hidden;
            background:#111827;
            color:#ffffff;
            font-family:
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .kd-error-page{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
        }

        .kd-error-content{
            display:flex;
            align-items:center;
            gap:24px;
            max-width:950px;
        }

        .kd-error-code{
            font-size:28px;
            font-weight:800;
            color:#ffffff;
            white-space:nowrap;
            flex-shrink:0;
        }

        .kd-error-line{
            width:1px;
            height:48px;
            background:rgba(255,255,255,.45);
            flex-shrink:0;
        }

        .kd-error-message{
            color:#ffffff;
            font-size:24px;
            font-weight:500;
            letter-spacing:.04em;
            line-height:1.65;
            text-transform:uppercase;
            word-break:break-word;
        }

        @media(max-width:768px){

            .kd-error-content{
                gap:18px;
            }

            .kd-error-code{
                font-size:24px;
            }

            .kd-error-message{
                font-size:18px;
                line-height:1.7;
            }
        }

        @media(max-width:480px){

            .kd-error-content{
                gap:14px;
            }

            .kd-error-code{
                font-size:20px;
            }

            .kd-error-line{
                height:40px;
            }

            .kd-error-message{
                font-size:16px;
                line-height:1.75;
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
            {{ $displayMessage }}
        </div>

    </div>

</div>

<script>
    document.addEventListener('contextmenu', function(e){
        e.preventDefault();
    });

    document.addEventListener('keydown', function(e){

        const key = e.key.toUpperCase();

        if(
            e.key === 'F12' ||
            (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(key)) ||
            (e.ctrlKey && key === 'U') ||
            (e.ctrlKey && key === 'S')
        ){
            e.preventDefault();
            return false;
        }
    });

    document.addEventListener('selectstart', function(e){
        e.preventDefault();
    });

    document.addEventListener('dragstart', function(e){
        e.preventDefault();
    });
</script>

</body>
</html>