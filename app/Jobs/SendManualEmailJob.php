<?php

namespace App\Jobs;

use App\Mail\ManualEmail;
use App\Models\ManualEmailDispatch;
use App\Services\Mail\CountryMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendManualEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 1;

    public function __construct(public int $dispatchId) {}

    public function handle(): void
    {
        $dispatch = ManualEmailDispatch::with('sender:id,country_code')->find($this->dispatchId);

        if (!$dispatch || $dispatch->status !== 'queued') {
            return;
        }

        $dispatch->update(['status' => 'sending']);

        // ── Resolve the country for this dispatch ────────────────
        // Priority:
        //   1. The dispatch's own country_code (set at creation)
        //   2. The sending admin's country_code
        //   3. null → CountryMailer falls back to the default SMTP
        $countryCode = $dispatch->country_code
            ?? $dispatch->sender?->country_code
            ?? null;

        $sent   = 0;
        $failed = 0;

        foreach ($dispatch->recipients as $recipient) {
            try {
                app(CountryMailer::class)
                    ->country($countryCode)
                    ->to($recipient['email'], $recipient['name'] ?? null)
                    ->cc($this->parseList($dispatch->cc))
                    ->bcc($this->parseList($dispatch->bcc))
                    ->send(new ManualEmail(
                        emailSubject:    $dispatch->subject,
                        emailBody:       $dispatch->body,
                        attachmentPaths: $dispatch->attachments ?? [],
                        ccList:          $dispatch->cc,
                        bccList:         $dispatch->bcc,
                        recipientName:   $recipient['name'] ?? '',
                    ));

                $sent++;

                // Gentle throttle to protect your SMTP provider
                usleep(150_000);
            } catch (\Throwable $e) {
                $failed++;
                Log::warning("ManualEmail: failed for {$recipient['email']}", [
                    'dispatch_id' => $dispatch->id,
                    'country'     => $countryCode,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $status = match (true) {
            $failed === 0 => 'sent',
            $sent   === 0 => 'failed',
            default       => 'partial',
        };

        $dispatch->update([
            'status'       => $status,
            'sent_count'   => $sent,
            'failed_count' => $failed,
            'sent_at'      => now(),
        ]);
    }

    /**
     * "a@x.com, b@y.com; c@z.com" or null → array of clean addresses.
     */
    private function parseList(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $raw))));
    }
}