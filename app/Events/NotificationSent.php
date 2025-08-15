<?php

namespace App\Events;

use App\Models\DatabaseNotification;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public DatabaseNotification $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, DatabaseNotification $notification)
    {
        $this->user = $user;
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'priority' => $this->notification->data['priority'] ?? 'normal',
            'priority_color' => $this->notification->priority_color,
            'action_url' => $this->notification->action_url,
            'action_text' => $this->notification->action_text,
            'created_at' => $this->notification->created_at->toISOString(),
            'time_ago' => $this->notification->time_ago,
            'icon' => $this->notification->icon,
            'data' => $this->notification->data,
        ];
    }


}