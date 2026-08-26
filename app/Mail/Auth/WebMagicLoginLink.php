<?php

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Job\Country;

/**
 * Magic link email for both job seekers and employers.
 * The link points to the country-specific web app URL.
 */
class WebMagicLoginLink extends Mailable
{
    use Queueable, SerializesModels;

    public string $url;
    public bool   $isNew;
    protected User $user;
    protected ?Country $country;

    public function __construct(User $user, string $token, bool $isNew = false)
    {
        $this->user = $user;
        $this->isNew = $isNew;
        $this->country = $this->getCountry($user->country_code);
        
        // Get the web app URL based on user's country code
        $webBase = $this->getWebAppUrl($user->country_code);
        $this->url = rtrim($webBase, '/') . '/login/magic-link/' . $token;
    }

    /**
     * Get the country from the database
     */
    protected function getCountry(?string $countryCode): ?Country
    {
        if (empty($countryCode)) {
            return null;
        }

        return Country::where('code', strtoupper($countryCode))->first();
    }

    /**
     * Get the web app URL for the user's country
     */
    protected function getWebAppUrl(?string $countryCode): string
    {
        // Default fallback
        $defaultUrl = config('app.web_app_url', 'http://127.0.0.1:8001');
        
        if (empty($countryCode)) {
            return $defaultUrl;
        }

        $country = $this->country;
        
        // Check if country has a frontend_url in the database
        if ($country && !empty($country->frontend_url)) {
            return rtrim($country->frontend_url, '/');
        }

        // Try environment variable as fallback
        $countryCode = strtoupper($countryCode);
        $countryWebUrlKey = $countryCode . '_WEB_URL';
        $webUrl = env($countryWebUrlKey);
        
        if ($webUrl) {
            return rtrim($webUrl, '/');
        }

        // Fallback to default
        return $defaultUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isNew
                ? 'Welcome to ' . $this->getCountryName() . ' — sign in to get started'
                : 'Your ' . $this->getCountryName() . ' magic link',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.magic-login-link-api',
            with: [
                'url' => $this->url,
                'isNew' => $this->isNew,
                'user' => $this->user,
                'countryName' => $this->getCountryName(),
                'countryFlag' => $this->getCountryFlag(),
            ],
        );
    }

    /**
     * Get the country name from the Country model
     */
    protected function getCountryName(): string
    {
        if ($this->country && !empty($this->country->name)) {
            return $this->country->name;
        }

        // Fallback to user's country code or default
        return $this->user->country_code ?? 'Stardena Careers';
    }

    /**
     * Get the country flag emoji from the Country model
     */
    protected function getCountryFlag(): string
    {
        if ($this->country && !empty($this->country->flag)) {
            return $this->country->flag;
        }

        return '🌍';
    }
}