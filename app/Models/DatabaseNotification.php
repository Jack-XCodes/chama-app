<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;
use App\Events\NotificationSent;

class DatabaseNotification extends BaseDatabaseNotification
{
    /**
     * Get the notification's priority color.
     */
    public function getPriorityColorAttribute(): string
    {
        $priority = $this->data['priority'] ?? 'normal';
        
        return match($priority) {
            'low' => 'gray',
            'normal' => 'blue',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'blue',
        };
    }

    /**
     * Get the notification's priority display.
     */
    public function getPriorityDisplayAttribute(): string
    {
        $priority = $this->data['priority'] ?? 'normal';
        
        return match($priority) {
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => 'Normal',
        };
    }

    /**
     * Get the notification's title.
     */
    public function getTitleAttribute(): string
    {
        return $this->data['title'] ?? 'Notification';
    }

    /**
     * Get the notification's message.
     */
    public function getMessageAttribute(): string
    {
        return $this->data['message'] ?? '';
    }

    /**
     * Get the notification's action URL.
     */
    public function getActionUrlAttribute(): ?string
    {
        return $this->data['action_url'] ?? null;
    }

    /**
     * Get the notification's action text.
     */
    public function getActionTextAttribute(): ?string
    {
        return $this->data['action_text'] ?? null;
    }

    /**
     * Get formatted time ago.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Check if notification is unread.
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Check if notification is read.
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    /**
     * Get notification icon based on type.
     */
    public function getIconAttribute(): string
    {
        return match($this->type) {
            'App\Notifications\PaymentStatusChanged' => '💳',
            'App\Notifications\DocumentUploadNotification' => '📄',
            'App\Notifications\FinancialReportNotification' => '📊',
            'App\Notifications\AnnouncementNotification' => '📢',
            'App\Notifications\AccountStatusNotification' => '👤',
            'App\Notifications\TransactionNotification' => '💰',
            default => '🔔',
        };
    }

    /**
     * Scope to unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to read notifications.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope to recent notifications.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to notifications with specific priority.
     */
    public function scopeWithPriority($query, string $priority)
    {
        return $query->whereJsonContains('data->priority', $priority);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($notification) {
            // Broadcast the notification for real-time updates
            if ($notification->notifiable) {
                broadcast(new NotificationSent($notification->notifiable, $notification))->toOthers();
            }
        });
    }
}