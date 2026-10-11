<?php

namespace App\Services\Alerts\Channels;

use Illuminate\Support\Facades\Http;

class TelegramChannel
{
    public function send($seeker, array $decision, $jobs): void
    {
        $botToken = config('services.telegram.bot_token');
        if (!$botToken) {
            throw new \Exception('Telegram channel is not configured.');
        }

        $chatId = $seeker->user->telegram_chat_id ?? null;
        if (!$chatId) {
            throw new \Exception('No Telegram chat id on file.');
        }

        $message = $this->compose($seeker, $decision, $jobs);

        Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id'                  => $chatId,
            'text'                     => $message,
            'parse_mode'               => 'Markdown',
            'disable_web_page_preview' => true,
        ])->throw();
    }

    protected function compose($seeker, array $decision, $jobs): string
    {
        $name = $seeker->first_name ?: $seeker->user->first_name ?: 'there';

        $intro = match ($decision['kind']) {
            'cv_prompt'          => "*Hi {$name},* upload your CV so employers can find you.\n\n" . $this->proxyUrl('/cv/edit'),
            'profile_prompt'     => "*Hi {$name},* your profile is incomplete. Complete it to get noticed.\n\n" . $this->proxyUrl('/profile'),
            'preferences_prompt' => "*Hi {$name},* set your job preferences so we send only relevant jobs.\n\n" . $this->proxyUrl('/alert/preferences'),
            default              => "*Hi {$name}, here are some open positions:*",
        };

        if ($decision['kind'] !== 'jobs') {
            return $intro;
        }

        $lines = [$intro, ''];
        foreach ($jobs->take(5) as $job) {
            $lines[] = '• [' . $job->job_title . '](' . $this->jobUrl($job) . ')';
        }
        $lines[] = '';
        $lines[] = '[See more jobs](' . $this->proxyUrl('/jobs') . ')';

        return implode("\n", $lines);
    }

    protected function proxyUrl(string $path): string
    {
        return rtrim(config('app.proxy_url', config('app.url')), '/') . $path;
    }

    protected function jobUrl($job): string
    {
        return rtrim(config('app.proxy_url', config('app.url')), '/') . '/job/' . $job->slug;
    }
}