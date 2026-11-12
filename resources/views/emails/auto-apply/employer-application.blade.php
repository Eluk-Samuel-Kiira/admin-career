<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Application for {{ $job->job_title }}</title>
</head>
<body style="margin:0;padding:0;background:#F4F8F7;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="max-width:600px;background:#fff;border-radius:12px;border:1px solid #E5EBEA;overflow:hidden;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#20AA3E 0%,#03A588 100%);
                                   padding:28px 32px;color:#fff;">
                            <h1 style="margin:0;font-size:20px;font-weight:700;">
                                Application: {{ $job->job_title }}
                            </h1>
                            <p style="margin:6px 0 0;font-size:14px;opacity:0.9;">
                                From {{ trim(($seeker->first_name ?? '') . ' ' . ($seeker->last_name ?? '')) }}
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:28px 32px;color:#0F1B2D;font-size:15px;line-height:1.6;">
                            {!! $coverHtml ?? '<p>Please find attached my CV for this role.</p>' !!}

                            <hr style="border:none;border-top:1px solid #E5EBEA;margin:24px 0;">

                            <p style="margin:0;font-size:13px;color:#64748B;">
                                <strong style="color:#0F1B2D;">Attached documents:</strong><br>
                                • CV (PDF)<br>
                                @if(!empty($coverHtml))
                                    • Cover Letter (PDF)
                                @endif
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#F4F8F7;padding:18px 32px;text-align:center;
                                   font-size:12px;color:#64748B;">
                            Sent via {{ config('app.name') }}
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>