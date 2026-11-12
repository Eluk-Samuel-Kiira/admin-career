<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Application for {{ $job->job_title }}</title>
</head>
<body style="margin:0;padding:0;background:#F4F8F7;font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0F1B2D;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="padding:24px 12px;background:#F4F8F7;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;background:#fff;border-radius:12px;
                      border:1px solid #E5EBEA;overflow:hidden;">

            {{-- ─── Header ─── --}}
            <tr>
                <td style="background:linear-gradient(135deg,#20AA3E 0%,#03A588 100%);
                           padding:28px 32px;color:#fff;">
                    <div style="font-size:11px;font-weight:700;letter-spacing:0.08em;
                                text-transform:uppercase;color:#BFF7D2;margin-bottom:8px;opacity:.95;">
                        Job Application
                    </div>
                    <h1 style="margin:0;font-size:22px;line-height:1.25;font-weight:800;color:#fff;">
                        {{ $job->job_title }}
                    </h1>
                    <p style="margin:8px 0 0;font-size:14px;opacity:.9;line-height:1.4;">
                        @if($job->company)
                            {{ $job->company->name }}
                            @if($job->duty_station || $job->city)
                                · {{ $job->duty_station ?: $job->city }}
                            @endif
                        @else
                            Please find below my application
                        @endif
                    </p>
                </td>
            </tr>

            {{-- ─── Candidate identity strip ─── --}}
            <tr>
                <td style="padding:20px 32px;border-bottom:1px solid #E5EBEA;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                        <tr>
                            <td width="48" valign="top" style="padding-right:12px;">
                                @php
                                    $initials = strtoupper(substr(
                                        ($seeker->first_name ?? 'C') . ($seeker->last_name ?? ''),
                                        0, 2
                                    ));
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
                                    {{ $seeker->full_name }}
                                </div>
                                @if($seeker->professional_title)
                                    <div style="font-size:13px;color:#33475B;font-weight:600;margin-top:2px;">
                                        {{ $seeker->professional_title }}
                                    </div>
                                @endif
                                <div style="font-size:12px;color:#64748B;margin-top:4px;">
                                    @php
                                        $bits = array_filter([
                                            trim(($seeker->city ?? '') . (($seeker->city && $seeker->country) ? ', ' : '') . ($seeker->country ?? '')),
                                            $seeker->phone ?: $seeker->user?->phone,
                                            $seeker->user?->email,
                                        ]);
                                    @endphp
                                    {{ implode(' · ', $bits) }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            {{-- ─── Body (cover letter) ─── --}}
            <tr>
                <td style="padding:28px 32px;font-size:15px;line-height:1.65;color:#33475B;">
                    @if(!empty($coverHtml))
                        {!! $coverHtml !!}
                    @else
                        <p>Dear Hiring Manager,</p>
                        <p>Please find attached my curriculum vitae in application for the
                           <strong>{{ $job->job_title }}</strong> role.
                           I would welcome the opportunity to discuss how my experience
                           can contribute to your team.</p>
                        <p>Thank you for your consideration.</p>
                    @endif
                </td>
            </tr>

            {{-- ─── Attachment notice ─── --}}
            <tr>
                <td style="padding:0 32px 24px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                           style="background:#F4F8F7;border-radius:8px;padding:16px 20px;">
                        <tr>
                            <td style="font-size:13px;color:#64748B;line-height:1.6;">
                                <strong style="color:#0F1B2D;display:block;margin-bottom:6px;">
                                    Attached documents
                                </strong>
                                ✓ Curriculum Vitae (PDF)<br>
                                @if(!empty($coverHtml))
                                    ✓ Cover Letter (PDF)<br>
                                @endif
                                @if(!empty($job->is_academic_documents_required))
                                    ✓ Academic Documents (PDF)
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            {{-- ─── Footer ─── --}}
            <tr>
                <td style="background:#F4F8F7;padding:18px 32px;text-align:center;
                           font-size:11px;color:#94A3B8;line-height:1.5;">
                    Sent on behalf of <strong style="color:#33475B;">{{ $seeker->full_name }}</strong>
                    via {{ config('app.name') }}
                </td>
            </tr>

        </table>
    </td></tr>
</table>

</body>
</html>