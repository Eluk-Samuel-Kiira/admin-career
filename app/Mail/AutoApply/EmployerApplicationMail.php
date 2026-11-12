<?php

namespace App\Mail\AutoApply;

use App\Models\Job\JobPost;
use App\Models\SeekerProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class EmployerApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SeekerProfile $seeker,
        public JobPost $job,
        public string $cvHtml,
        public ?string $coverHtml = null,
    ) {}

    public function envelope(): Envelope
    {
        // 1. Resolve Employer Email (Email column OR extract from text)
        $toEmail = $this->job->email;
        if (!$toEmail) {
            $text = strip_tags(($this->job->qualifications ?? '') . ' ' . ($this->job->application_procedure ?? ''));
            if (preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $text, $matches)) {
                $toEmail = $matches[0];
            }
        }

        // 2. CC the Seeker (so they know what was sent)
        $cc = [];
        if ($this->seeker->user?->email) {
            $cc[] = new Address($this->seeker->user->email, $this->seeker->full_name);
        }

        return new Envelope(
            subject: 'Application for ' . $this->job->job_title . ' — ' . $this->seeker->full_name,
            to: $toEmail ? [new Address($toEmail)] : [],
            cc: $cc,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auto-apply.employer-application',
            with: [
                'seeker'    => $this->seeker,
                'job'       => $this->job,
                'coverHtml' => $this->coverHtml,
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        // 1. CV
        if (!empty($this->cvHtml)) {
            $pdf = Pdf::loadView('pdf.cv-rewrite', [
                'title'   => 'CV - ' . $this->job->job_title,
                'content' => $this->cvHtml,
            ])->setPaper('a4');
            $attachments[] = Attachment::fromData(fn () => $pdf->output(), 'CV.pdf')->withMime('application/pdf');
        }

        // 2. Cover Letter
        if (!empty($this->coverHtml)) {
            $pdf = Pdf::loadView('pdf.cv-rewrite', [
                'title'   => 'Cover Letter - ' . $this->job->job_title,
                'content' => $this->coverHtml,
            ])->setPaper('a4');
            $attachments[] = Attachment::fromData(fn () => $pdf->output(), 'Cover_Letter.pdf')->withMime('application/pdf');
        }

        // 3. Academic Documents (Only if required by the job AND uploaded by the seeker)
        if ($this->job->is_academic_documents_required && $this->seeker->academic_documents_path) {
            if (Storage::disk('public')->exists($this->seeker->academic_documents_path)) {
                $attachments[] = Attachment::fromPath(
                    Storage::disk('public')->path($this->seeker->academic_documents_path)
                )->as('Academic_Documents.pdf')->withMime('application/pdf');
            }
        }

        return $attachments;
    }
}