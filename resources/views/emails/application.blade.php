{{-- resources/views/emails/application.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $emailSubject }}</title>
</head>
<body style="margin:0;padding:0;background:#F4F8F7;font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0F1B2D;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F4F8F7;padding:24px 12px;">
<tr><td align="center">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="max-width:640px;background:#ffffff;border:1px solid #E5EBEA;border-radius:12px;overflow:hidden;">

        {{-- Accent bar --}}
        <tr><td style="height:5px;background:#20AA3E;background-image:linear-gradient(90deg,#20AA3E,#03A588);font-size:0;line-height:0;">&nbsp;</td></tr>

        {{-- Applicant identity --}}
        <tr>
            <td style="padding:24px 32px 18px;border-bottom:1px solid #E5EBEA;">
                @php
                    $initials = '';
                    foreach (preg_split('/\s+/', trim($seekerName)) as $part) {
                        if ($part !== '') $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                        if (mb_strlen($initials) >= 2) break;
                    }
                @endphp
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td width="52" valign="middle">
                            <div style="width:44px;height:44px;border-radius:50%;background:#20AA3E;background-image:linear-gradient(135deg,#20AA3E,#03A588);color:#fff;font-weight:800;font-size:15px;text-align:center;line-height:44px;">{{ $initials ?: 'A' }}</div>
                        </td>
                        <td valign="middle" style="line-height:1.35;">
                            <div style="font-size:16px;font-weight:800;color:#0F1B2D;">{{ $seekerName }}</div>
                            <div style="font-size:13px;color:#64748B;">
                                Application
                                @if(!empty($jobTitle)) for <strong style="color:#33475B;">{{ $jobTitle }}</strong>@endif
                                @if(!empty($companyName)) &middot; {{ $companyName }}@endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- Message body (composed by the sender) --}}
        <tr>
            <td style="padding:26px 32px 8px;font-size:15px;line-height:1.65;color:#27384A;">
                {!! $bodyHtml !!}
            </td>
        </tr>

        {{-- Signature --}}
        <tr>
            <td style="padding:8px 32px 24px;font-size:14px;line-height:1.5;color:#27384A;">
                <strong style="color:#0F1B2D;">{{ $seekerName }}</strong>
                @if(!empty($seekerEmail))
                    <br><a href="mailto:{{ $seekerEmail }}" style="color:#0A8F6B;text-decoration:none;">{{ $seekerEmail }}</a>
                @endif
            </td>
        </tr>

        {{-- Attachments --}}
        @if(count($files))
        <tr>
            <td style="padding:0 32px 28px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="background:#F4F8F7;border:1px solid #E5EBEA;border-radius:8px;">
                    <tr>
                        <td style="padding:14px 18px;font-size:13px;line-height:1.7;color:#475569;">
                            <strong style="color:#0F1B2D;display:block;margin-bottom:4px;">
                                {{ count($files) }} attached {{ count($files) === 1 ? 'document' : 'documents' }}
                            </strong>
                            @foreach($files as $f)
                                &#128206; {{ $f['original_name'] }}
                                @if(!empty($f['size'])) <span style="color:#94A3B8;">({{ number_format($f['size'] / 1024) }} KB)</span>@endif
                                <br>
                            @endforeach
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @endif

        {{-- Footer: via Stardena Careers + ads notice --}}
        <tr>
            <td style="background:#F4F8F7;border-top:1px solid #E5EBEA;padding:20px 32px;text-align:center;font-size:12px;line-height:1.6;color:#64748B;">
                Sent by <strong style="color:#33475B;">{{ $seekerName }}</strong>
                via <strong style="color:#0A8F6B;">{{ config('app.name') }}</strong>@if($countryName) &middot; {{ $countryName }}@endif
                <br>
                <span style="color:#94A3B8;">
                    Hiring? To post a job on {{ config('app.name') }}@if($countryName) in {{ $countryName }}@endif, send us an email at
                    <a href="mailto:{{ config('mail.from.address') }}" style="color:#0A8F6B;text-decoration:underline;">{{ config('mail.from.address') }}</a>.
                </span>
                <br>
                <span style="color:#94A3B8;">Reply to this email to respond directly to {{ $seekerName }}.</span>
            </td>
        </tr>
    </table>

</td></tr>
</table>
</body>
</html>