<?php
// app/Mail/ApplicationEmail.php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Professional "application on behalf of a seeker" email.
 * Shows as: "Samuel Kiira Eluk via Stardena Careers <noreply@...>"
 * Reply-To is the applicant, so the employer's reply goes straight to them.
 */
class ApplicationEmail extends Mailable
{
    /**
     * @param array<int,array{path:string,original_name:string,size?:int}> $files
     */
    public function __construct(
        public string  $emailSubject,
        public string  $bodyHtml,
        public array   $files,
        public string  $seekerName,
        public ?string $seekerEmail = null,
        public ?string $jobTitle = null,
        public ?string $companyName = null,
        public string  $countryName = '',
    ) {}

    public function envelope(): Envelope
    {
        $from = new Address(
            config('mail.from.address'),
            $this->seekerName . ' via ' . config('app.name')
        );

        return new Envelope(
            from: $from,
            replyTo: $this->seekerEmail ? [new Address($this->seekerEmail, $this->seekerName)] : [],
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application',
            text: 'emails.application_text',
        );
    }

    public function attachments(): array
    {
        return collect($this->files)->map(fn ($f) =>
            Attachment::fromStorageDisk('public', $f['path'])->as($f['original_name'])
        )->all();
    }
}