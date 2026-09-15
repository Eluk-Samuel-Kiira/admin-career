<?php

namespace App\Services\Delivery;

use App\Helpers\CountryHelper;
use App\Mail\CvReviewReadyMail;
use App\Models\Service\CvReviewRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReviewDeliveryService
{
    public function deliver(CvReviewRequest $req, array $channels, ?string $customMessage = null): array
    {
        $results = [];

        if (in_array('email', $channels, true)) {
            $results['email'] = $this->sendEmail($req, $customMessage);
        }

        if (in_array('whatsapp', $channels, true)) {
            $results['whatsapp'] = $this->sendWhatsApp($req, $customMessage);
        }

        return $results;
    }

    // ─────────────────────────────────────────────────────────────
    // EMAIL
    // ─────────────────────────────────────────────────────────────
    protected function sendEmail(CvReviewRequest $req, ?string $customMessage): array
    {
        $email = $req->user->email ?? null;
        if (!$email) {
            return ['success' => false, 'message' => 'No email on file.'];
        }

        try {
            Mail::to($email)->send(new CvReviewReadyMail(
                $req,
                $customMessage,
                $this->seekerReviewUrl($req)
            ));

            Log::info('Review email sent', ['id' => $req->id, 'to' => $email]);

            return ['success' => true, 'message' => "Email sent to {$email}"];
        } catch (\Throwable $e) {
            Log::error('Review email failed', ['id' => $req->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Email failed: ' . $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // WHATSAPP — stub for now
    // ─────────────────────────────────────────────────────────────
    protected function sendWhatsApp(CvReviewRequest $req, ?string $customMessage): array
    {
        $phone = $req->user->phone ?? null;
        if (!$phone) {
            return ['success' => false, 'message' => 'No phone number on file.'];
        }

        Log::info('WhatsApp delivery (stub)', ['id' => $req->id, 'phone' => $phone]);

        return ['success' => true, 'message' => "Queued for {$phone} (stub)"];
    }

    // ─────────────────────────────────────────────────────────────
    // URL BUILDER — uses countries.frontend_url via CountryHelper
    // ─────────────────────────────────────────────────────────────
    protected function seekerReviewUrl(CvReviewRequest $req): string
    {
        // Country code from the request itself; fall back to the user's country
        $countryCode = $req->country_code
            ?: ($req->user->country_code ?? null);

        // CountryHelper caches this, so it's cheap
        $base = $countryCode
            ? CountryHelper::getFrontendUrl($countryCode)   // e.g. https://www.greatugandajobs.com
            : config('app.url');

        $base = rtrim($base, '/');

        return "{$base}/cv-review/{$req->uuid}";
    }
}