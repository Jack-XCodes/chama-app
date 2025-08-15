<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Document;
use App\Models\Transaction;
use App\Models\Announcement;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\View;

class ActivityFeedWidget extends Widget
{
    protected static ?int $sort = 4;
    protected static bool $isLazy = true;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '15s';

    public function getActivityItems(): Collection
    {
        $activities = collect();

        // Get recent user registrations
        $newUsers = User::where('created_at', '>=', now()->subDays(7))
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'user_registered',
                    'icon' => 'heroicon-m-user-plus',
                    'color' => 'success',
                    'title' => 'New Member Joined',
                    'description' => "{$user->name} joined the group",
                    'timestamp' => $user->created_at,
                    'url' => route('admin.members'),
                ];
            });
        $activities = $activities->concat($newUsers);

        // Get recent document uploads
        $documents = Document::where('created_at', '>=', now()->subDays(7))
            ->with('uploader')
            ->get()
            ->map(function ($document) {
                return [
                    'type' => 'document_uploaded',
                    'icon' => 'heroicon-m-document',
                    'color' => 'info',
                    'title' => 'Document Uploaded',
                    'description' => "{$document->uploader->name} uploaded {$document->title}",
                    'timestamp' => $document->created_at,
                    'url' => route('documents.show', $document->id),
                ];
            });
        $activities = $activities->concat($documents);

        // Get recent payment submissions
        $payments = Transaction::where('created_at', '>=', now()->subDays(7))
            ->where('type', Transaction::TYPE_PAYMENT)
            ->with('user')
            ->get()
            ->map(function ($transaction) {
                return [
                    'type' => 'payment_submitted',
                    'icon' => 'heroicon-m-banknotes',
                    'color' => 'warning',
                    'title' => 'Payment Submitted',
                    'description' => "{$transaction->user->name} submitted payment of {$transaction->formatted_amount}",
                    'timestamp' => $transaction->created_at,
                    'url' => route('treasurer.payments'),
                ];
            });
        $activities = $activities->concat($payments);

        // Get recent announcements
        $announcements = Announcement::where('created_at', '>=', now()->subDays(7))
            ->with('creator')
            ->get()
            ->map(function ($announcement) {
                return [
                    'type' => 'announcement_created',
                    'icon' => 'heroicon-m-megaphone',
                    'color' => $announcement->is_urgent ? 'danger' : 'primary',
                    'title' => $announcement->is_urgent ? 'Urgent Announcement' : 'New Announcement',
                    'description' => "{$announcement->creator->name} posted: {$announcement->title}",
                    'timestamp' => $announcement->created_at,
                    'url' => route('announcements.show', $announcement->id),
                ];
            });
        $activities = $activities->concat($announcements);

        // Get status changes
        $statusChanges = Transaction::where('updated_at', '>=', now()->subDays(7))
            ->whereNotNull('processed_at')
            ->with(['user', 'processor'])
            ->get()
            ->map(function ($transaction) {
                $action = match($transaction->status) {
                    Transaction::STATUS_APPROVED => 'approved',
                    Transaction::STATUS_REJECTED => 'rejected',
                    Transaction::STATUS_REQUIRES_VERIFICATION => 'flagged for verification',
                    Transaction::STATUS_VERIFIED => 'verified',
                    default => 'updated',
                };

                return [
                    'type' => 'status_changed',
                    'icon' => 'heroicon-m-arrow-path',
                    'color' => match($transaction->status) {
                        Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED => 'success',
                        Transaction::STATUS_REJECTED => 'danger',
                        Transaction::STATUS_REQUIRES_VERIFICATION => 'warning',
                        default => 'gray',
                    },
                    'title' => 'Payment Status Updated',
                    'description' => "{$transaction->processor->name} {$action} payment of {$transaction->formatted_amount} from {$transaction->user->name}",
                    'timestamp' => $transaction->processed_at,
                    'url' => route('treasurer.payments'),
                ];
            });
        $activities = $activities->concat($statusChanges);

        // Sort by timestamp and take latest 50
        return $activities->sortByDesc('timestamp')->take(50);
    }

    protected function getViewData(): array
    {
        return [
            'activities' => $this->getActivityItems(),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->can('view-activity-feed');
    }

    public function render(): View
    {
        return view('filament.widgets.activity-feed-widget', $this->getViewData());
    }
}