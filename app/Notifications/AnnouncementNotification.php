<?php

namespace App\Notifications;

use App\Models\Announcement;
use App\Models\Notification as AppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Announcement $announcement
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];
        
        if ($notifiable->shouldReceiveNotification(AppNotification::TYPE_ANNOUNCEMENT, 'email')) {
            $channels[] = 'mail';
        }
        
        if ($notifiable->shouldReceiveNotification(AppNotification::TYPE_ANNOUNCEMENT, 'in_app')) {
            $channels[] = 'database';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->announcement->is_urgent 
            ? "🚨 URGENT: {$this->announcement->title}" 
            : $this->announcement->title;
            
        $message = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name}!")
            ->line($this->announcement->is_urgent ? '🚨 This is an urgent announcement:' : 'New announcement from the group:')
            ->line($this->announcement->content)
            ->line("Posted by: {$this->announcement->creator->name}")
            ->line("Posted on: {$this->announcement->published_at->format('F j, Y g:i A')}");

        // Add attachments info if any
        if (!empty($this->announcement->attachments)) {
            $attachmentCount = count($this->announcement->attachments);
            $message->line("📎 This announcement includes {$attachmentCount} attachment(s).");
        }

        // Add expiration info if applicable
        if ($this->announcement->expires_at) {
            $message->line("⏰ This announcement expires on: {$this->announcement->expires_at->format('F j, Y')}");
        }

        return $message
            ->action('View Full Announcement', route('announcements.show', $this->announcement->id))
            ->line('Stay connected with important group updates!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $title = $this->announcement->is_urgent 
            ? "🚨 URGENT: {$this->announcement->title}" 
            : $this->announcement->title;
            
        $message = \Str::limit($this->announcement->content, 100);
        if ($this->announcement->is_urgent) {
            $message = '🚨 URGENT: ' . $message;
        }
        
        return [
            'type' => AppNotification::TYPE_ANNOUNCEMENT,
            'title' => $title,
            'message' => $message,
            'action_url' => route('announcements.show', $this->announcement->id),
            'action_text' => 'View Announcement',
            'priority' => $this->announcement->is_urgent ? AppNotification::PRIORITY_URGENT : $this->announcement->priority,
            'data' => [
                'announcement_id' => $this->announcement->id,
                'title' => $this->announcement->title,
                'is_urgent' => $this->announcement->is_urgent,
                'priority' => $this->announcement->priority,
                'has_attachments' => !empty($this->announcement->attachments),
                'attachment_count' => count($this->announcement->attachments ?? []),
                'expires_at' => $this->announcement->expires_at?->toISOString(),
                'creator_name' => $this->announcement->creator->name,
            ],
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}