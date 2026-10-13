<?php

namespace App\Services\Alerts\Channels;

use App\Mail\Alerts\JobAlertMail;
use App\Models\Job\Country;
use App\Models\LoginToken;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailChannel
{
    public function send($seeker, array $decision, $jobs): void
    {
        // 1. Get the country row from the seeker's own data
        $country = $this->resolveCountry($seeker);

        // 2. Use its frontend_url as the proxy base
        $baseUrl = $country?->frontend_url ?: config('app.url');

        // 3. Issue a magic login token
        $token    = $this->issueMagicToken($seeker);
        $magicUrl = "{$baseUrl}/login/magic-link/{$token}";

        Mail::to($seeker->user->email)->send(new JobAlertMail(
            seeker:      $seeker,
            jobs:        $jobs,
            decision:    $decision,
            magicUrl:    $magicUrl,
            countryCode: $country?->code,
            countryName: $country?->name,
            baseUrl:     $baseUrl,
        ));
    }

    protected function issueMagicToken($seeker): string
    {
        LoginToken::where('user_id', $seeker->user_id)
            ->whereNull('used_at')
            ->delete();

        $token = Str::random(64);

        LoginToken::create([
            'user_id'    => $seeker->user_id,
            'token'      => $token,
            'expires_at' => now()->addDays(7),
        ]);

        return $token;
    }

    /**
     * The seeker's country column may contain a code ("UG") or a name ("Uganda").
     * Look up the Country row using whichever matches.
     */
    protected function resolveCountry($seeker): ?Country
    {
        \Log::info($seeker->country);
        \Log::info($seeker->country);

        $candidates = array_filter([
            $seeker->preferred_country_code ?? null,
            $seeker->country                ?? null,
            $seeker->user->country_code     ?? null,
        ]);

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') continue;

            $country = Country::where('code', strtoupper($candidate))
                ->orWhere('name', $candidate)
                ->first();

            if ($country) return $country;
        }

        return null;
    }
}