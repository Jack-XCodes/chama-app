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
        $pendingCount = Transaction::where('status', 'pending')->count();
        $pendingAmount = Transaction::where('status', 'pending')->sum('amount');

        $recentActivity = Transaction::with(['user', 'category'])
            ->latest()
            ->limit(5)
            ->get();

        $categoryTotals = TransactionCategory::query()
            ->withSum(['transactions' => function ($query) {
                $query->where('status', 'approved');
            }], 'amount')
            ->withCount(['transactions' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->orderByRaw('ABS(transactions_sum_amount) DESC')
            ->limit(5)
            ->get();

        $topTags = TransactionTag::query()
            ->withCount(['transactions' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->orderByDesc('transactions_count')
            ->limit(5)
            ->get();

        $totalBalance = Transaction::where('status', 'approved')->sum('amount');
        $monthlyIncome = Transaction::where('status', 'approved')
            ->where('amount', '>', 0)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');
        $monthlyExpense = abs(Transaction::where('status', 'approved')
            ->where('amount', '<', 0)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount'));

        return view('livewire.treasurer.dashboard', [
            'pendingCount' => $pendingCount,
            'pendingAmount' => $pendingAmount,
            'recentActivity' => $recentActivity,
            'categoryTotals' => $categoryTotals,
            'topTags' => $topTags,
            'totalBalance' => $totalBalance,
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpense' => $monthlyExpense,
        ]);
    }
}
