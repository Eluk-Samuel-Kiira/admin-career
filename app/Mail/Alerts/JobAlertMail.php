<?php

namespace App\Mail\Alerts;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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
            'cv_prompt'          => "{$firstName}, upload your CV to get noticed",
            'profile_prompt'     => "{$firstName}, finish your profile to get more jobs",
            'preferences_prompt' => "{$firstName}, set your job preferences",
            default              => "New jobs for you, {$firstName}",
        };

        return $this->subject($subject)
            ->view('emails.alerts.job-alert')
            ->with([
                'firstName'   => $firstName,
                'jobs'        => $this->jobs,
                'decision'    => $this->decision,
                'magicUrl'    => $this->magicUrl,
                'countryName' => $this->countryName ?: 'Your Country',
                'proxyUrl'    => $this->baseUrl,
            ]);
    }
}