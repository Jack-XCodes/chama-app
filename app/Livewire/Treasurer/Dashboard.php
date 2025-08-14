<?php

namespace App\Livewire\Treasurer;

use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\TransactionTag;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public $timeframe = 'month';
    public $chartData = [];

    public function mount()
    {
        $this->loadChartData();
    }

    public function updatedTimeframe()
    {
        $this->loadChartData();
    }

    protected function loadChartData()
    {
        $startDate = match($this->timeframe) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        $this->chartData = Transaction::query()
            ->where('status', 'approved')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(CASE WHEN amount >= 0 THEN amount ELSE 0 END) as income, SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expense')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('M j'),
                    'income' => round($item->income, 2),
                    'expense' => round($item->expense, 2),
                ];
            });
    }

    public function render()
    {
        // Pending transactions requiring attention
        $pendingCount = Transaction::where('status', Transaction::STATUS_PENDING)->count();
        $pendingAmount = Transaction::where('status', Transaction::STATUS_PENDING)->sum('amount');
        
        // Verification queue
        $verificationCount = Transaction::where('status', Transaction::STATUS_REQUIRES_VERIFICATION)->count();
        $verificationAmount = Transaction::where('status', Transaction::STATUS_REQUIRES_VERIFICATION)->sum('amount');

        // Recent activity
        $recentActivity = Transaction::with(['user', 'category', 'processor', 'verifier'])
            ->latest()
            ->limit(8)
            ->get();

        // Category analysis
        $categoryTotals = TransactionCategory::query()
            ->withSum(['transactions' => function ($query) {
                $query->whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED]);
            }], 'amount')
            ->withCount(['transactions' => function ($query) {
                $query->whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED]);
            }])
            ->orderByRaw('ABS(transactions_sum_amount) DESC')
            ->limit(5)
            ->get();

        // Popular tags
        $topTags = TransactionTag::query()
            ->withCount(['transactions' => function ($query) {
                $query->whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED]);
            }])
            ->orderByDesc('transactions_count')
            ->limit(6)
            ->get();

        // Financial metrics
        $totalBalance = Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])->sum('amount');
        
        $monthlyIncome = Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->where('amount', '>', 0)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');
            
        $monthlyExpense = abs(Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->where('amount', '<', 0)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount'));

        // Transaction type breakdown for current month
        $transactionTypeBreakdown = Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('type')
            ->get();

        // Large transactions requiring attention
        $largeTransactions = Transaction::largeTransactions()
            ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_REQUIRES_VERIFICATION])
            ->with(['user', 'category'])
            ->orderByDesc('amount')
            ->limit(5)
            ->get();

        // Quick stats for alerts
        $alertStats = [
            'overdue_verifications' => Transaction::where('status', Transaction::STATUS_REQUIRES_VERIFICATION)
                ->where('created_at', '<', now()->subDays(3))
                ->count(),
            'high_value_pending' => Transaction::where('status', Transaction::STATUS_PENDING)
                ->where('amount', '>=', Transaction::VERIFICATION_THRESHOLD)
                ->count(),
            'rejected_this_week' => Transaction::where('status', Transaction::STATUS_REJECTED)
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
        ];

        return view('livewire.treasurer.dashboard', [
            'pendingCount' => $pendingCount,
            'pendingAmount' => $pendingAmount,
            'verificationCount' => $verificationCount,
            'verificationAmount' => $verificationAmount,
            'recentActivity' => $recentActivity,
            'categoryTotals' => $categoryTotals,
            'topTags' => $topTags,
            'totalBalance' => $totalBalance,
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpense' => $monthlyExpense,
            'transactionTypeBreakdown' => $transactionTypeBreakdown,
            'largeTransactions' => $largeTransactions,
            'alertStats' => $alertStats,
        ]);
    }
}
