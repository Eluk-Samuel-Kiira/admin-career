<?php

namespace App\Mail\AutoApply;

use App\Models\Job\JobPost;
use App\Models\SeekerProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TailoredApplicationMail extends Mailable
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
        return new Envelope(
            subject: 'Your tailored CV is ready — ' . $this->job->job_title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auto-apply.tailored-application',
            with: ['seeker' => $this->seeker, 'job' => $this->job],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        // CV
        if (!empty($this->cvHtml)) {
            $pdf = Pdf::loadView('pdf.cv-rewrite', [
                'title'   => 'CV — ' . $this->job->job_title,
                'content' => $this->cvHtml,
            ])->setPaper('a4');
            $attachments[] = Attachment::fromData(fn () => $pdf->output(), 'CV.pdf')->withMime('application/pdf');
        }

        // Cover Letter
        if (!empty($this->coverHtml)) {
            $pdf = Pdf::loadView('pdf.cv-rewrite', [
                'title'   => 'Cover Letter — ' . $this->job->job_title,
                'content' => $this->coverHtml,
            ])->setPaper('a4');
            $attachments[] = Attachment::fromData(fn () => $pdf->output(), 'Cover_Letter.pdf')->withMime('application/pdf');
        }

        // Academic Documents — ONLY if the job requires them
        if ($this->job->is_academic_documents_required && $this->seeker->academic_documents_path) {
            $abs = \Storage::disk('public')->path($this->seeker->academic_documents_path);
            if (file_exists($abs)) {
                $attachments[] = Attachment::fromPath($abs)
                    ->as('Academic_Documents.pdf')
                    ->withMime('application/pdf');
            }
        }

        return $attachments;
    }

}