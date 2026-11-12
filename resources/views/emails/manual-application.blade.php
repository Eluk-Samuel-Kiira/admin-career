<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Application: {{ $jobTitle ?? 'Job Application' }}</title>
</head>
<body style="margin:0;padding:0;background:#F4F8F7;font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0F1B2D;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="padding:24px 12px;background:#F4F8F7;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;background:#fff;border-radius:12px;
                      border:1px solid #E5EBEA;overflow:hidden;">

            {{-- ── Header ── --}}
            <tr>
                <td style="background:linear-gradient(135deg,#20AA3E 0%,#03A588 100%);
                           padding:28px 32px;color:#fff;">
                    <div style="font-size:11px;font-weight:700;letter-spacing:0.08em;
                                text-transform:uppercase;color:#BFF7D2;margin-bottom:8px;opacity:.95;">
                        Job Application
                    </div>
                    <h1 style="margin:0;font-size:22px;line-height:1.25;font-weight:800;color:#fff;">
                        Application: {{ $jobTitle }}
                    </h1>
                    @if(!empty($companyName))
                        <p style="margin:8px 0 0;font-size:14px;opacity:.9;line-height:1.4;">
                            {{ $companyName }}
                        </p>
                    @endif
                </td>
            </tr>

            {{-- ── Candidate identity ── --}}
            <tr>
                <td style="padding:20px 32px;border-bottom:1px solid #E5EBEA;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                        <tr>
                            <td width="48" valign="top" style="padding-right:12px;">
                                @php
                                    $initials = '';
                                    foreach (preg_split('/\s+/', trim((string) $seekerName)) as $part) {
                                        if ($part !== '') $initials .= strtoupper($part[0]);
                                        if (strlen($initials) >= 2) break;
                                    }
                                    $initials = $initials ?: 'AP';
                                @endphp
                                <div style="width:48px;height:48px;border-radius:50%;
                                            background:linear-gradient(135deg,#20AA3E 0%,#03A588 100%);
                                            color:#fff;font-weight:800;font-size:15px;
                                            text-align:center;line-height:48px;">
                                    {{ $initials }}
                                </div>
                            </td>
                            <td valign="middle" style="line-height:1.4;">
                                <div style="font-size:16px;font-weight:800;color:#0F1B2D;">
                                    {{ $seekerName }}
                                </div>
                                @if(!empty($seekerEmail))
                                    <div style="font-size:13px;color:#64748B;margin-top:2px;">
                                        {{ $seekerEmail }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            {{-- ── Body ── --}}
            <tr>
                <td style="padding:28px 32px;font-size:15px;line-height:1.65;color:#33475B;">
                    <p>Dear Hiring Manager,</p>

                    <p>
                        Please find attached my application for the
                        <strong>{{ $jobTitle }}</strong> role
                        @if(!empty($companyName)) at {{ $companyName }} @endif.
                    </p>

                    @if(!empty($hasCoverLetter))
                        <p>
                            My cover letter and curriculum vitae are attached to this email.
                            They outline the experience, qualifications and skills I would bring
                            to the role.
                        </p>
                    @else
                        <p>
                            My curriculum vitae is attached to this email and provides the
                            details of my experience, qualifications and skills.
                        </p>
                    @endif

                    <p>
                        I would welcome the opportunity to discuss my application with you
                        at your earliest convenience.
                    </p>

                    <p>Thank you for your time and consideration.</p>

                    <p style="margin-top:24px;">
                        Yours faithfully,<br>
                        <strong style="color:#0F1B2D;">{{ $seekerName }}</strong>
                    </p>
                </td>
            </tr>

            {{-- ── Attachments ── --}}
            <tr>
                <td style="padding:0 32px 24px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                           style="background:#F4F8F7;border-radius:8px;padding:16px 20px;">
                        <tr>
                            <td style="font-size:13px;color:#64748B;line-height:1.6;">
                                <strong style="color:#0F1B2D;display:block;margin-bottom:6px;">
                                    Attached documents
                                </strong>
                                @foreach($attachmentNames as $name)
                                    ✓ {{ $name }}<br>
                                @endforeach
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            {{-- ── Footer ── --}}
            <tr>
                <td style="background:#F4F8F7;padding:18px 32px;text-align:center;
                           font-size:11px;color:#94A3B8;line-height:1.5;">
                    Sent on behalf of <strong style="color:#33475B;">{{ $seekerName }}</strong>
                    via {{ config('app.name') }}
                </td>
            </tr>

        </table>
    </td></tr>
</table>

</body>
</html>