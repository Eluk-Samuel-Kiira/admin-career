<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your CV Review is Ready</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,Helvetica,sans-serif;color:#333;line-height:1.6;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:30px 15px;">
        <tr>
            <td align="center">

                {{-- Main card --}}
                <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                       style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.06);max-width:600px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#0d6efd;padding:24px 30px;text-align:left;">
                            <h1 style="margin:0;font-size:20px;color:#ffffff;font-weight:600;">
                                Your CV Review is Ready
                            </h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:30px;">

                            <p style="margin:0 0 20px;font-size:15px;">
                                Hi <strong>{{ $request->user->name ?? 'there' }}</strong>,
                            </p>

                            <p style="margin:0 0 25px;font-size:15px;">
                                {{ $customMessage ?: 'We have finished analysing your CV. Here is a summary of what we found:' }}
                            </p>

                            {{-- Score --}}
                            @php
                                $review = $request->effective_review;
                                $score  = $review['overall_score'] ?? null;
                            @endphp

                            @if($score !== null)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                       style="margin-bottom:25px;background:#eef6ff;border-left:4px solid #0d6efd;border-radius:4px;">
                                    <tr>
                                        <td style="padding:18px 20px;">
                                            <div style="font-size:13px;color:#666;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">
                                                Overall Score
                                            </div>
                                            <div style="font-size:32px;font-weight:700;color:#0d6efd;">
                                                {{ $score }}<span style="font-size:16px;color:#666;font-weight:400;">/100</span>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            {{-- Summary --}}
                            @if(!empty($review['summary']))
                                <p style="margin:0 0 25px;font-size:15px;color:#444;">
                                    {{ $review['summary'] }}
                                </p>
                            @endif

                            {{-- Sections table --}}
                            @if(!empty($review['sections']))
                                <h2 style="margin:0 0 12px;font-size:16px;color:#222;">Section Breakdown</h2>

                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                       style="border-collapse:collapse;margin-bottom:25px;font-size:14px;">
                                    <thead>
                                        <tr style="background:#f8f9fa;">
                                            <th align="left" style="padding:10px;border-bottom:2px solid #e5e5e5;color:#555;font-weight:600;">Section</th>
                                            <th align="left" style="padding:10px;border-bottom:2px solid #e5e5e5;color:#555;font-weight:600;">Status</th>
                                            <th align="left" style="padding:10px;border-bottom:2px solid #e5e5e5;color:#555;font-weight:600;">Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($review['sections'] as $sec)
                                            @php
                                                $status = $sec['status'] ?? '';
                                                $statusColor = match($status) {
                                                    'ok'      => '#198754',
                                                    'weak'    => '#ffc107',
                                                    'missing' => '#dc3545',
                                                    default   => '#6c757d',
                                                };
                                                $statusLabel = ucfirst($status ?: '—');
                                            @endphp
                                            <tr>
                                                <td style="padding:10px;border-bottom:1px solid #eee;font-weight:600;color:#333;">
                                                    {{ $sec['section'] ?? '' }}
                                                </td>
                                                <td style="padding:10px;border-bottom:1px solid #eee;">
                                                    <span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:{{ $statusColor }}20;color:{{ $statusColor }};">
                                                        {{ $statusLabel }}
                                                    </span>
                                                </td>
                                                <td style="padding:10px;border-bottom:1px solid #eee;color:#555;">
                                                    {{ $sec['note'] ?? '' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif

                            {{-- Missing fields --}}
                            @if(!empty($review['missing_fields']))
                                <h2 style="margin:0 0 12px;font-size:16px;color:#222;">What's Missing</h2>
                                <p style="margin:0 0 25px;font-size:15px;color:#444;">
                                    We noticed the following information is missing or thin in your CV:
                                </p>
                                <ul style="margin:0 0 25px;padding-left:20px;font-size:14px;color:#444;">
                                    @foreach($review['missing_fields'] as $field)
                                        <li style="margin-bottom:6px;">
                                            {{ ucwords(str_replace('_', ' ', $field)) }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            {{-- CTA button --}}
                            @if($reviewUrl)
                                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:30px 0 10px;">
                                    <tr>
                                        <td style="background:#0d6efd;border-radius:6px;">
                                            <a href="{{ $reviewUrl }}"
                                               style="display:inline-block;padding:14px 28px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;">
                                                View Full Review &amp; Fill In Gaps
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <p style="margin:25px 0 0;font-size:14px;color:#666;">
                                If the button doesn't work, copy and paste this link into your browser:<br>
                                <a href="{{ $reviewUrl }}" style="color:#0d6efd;word-break:break-all;">{{ $reviewUrl }}</a>
                            </p>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f8f9fa;padding:20px 30px;text-align:center;border-top:1px solid #eee;">
                            <p style="margin:0;font-size:13px;color:#888;">
                                Thanks for choosing {{ config('app.name') }}.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>