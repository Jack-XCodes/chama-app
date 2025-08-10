<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentStatusChanged extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
        public function __construct(
        protected Transaction $transaction
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->transaction->status;
        $amount = $this->transaction->formatted_amount;

        $message = (new MailMessage)
            ->subject("Payment {$status}: {$amount}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your payment of {$amount} has been {$status}.");

        if ($status === 'rejected' && $this->transaction->treasurer_notes) {
            $message->line("Treasurer's notes: {$this->transaction->treasurer_notes}");
        }

        return $message
            ->action('View Payment History', url('/payments'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
