<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the user's notifications.
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable');
    }

    /**
     * Get the user's notification preferences.
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /**
     * Get the user's announcements (created by this user).
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    /**
     * Get unread notifications count.
     */
    public function getUnreadNotificationsCountAttribute(): int
    {
        return $this->notifications()->unread()->count();
    }

    /**
     * Get recent unread notifications.
     */
    public function getRecentNotificationsAttribute(): \Illuminate\Support\Collection
    {
        return $this->notifications()
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsAsRead(): void
    {
        $this->notifications()->unread()->update(['read_at' => now()]);
    }

    /**
     * Get notification preference for a specific type.
     */
    public function getNotificationPreference(string $type): NotificationPreference
    {
        return NotificationPreference::getOrCreateForUser($this, $type);
    }

    /**
     * Check if user should receive notification of a specific type via channel.
     */
    public function shouldReceiveNotification(string $type, string $channel = 'in_app'): bool
    {
        return NotificationPreference::shouldUserReceiveNotification($this, $type, $channel);
    }
}
