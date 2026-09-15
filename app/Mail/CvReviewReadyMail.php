<?php

namespace App\Mail;

use App\Models\Service\CvReviewRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CvReviewReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CvReviewRequest $request,
        public ?string $customMessage = null,
        public ?string $reviewUrl = null,
    ) {}

    public function build()
    {
        return $this->subject('Your CV Review is Ready')
            ->view('emails.cv-review-ready');  
    }
}