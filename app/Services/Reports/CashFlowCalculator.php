<?php

namespace App\Services\Reports;

use App\Models\Transaction;

class CashFlowCalculator extends BaseReportCalculator
{
    public function getTitle(): string
    {
        return 'Cash Flow Statement for ' . 
               $this->startDate->format('F j, Y') . ' to ' . 
               $this->endDate->format('F j, Y');
    }

    public function calculate(): array
    {
        return [
            'title' => $this->getTitle(),
            'operating_activities' => $this->calculateOperatingActivities(),
            'investing_activities' => $this->calculateInvestingActivities(),
            'financing_activities' => $this->calculateFinancingActivities(),
            'net_cash_flow' => $this->calculateNetCashFlow(),
            'cash_summary' => $this->calculateCashSummary(),
            'metadata' => $this->getMetadata(),
        ];
    }

    private function calculateOperatingActivities(): array
    {
        $transactions = $this->getApprovedTransactions();

        // Operating cash inflows
        $memberContributions = $transactions
            ->where('type', Transaction::TYPE_PAYMENT)
            ->where('amount', '>', 0)
            ->sum('amount');

        $otherIncome = $transactions
            ->where('type', Transaction::TYPE_INCOME)
            ->where('amount', '>', 0)
            ->sum('amount');

        $totalInflows = $memberContributions + $otherIncome;

        // Operating cash outflows
        $operatingExpenses = abs($transactions
            ->where('type', Transaction::TYPE_EXPENSE)
            ->where('amount', '<', 0)
            ->sum('amount'));

        $bankCharges = abs($transactions
            ->where('type', Transaction::TYPE_BANK_CHARGE)
            ->where('amount', '<', 0)
            ->sum('amount'));

        $totalOutflows = $operatingExpenses + $bankCharges;

        $netOperatingCashFlow = $totalInflows - $totalOutflows;

        return [
            'cash_inflows' => [
                'member_contributions' => [
                    'amount' => $memberContributions,
                    'formatted' => $this->formatCurrency($memberContributions),
                ],
                'other_income' => [
                    'amount' => $otherIncome,
                    'formatted' => $this->formatCurrency($otherIncome),
                ],
                'total_inflows' => [
                    'amount' => $totalInflows,
                    'formatted' => $this->formatCurrency($totalInflows),
                ],
            ],
            'cash_outflows' => [
                'operating_expenses' => [
                    'amount' => $operatingExpenses,
                    'formatted' => $this->formatCurrency($operatingExpenses),
                ],
                'bank_charges' => [
                    'amount' => $bankCharges,
                    'formatted' => $this->formatCurrency($bankCharges),
                ],
                'total_outflows' => [
                    'amount' => $totalOutflows,
                    'formatted' => $this->formatCurrency($totalOutflows),
                ],
            ],
            'net_operating_cash_flow' => [
                'amount' => $netOperatingCashFlow,
                'formatted' => $this->formatCurrency($netOperatingCashFlow),
            ],
        ];
    }

    private function calculateInvestingActivities(): array
    {
        $transactions = $this->getApprovedTransactions();

        // Investment purchases (cash outflows)
        $investmentPurchases = abs($transactions
            ->where('type', Transaction::TYPE_INVESTMENT)
            ->where('amount', '<', 0)
            ->sum('amount'));

        // Investment sales/returns (cash inflows)
        $investmentReturns = $transactions
            ->where('type', Transaction::TYPE_INCOME)
            ->where('amount', '>', 0)
            ->filter(function ($transaction) {
                return $transaction->category && 
                       str_contains(strtolower($transaction->category->name), 'investment');
            })
            ->sum('amount');

        $netInvestingCashFlow = $investmentReturns - $investmentPurchases;

        return [
            'cash_outflows' => [
                'investment_purchases' => [
                    'amount' => $investmentPurchases,
                    'formatted' => $this->formatCurrency($investmentPurchases),
                ],
            ],
            'cash_inflows' => [
                'investment_returns' => [
                    'amount' => $investmentReturns,
                    'formatted' => $this->formatCurrency($investmentReturns),
                ],
            ],
            'net_investing_cash_flow' => [
                'amount' => $netInvestingCashFlow,
                'formatted' => $this->formatCurrency($netInvestingCashFlow),
            ],
        ];
    }

