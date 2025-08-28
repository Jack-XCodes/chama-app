<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'priority',
        'is_urgent',
        'attachments',
        'created_by',
        'published_at',
        'expires_at',
        'is_published',
        'send_email',
        'send_in_app',
        'target_roles',
        'target_users',
        'views_count',
        'metadata',
    ];

    protected $casts = [
        'is_urgent' => 'boolean',
        'attachments' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_published' => 'boolean',
        'send_email' => 'boolean',
        'send_in_app' => 'boolean',
        'target_roles' => 'array',
        'target_users' => 'array',
        'metadata' => 'array',
    ];

    // Priority levels
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Get the user who created the announcement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get priority badge color.
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'gray',
            self::PRIORITY_NORMAL => 'blue',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_URGENT => 'red',
            default => 'blue',
        };
    }

    /**
     * Get priority display name.
     */
    public function getPriorityDisplayAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'Low Priority',
            self::PRIORITY_NORMAL => 'Normal Priority',
            self::PRIORITY_HIGH => 'High Priority',
            self::PRIORITY_URGENT => 'Urgent',
            default => 'Normal Priority',
        };
    }

    /**
     * Check if announcement is published.
     */
    public function isPublished(): bool
    {
        return $this->is_published && 
               $this->published_at && 
               $this->published_at->isPast();
    }

    /**
     * Check if announcement is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if announcement is active.
     */
    public function isActive(): bool
    {
        return $this->isPublished() && !$this->isExpired();
    }

    /**
     * Get formatted content with line breaks.
     */
    public function getFormattedContentAttribute(): string
    {
        return nl2br(e($this->content));
    }

    /**
     * Get attachment URLs.
     */
    public function getAttachmentUrls(): array
    {
        if (!$this->attachments) {
            return [];
        }

        return collect($this->attachments)->map(function ($attachment) {
            return [
                'name' => $attachment['name'] ?? 'Unknown',
                'url' => Storage::url($attachment['path']),
                'size' => $attachment['size'] ?? 0,
                'type' => $attachment['type'] ?? 'unknown',
            ];
        })->toArray();
    }

    /**
     * Increment views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Publish the announcement.
     */
    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Send notifications to targeted users
        $this->sendNotifications();
    }

    /**
     * Unpublish the announcement.
     */
    public function unpublish(): void
    {
        $this->update([
            'is_published' => false,
        ]);
    }

    /**
     * Send notifications for this announcement.
     */
    public function sendNotifications(): void
    {
        $users = $this->getTargetedUsers();

        if ($users->isEmpty()) {
            return;
        }

        $notifications = [];
        $batchId = \Str::uuid();

        foreach ($users as $user) {
            // Check user preferences
            if ($this->send_in_app && NotificationPreference::shouldUserReceiveNotification($user, Notification::TYPE_ANNOUNCEMENT, 'in_app')) {
                $notifications[] = Notification::createForUser(
                    $user,
                    Notification::TYPE_ANNOUNCEMENT,
                    $this->title,
                    $this->getNotificationMessage(),
                    [
                        'announcement_id' => $this->id,
                        'is_urgent' => $this->is_urgent,
                        'has_attachments' => !empty($this->attachments),
                    ],
                    $this->is_urgent ? Notification::PRIORITY_URGENT : $this->priority,
                    route('announcements.show', $this->id),
                    'View Announcement',
                    $this
                );
            }

            // Send email if enabled
            if ($this->send_email && NotificationPreference::shouldUserReceiveNotification($user, Notification::TYPE_ANNOUNCEMENT, 'email')) {
                // Queue email notification
                $user->notify(new \App\Notifications\AnnouncementNotification($this));
            }
        }

        // Update batch ID for all notifications
        if (!empty($notifications)) {
            Notification::whereIn('id', collect($notifications)->pluck('id'))
                ->update(['batch_id' => $batchId]);
        }
    }

    /**
     * Get targeted users for this announcement.
     */
    public function getTargetedUsers(): \Illuminate\Support\Collection
    {
        $users = collect();

        // If specific users are targeted
        if (!empty($this->target_users)) {
            $users = $users->merge(User::whereIn('id', $this->target_users)->get());
        }

        // If specific roles are targeted
        if (!empty($this->target_roles)) {
            $roleUsers = User::role($this->target_roles)->get();
            $users = $users->merge($roleUsers);
        }

        // If no specific targeting, send to all users
        if (empty($this->target_users) && empty($this->target_roles)) {
            $users = User::all();
        }

        return $users->unique('id');
    }

    /**
     * Get notification message.
     */
    private function getNotificationMessage(): string
    {
        $message = \Str::limit($this->content, 100);
        
        if ($this->is_urgent) {
            $message = '🚨 URGENT: ' . $message;
        }

        if (!empty($this->attachments)) {
            $message .= ' (Has attachments)';
        }

        return $message;
    }

    /**
     * Scope to published announcements.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope to active announcements.
     */
    public function scopeActive($query)
    {
        return $query->published()
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                              ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope to urgent announcements.
     */
    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }

    /**
     * Scope to announcements for a specific user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($query) use ($user) {
            $query->whereNull('target_users')
                  ->orWhereJsonContains('target_users', $user->id);
        })->where(function ($query) use ($user) {
            $userRoles = $user->getRoleNames()->toArray();
            $query->whereNull('target_roles');
            
            foreach ($userRoles as $role) {
                $query->orWhereJsonContains('target_roles', $role);
            }
        });
    }

    /**
     * Get all priority levels.
     */
    public static function getPriorityLevels(): array
    {
        return [
            self::PRIORITY_LOW => 'Low Priority',
            self::PRIORITY_NORMAL => 'Normal Priority',
            self::PRIORITY_HIGH => 'High Priority',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    /**
     * Delete announcement and its attachments.
     */
    public function delete()
    {
        // Delete attachments from storage
        if ($this->attachments) {
            foreach ($this->attachments as $attachment) {
                if (isset($attachment['path']) && Storage::exists($attachment['path'])) {
                    Storage::delete($attachment['path']);
                }
            }
        }

        return parent::delete();
    }
}