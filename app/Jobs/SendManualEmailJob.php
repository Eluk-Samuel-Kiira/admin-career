<?php

namespace App\Jobs;

use App\Mail\ManualEmail;
use App\Models\ManualEmailDispatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendManualEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 1;

    public function __construct(public int $dispatchId) {}

    public function handle(): void
    {
        $dispatch = ManualEmailDispatch::find($this->dispatchId);
        if (!$dispatch || $dispatch->status !== 'queued') return;

        $dispatch->update(['status' => 'sending']);

        $sent   = 0;
        $failed = 0;

        foreach ($dispatch->recipients as $recipient) {
            try {
                Mail::to($recipient['email'], $recipient['name'] ?? null)
                    ->send(new ManualEmail(
                        emailSubject:    $dispatch->subject,
                        emailBody:       $dispatch->body,
                        attachmentPaths: $dispatch->attachments ?? [],
                        ccList:          $dispatch->cc,          // ← renamed
                        bccList:         $dispatch->bcc,         // ← renamed
                        recipientName:   $recipient['name'] ?? '',
                    ));

                $sent++;

                // Gentle throttle to protect your SMTP provider
                usleep(150_000); // 150 ms between sends
            } catch (\Throwable $e) {
                $failed++;
                Log::warning("ManualEmail: failed for {$recipient['email']}", [
                    'dispatch_id' => $dispatch->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $status = match (true) {
            $failed === 0  => 'sent',
            $sent   === 0  => 'failed',
            default        => 'partial',
        };

        $dispatch->update([
            'status'       => $status,
            'sent_count'   => $sent,
            'failed_count' => $failed,
            'sent_at'      => now(),
        ]);
    }
}