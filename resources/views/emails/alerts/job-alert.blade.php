<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Job Alerts</title>
    <style>
        body { margin:0; padding:0; background:#F4F8F7; font-family: Inter, Arial, sans-serif; color:#0F1B2D; }
        .wrapper { max-width:600px; margin:0 auto; background:#fff; border-radius:14px; overflow:hidden; }
        .header { background:linear-gradient(135deg,#20AA3E 0%,#03A588 100%); padding:28px 32px; color:#fff; }
        .header h1 { margin:0; font-size:22px; font-weight:800; }
        .header p { margin:6px 0 0; opacity:.85; font-size:14px; }
        .body { padding:28px 32px; }
        .lead { font-size:15px; line-height:1.6; color:#33475B; margin-bottom:20px; }
        .job { border:1px solid #E5EBEA; border-radius:12px; padding:16px 18px; margin-bottom:12px; }
        .job h3 { margin:0 0 4px; font-size:16px; color:#0F1B2D; }
        .job .meta { font-size:13px; color:#64748B; margin-bottom:10px; }
        .job a { color:#03A588; text-decoration:none; font-weight:700; font-size:14px; }
        .btn { display:inline-block; background:linear-gradient(135deg,#20AA3E 0%,#03A588 100%); color:#fff !important; padding:12px 22px; border-radius:10px; text-decoration:none; font-weight:700; }
        .btn-outline { display:inline-block; background:#F4F8F7; color:#03A588 !important; padding:12px 22px; border-radius:10px; text-decoration:none; font-weight:700; }
        .footer { background:#F4F8F7; padding:20px 32px; text-align:center; font-size:12px; color:#64748B; }
        .divider { height:1px; background:#E5EBEA; margin:24px 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ $firstName }}, here's what's new</h1>
            <p>{{ $countryName }} · Job Alerts</p>
        </div>

        <div class="body">

            @if($decision['kind'] === 'cv_prompt')
                <p class="lead">
                    <strong>You haven't uploaded a CV yet.</strong>
                    Employers search this platform daily. Without a CV, they can't find you, and you can't receive
                    tailored job alerts. Upload yours now — it takes a minute.
                </p>
                <p style="text-align:center;margin:28px 0;">
                    <a href="{{ $magicUrl }}" class="btn">Upload Your CV</a>
                </p>

            @elseif($decision['kind'] === 'profile_prompt')
                <p class="lead">
                    <strong>Your profile is incomplete.</strong>
                    Add your preferred job category, industry, location, experience level, education, and skills.
                    Employers filter by these fields — a complete profile is how you get noticed.
                </p>
                <p style="text-align:center;margin:28px 0;">
                    <a href="{{ $magicUrl }}" class="btn">Complete My Profile</a>
                </p>

            @elseif($decision['kind'] === 'preferences_prompt')
                <p class="lead">
                    <strong>Set your job preferences.</strong>
                    Right now you're set to receive every job we post. Tell us what you actually want —
                    categories, skills, and location — and we'll only send relevant roles.
                </p>
                <p style="text-align:center;margin:28px 0;">
                    <a href="{{ $magicUrl }}" class="btn">Set My Preferences</a>
                </p>

            @else
                <p class="lead">
                    We found <strong>{{ $jobs->count() }}</strong> roles matching your preferences.
                    Here are the top ones:
                </p>

                @foreach($jobs as $job)
                    <div class="job">
                        <h3>{{ $job->job_title }}</h3>
                        <div class="meta">
                            {{ optional($job->company)->name ?? 'Company' }}
                            @if($job->jobLocation)
                                · {{ $job->jobLocation->district ?? $job->jobLocation->city }}
                            @endif
                        </div>
                        <a href="{{ $proxyUrl }}/job/{{ $job->slug }}" target="_blank">View Job</a>
                    </div>
                @endforeach

                <div class="divider"></div>

                <p style="text-align:center;margin:0;">
                    <a href="{{ $magicUrl }}" class="btn-outline">Log In & See All Jobs</a>
                </p>
            @endif

        </div>

        <div class="footer">
            Sent by {{ config('app.name') }} · You're receiving this because you signed up for job alerts.
        </div>
    </div>
</body>
</html>