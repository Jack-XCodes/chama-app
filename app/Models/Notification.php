<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;
use App\Events\NotificationSent;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'title',
        'message',
        'action_url',
        'action_text',
        'priority',
        'read_at',
        'email_sent',
        'email_sent_at',
        'batch_id',
        'related_id',
        'related_type',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
    ];

    // Notification types
    const TYPE_PAYMENT_STATUS = 'payment_status';
    const TYPE_DOCUMENT_UPLOAD = 'document_upload';
    const TYPE_FINANCIAL_REPORT = 'financial_report';
    const TYPE_ANNOUNCEMENT = 'announcement';
    const TYPE_ACCOUNT_STATUS = 'account_status';
    const TYPE_TRANSACTION_CREATED = 'transaction_created';
    const TYPE_VERIFICATION_REQUIRED = 'verification_required';
    const TYPE_MEMBER_JOINED = 'member_joined';
    const TYPE_SYSTEM_MAINTENANCE = 'system_maintenance';

    // Priority levels
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Get the notifiable entity (usually User).
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the related model if applicable.
     */
    public function related()
    {
        if ($this->related_type && $this->related_id) {
            return $this->related_type::find($this->related_id);
        }
        return null;
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
     * Check if notification is read.
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if notification is unread.
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Get formatted time ago.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
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
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
            default => 'Normal',
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
     * Scope to notifications of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to notifications with specific priority.
     */
    public function scopeWithPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to recent notifications.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to notifications in a batch.
     */
    public function scopeInBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Create a notification for a user.
     */
    public static function createForUser(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = self::PRIORITY_NORMAL,
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?Model $relatedModel = null
    ): self {
        $notification = self::create([
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'priority' => $priority,
            'action_url' => $actionUrl,
            'action_text' => $actionText,
            'related_id' => $relatedModel?->id,
            'related_type' => $relatedModel ? get_class($relatedModel) : null,
        ]);

        // Broadcast the notification for real-time updates
        broadcast(new NotificationSent($user, $notification))->toOthers();

        return $notification;
    }

    /**
     * Create notifications for multiple users.
     */
    public static function createForUsers(
        array $users,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = self::PRIORITY_NORMAL,
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?Model $relatedModel = null,
        ?string $batchId = null
    ): array {
        $notifications = [];
        $batchId = $batchId ?: \Str::uuid();

        foreach ($users as $user) {
            $notifications[] = self::create([
                'type' => $type,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'priority' => $priority,
                'action_url' => $actionUrl,
                'action_text' => $actionText,
                'related_id' => $relatedModel?->id,
                'related_type' => $relatedModel ? get_class($relatedModel) : null,
                'batch_id' => $batchId,
            ]);
        }

        return $notifications;
    }

    /**
     * Get all notification types.
     */
    public static function getNotificationTypes(): array
    {
        return [
            self::TYPE_PAYMENT_STATUS => 'Payment Status Changes',
            self::TYPE_DOCUMENT_UPLOAD => 'Document Uploads',
            self::TYPE_FINANCIAL_REPORT => 'Financial Reports',
            self::TYPE_ANNOUNCEMENT => 'Announcements',
            self::TYPE_ACCOUNT_STATUS => 'Account Status Changes',
            self::TYPE_TRANSACTION_CREATED => 'New Transactions',
            self::TYPE_VERIFICATION_REQUIRED => 'Verification Required',
            self::TYPE_MEMBER_JOINED => 'New Members',
            self::TYPE_SYSTEM_MAINTENANCE => 'System Maintenance',
        ];
    }

    /**
     * Get all priority levels.
     */
    public static function getPriorityLevels(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }
}