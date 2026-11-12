<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $emailSubject ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#F4F8F7;font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0F1B2D;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#F4F8F7;padding:24px 12px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid rgba(15,27,45,0.08);">

            <tr>
                <td style="background:linear-gradient(135deg,#20AA3E 0%,#03A588 100%);background-color:#20AA3E;
                           padding:28px 32px;color:#ffffff;">
                    <div style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;
                                color:#BFF7D2;margin-bottom:8px;opacity:0.95;">
                        {{ config('app.name') }}
                    </div>
                    <h1 style="margin:0;font-size:22px;line-height:1.25;font-weight:800;color:#ffffff;">
                        {{ $recipientName ? 'Hello ' . $recipientName . ',' : 'Hello,' }}
                    </h1>
                </td>
            </tr>

            <tr>
                <td style="padding:28px 32px;font-size:15px;line-height:1.6;color:#33475B;">
                    {!! $body !!}
                </td>
            </tr>

            <tr>
                <td style="background:#F4F8F7;padding:20px 32px;text-align:center;
                           font-size:12px;line-height:1.5;color:#64748B;">
                    Sent by {{ config('app.name') }}<br>
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </td>
            </tr>

        </table>
    </td></tr>
</table>

</body>
</html>