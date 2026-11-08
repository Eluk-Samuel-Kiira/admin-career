<?php

namespace App\Mail\Alerts;

use App\Models\SeekerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PreferencesPromptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SeekerProfile $seeker,
        public string $magicUrl,
        public string $actionLabel = 'Set Job Preferences',
        public string $heading = 'Set your job preferences',
        public string $intro = 'You have not set your job preferences yet. Set them so we can send you only the jobs you actually want.',
        public string $preferencesUrl = '/alert/preferences',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->heading,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alerts.preferences-prompt',
            with: [
                'seeker'       => $this->seeker,
                'name'         => $this->seeker->first_name
                                  ?: $this->seeker->user?->first_name
                                  ?: 'there',
                'magicUrl'     => $this->magicUrl,
                'actionLabel'  => $this->actionLabel,
                'heading'      => $this->heading,
                'intro'        => $this->intro,
                'preferencesUrl' => $this->preferencesUrl,
            ],
        );
    }
}