<?php

namespace App\Livewire;

use App\Models\Document;
use App\Models\Transaction;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $totalBalance = 0;
    public $pendingPayments = 0;
    public $recentDocuments = 0;
    public $contributionStatus = '';
    public $recentActivity = [];
    public $upcomingPayments = [];
    public $monthlyContributions = [];
    public $userStats = [];
    public $notifications = [];

    public function mount()
    {
        $this->loadDashboardData();
    }

    protected function loadDashboardData()
    {
        // Calculate total balance
        $this->totalBalance = Transaction::where('status', 'approved')
            ->sum('amount');

        // Count pending payments
        $this->pendingPayments = Transaction::where('status', 'pending')
            ->count();

        // Count recent documents
        $this->recentDocuments = Document::where('created_at', '>=', now()->subDays(30))
            ->count();

        // Get user's contribution status and stats
        $user = Auth::user();
        $latestPayment = Transaction::where('user_id', $user->id)
            ->where('type', 'payment')
            ->latest()
            ->first();
        
        $this->contributionStatus = $latestPayment && $latestPayment->created_at->isAfter(now()->subMonth())
            ? 'Up to date'
            : 'Payment required';

        // Calculate user stats
        $this->userStats = [
            'total_contributions' => Transaction::where('user_id', $user->id)
                ->where('type', 'payment')
                ->where('status', 'approved')
                ->sum('amount'),
            'documents_uploaded' => Document::where('user_id', $user->id)->count(),
            'last_payment_date' => $latestPayment ? $latestPayment->created_at->format('M d, Y') : 'No payments yet',
            'membership_since' => $user->created_at->format('M Y')
        ];

        // Get monthly contributions for the chart
        $this->monthlyContributions = Transaction::where('type', 'payment')
            ->where('status', 'approved')
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => date('M Y', strtotime($item->month)),
                    'total' => $item->total
                ];
            });

        // Get recent activity
        $this->recentActivity = $this->getRecentActivity();

        // Get upcoming payments
        $this->upcomingPayments = $this->getUpcomingPayments();

        // Get unread notifications
        $this->notifications = $user->notifications()
            ->unread()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'time' => $notification->created_at->diffForHumans(),
                    'type' => $notification->type,
                    'read' => !is_null($notification->read_at)
                ];
            });
    }

    protected function getRecentActivity()
    {
        $activity = collect();

        // Get recent transactions
        $transactions = Transaction::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($transaction) {
                return (object) [
                    'type' => 'transaction',
                    'description' => "{$transaction->user->name} {$transaction->type} of KES " . number_format($transaction->amount, 2),
                    'created_at' => $transaction->created_at,
                    'type_color' => 'bg-green-100',
                    'icon' => '<svg class="h-5 w-5 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                    </svg>'
                ];
            });
        $activity = $activity->concat($transactions);

        // Get recent documents
        $documents = Document::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($document) {
                return (object) [
                    'type' => 'document',
                    'description' => "{$document->user->name} uploaded {$document->title}",
                    'created_at' => $document->created_at,
                    'type_color' => 'bg-blue-100',
                    'icon' => '<svg class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                    </svg>'
                ];
            });
        $activity = $activity->concat($documents);

        // Sort by created_at and take 5
        return $activity->sortByDesc('created_at')->take(5);
    }

    protected function getUpcomingPayments()
    {
        return Transaction::where('user_id', Auth::id())
            ->where('type', 'payment')
            ->where('due_date', '>=', now())
            ->orderBy('due_date')
            ->take(5)
            ->get()
            ->map(function ($payment) {
                return (object) [
                    'description' => $payment->description,
                    'amount' => $payment->amount,
                    'due_date' => $payment->due_date,
                    'status' => $payment->status,
                    'status_color' => match($payment->status) {
                        'paid' => 'bg-green-100 text-green-800',
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'overdue' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-800',
                    }
                ];
            });
    }

    public function render()
    {
        return view('dashboard');
    }
}

