<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Job Alerts — {{ config('app.name') }}</title>
    <!--[if mso]>
    <style>table,td{font-family:Arial,sans-serif !important;}</style>
    <![endif]-->
    <style>
        /* ── Mobile-first media queries ── */
        @media only screen and (max-width: 480px) {
            .em-outer   { padding: 12px 8px !important; }
            .em-card    { border-radius: 12px !important; }
            .em-header  { padding: 24px 20px 20px !important; }
            .em-header h1 { font-size: 20px !important; line-height: 1.25 !important; }
            .em-header-eyebrow { font-size: 10px !important; margin-bottom: 8px !important; }
            .em-header-sub { font-size: 13px !important; margin-top: 6px !important; }
            .em-body    { padding: 20px 16px 18px !important; }
            .em-body p  { font-size: 14px !important; }
            
            .em-job-card { border-radius: 10px !important; margin-bottom: 8px !important; }
            .em-job-inner { padding: 10px 12px !important; }
            .em-job-logo  { width: 36px !important; height: 36px !important; padding-right: 10px !important; }
            .em-job-logo img, .em-job-logo table { width: 36px !important; height: 36px !important; }
            .em-job-logo td { font-size: 10px !important; }
            .em-job-title { font-size: 13px !important; margin-bottom: 2px !important; }
            .em-job-company { font-size: 11px !important; }
            
            /* Hide the right-aligned deadline column on mobile and let it wrap under the title naturally */
            .em-job-deadline-cell { display: none !important; }
            .em-mobile-deadline { display: block !important; font-size: 10px !important; color: #64748B !important; margin-top: 2px !important; }
            
            .em-job-cta-cell { padding-left: 5px !important; }
            .em-job-cta { padding: 4px 10px !important; font-size: 11px !important; }
            
            .em-job-meta { font-size: 10px !important; margin-top: 6px !important; }
            .em-job-pill { font-size: 9px !important; padding: 2px 6px !important; }
            
            .em-cta-full { display: block !important; width: 100% !important; text-align: center !important; box-sizing: border-box !important; }
            .em-footer { padding: 18px 20px !important; }
            .em-footer p { font-size: 11px !important; }
            .em-smallprint { padding: 12px 10px 0 !important; font-size: 10px !important; }
        }

        .em-mobile-deadline { display: none; } /* Hidden by default on desktop */
    </style>
</head>
<body style="margin:0;padding:0;background:#F4F8F7;font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0F1B2D;-webkit-font-smoothing:antialiased;">

    {{-- Preheader --}}
    <div style="display:none;font-size:1px;color:#F4F8F7;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        @if($decision['kind'] === 'preferences_prompt')
            Set your job preferences to receive personalised alerts.
        @else
            {{ $jobs->count() }} new {{ $jobs->count() === 1 ? 'role' : 'roles' }} matching your preferences in {{ $countryName }}.
        @endif
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           class="em-outer" style="background:#F4F8F7;padding:24px 12px;">
        <tr>
            <td align="center">

                {{-- MAIN CARD --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       class="em-card"
                       style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid rgba(15,27,45,0.08);">

                    {{-- HEADER --}}
                    <tr>
                        <td class="em-header"
                            style="background:linear-gradient(135deg,#20AA3E 0%,#03A588 100%);
                                   background-color:#20AA3E;
                                   padding:32px 32px 28px;color:#ffffff;">
                            <div class="em-header-eyebrow"
                                 style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;
                                        color:#BFF7D2;margin-bottom:10px;opacity:0.95;">
                                Job Alerts · {{ $countryName }}
                            </div>
                            <h1 style="margin:0;font-size:24px;line-height:1.2;font-weight:800;color:#ffffff;
                                       letter-spacing:-0.01em;">
                                @if($decision['kind'] === 'preferences_prompt')
                                    Hi {{ $firstName }}, let's get you matched
                                @else
                                    Hi {{ $firstName }}, here's what's new
                                @endif
                            </h1>
                            <p class="em-header-sub"
                               style="margin:8px 0 0;font-size:14px;color:#ffffff;opacity:0.9;line-height:1.4;">
                                @if($decision['kind'] === 'preferences_prompt')
                                    One quick setup and we'll start sending you the right jobs.
                                @else
                                    {{ $jobs->count() }} new {{ $jobs->count() === 1 ? 'role' : 'roles' }} hand-picked for you.
                                @endif
                            </p>
                        </td>
                    </tr>

                    {{-- BODY --}}
                    <tr>
                        <td class="em-body" style="padding:32px 28px 24px;">

                            @if($decision['kind'] === 'preferences_prompt')

                                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#33475B;">
                                    <strong style="color:#0F1B2D;">Set your job preferences.</strong>
                                    Tell us which categories you're interested in, or turn on skill-match alerts
                                    so we only send you what matters.
                                </p>
                                <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#33475B;">
                                    Without preferences, we can't tailor alerts to you.
                                </p>

                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;">
                                    <tr>
                                        <td class="em-cta-full"
                                            style="border-radius:10px;background:linear-gradient(135deg,#20AA3E 0%,#03A588 100%);background-color:#20AA3E;text-align:center;">
                                            <a href="{{ $magicUrl }}"
                                               style="display:inline-block;padding:14px 26px;font-size:15px;font-weight:700;
                                                      color:#ffffff;text-decoration:none;border-radius:10px;">
                                                Set My Preferences →
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin:20px 0 0;font-size:12px;line-height:1.5;color:#64748B;word-break:break-all;">
                                    Or copy this link into your browser:<br>
                                    <a href="{{ $magicUrl }}" style="color:#03A588;text-decoration:none;">
                                        {{ $magicUrl }}
                                    </a>
                                </p>

                            @else

                                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#33475B;">
                                    We found <strong style="color:#0F1B2D;">{{ $jobs->count() }}</strong>
                                    {{ $jobs->count() === 1 ? 'role' : 'roles' }} matching your preferences.
                                    Here are the top ones:
                                </p>

                                @foreach($jobs as $job)
                                    @php
                                        $companyName = optional($job->company)->name ?? 'Company';
                                        $initials    = $companyName !== ''
                                            ? strtoupper(substr(preg_replace('/\s+/', '', $companyName), 0, 2))
                                            : 'JM';
                                        $logoUrl     = optional($job->company)->logo ?? null;
                                        $location    = optional($job->jobLocation)->district
                                                        ?? optional($job->jobLocation)->city
                                                        ?? ($job->duty_station ?? null);
                                        $category    = optional($job->jobCategory)->name
                                                        ?? optional($job->job_category)->name
                                                        ?? null;
                                        $deadline    = $job->deadline ? $job->deadline->format('d M Y') : null;
                                        $jobUrl      = $proxyUrl . '/job/' . $job->slug;
                                        $employment  = $job->employment_type
                                            ? ucfirst(str_replace('-', ' ', $job->employment_type))
                                            : 'Full-time';
                                    @endphp

                                    {{-- SLEEK COMPACT JOB CARD --}}
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                           class="em-job-card"
                                           style="margin:0 0 10px;border:1px solid #E5EBEA;border-radius:12px;
                                                  background-color:#F1FAF8;overflow:hidden;">
                                        <tr>
                                            <td class="em-job-inner" style="padding:12px 14px;">

                                                {{-- TOP ROW: Logo, Title, Button --}}
                                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                                    <tr>
                                                        {{-- Logo --}}
                                                        <td class="em-job-logo" width="40" valign="top" style="padding-right:10px;">
                                                            @if($logoUrl)
                                                                <img src="{{ $logoUrl }}" alt="{{ $companyName }}"
                                                                     width="40" height="40"
                                                                     style="display:block;width:40px;height:40px;object-fit:contain;
                                                                            border:1px solid #E5EBEA;border-radius:8px;
                                                                            padding:2px;background:#ffffff;">
                                                            @else
                                                                <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                                                       width="40" height="40" style="width:40px;height:40px;">
                                                                    <tr>
                                                                        <td align="center" valign="middle"
                                                                            style="background:#ffffff;border:1px solid #E5EBEA;
                                                                                   border-radius:8px;font-size:11px;
                                                                                   font-weight:800;color:#03A588;">
                                                                            {{ $initials }}
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            @endif
                                                        </td>

                                                        {{-- Title & Company --}}
                                                        <td valign="top">
                                                            <a class="em-job-title" href="{{ $jobUrl }}"
                                                               style="display:block;font-size:14px;line-height:1.2;
                                                                      font-weight:700;color:#0F1B2D;text-decoration:none;
                                                                      margin:0 0 2px;">
                                                                {{ $job->job_title }}
                                                            </a>
                                                            <div class="em-job-company"
                                                                 style="font-size:12px;line-height:1.2;color:#64748B;">
                                                                {{ $companyName }}
                                                            </div>
                                                            
                                                            {{-- Mobile-only Deadline (shows under company on small screens) --}}
                                                            @if($deadline)
                                                                <span class="em-mobile-deadline">⏱ {{ $deadline }}</span>
                                                            @endif
                                                        </td>

                                                        {{-- Right Side: Deadline (Desktop) + Button --}}
                                                        <td class="em-job-cta-cell" align="right" valign="middle" style="padding-left:8px;">
                                                            @if($deadline)
                                                                <div class="em-job-deadline-cell" style="font-size:10px;font-weight:700;color:#64748B;margin-bottom:4px;white-space:nowrap;">
                                                                    ⏱ {{ $deadline }}
                                                                </div>
                                                            @endif
                                                            
                                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="right">
                                                                <tr>
                                                                    <td style="border-radius:6px;background:linear-gradient(135deg,#20AA3E 0%,#03A588 100%);background-color:#20AA3E;">
                                                                        <a class="em-job-cta" href="{{ $jobUrl }}"
                                                                           style="display:inline-block;padding:5px 12px;
                                                                                  font-size:11px;font-weight:700;
                                                                                  color:#ffffff;text-decoration:none;
                                                                                  border-radius:6px;white-space:nowrap;">
                                                                            Apply
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>

                                                {{-- BOTTOM ROW: Meta (Location · Category · Type) --}}
                                                <div class="em-job-meta" style="margin-top:8px;font-size:11px;line-height:1.3;color:#94A3B8;border-top:1px solid rgba(229,235,234,0.7);padding-top:8px;">
                                                    @if($location)📍 {{ $location }}@endif
                                                    
                                                    @if($location && ($category || $employment))
                                                        <span style="color:#D1D5DB;"> • </span>
                                                    @endif

                                                    @if($category){{ $category }}@endif

                                                    @if($category && $employment)
                                                        <span style="color:#D1D5DB;"> • </span>
                                                    @endif

                                                    @if($employment)
                                                        <span class="em-job-pill" style="display:inline-block;background:#ffffff;
                                                                                     color:#3B5166;font-size:10px;font-weight:700;
                                                                                     padding:2px 6px;border-radius:4px;
                                                                                     border:1px solid #E5EBEA;">
                                                            {{ $employment }}
                                                        </span>
                                                    @endif
                                                </div>

                                            </td>
                                        </tr>
                                    </table>
                                @endforeach

                                <div style="height:1px;background:#E5EBEA;margin:20px 0;"></div>

                                {{-- Secondary CTA --}}
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                    <tr>
                                        <td align="center" style="padding:0 0 8px;">
                                            <a class="em-cta-full" href="{{ $magicUrl }}"
                                               style="display:inline-block;padding:13px 24px;font-size:14px;font-weight:700;
                                                      color:#03A588;text-decoration:none;border-radius:10px;
                                                      background:#F4F8F7;border:1px solid #E5EBEA;">
                                                Log In &amp; See All Jobs
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                            @endif

                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td class="em-footer" style="background:#F4F8F7;padding:22px 32px;text-align:center;">
                            <p style="margin:0 0 6px;font-size:12px;font-weight:700;color:#0F1B2D;letter-spacing:0.02em;">
                                {{ config('app.name') }}
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.5;color:#64748B;">
                                You're receiving this because you signed up for job alerts.<br>
                                <a href="{{ $proxyUrl }}/alert/preferences"
                                   style="color:#64748B;text-decoration:underline;">
                                    Manage preferences
                                </a>
                                ·
                                <a href="{{ $proxyUrl }}/unsubscribe"
                                   style="color:#64748B;text-decoration:underline;">
                                    Unsubscribe
                                </a>
                            </p>
                        </td>
                    </tr>

                </table>

                {{-- Small print --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       class="em-smallprint" style="max-width:600px;">
                    <tr>
                        <td align="center" style="padding:16px 12px 0;font-size:11px;line-height:1.5;color:#94A3B8;">
                            Sent by {{ config('app.name') }} · {{ $countryName }}<br>
                            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>