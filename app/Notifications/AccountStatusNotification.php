<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Notification as AppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $statusType,
        protected string $statusMessage,
        protected array $data = []
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];
        
        if ($notifiable->shouldReceiveNotification(AppNotification::TYPE_ACCOUNT_STATUS, 'email')) {
            $channels[] = 'mail';
        }
        
        if ($notifiable->shouldReceiveNotification(AppNotification::TYPE_ACCOUNT_STATUS, 'in_app')) {
            $channels[] = 'database';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = match($this->statusType) {
            'role_changed' => 'Your Role Has Been Updated',
            'permissions_changed' => 'Your Permissions Have Been Updated',
            'account_activated' => 'Your Account Has Been Activated',
            'account_deactivated' => 'Your Account Has Been Deactivated',
            'profile_updated' => 'Your Profile Has Been Updated',
            'password_changed' => 'Your Password Has Been Changed',
            'email_verified' => 'Your Email Has Been Verified',
            'membership_status' => 'Your Membership Status Has Changed',
            default => 'Account Status Update',
        };

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name}!")
            ->line($this->statusMessage);

        // Add specific information based on status type
        switch ($this->statusType) {
            case 'role_changed':
                if (isset($this->data['new_role'])) {
                    $message->line("Your new role: {$this->data['new_role']}");
                }
                if (isset($this->data['changed_by'])) {
                    $message->line("Changed by: {$this->data['changed_by']}");
                }
                break;
                
            case 'permissions_changed':
                if (isset($this->data['added_permissions'])) {
                    $message->line("Added permissions: " . implode(', ', $this->data['added_permissions']));
                }
                if (isset($this->data['removed_permissions'])) {
                    $message->line("Removed permissions: " . implode(', ', $this->data['removed_permissions']));
                }
                break;
                
            case 'membership_status':
                if (isset($this->data['status'])) {
                    $message->line("New status: {$this->data['status']}");
                }
                if (isset($this->data['effective_date'])) {
                    $message->line("Effective date: {$this->data['effective_date']}");
                }
                break;
        }

        if (isset($this->data['reason'])) {
            $message->line("Reason: {$this->data['reason']}");
        }

        return $message
            ->action('View Account Details', route('profile'))
            ->line('If you have any questions, please contact the administrators.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $title = match($this->statusType) {
            'role_changed' => 'Role Updated',
            'permissions_changed' => 'Permissions Updated',
            'account_activated' => 'Account Activated',
            'account_deactivated' => 'Account Deactivated',
            'profile_updated' => 'Profile Updated',
            'password_changed' => 'Password Changed',
            'email_verified' => 'Email Verified',
            'membership_status' => 'Membership Status Changed',
            default => 'Account Status Update',
        };

        $priority = match($this->statusType) {
            'account_deactivated', 'password_changed' => AppNotification::PRIORITY_HIGH,
            'role_changed', 'permissions_changed' => AppNotification::PRIORITY_NORMAL,
            default => AppNotification::PRIORITY_LOW,
        };
        
        return [
            'type' => AppNotification::TYPE_ACCOUNT_STATUS,
            'title' => $title,
            'message' => $this->statusMessage,
            'action_url' => route('profile'),
            'action_text' => 'View Account',
            'priority' => $priority,
            'data' => array_merge([
                'status_type' => $this->statusType,
                'timestamp' => now()->toISOString(),
            ], $this->data),
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Create notification for role change.
     */
    public static function roleChanged(string $newRole, string $changedBy, ?string $reason = null): self
    {
        return new self(
            'role_changed',
            "Your role has been changed to {$newRole}.",
            [
                'new_role' => $newRole,
                'changed_by' => $changedBy,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Create notification for permission changes.
     */
    public static function permissionsChanged(array $addedPermissions = [], array $removedPermissions = [], ?string $changedBy = null): self
    {
        $message = 'Your account permissions have been updated.';
        
        return new self(
            'permissions_changed',
            $message,
            [
                'added_permissions' => $addedPermissions,
                'removed_permissions' => $removedPermissions,
                'changed_by' => $changedBy,
            ]
        );
    }

    /**
     * Create notification for account activation.
     */
    public static function accountActivated(): self
    {
        return new self(
            'account_activated',
            'Your account has been activated and you now have full access to the system.'
        );
    }

    /**
     * Create notification for account deactivation.
     */
    public static function accountDeactivated(string $reason = null): self
    {
        return new self(
            'account_deactivated',
            'Your account has been deactivated. Please contact an administrator if you believe this is an error.',
            ['reason' => $reason]
        );
    }

    /**
     * Create notification for membership status change.
     */
    public static function membershipStatusChanged(string $newStatus, string $effectiveDate = null, string $reason = null): self
    {
        return new self(
            'membership_status',
            "Your membership status has been changed to {$newStatus}.",
            [
                'status' => $newStatus,
                'effective_date' => $effectiveDate,
                'reason' => $reason,
            ]
        );
    }
}