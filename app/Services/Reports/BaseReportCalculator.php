<?php

namespace App\Services\Reports;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

abstract class BaseReportCalculator
{
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected ?User $user;

    public function __construct(Carbon $startDate, Carbon $endDate, ?User $user = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->user = $user;
    }

    /**
     * Calculate the report data.
     */
    abstract public function calculate(): array;

    /**
     * Get the report title.
     */
    abstract public function getTitle(): string;

    /**
     * Get approved transactions for the date range.
     */
    protected function getApprovedTransactions(): Collection
    {
        return Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->with(['user', 'category', 'tags'])
            ->get();
    }

    /**
     * Get transactions filtered by user if specified.
     */
    protected function getUserTransactions(): Collection
    {
        $query = Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->user) {
            $query->where('user_id', $this->user->id);
        }

        return $query->with(['user', 'category', 'tags'])->get();
    }

    /**
     * Get transactions by type.
     */
    protected function getTransactionsByType(string $type): Collection
    {
        return $this->getApprovedTransactions()->where('type', $type);
    }

    /**
     * Get income transactions (positive amounts).
     */
    protected function getIncomeTransactions(): Collection
    {
        return $this->getApprovedTransactions()->where('amount', '>', 0);
    }

    /**
     * Get expense transactions (negative amounts).
     */
    protected function getExpenseTransactions(): Collection
    {
        return $this->getApprovedTransactions()->where('amount', '<', 0);
    }

    /**
     * Format currency amount.
     */
    protected function formatCurrency(float $amount): string
    {
        return 'KES ' . number_format($amount, 2);
    }

    /**
     * Get total amount for a collection of transactions.
     */
    protected function getTotalAmount(Collection $transactions): float
    {
        return $transactions->sum('amount');
    }

    /**
     * Get transactions grouped by category.
     */
    protected function getTransactionsByCategory(): Collection
    {
        return $this->getApprovedTransactions()
            ->groupBy('category.name')
            ->map(function ($transactions) {
                return [
                    'transactions' => $transactions,
                    'total' => $transactions->sum('amount'),
                    'count' => $transactions->count(),
                ];
            });
    }

    /**
     * Get member contribution summary.
     */
    protected function getMemberContributions(): Collection
    {
        return User::whereHas('transactions', function ($query) {
            $query->whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
                ->whereBetween('created_at', [$this->startDate, $this->endDate]);
        })
        ->with(['transactions' => function ($query) {
            $query->whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
                ->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }])
        ->get()
        ->map(function ($user) {
            $transactions = $user->transactions;
            return [
                'user' => $user,
                'total_contributions' => $transactions->where('type', Transaction::TYPE_PAYMENT)->sum('amount'),
                'total_transactions' => $transactions->count(),
                'last_contribution' => $transactions->where('type', Transaction::TYPE_PAYMENT)->max('created_at'),
            ];
        });
    }

    /**
     * Get opening balance (transactions before start date).
     */
    protected function getOpeningBalance(): float
    {
        return Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->where('created_at', '<', $this->startDate)
            ->sum('amount');
    }

    /**
     * Get closing balance (all transactions up to end date).
     */
    protected function getClosingBalance(): float
    {
        return Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->where('created_at', '<=', $this->endDate)
            ->sum('amount');
    }

    /**
     * Get period summary.
     */
    protected function getPeriodSummary(): array
    {
        $transactions = $this->getApprovedTransactions();
        
        return [
            'total_transactions' => $transactions->count(),
            'total_income' => $this->getTotalAmount($this->getIncomeTransactions()),
            'total_expenses' => abs($this->getTotalAmount($this->getExpenseTransactions())),
            'net_change' => $this->getTotalAmount($transactions),
            'opening_balance' => $this->getOpeningBalance(),
            'closing_balance' => $this->getClosingBalance(),
        ];
    }

    /**
     * Get report metadata.
     */
    protected function getMetadata(): array
    {
        return [
            'generated_at' => now()->toISOString(),
            'period' => [
                'start_date' => $this->startDate->format('Y-m-d'),
                'end_date' => $this->endDate->format('Y-m-d'),
                'days' => $this->startDate->diffInDays($this->endDate) + 1,
            ],
            'user_filter' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null,
            'transaction_count' => $this->getApprovedTransactions()->count(),
        ];
    }
}