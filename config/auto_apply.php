<?php

return [
    // How many jobs a run may attempt per seeker, regardless of quota.
    // Safety rail against a bad filter returning 10,000 jobs.
    'max_jobs_per_run' => (int) env('AUTO_APPLY_MAX_PER_RUN', 50),

    // Daily schedule time.
    'run_at' => env('AUTO_APPLY_RUN_AT', '03:00'),

    // Queue name for the fanout and per-job work.
    'queue' => env('AUTO_APPLY_QUEUE', 'auto-apply'),

    // Channel priority — first viable wins.
    'channel_priority' => ['ats', 'email', 'whatsapp'],

    // Retry policy for per-job work.
    'job_tries'   => 3,
    'job_timeout' => 180,

    // Per-plan quotas. null = unlimited.
    // This is the source of truth until billing ships.
    'quotas' => [
        'free'       => 0,
        'standard'   => 30,
        'premium'    => 100,
        'enterprise' => null,
    ],
];