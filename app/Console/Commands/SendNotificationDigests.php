<?php

namespace App\Console\Commands;

use App\Services\Notifications\NotificationService;
use Illuminate\Console\Command;

class SendNotificationDigests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-digests 
                          {frequency=weekly : The digest frequency (daily, weekly, monthly)}
                          {--force : Force sending even if no notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification digest emails to users';

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $frequency = $this->argument('frequency');
        $force = $this->option('force');

        if (!in_array($frequency, ['daily', 'weekly', 'monthly'])) {
            $this->error('Invalid frequency. Use: daily, weekly, or monthly');
            return Command::FAILURE;
        }

        $this->info("Sending {$frequency} notification digests...");

        $results = $this->notificationService->sendDigestEmails($frequency);

        $this->info("Digest sending completed:");
        $this->line("✅ Sent: {$results['sent']}");
        $this->line("⏭️  Skipped (no notifications): {$results['skipped']}");
        $this->line("❌ Failed: {$results['failed']}");

        if ($results['failed'] > 0) {
            $this->warn("Some digest emails failed to send. Check the logs for details.");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}