<?php

namespace App\Livewire;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationCenter extends Component
{
    use WithPagination;

    public $showDropdown = false;
    public $filter = 'all'; // all, unread, read
    public $selectedType = '';
    public $selectedPriority = '';
    public $showFilters = false;
    public $markingAllAsRead = false;

    public function mount()
    {
        // Listen for real-time notifications
        $this->dispatch('notification-center-mounted');
    }

    public function toggleDropdown()
    {
        $this->showDropdown = !$this->showDropdown;
        
        if ($this->showDropdown) {
            $this->dispatch('notification-dropdown-opened');
        }
    }

    public function closeDropdown()
    {
        $this->showDropdown = false;
    }

    public function markAsRead(int $notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        
        if ($notification) {
            $notification->markAsRead();
            $this->dispatch('notification-read', $notificationId);
        }
    }

    public function markAsUnread(int $notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        
        if ($notification) {
            $notification->markAsUnread();
            $this->dispatch('notification-unread', $notificationId);
        }
    }

    public function markAllAsRead()
    {
        $this->markingAllAsRead = true;
        
        Auth::user()->markAllNotificationsAsRead();
        
        $this->markingAllAsRead = false;
        $this->dispatch('all-notifications-read');
        
        session()->flash('message', 'All notifications marked as read.');
    }

    public function deleteNotification(int $notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        
        if ($notification) {
            $notification->delete();
            $this->dispatch('notification-deleted', $notificationId);
        }
    }

    public function clearAllRead()
    {
        Auth::user()->notifications()->read()->delete();
        $this->dispatch('read-notifications-cleared');
        
        session()->flash('message', 'All read notifications cleared.');
    }

    public function handleNotificationClick(int $notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        
        if ($notification) {
            // Mark as read if unread
            if ($notification->isUnread()) {
                $notification->markAsRead();
                $this->dispatch('notification-read', $notificationId);
            }
            
            // Redirect to action URL if available
            if ($notification->action_url) {
                return redirect($notification->action_url);
            }
        }
    }

    public function getUnreadCount()
    {
        return Auth::user()->unread_notifications_count;
    }

    public function getFilteredNotifications()
    {
        $query = Auth::user()->notifications();

        // Apply read/unread filter
        switch ($this->filter) {
            case 'unread':
                $query->unread();
                break;
            case 'read':
                $query->read();
                break;
        }

        // Apply type filter
        if ($this->selectedType) {
            $query->where('type', $this->selectedType);
        }

        // Apply priority filter
        if ($this->selectedPriority) {
            $query->where('priority', $this->selectedPriority);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function getRecentNotifications()
    {
        return Auth::user()->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function updatedFilter()
    {
        $this->resetPage();
    }

    public function updatedSelectedType()
    {
        $this->resetPage();
    }

    public function updatedSelectedPriority()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filter = 'all';
        $this->selectedType = '';
        $this->selectedPriority = '';
        $this->resetPage();
    }

    public function getNotificationIcon(Notification $notification): string
    {
        return match($notification->type) {
            Notification::TYPE_PAYMENT_STATUS => '💳',
            Notification::TYPE_DOCUMENT_UPLOAD => '📄',
            Notification::TYPE_FINANCIAL_REPORT => '📊',
            Notification::TYPE_ANNOUNCEMENT => '📢',
            Notification::TYPE_ACCOUNT_STATUS => '👤',
            Notification::TYPE_TRANSACTION_CREATED => '💰',
            Notification::TYPE_VERIFICATION_REQUIRED => '✅',
            Notification::TYPE_MEMBER_JOINED => '👥',
            Notification::TYPE_SYSTEM_MAINTENANCE => '🔧',
            default => '🔔',
        };
    }

    public function getNotificationSummary()
    {
        $user = Auth::user();
        
        return [
            'total' => $user->notifications()->count(),
            'unread' => $user->notifications()->unread()->count(),
            'urgent' => $user->notifications()->unread()->where('priority', Notification::PRIORITY_URGENT)->count(),
            'high' => $user->notifications()->unread()->where('priority', Notification::PRIORITY_HIGH)->count(),
            'today' => $user->notifications()->whereDate('created_at', today())->count(),
            'this_week' => $user->notifications()->where('created_at', '>=', now()->startOfWeek())->count(),
        ];
    }

    public function render()
    {
        $notifications = $this->getFilteredNotifications()->paginate(20);
        $recentNotifications = $this->getRecentNotifications();
        $summary = $this->getNotificationSummary();

        return view('livewire.notification-center', [
            'notifications' => $notifications,
            'recentNotifications' => $recentNotifications,
            'summary' => $summary,
            'unreadCount' => $this->getUnreadCount(),
            'notificationTypes' => Notification::getNotificationTypes(),
            'priorityLevels' => Notification::getPriorityLevels(),
        ]);
    }
}