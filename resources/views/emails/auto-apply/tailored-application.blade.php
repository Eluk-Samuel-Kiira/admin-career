<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your tailored CV</title>
</head>
<body style="margin:0;padding:0;background:#F4F8F7;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="padding:24px;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                   style="max-width:560px;background:#fff;border-radius:12px;border:1px solid #E5EBEA;overflow:hidden;">
                <tr>
                    <td style="background:#03A588;padding:24px 28px;color:#fff;">
                        <h1 style="margin:0;font-size:18px;">Ready to apply: {{ $job->job_title }}</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;color:#0F1B2D;font-size:15px;line-height:1.6;">
                        <p>Hi {{ $seeker->first_name ?? 'there' }},</p>
                        <p>Your tailored CV and cover letter for <strong>{{ $job->job_title }}</strong>
                           are attached. Apply directly using the employer's channel.</p>
                        <p style="text-align:center;margin:24px 0;">
                            <a href="{{ url('/job/' . $job->slug) }}"
                               style="display:inline-block;padding:12px 22px;background:#20AA3E;color:#fff;
                                      text-decoration:none;border-radius:8px;font-weight:700;">
                                View Job &amp; Apply
                            </a>
                        </p>
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>