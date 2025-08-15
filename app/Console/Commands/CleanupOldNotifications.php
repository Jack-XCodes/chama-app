<?php

namespace App\Console\Commands;

use App\Services\Notifications\NotificationService;
use Illuminate\Console\Command;

class CleanupOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup 
                          {--days=90 : Number of days to keep notifications}
                          {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old read notifications';

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
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        if ($days < 1) {
            $this->error('Days must be a positive number');
            return Command::FAILURE;
        }

        $this->info("Cleaning up notifications older than {$days} days...");

        if ($dryRun) {
            $count = \App\Models\Notification::where('created_at', '<', now()->subDays($days))
                ->whereNotNull('read_at')
                ->count();
            
            $this->info("DRY RUN: Would delete {$count} old notifications");
            return Command::SUCCESS;
        }

        $deletedCount = $this->notificationService->cleanupOldNotifications($days);

        $this->info("✅ Deleted {$deletedCount} old notifications");

        return Command::SUCCESS;
    }
}