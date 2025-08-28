<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SystemHealthWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $storageUsage = $this->getStorageUsage();
        $storageLimit = 10 * 1024 * 1024 * 1024; // 10GB example limit
        $storagePercentage = ($storageUsage / $storageLimit) * 100;

        $recentErrors = $this->getRecentErrors();
        $errorCount = count($recentErrors);

        $activeUsers = $this->getActiveUsers();
        $totalUsers = \App\Models\User::count();
        $activePercentage = $totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0;

        $queueStatus = $this->getQueueStatus();
        $failedJobs = $queueStatus['failed'];
        $pendingJobs = $queueStatus['pending'];

        return [
            Stat::make('Storage Usage', $this->formatBytes($storageUsage))
                ->description($storagePercentage > 80 ? 'Storage nearly full' : 'Storage healthy')
                ->descriptionIcon($storagePercentage > 80 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle')
                ->color($storagePercentage > 80 ? 'danger' : ($storagePercentage > 60 ? 'warning' : 'success'))
                ->chart([
                    $storagePercentage,
                    100 - $storagePercentage,
                ]),

            Stat::make('Recent Errors', $errorCount)
                ->description($errorCount > 0 ? 'Last 24 hours' : 'No recent errors')
                ->descriptionIcon($errorCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($errorCount > 10 ? 'danger' : ($errorCount > 0 ? 'warning' : 'success')),

            Stat::make('Active Users', $activeUsers)
                ->description(number_format($activePercentage, 1) . '% of total users')
                ->descriptionIcon('heroicon-m-users')
                ->color($activePercentage > 50 ? 'success' : 'warning')
                ->chart([
                    $activeUsers,
                    $totalUsers - $activeUsers,
                ]),

            Stat::make('Queue Status', $pendingJobs . ' pending')
                ->description($failedJobs > 0 ? $failedJobs . ' failed jobs' : 'All jobs processed')
                ->descriptionIcon($failedJobs > 0 ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                ->color($failedJobs > 0 ? 'danger' : ($pendingJobs > 10 ? 'warning' : 'success')),
        ];
    }

    private function getStorageUsage(): int
    {
        $usage = 0;

        // Calculate storage usage for different directories
        $directories = [
            'documents',
            'transaction-proofs',
            'announcements',
            'reports',
        ];

        foreach ($directories as $directory) {
            $files = Storage::allFiles($directory);
            foreach ($files as $file) {
                $usage += Storage::size($file);
            }
        }

        return $usage;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function getRecentErrors(): array
    {
        $cacheKey = 'recent_errors';

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            try {
                $logFile = storage_path('logs/laravel-' . now()->format('Y-m-d') . '.log');
                if (!file_exists($logFile)) {
                    return [];
                }

                $command = "tail -n 1000 " . escapeshellarg($logFile) . " | grep -i 'error\\|exception' -A 3";
                exec($command, $output);

                return array_slice($output, -50); // Return last 50 error lines
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    private function getActiveUsers(): int
    {
        return Cache::remember('active_users_count', now()->addMinutes(5), function () {
            return DB::table('sessions')
                ->where('last_activity', '>=', now()->subMinutes(5)->getTimestamp())
                ->count();
        });
    }

    private function getQueueStatus(): array
    {
        return [
            'failed' => DB::table('failed_jobs')->count(),
            'pending' => DB::table('jobs')->count(),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->can('view-system-health');
    }
}