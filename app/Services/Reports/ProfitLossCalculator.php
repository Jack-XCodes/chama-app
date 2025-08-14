<?php

namespace App\Services\Reports;

use App\Models\Transaction;
use App\Models\TransactionCategory;

class ProfitLossCalculator extends BaseReportCalculator
{
    public function getTitle(): string
    {
        return 'Profit & Loss Statement for ' . 
               $this->startDate->format('F j, Y') . ' to ' . 
               $this->endDate->format('F j, Y');
    }

    public function calculate(): array
    {
        return [
            'title' => $this->getTitle(),
            'income' => $this->calculateIncome(),
            'expenses' => $this->calculateExpenses(),
            'net_result' => $this->calculateNetResult(),
            'summary' => $this->getPeriodSummary(),
            'metadata' => $this->getMetadata(),
        ];
    }

    private function calculateIncome(): array
    {
        $incomeTransactions = $this->getApprovedTransactions()
            ->whereIn('type', [
                Transaction::TYPE_INCOME,
                Transaction::TYPE_PAYMENT, // Member contributions
            ])
            ->where('amount', '>', 0);

        // Group by category
        $incomeByCategory = $incomeTransactions
            ->groupBy('category.name')
            ->map(function ($transactions, $categoryName) {
                return [
                    'category' => $categoryName ?: 'Uncategorized',
                    'amount' => $transactions->sum('amount'),
                    'formatted' => $this->formatCurrency($transactions->sum('amount')),
                    'count' => $transactions->count(),
                    'transactions' => $transactions->map(function ($transaction) {
                        return [
                            'id' => $transaction->id,
                            'description' => $transaction->description,
                            'amount' => $transaction->amount,
                            'formatted' => $this->formatCurrency($transaction->amount),
                            'date' => $transaction->created_at->format('Y-m-d'),
                            'user' => $transaction->user->name,
                        ];
                    }),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        $totalIncome = $incomeTransactions->sum('amount');

        return [
            'categories' => $incomeByCategory,
            'total_income' => [
                'amount' => $totalIncome,
                'formatted' => $this->formatCurrency($totalIncome),
            ],
            'breakdown' => [
                'member_contributions' => [
                    'amount' => $incomeTransactions->where('type', Transaction::TYPE_PAYMENT)->sum('amount'),
                    'formatted' => $this->formatCurrency($incomeTransactions->where('type', Transaction::TYPE_PAYMENT)->sum('amount')),
                ],
                'other_income' => [
                    'amount' => $incomeTransactions->where('type', Transaction::TYPE_INCOME)->sum('amount'),
                    'formatted' => $this->formatCurrency($incomeTransactions->where('type', Transaction::TYPE_INCOME)->sum('amount')),
                ],
            ],
        ];
    }

    private function calculateExpenses(): array
    {
        $expenseTransactions = $this->getApprovedTransactions()
            ->whereIn('type', [
                Transaction::TYPE_EXPENSE,
                Transaction::TYPE_BANK_CHARGE,
            ])
            ->where('amount', '<', 0);

        // Group by category
        $expensesByCategory = $expenseTransactions
            ->groupBy('category.name')
            ->map(function ($transactions, $categoryName) {
                $amount = abs($transactions->sum('amount')); // Convert to positive for display
                return [
                    'category' => $categoryName ?: 'Uncategorized',
                    'amount' => $amount,
                    'formatted' => $this->formatCurrency($amount),
                    'count' => $transactions->count(),
                    'transactions' => $transactions->map(function ($transaction) {
                        return [
                            'id' => $transaction->id,
                            'description' => $transaction->description,
                            'amount' => abs($transaction->amount),
                            'formatted' => $this->formatCurrency(abs($transaction->amount)),
                            'date' => $transaction->created_at->format('Y-m-d'),
                            'user' => $transaction->user->name,
                        ];
                    }),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        $totalExpenses = abs($expenseTransactions->sum('amount'));

        return [
            'categories' => $expensesByCategory,
            'total_expenses' => [
                'amount' => $totalExpenses,
                'formatted' => $this->formatCurrency($totalExpenses),
            ],
            'breakdown' => [
                'operating_expenses' => [
                    'amount' => abs($expenseTransactions->where('type', Transaction::TYPE_EXPENSE)->sum('amount')),
                    'formatted' => $this->formatCurrency(abs($expenseTransactions->where('type', Transaction::TYPE_EXPENSE)->sum('amount'))),
                ],
                'bank_charges' => [
                    'amount' => abs($expenseTransactions->where('type', Transaction::TYPE_BANK_CHARGE)->sum('amount')),
                    'formatted' => $this->formatCurrency(abs($expenseTransactions->where('type', Transaction::TYPE_BANK_CHARGE)->sum('amount'))),
                ],
            ],
        ];
    }

    private function calculateNetResult(): array
    {
        $income = $this->calculateIncome();
        $expenses = $this->calculateExpenses();

        $netAmount = $income['total_income']['amount'] - $expenses['total_expenses']['amount'];
        $isProfit = $netAmount > 0;

        return [
            'net_amount' => [
                'amount' => $netAmount,
                'formatted' => $this->formatCurrency($netAmount),
            ],
            'is_profit' => $isProfit,
            'result_text' => $isProfit ? 'Net Profit' : 'Net Loss',
            'margin_percentage' => $income['total_income']['amount'] > 0 
                ? round(($netAmount / $income['total_income']['amount']) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get comparative analysis with previous period.
     */
    public function getComparativeAnalysis(): array
    {
        $periodDays = $this->startDate->diffInDays($this->endDate) + 1;
        $previousStartDate = $this->startDate->copy()->subDays($periodDays);
        $previousEndDate = $this->startDate->copy()->subDay();

        $previousCalculator = new self($previousStartDate, $previousEndDate);
        $previousData = $previousCalculator->calculate();

        $currentIncome = $this->calculateIncome()['total_income']['amount'];
        $previousIncome = $previousData['income']['total_income']['amount'];
        
        $currentExpenses = $this->calculateExpenses()['total_expenses']['amount'];
        $previousExpenses = $previousData['expenses']['total_expenses']['amount'];

        $currentNet = $currentIncome - $currentExpenses;
        $previousNet = $previousIncome - $previousExpenses;

        return [
            'income_change' => [
                'amount' => $currentIncome - $previousIncome,
                'percentage' => $previousIncome > 0 ? round((($currentIncome - $previousIncome) / $previousIncome) * 100, 2) : 0,
                'formatted' => $this->formatCurrency($currentIncome - $previousIncome),
            ],
            'expense_change' => [
                'amount' => $currentExpenses - $previousExpenses,
                'percentage' => $previousExpenses > 0 ? round((($currentExpenses - $previousExpenses) / $previousExpenses) * 100, 2) : 0,
                'formatted' => $this->formatCurrency($currentExpenses - $previousExpenses),
            ],
            'net_change' => [
                'amount' => $currentNet - $previousNet,
                'percentage' => $previousNet != 0 ? round((($currentNet - $previousNet) / abs($previousNet)) * 100, 2) : 0,
                'formatted' => $this->formatCurrency($currentNet - $previousNet),
            ],
            'previous_period' => [
                'start_date' => $previousStartDate->format('Y-m-d'),
                'end_date' => $previousEndDate->format('Y-m-d'),
                'income' => $previousIncome,
                'expenses' => $previousExpenses,
                'net' => $previousNet,
            ],
        ];
    }
}