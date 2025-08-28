<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Models\Notification as AppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Transaction $transaction,
        protected string $eventType = 'created'
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];
        
        $notificationType = match($this->eventType) {
            'verification_required' => AppNotification::TYPE_VERIFICATION_REQUIRED,
            default => AppNotification::TYPE_TRANSACTION_CREATED,
        };
        
        if ($notifiable->shouldReceiveNotification($notificationType, 'email')) {
            $channels[] = 'mail';
        }
        
        if ($notifiable->shouldReceiveNotification($notificationType, 'in_app')) {
            $channels[] = 'database';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $amount = $this->transaction->formatted_amount;
        $transactionType = $this->transaction->type_display;
        
        $subject = match($this->eventType) {
            'verification_required' => "Transaction Verification Required: {$amount}",
            'created' => "New {$transactionType}: {$amount}",
            'processed' => "Transaction Processed: {$amount}",
            default => "Transaction Update: {$amount}",
        };

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name}!");

        switch ($this->eventType) {
            case 'verification_required':
                $message->line("A large transaction requires your verification:")
                       ->line("⚠️ Amount: {$amount}")
                       ->line("Type: {$transactionType}")
                       ->line("Description: {$this->transaction->description}")
                       ->line("Submitted by: {$this->transaction->user->name}")
                       ->line("Date: {$this->transaction->created_at->format('F j, Y g:i A')}")
                       ->line("This transaction exceeds the verification threshold and requires your approval.");
                break;
                
            case 'created':
                $message->line("A new {$transactionType} has been recorded:")
                       ->line("Amount: {$amount}")
                       ->line("Description: {$this->transaction->description}")
                       ->line("Submitted by: {$this->transaction->user->name}")
                       ->line("Date: {$this->transaction->created_at->format('F j, Y g:i A')}");
                       
                if ($this->transaction->category) {
                    $message->line("Category: {$this->transaction->category->name}");
                }
                break;
                
            case 'processed':
                $message->line("A transaction has been processed:")
                       ->line("Amount: {$amount}")
                       ->line("Status: " . ucfirst($this->transaction->status))
                       ->line("Processed by: {$this->transaction->processor->name}")
                       ->line("Date: {$this->transaction->processed_at->format('F j, Y g:i A')}");
                       
                if ($this->transaction->treasurer_notes) {
                    $message->line("Notes: {$this->transaction->treasurer_notes}");
                }
                break;
        }

        $actionText = match($this->eventType) {
            'verification_required' => 'Review Transaction',
            default => 'View Transaction',
        };

        $actionUrl = match($this->eventType) {
            'verification_required' => route('treasurer.verification'),
            default => route('transactions.show', $this->transaction->id),
        };

        return $message
            ->action($actionText, $actionUrl)
            ->line('Thank you for staying engaged with group financial activities!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $amount = $this->transaction->formatted_amount;
        $transactionType = $this->transaction->type_display;
        
        $data = match($this->eventType) {
            'verification_required' => [
                'type' => AppNotification::TYPE_VERIFICATION_REQUIRED,
                'title' => 'Transaction Verification Required',
                'message' => "A large {$transactionType} of {$amount} requires your verification.",
                'action_url' => route('treasurer.verification'),
                'action_text' => 'Review Transaction',
                'priority' => AppNotification::PRIORITY_HIGH,
            ],
            'created' => [
                'type' => AppNotification::TYPE_TRANSACTION_CREATED,
                'title' => "New {$transactionType}",
                'message' => "A new {$transactionType} of {$amount} has been recorded by {$this->transaction->user->name}.",
                'action_url' => route('transactions.show', $this->transaction->id),
                'action_text' => 'View Transaction',
                'priority' => AppNotification::PRIORITY_NORMAL,
            ],
            'processed' => [
                'type' => AppNotification::TYPE_TRANSACTION_CREATED,
                'title' => 'Transaction Processed',
                'message' => "A {$transactionType} of {$amount} has been " . $this->transaction->status . ".",
                'action_url' => route('transactions.show', $this->transaction->id),
                'action_text' => 'View Transaction',
                'priority' => $this->transaction->status === 'rejected' ? AppNotification::PRIORITY_HIGH : AppNotification::PRIORITY_NORMAL,
            ],
            default => [
                'type' => AppNotification::TYPE_TRANSACTION_CREATED,
                'title' => 'Transaction Update',
                'message' => "Transaction {$amount} has been updated.",
                'action_url' => route('transactions.show', $this->transaction->id),
                'action_text' => 'View Transaction',
                'priority' => AppNotification::PRIORITY_NORMAL,
            ],
        ];
        
        $data['data'] = [
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amount,
            'type' => $this->transaction->type,
            'status' => $this->transaction->status,
            'user_name' => $this->transaction->user->name,
            'event_type' => $this->eventType,
            'category' => $this->transaction->category?->name,
            'requires_verification' => $this->transaction->requires_verification,
        ];
        
        return $data;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Create notification for new transaction.
     */
    public static function created(Transaction $transaction): self
    {
        return new self($transaction, 'created');
    }

    /**
     * Create notification for transaction requiring verification.
     */
    public static function verificationRequired(Transaction $transaction): self
    {
        return new self($transaction, 'verification_required');
    }

    /**
     * Create notification for processed transaction.
     */
    public static function processed(Transaction $transaction): self
    {
        return new self($transaction, 'processed');
    }
}