    private function calculateFinancingActivities(): array
    {
        $transactions = $this->getApprovedTransactions();

        // Member withdrawals/refunds (cash outflows)
        $memberRefunds = abs($transactions
            ->where('type', Transaction::TYPE_REFUND)
            ->where('amount', '<', 0)
            ->sum('amount'));

        // Transfers between accounts
        $transfersOut = abs($transactions
            ->where('type', Transaction::TYPE_TRANSFER)
            ->where('amount', '<', 0)
            ->sum('amount'));

        $transfersIn = $transactions
            ->where('type', Transaction::TYPE_TRANSFER)
            ->where('amount', '>', 0)
            ->sum('amount');

        $netTransfers = $transfersIn - $transfersOut;
        $netFinancingCashFlow = $netTransfers - $memberRefunds;

        return [
            'cash_outflows' => [
                'member_refunds' => [
                    'amount' => $memberRefunds,
                    'formatted' => $this->formatCurrency($memberRefunds),
                ],
                'transfers_out' => [
                    'amount' => $transfersOut,
                    'formatted' => $this->formatCurrency($transfersOut),
                ],
            ],
            'cash_inflows' => [
                'transfers_in' => [
                    'amount' => $transfersIn,
                    'formatted' => $this->formatCurrency($transfersIn),
                ],
            ],
            'net_financing_cash_flow' => [
                'amount' => $netFinancingCashFlow,
                'formatted' => $this->formatCurrency($netFinancingCashFlow),
            ],
        ];
    }

    private function calculateNetCashFlow(): array
    {
        $operating = $this->calculateOperatingActivities()['net_operating_cash_flow']['amount'];
        $investing = $this->calculateInvestingActivities()['net_investing_cash_flow']['amount'];
        $financing = $this->calculateFinancingActivities()['net_financing_cash_flow']['amount'];

        $netCashFlow = $operating + $investing + $financing;

        return [
            'operating_cash_flow' => [
                'amount' => $operating,
                'formatted' => $this->formatCurrency($operating),
            ],
            'investing_cash_flow' => [
                'amount' => $investing,
                'formatted' => $this->formatCurrency($investing),
            ],
            'financing_cash_flow' => [
                'amount' => $financing,
                'formatted' => $this->formatCurrency($financing),
            ],
            'net_cash_flow' => [
                'amount' => $netCashFlow,
                'formatted' => $this->formatCurrency($netCashFlow),
            ],
        ];
    }

    private function calculateCashSummary(): array
    {
        $openingBalance = $this->getOpeningBalance();
        $netCashFlow = $this->calculateNetCashFlow()['net_cash_flow']['amount'];
        $closingBalance = $openingBalance + $netCashFlow;

        return [
            'opening_cash_balance' => [
                'amount' => $openingBalance,
                'formatted' => $this->formatCurrency($openingBalance),
            ],
            'net_cash_flow_for_period' => [
                'amount' => $netCashFlow,
                'formatted' => $this->formatCurrency($netCashFlow),
            ],
            'closing_cash_balance' => [
                'amount' => $closingBalance,
                'formatted' => $this->formatCurrency($closingBalance),
            ],
            'verification' => [
                'calculated_closing' => $closingBalance,
                'actual_closing' => $this->getClosingBalance(),
                'difference' => $closingBalance - $this->getClosingBalance(),
                'balanced' => abs($closingBalance - $this->getClosingBalance()) < 0.01,
            ],
        ];
    }

    /**
     * Get monthly cash flow trend for the period.
     */
    public function getMonthlyCashFlowTrend(): array
    {
        $monthlyData = [];
        $current = $this->startDate->copy()->startOfMonth();
        $end = $this->endDate->copy()->endOfMonth();

        while ($current <= $end) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            // Ensure we don't go beyond our report period
            if ($monthStart < $this->startDate) {
                $monthStart = $this->startDate->copy();
            }
            if ($monthEnd > $this->endDate) {
                $monthEnd = $this->endDate->copy();
            }

            $monthlyCalculator = new self($monthStart, $monthEnd);
            $monthlyData[] = [
                'month' => $current->format('Y-m'),
                'month_name' => $current->format('F Y'),
                'start_date' => $monthStart->format('Y-m-d'),
                'end_date' => $monthEnd->format('Y-m-d'),
                'cash_flow' => $monthlyCalculator->calculateNetCashFlow(),
            ];

            $current->addMonth();
        }

        return $monthlyData;
    }
}