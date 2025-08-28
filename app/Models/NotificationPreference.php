<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_type',
        'in_app_enabled',
        'email_enabled',
        'digest_enabled',
        'digest_frequency',
        'settings',
    ];

    protected $casts = [
        'in_app_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'digest_enabled' => 'boolean',
        'settings' => 'array',
    ];

    // Digest frequencies
    const DIGEST_DAILY = 'daily';
    const DIGEST_WEEKLY = 'weekly';
    const DIGEST_MONTHLY = 'monthly';

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notification type display name.
     */
    public function getTypeDisplayAttribute(): string
    {
        $types = Notification::getNotificationTypes();
        return $types[$this->notification_type] ?? ucfirst(str_replace('_', ' ', $this->notification_type));
    }

    /**
     * Check if user should receive in-app notifications for this type.
     */
    public function shouldReceiveInApp(): bool
    {
        return $this->in_app_enabled;
    }

    /**
     * Check if user should receive email notifications for this type.
     */
    public function shouldReceiveEmail(): bool
    {
        return $this->email_enabled;
    }

    /**
     * Check if user should receive digest emails for this type.
     */
    public function shouldReceiveDigest(): bool
    {
        return $this->digest_enabled;
    }

    /**
     * Get or create preference for user and notification type.
     */
    public static function getOrCreateForUser(User $user, string $notificationType): self
    {
        return self::firstOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => $notificationType,
            ],
            [
                'in_app_enabled' => true,
                'email_enabled' => true,
                'digest_enabled' => false,
                'digest_frequency' => self::DIGEST_WEEKLY,
            ]
        );
    }

    /**
     * Update preferences for a user.
     */
    public static function updatePreferencesForUser(User $user, array $preferences): void
    {
        foreach ($preferences as $notificationType => $settings) {
            self::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_type' => $notificationType,
                ],
                [
                    'in_app_enabled' => $settings['in_app_enabled'] ?? true,
                    'email_enabled' => $settings['email_enabled'] ?? true,
                    'digest_enabled' => $settings['digest_enabled'] ?? false,
                    'digest_frequency' => $settings['digest_frequency'] ?? self::DIGEST_WEEKLY,
                    'settings' => $settings['settings'] ?? null,
                ]
            );
        }
    }

    /**
     * Get all preferences for a user.
     */
    public static function getPreferencesForUser(User $user): \Illuminate\Support\Collection
    {
        $preferences = self::where('user_id', $user->id)->get()->keyBy('notification_type');
        
        // Ensure all notification types have preferences
        foreach (Notification::getNotificationTypes() as $type => $display) {
            if (!$preferences->has($type)) {
                $preferences[$type] = self::getOrCreateForUser($user, $type);
            }
        }

        return $preferences;
    }

    /**
     * Check if user should receive notification based on preferences.
     */
    public static function shouldUserReceiveNotification(User $user, string $notificationType, string $channel = 'in_app'): bool
    {
        $preference = self::getOrCreateForUser($user, $notificationType);

        return match($channel) {
            'in_app' => $preference->shouldReceiveInApp(),
            'email' => $preference->shouldReceiveEmail(),
            'digest' => $preference->shouldReceiveDigest(),
            default => false,
        };
    }

    /**
     * Get users who should receive notifications of a specific type via specific channel.
     */
    public static function getUsersForNotification(string $notificationType, string $channel = 'in_app'): \Illuminate\Support\Collection
    {
        $column = match($channel) {
            'in_app' => 'in_app_enabled',
            'email' => 'email_enabled',
            'digest' => 'digest_enabled',
            default => 'in_app_enabled',
        };

        return User::whereHas('notificationPreferences', function ($query) use ($notificationType, $column) {
            $query->where('notification_type', $notificationType)
                  ->where($column, true);
        })->orWhereDoesntHave('notificationPreferences', function ($query) use ($notificationType) {
            $query->where('notification_type', $notificationType);
        })->get();
    }

    /**
     * Get digest frequencies.
     */
    public static function getDigestFrequencies(): array
    {
        return [
            self::DIGEST_DAILY => 'Daily',
            self::DIGEST_WEEKLY => 'Weekly',
            self::DIGEST_MONTHLY => 'Monthly',
        ];
    }

    /**
     * Scope to preferences for a specific notification type.
     */
    public function scopeForType($query, string $notificationType)
    {
        return $query->where('notification_type', $notificationType);
    }

    /**
     * Scope to preferences with in-app enabled.
     */
    public function scopeInAppEnabled($query)
    {
        return $query->where('in_app_enabled', true);
    }

    /**
     * Scope to preferences with email enabled.
     */
    public function scopeEmailEnabled($query)
    {
        return $query->where('email_enabled', true);
    }

    /**
     * Scope to preferences with digest enabled.
     */
    public function scopeDigestEnabled($query)
    {
        return $query->where('digest_enabled', true);
    }
}