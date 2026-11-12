<?php

namespace App\Services\AutoApply;

use App\Models\Job\JobPost;

class DispatchRouter
{
    public const TYPE_EMAIL    = 'email';
    public const TYPE_WHATSAPP = 'whatsapp';
    public const TYPE_EXTERNAL = 'external';
    public const TYPE_SKIP     = 'skip';

    public function route(JobPost $job): array
    {
        // 1. Check for External Link in the procedure
        $url = $this->extractUrl($job->application_procedure);
        if ($url) {
            return ['type' => self::TYPE_EXTERNAL, 'url' => $url, 'reason' => 'External Application Link'];
        }

        // 2. Check for WhatsApp
        if ($job->is_whatsapp_contact && !empty($job->telephone)) {
            return ['type' => self::TYPE_WHATSAPP, 'url' => null, 'reason' => 'WhatsApp Contact'];
        }

        // 3. Check for Email
        if (!empty($job->email) && filter_var($job->email, FILTER_VALIDATE_EMAIL)) {
            return ['type' => self::TYPE_EMAIL, 'url' => null, 'reason' => 'Direct Email'];
        }

        // 4. Nothing usable
        return ['type' => self::TYPE_SKIP, 'url' => null, 'reason' => 'No valid contact method found'];
    }

    private function extractUrl(?string $html): ?string
    {
        if (!$html) return null;
        if (preg_match('/href=["\'](https?:\/\/[^"\']+)["\']/i', $html, $m)) return $m[1];
        if (preg_match('/(https?:\/\/[^\s<]+)/i', strip_tags($html), $m)) return $m[0];
        return null;
    }
}