<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\Notification as AppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentUploadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Document $document
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];
        
        if ($notifiable->shouldReceiveNotification(AppNotification::TYPE_DOCUMENT_UPLOAD, 'email')) {
            $channels[] = 'mail';
        }
        
        if ($notifiable->shouldReceiveNotification(AppNotification::TYPE_DOCUMENT_UPLOAD, 'in_app')) {
            $channels[] = 'database';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $documentType = $this->document->documentType->name ?? 'Document';
        
        return (new MailMessage)
            ->subject("New {$documentType} Available")
            ->greeting("Hello {$notifiable->name}!")
            ->line("A new {$documentType} has been uploaded and is now available for download.")
            ->line("Document: {$this->document->title}")
            ->line("Uploaded by: {$this->document->uploader->name}")
            ->line("Upload Date: {$this->document->created_at->format('F j, Y g:i A')}")
            ->when($this->document->description, function ($message) {
                return $message->line("Description: {$this->document->description}");
            })
            ->action('View Document', route('documents.show', $this->document->id))
            ->line('Stay informed with the latest group documents!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $documentType = $this->document->documentType->name ?? 'Document';
        
        return [
            'type' => AppNotification::TYPE_DOCUMENT_UPLOAD,
            'title' => "New {$documentType} Available",
            'message' => "A new {$documentType} '{$this->document->title}' has been uploaded by {$this->document->uploader->name}.",
            'action_url' => route('documents.show', $this->document->id),
            'action_text' => 'View Document',
            'priority' => AppNotification::PRIORITY_NORMAL,
            'data' => [
                'document_id' => $this->document->id,
                'document_title' => $this->document->title,
                'document_type' => $documentType,
                'uploader_name' => $this->document->uploader->name,
                'file_size' => $this->document->file_size,
                'file_type' => $this->document->file_type,
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