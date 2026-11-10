<?php

namespace App\Mail\Alerts;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class JobAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $seeker,
        public $jobs,
        public array $decision,
        public string $magicUrl,
        public ?string $countryCode,
        public ?string $countryName,
        public string $baseUrl,
    ) {}

    public function build()
    {
        $firstName = $this->seeker->first_name
            ?: $this->seeker->user->first_name
            ?: 'there';

        $subject = match ($this->decision['kind']) {
            'preferences_prompt' => "{$firstName}, set your job preferences",
            default              => "New jobs for you, {$firstName}",
        };

        // ─────────────────────────────────────────────────────────────
        // FIX: Force the root URL to the main app (8000) so the
        // signature is generated for the correct domain, even when
        // this is running inside a Queue Worker without HTTP context.
        // ─────────────────────────────────────────────────────────────
        $mainAppUrl = rtrim(config('app.url'), '/'); 
        
        // Temporarily force the URL generator to use the main app URL
        \Illuminate\Support\Facades\URL::forceRootUrl($mainAppUrl);

        try {
            // 1. Generate the signed URL (now guaranteed to use 8000)
            $signedMainApiUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'api.unsubscribe',
                now()->addDays(30),
                ['user' => $this->seeker->user_id]
            );

            // 2. Swap the domain to point to the proxy (8001)
            $proxyBase = rtrim($this->baseUrl, '/');
            
            $unsubscribeUrl = str_replace(
                [$mainAppUrl . '/api/unsubscribe', $mainAppUrl],
                [$proxyBase . '/unsubscribe',      $proxyBase],
                $signedMainApiUrl
            );

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to generate unsubscribe URL: ' . $e->getMessage());
            $unsubscribeUrl = $this->baseUrl . '/unsubscribe';
        } finally {
            // Optional: reset the root URL just in case other emails in the same worker 
            // need it to be the default
            \Illuminate\Support\Facades\URL::forceRootUrl(null); 
        }

        // Log it so we can verify in storage/logs/laravel.log
        // \Illuminate\Support\Facades\Log::info('Unsubscribe URL for email:', ['url' => $unsubscribeUrl]);

        return $this->subject($subject)
            ->view('emails.alerts.job-alert')
            ->with([
                'firstName'      => $firstName,
                'jobs'           => $this->jobs,
                'decision'       => $this->decision,
                'magicUrl'       => $this->magicUrl,
                'countryName'    => $this->countryName ?: 'Your Country',
                'proxyUrl'       => $this->baseUrl,
                'unsubscribeUrl' => $unsubscribeUrl,
            ]);
    }


}