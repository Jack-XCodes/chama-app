<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Models\Notification as AppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Transaction $transaction
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];
        
        if ($notifiable->shouldReceiveNotification(AppNotification::TYPE_PAYMENT_STATUS, 'email')) {
            $channels[] = 'mail';
        }
        
        if ($notifiable->shouldReceiveNotification(AppNotification::TYPE_PAYMENT_STATUS, 'in_app')) {
            $channels[] = 'database';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $status = ucfirst($this->transaction->status);
        $amount = $this->transaction->formatted_amount;
        $subject = "Payment {$status}: {$amount}";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your payment of {$amount} has been {$this->transaction->status}.")
            ->line("Transaction Date: {$this->transaction->created_at->format('F j, Y g:i A')}")
            ->line("Description: {$this->transaction->description}");

        if ($this->transaction->status === 'rejected' && $this->transaction->treasurer_notes) {
            $message->line("Treasurer's notes: {$this->transaction->treasurer_notes}");
        }

        if ($this->transaction->status === 'approved') {
            $message->line('🎉 Your payment has been successfully processed!');
        }

        if ($this->transaction->status === 'requires_verification') {
            $message->line('⏳ Your payment is being reviewed and requires additional verification.');
        }

        return $message
            ->action('View Payment Details', route('transactions.show', $this->transaction->id))
            ->line('Thank you for being a valued member of our group!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $status = ucfirst($this->transaction->status);
        $amount = $this->transaction->formatted_amount;

        return [
            'type' => AppNotification::TYPE_PAYMENT_STATUS,
            'title' => "Payment {$status}",
            'message' => "Your payment of {$amount} has been {$this->transaction->status}.",
            'action_url' => route('transactions.show', $this->transaction->id),
            'action_text' => 'View Details',
            'priority' => $this->transaction->status === 'rejected' ? AppNotification::PRIORITY_HIGH : AppNotification::PRIORITY_NORMAL,
            'data' => [
                'transaction_id' => $this->transaction->id,
                'amount' => $this->transaction->amount,
                'status' => $this->transaction->status,
                'treasurer_notes' => $this->transaction->treasurer_notes,
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