<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Send notification to a user.
     */
    public function sendToUser(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = Notification::PRIORITY_NORMAL,
        ?string $actionUrl = null,
        ?string $actionText = null,
        $relatedModel = null
    ): ?Notification {
        // Check if user should receive this type of notification
        if (!$user->shouldReceiveNotification($type, 'in_app')) {
            return null;
        }

        return Notification::createForUser(
            $user,
            $type,
            $title,
            $message,
            $data,
            $priority,
            $actionUrl,
            $actionText,
            $relatedModel
        );
    }

    /**
     * Send notification to multiple users.
     */
    public function sendToUsers(
        array $users,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = Notification::PRIORITY_NORMAL,
        ?string $actionUrl = null,
        ?string $actionText = null,
        $relatedModel = null
    ): array {
        $notifications = [];
        $batchId = Str::uuid();

        foreach ($users as $user) {
            if ($user->shouldReceiveNotification($type, 'in_app')) {
                $notification = Notification::createForUser(
                    $user,
                    $type,
                    $title,
                    $message,
                    $data,
                    $priority,
                    $actionUrl,
                    $actionText,
                    $relatedModel
                );

                if ($notification) {
                    $notification->update(['batch_id' => $batchId]);
                    $notifications[] = $notification;
                }
            }
        }

        return $notifications;
    }

    /**
     * Send notification to users with specific roles.
     */
    public function sendToRoles(
        array $roles,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = Notification::PRIORITY_NORMAL,
        ?string $actionUrl = null,
        ?string $actionText = null,
        $relatedModel = null
    ): array {
        $users = User::role($roles)->get();
        
        return $this->sendToUsers(
            $users->toArray(),
            $type,
            $title,
            $message,
            $data,
            $priority,
            $actionUrl,
            $actionText,
            $relatedModel
        );
    }

    /**
     * Send notification to all users.
     */
    public function sendToAll(
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = Notification::PRIORITY_NORMAL,
        ?string $actionUrl = null,
        ?string $actionText = null,
        $relatedModel = null
    ): array {
        $users = User::all();
        
        return $this->sendToUsers(
            $users->toArray(),
            $type,
            $title,
            $message,
            $data,
            $priority,
            $actionUrl,
            $actionText,
            $relatedModel
        );
    }

    /**
     * Get notifications for digest email.
     */
    public function getDigestNotifications(User $user, string $frequency = 'weekly'): Collection
    {
        $since = match($frequency) {
            'daily' => now()->subDay(),
            'weekly' => now()->subWeek(),
            'monthly' => now()->subMonth(),
            default => now()->subWeek(),
        };

        return $user->notifications()
            ->where('created_at', '>=', $since)
            ->where('email_sent', false)
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Send digest email to user.
     */
    public function sendDigestEmail(User $user, string $frequency = 'weekly'): bool
    {
        $notifications = $this->getDigestNotifications($user, $frequency);

        if ($notifications->isEmpty()) {
            return false;
        }

        // Group notifications by type
        $groupedNotifications = $notifications->groupBy('type');

        try {
            Mail::send('emails.notifications.digest', [
                'user' => $user,
                'notifications' => $notifications,
                'groupedNotifications' => $groupedNotifications,
                'frequency' => $frequency,
                'period' => $this->getDigestPeriod($frequency),
                'summary' => $this->getDigestSummary($notifications),
            ], function ($message) use ($user, $frequency) {
                $message->to($user->email, $user->name)
                       ->subject(ucfirst($frequency) . ' Digest - ' . config('app.name'));
            });

            // Mark notifications as email sent
            $notifications->each(function ($notification) {
                $notification->update([
                    'email_sent' => true,
                    'email_sent_at' => now(),
                ]);
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send digest email to user ' . $user->id . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send digest emails to all users who have digest enabled.
     */
    public function sendDigestEmails(string $frequency = 'weekly'): array
    {
        $users = User::whereHas('notificationPreferences', function ($query) use ($frequency) {
            $query->where('digest_enabled', true)
                  ->where('digest_frequency', $frequency);
        })->get();

        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($users as $user) {
            $sent = $this->sendDigestEmail($user, $frequency);
            
            if ($sent) {
                $results['sent']++;
            } else {
                $notifications = $this->getDigestNotifications($user, $frequency);
                if ($notifications->isEmpty()) {
                    $results['skipped']++;
                } else {
                    $results['failed']++;
                }
            }
        }

        return $results;
    }

    /**
     * Get digest period description.
     */
    private function getDigestPeriod(string $frequency): array
    {
        return match($frequency) {
            'daily' => [
                'start' => now()->subDay()->format('F j, Y'),
                'end' => now()->format('F j, Y'),
                'description' => 'yesterday',
            ],
            'weekly' => [
                'start' => now()->subWeek()->format('F j, Y'),
                'end' => now()->format('F j, Y'),
                'description' => 'the past week',
            ],
            'monthly' => [
                'start' => now()->subMonth()->format('F j, Y'),
                'end' => now()->format('F j, Y'),
                'description' => 'the past month',
            ],
            default => [
                'start' => now()->subWeek()->format('F j, Y'),
                'end' => now()->format('F j, Y'),
                'description' => 'the past week',
            ],
        };
    }

    /**
     * Get digest summary statistics.
     */
    private function getDigestSummary(Collection $notifications): array
    {
        return [
            'total' => $notifications->count(),
            'urgent' => $notifications->where('priority', Notification::PRIORITY_URGENT)->count(),
            'high' => $notifications->where('priority', Notification::PRIORITY_HIGH)->count(),
            'types' => $notifications->groupBy('type')->map->count(),
            'most_common_type' => $notifications->groupBy('type')
                ->map->count()
                ->sortDesc()
                ->keys()
                ->first(),
        ];
    }

    /**
     * Batch notifications by type and related model.
     */
    public function batchNotifications(Collection $notifications): Collection
    {
        return $notifications->groupBy(function ($notification) {
            return $notification->type . '|' . ($notification->related_type ?? 'none') . '|' . ($notification->related_id ?? 'none');
        });
    }

    /**
     * Clean up old notifications.
     */
    public function cleanupOldNotifications(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return Notification::where('created_at', '<', $cutoffDate)
            ->where('read_at', '!=', null) // Only delete read notifications
            ->delete();
    }

    /**
     * Get notification statistics for a user.
     */
    public function getUserNotificationStats(User $user): array
    {
        $notifications = $user->notifications();

        return [
            'total' => $notifications->count(),
            'unread' => $notifications->unread()->count(),
            'read' => $notifications->read()->count(),
            'urgent' => $notifications->where('priority', Notification::PRIORITY_URGENT)->count(),
            'high' => $notifications->where('priority', Notification::PRIORITY_HIGH)->count(),
            'today' => $notifications->whereDate('created_at', today())->count(),
            'this_week' => $notifications->where('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => $notifications->where('created_at', '>=', now()->startOfMonth())->count(),
            'by_type' => $notifications->get()->groupBy('type')->map->count(),
        ];
    }

    /**
     * Mark notifications as read for a specific batch.
     */
    public function markBatchAsRead(string $batchId): int
    {
        return Notification::where('batch_id', $batchId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get system-wide notification statistics.
     */
    public function getSystemNotificationStats(): array
    {
        return [
            'total_notifications' => Notification::count(),
            'unread_notifications' => Notification::unread()->count(),
            'notifications_today' => Notification::whereDate('created_at', today())->count(),
            'notifications_this_week' => Notification::where('created_at', '>=', now()->startOfWeek())->count(),
            'notifications_this_month' => Notification::where('created_at', '>=', now()->startOfMonth())->count(),
            'urgent_notifications' => Notification::where('priority', Notification::PRIORITY_URGENT)->unread()->count(),
            'users_with_unread' => User::whereHas('notifications', function ($query) {
                $query->unread();
            })->count(),
            'most_active_notification_type' => Notification::selectRaw('type, COUNT(*) as count')
                ->where('created_at', '>=', now()->subMonth())
                ->groupBy('type')
                ->orderByDesc('count')
                ->first(),
        ];
    }
}