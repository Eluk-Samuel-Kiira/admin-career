<?php

namespace App\Console\Commands;

use App\Models\SeekerProfile;
use App\Services\Alerts\AlertResolver;
use App\Services\Alerts\Channels\EmailChannel;
use App\Services\Alerts\Channels\TelegramChannel;
use App\Services\Alerts\Channels\WhatsAppChannel;
use App\Services\Alerts\JobMatcher;
use App\Services\Subscription\SubscriptionGate;
use Illuminate\Console\Command;

class TestAlertCommand extends Command
{
    protected $signature = 'alerts:test
        {email : Seeker email address}
        {--channel=email : email|whatsapp|telegram (comma-separated for multiple)}
        {--force-decision= : Override the resolver decision (jobs|cv_prompt|profile_prompt|preferences_prompt)}
        {--limit=5 : Max jobs to include}';

    protected $description = 'Send a real alert to a specific seeker email for testing.';

    public function handle(
        JobMatcher $matcher,
        AlertResolver $resolver,
        SubscriptionGate $gate,
        EmailChannel $email,
        WhatsAppChannel $whatsapp,
        TelegramChannel $telegram,
    ): int {
        $emailAddr = (string) $this->argument('email');

        $seeker = SeekerProfile::with('user')
            ->whereHas('user', fn ($q) => $q->where('email', $emailAddr))
            ->first();

        if (!$seeker) {
            $this->error("No seeker found with email: {$emailAddr}");
            return self::FAILURE;
        }
        if (!$seeker->user) {
            $this->error("Seeker #{$seeker->id} has no user row.");
            return self::FAILURE;
        }

        $prefs = $seeker->job_preferences_array;
        $this->line('<info>── Seeker loaded ──</info>');
        $this->line("ID:             {$seeker->id}");
        $this->line("Name:           {$seeker->first_name} {$seeker->last_name}");
        $this->line("Email:          {$seeker->user->email}");
        $this->line("Active:         " . ($seeker->is_active ? 'yes' : 'no'));
        $this->line("Prefs:          " . json_encode($prefs));

        // Resolve decision
        $decision = $resolver->decide($seeker, $prefs);
        $this->line("Decision:       {$decision['kind']} ({$decision['reason']})");

        // Allow override for testing
        if ($forced = $this->option('force-decision')) {
            $decision = ['kind' => $forced, 'reason' => 'forced via CLI'];
            $this->warn("Decision forced to: {$forced}");
        }

        // Job match
        $jobs = collect();
        if ($decision['kind'] === 'jobs') {
            $jobs = $matcher->match($seeker, $prefs, (int) $this->option('limit'));
            $this->line("Jobs matched:   {$jobs->count()}");

            if ($jobs->isEmpty()) {
                $this->warn('No jobs matched — job branch would exit early. Use --force-decision=preferences_prompt to test that path instead.');
            }
        }

        // Channel selection
        $requested = array_filter(array_map('trim', explode(',', (string) $this->option('channel'))));
        $this->line("Requested chans: " . implode(', ', $requested));

        // Gate check per channel
        $this->line('<info>── Subscription gate ──</info>');
        $this->line("Active sub:     " . ($gate->activeSubscription($seeker->user)?->service_key ?? 'none'));
        $this->line("Snapshot:       " . json_encode($gate->activeSubscription($seeker->user)?->feature_snapshot ?? []));
        $this->line("canWhatsApp:    " . ($gate->canWhatsApp($seeker->user) ? 'yes' : 'no'));
        $this->line("canTelegram:    " . ($gate->canTelegram($seeker->user) ? 'yes' : 'no'));

        // Send on each requested channel
        foreach ($requested as $channel) {
            $this->line('');
            $this->line("<comment>→ Sending via {$channel}...</comment>");

            try {
                match ($channel) {
                    'email'    => $email->send($seeker, $decision, $jobs),
                    'whatsapp' => $whatsapp->send($seeker, $decision, $jobs),
                    'telegram' => $telegram->send($seeker, $decision, $jobs),
                    default    => $this->warn("Unknown channel: {$channel}"),
                };
                $this->info("   ✓ {$channel} send succeeded");
            } catch (\Throwable $e) {
                $this->error("   ✗ {$channel} send failed: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    // php artisan alerts:test samuelkiira26@gmail.com

    // # 1. Check what the resolver thinks of this user
    // php artisan alerts:test samuelkiira26@gmail.com --channel=email

    // # 2. If decision is wrong, fix the prefs or force it
    // php artisan alerts:test samuelkiira26@gmail.com --force-decision=preferences_prompt

    // # 3. Once email looks right, test WhatsApp (will fail loudly if snapshot missing)
    // php artisan alerts:test samuelkiira26@gmail.com --channel=whatsapp


}