<?php

namespace App\Services\Reports;

use App\Models\Transaction;
use Carbon\Carbon;

class BalanceSheetCalculator extends BaseReportCalculator
{
    public function getTitle(): string
    {
        return 'Balance Sheet as of ' . $this->endDate->format('F j, Y');
    }

    public function calculate(): array
    {
        return [
            'title' => $this->getTitle(),
            'assets' => $this->calculateAssets(),
            'liabilities' => $this->calculateLiabilities(),
            'equity' => $this->calculateEquity(),
            'totals' => $this->calculateTotals(),
            'metadata' => $this->getMetadata(),
        ];
    }

    private function calculateAssets(): array
    {
        $cashAndBank = $this->getClosingBalance();
        
        // Get investment assets
        $investments = Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->where('created_at', '<=', $this->endDate)
            ->where('type', Transaction::TYPE_INVESTMENT)
            ->sum('amount');

        // Calculate receivables (pending member payments)
        $receivables = Transaction::where('status', Transaction::STATUS_PENDING)
            ->where('type', Transaction::TYPE_PAYMENT)
            ->where('created_at', '<=', $this->endDate)
            ->sum('amount');

        $currentAssets = [
            'cash_and_bank' => [
                'name' => 'Cash and Bank',
                'amount' => $cashAndBank,
                'formatted' => $this->formatCurrency($cashAndBank),
            ],
            'member_receivables' => [
                'name' => 'Member Receivables',
                'amount' => $receivables,
                'formatted' => $this->formatCurrency($receivables),
            ],
        ];

        $nonCurrentAssets = [
            'investments' => [
                'name' => 'Investments',
                'amount' => abs($investments), // Investments are recorded as negative expenses
                'formatted' => $this->formatCurrency(abs($investments)),
            ],
        ];

        $totalCurrentAssets = collect($currentAssets)->sum('amount');
        $totalNonCurrentAssets = collect($nonCurrentAssets)->sum('amount');
        $totalAssets = $totalCurrentAssets + $totalNonCurrentAssets;

        return [
            'current_assets' => $currentAssets,
            'non_current_assets' => $nonCurrentAssets,
            'total_current_assets' => [
                'amount' => $totalCurrentAssets,
                'formatted' => $this->formatCurrency($totalCurrentAssets),
            ],
            'total_non_current_assets' => [
                'amount' => $totalNonCurrentAssets,
                'formatted' => $this->formatCurrency($totalNonCurrentAssets),
            ],
            'total_assets' => [
                'amount' => $totalAssets,
                'formatted' => $this->formatCurrency($totalAssets),
            ],
        ];
    }

    private function calculateLiabilities(): array
    {
        // For a chama, liabilities might include:
        // - Pending refunds to members
        // - Outstanding expenses
        
        $pendingRefunds = Transaction::where('status', Transaction::STATUS_PENDING)
            ->where('type', Transaction::TYPE_REFUND)
            ->where('created_at', '<=', $this->endDate)
            ->sum('amount');

        $outstandingExpenses = Transaction::where('status', Transaction::STATUS_PENDING)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->where('created_at', '<=', $this->endDate)
            ->sum('amount');

        $currentLiabilities = [
            'pending_refunds' => [
                'name' => 'Pending Member Refunds',
                'amount' => abs($pendingRefunds),
                'formatted' => $this->formatCurrency(abs($pendingRefunds)),
            ],
            'outstanding_expenses' => [
                'name' => 'Outstanding Expenses',
                'amount' => abs($outstandingExpenses),
                'formatted' => $this->formatCurrency(abs($outstandingExpenses)),
            ],
        ];

        $totalCurrentLiabilities = collect($currentLiabilities)->sum('amount');

        return [
            'current_liabilities' => $currentLiabilities,
            'non_current_liabilities' => [],
            'total_current_liabilities' => [
                'amount' => $totalCurrentLiabilities,
                'formatted' => $this->formatCurrency($totalCurrentLiabilities),
            ],
            'total_non_current_liabilities' => [
                'amount' => 0,
                'formatted' => $this->formatCurrency(0),
            ],
            'total_liabilities' => [
                'amount' => $totalCurrentLiabilities,
                'formatted' => $this->formatCurrency($totalCurrentLiabilities),
            ],
        ];
    }

    private function calculateEquity(): array
    {
        // Calculate total member contributions
        $memberContributions = Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->where('created_at', '<=', $this->endDate)
            ->where('type', Transaction::TYPE_PAYMENT)
            ->sum('amount');

        // Calculate retained earnings (accumulated profits/losses)
        $totalIncome = Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->where('created_at', '<=', $this->endDate)
            ->whereIn('type', [Transaction::TYPE_INCOME])
            ->sum('amount');

        $totalExpenses = Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->where('created_at', '<=', $this->endDate)
            ->whereIn('type', [Transaction::TYPE_EXPENSE, Transaction::TYPE_BANK_CHARGE])
            ->sum('amount');

        $retainedEarnings = $totalIncome + $totalExpenses; // Expenses are negative

        $equity = [
            'member_contributions' => [
                'name' => 'Member Contributions',
                'amount' => $memberContributions,
                'formatted' => $this->formatCurrency($memberContributions),
            ],
            'retained_earnings' => [
                'name' => 'Retained Earnings',
                'amount' => $retainedEarnings,
                'formatted' => $this->formatCurrency($retainedEarnings),
            ],
        ];

        $totalEquity = collect($equity)->sum('amount');

        return [
            'equity_items' => $equity,
            'total_equity' => [
                'amount' => $totalEquity,
                'formatted' => $this->formatCurrency($totalEquity),
            ],
        ];
    }

    private function calculateTotals(): array
    {
        $assets = $this->calculateAssets();
        $liabilities = $this->calculateLiabilities();
        $equity = $this->calculateEquity();

        $totalAssetsAmount = $assets['total_assets']['amount'];
        $totalLiabilitiesAndEquity = $liabilities['total_liabilities']['amount'] + $equity['total_equity']['amount'];

        return [
            'total_assets' => [
                'amount' => $totalAssetsAmount,
                'formatted' => $this->formatCurrency($totalAssetsAmount),
            ],
            'total_liabilities_and_equity' => [
                'amount' => $totalLiabilitiesAndEquity,
                'formatted' => $this->formatCurrency($totalLiabilitiesAndEquity),
            ],
            'balanced' => abs($totalAssetsAmount - $totalLiabilitiesAndEquity) < 0.01,
            'difference' => [
                'amount' => $totalAssetsAmount - $totalLiabilitiesAndEquity,
                'formatted' => $this->formatCurrency($totalAssetsAmount - $totalLiabilitiesAndEquity),
            ],
        ];
    }
}