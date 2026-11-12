<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ManualEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $emailSubject,
        public string $emailBody,
        public array $attachmentPaths = [],
        public ?string $ccList = null,
        public ?string $bccList = null,
        public string $recipientName = '',

        // ── Template + application context ──
        public string $template = 'default',   // 'default' | 'application'
        public array $applicationContext = [], // job_title, company_name, seeker_name, ...
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
            cc: $this->parseEmails($this->ccList),
            bcc: $this->parseEmails($this->bccList),
        );
    }

    public function content(): Content
    {
        // ── Application mode: professional letterhead ──
        if ($this->template === 'application') {
            return new Content(
                view: 'emails.manual-application',
                with: [
                    'jobTitle'        => $this->applicationContext['job_title']        ?? 'Job Application',
                    'companyName'     => $this->applicationContext['company_name']     ?? null,
                    'seekerName'      => $this->applicationContext['seeker_name']      ?? 'Applicant',
                    'seekerEmail'     => $this->applicationContext['seeker_email']     ?? null,
                    'hasCoverLetter'  => $this->applicationContext['has_cover_letter'] ?? false,
                    'attachmentNames' => $this->applicationContext['attachment_names'] ?? [],
                ],
            );
        }

        // ── Default mode: manual dispatcher ──
        return new Content(
            view: 'emails.manual-dispatcher',
            with: [
                'body'          => $this->emailBody,
                'recipientName' => $this->recipientName,
                'emailSubject'  => $this->emailSubject,
            ],
        );
    }

    public function attachments(): array
    {
        $out = [];
        foreach ($this->attachmentPaths as $path) {
            if (Storage::disk('public')->exists($path)) {
                $out[] = Attachment::fromPath(Storage::disk('public')->path($path))
                    ->as(basename($path))
                    ->withMime(Storage::disk('public')->mimeType($path) ?: 'application/octet-stream');
            }
        }
        return $out;
    }

    private function parseEmails(?string $list): array
    {
        if (!$list) return [];
        return collect(explode(',', $list))
            ->map(fn ($e) => trim($e))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();
    }
}