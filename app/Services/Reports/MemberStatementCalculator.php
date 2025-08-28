<?php

namespace App\Services\Reports;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class MemberStatementCalculator extends BaseReportCalculator
{
    public function getTitle(): string
    {
        $memberName = $this->user ? $this->user->name : 'All Members';
        return 'Member Statement for ' . $memberName . ' (' . 
               $this->startDate->format('F j, Y') . ' to ' . 
               $this->endDate->format('F j, Y') . ')';
    }

    public function calculate(): array
    {
        if ($this->user) {
            return $this->calculateIndividualStatement();
        } else {
            return $this->calculateAllMembersStatement();
        }
    }

    private function calculateIndividualStatement(): array
    {
        $transactions = $this->getUserTransactions();
        $openingBalance = $this->getMemberOpeningBalance($this->user);

        // Group transactions by type
        $contributions = $transactions->where('type', Transaction::TYPE_PAYMENT);
        $expenses = $transactions->where('amount', '<', 0);
        $income = $transactions->where('amount', '>', 0)->where('type', '!=', Transaction::TYPE_PAYMENT);

        $runningBalance = $openingBalance;
        $transactionHistory = $transactions->sortBy('created_at')->map(function ($transaction) use (&$runningBalance) {
            $runningBalance += $transaction->amount;
            return [
                'id' => $transaction->id,
                'date' => $transaction->created_at->format('Y-m-d'),
                'description' => $transaction->description,
                'type' => $transaction->type_display,
                'category' => $transaction->category?->name ?? 'Uncategorized',
                'debit' => $transaction->amount < 0 ? abs($transaction->amount) : 0,
                'credit' => $transaction->amount > 0 ? $transaction->amount : 0,
                'balance' => $runningBalance,
                'formatted_debit' => $transaction->amount < 0 ? $this->formatCurrency(abs($transaction->amount)) : '',
                'formatted_credit' => $transaction->amount > 0 ? $this->formatCurrency($transaction->amount) : '',
                'formatted_balance' => $this->formatCurrency($runningBalance),
                'reference_number' => $transaction->reference_number,
                'status' => $transaction->status,
            ];
        })->values();

        $closingBalance = $openingBalance + $transactions->sum('amount');

        return [
            'title' => $this->getTitle(),
            'member' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'summary' => [
                'opening_balance' => [
                    'amount' => $openingBalance,
                    'formatted' => $this->formatCurrency($openingBalance),
                ],
                'total_contributions' => [
                    'amount' => $contributions->sum('amount'),
                    'formatted' => $this->formatCurrency($contributions->sum('amount')),
                    'count' => $contributions->count(),
                ],
                'total_debits' => [
                    'amount' => abs($expenses->sum('amount')),
                    'formatted' => $this->formatCurrency(abs($expenses->sum('amount'))),
                    'count' => $expenses->count(),
                ],
                'total_credits' => [
                    'amount' => $income->sum('amount'),
                    'formatted' => $this->formatCurrency($income->sum('amount')),
                    'count' => $income->count(),
                ],
                'net_movement' => [
                    'amount' => $transactions->sum('amount'),
                    'formatted' => $this->formatCurrency($transactions->sum('amount')),
                ],
                'closing_balance' => [
                    'amount' => $closingBalance,
                    'formatted' => $this->formatCurrency($closingBalance),
                ],
                'transaction_count' => $transactions->count(),
            ],
            'transaction_history' => $transactionHistory,
            'contribution_analysis' => $this->getContributionAnalysis($this->user),
            'metadata' => $this->getMetadata(),
        ];
    }

    private function calculateAllMembersStatement(): array
    {
        $members = User::whereHas('transactions', function ($query) {
            $query->whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
                ->whereBetween('created_at', [$this->startDate, $this->endDate]);
        })->with(['transactions' => function ($query) {
            $query->whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
                ->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->orderBy('created_at');
        }])->get();

        $memberStatements = $members->map(function ($member) {
            $transactions = $member->transactions;
            $openingBalance = $this->getMemberOpeningBalance($member);
            $closingBalance = $openingBalance + $transactions->sum('amount');

            $contributions = $transactions->where('type', Transaction::TYPE_PAYMENT);
            $expenses = $transactions->where('amount', '<', 0);
            $income = $transactions->where('amount', '>', 0)->where('type', '!=', Transaction::TYPE_PAYMENT);

            return [
                'member' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                ],
                'summary' => [
                    'opening_balance' => [
                        'amount' => $openingBalance,
                        'formatted' => $this->formatCurrency($openingBalance),
                    ],
                    'total_contributions' => [
                        'amount' => $contributions->sum('amount'),
                        'formatted' => $this->formatCurrency($contributions->sum('amount')),
                        'count' => $contributions->count(),
                    ],
                    'total_debits' => [
                        'amount' => abs($expenses->sum('amount')),
                        'formatted' => $this->formatCurrency(abs($expenses->sum('amount'))),
                        'count' => $expenses->count(),
                    ],
                    'total_credits' => [
                        'amount' => $income->sum('amount'),
                        'formatted' => $this->formatCurrency($income->sum('amount')),
                        'count' => $income->count(),
                    ],
                    'closing_balance' => [
                        'amount' => $closingBalance,
                        'formatted' => $this->formatCurrency($closingBalance),
                    ],
                    'transaction_count' => $transactions->count(),
                    'last_contribution' => $contributions->max('created_at'),
                ],
                'contribution_analysis' => $this->getContributionAnalysis($member),
            ];
        })->sortByDesc('summary.total_contributions.amount')->values();

        // Calculate totals
        $totalOpeningBalance = $memberStatements->sum('summary.opening_balance.amount');
        $totalContributions = $memberStatements->sum('summary.total_contributions.amount');
        $totalDebits = $memberStatements->sum('summary.total_debits.amount');
        $totalCredits = $memberStatements->sum('summary.total_credits.amount');
        $totalClosingBalance = $memberStatements->sum('summary.closing_balance.amount');

        return [
            'title' => $this->getTitle(),
            'member_statements' => $memberStatements,
            'totals' => [
                'opening_balance' => [
                    'amount' => $totalOpeningBalance,
                    'formatted' => $this->formatCurrency($totalOpeningBalance),
                ],
                'total_contributions' => [
                    'amount' => $totalContributions,
                    'formatted' => $this->formatCurrency($totalContributions),
                ],
                'total_debits' => [
                    'amount' => $totalDebits,
                    'formatted' => $this->formatCurrency($totalDebits),
                ],
                'total_credits' => [
                    'amount' => $totalCredits,
                    'formatted' => $this->formatCurrency($totalCredits),
                ],
                'closing_balance' => [
                    'amount' => $totalClosingBalance,
                    'formatted' => $this->formatCurrency($totalClosingBalance),
                ],
                'member_count' => $memberStatements->count(),
            ],
            'metadata' => $this->getMetadata(),
        ];
    }

    private function getMemberOpeningBalance(User $member): float
    {
        return Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->where('user_id', $member->id)
            ->where('created_at', '<', $this->startDate)
            ->sum('amount');
    }

    private function getContributionAnalysis(User $member): array
    {
        // Get all contributions for the member up to the end date
        $allContributions = Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->where('user_id', $member->id)
            ->where('type', Transaction::TYPE_PAYMENT)
            ->where('created_at', '<=', $this->endDate)
            ->orderBy('created_at')
            ->get();

        // Expected monthly contribution (you might want to make this configurable)
        $expectedMonthlyContribution = 5000; // KES 5,000 per month

        // Calculate months between first contribution and end date
        $firstContribution = $allContributions->first();
        $monthsSinceFirst = $firstContribution 
            ? $firstContribution->created_at->diffInMonths($this->endDate) + 1 
            : 0;

        $totalExpected = $monthsSinceFirst * $expectedMonthlyContribution;
        $totalActual = $allContributions->sum('amount');
        $balance = $totalActual - $totalExpected;

        // Get contribution frequency
        $contributionsByMonth = $allContributions->groupBy(function ($contribution) {
            return $contribution->created_at->format('Y-m');
        });

        $monthsWithContributions = $contributionsByMonth->count();
        $averageMonthlyContribution = $monthsSinceFirst > 0 ? $totalActual / $monthsSinceFirst : 0;

        return [
            'expected_total' => [
                'amount' => $totalExpected,
                'formatted' => $this->formatCurrency($totalExpected),
            ],
            'actual_total' => [
                'amount' => $totalActual,
                'formatted' => $this->formatCurrency($totalActual),
            ],
            'balance' => [
                'amount' => $balance,
                'formatted' => $this->formatCurrency($balance),
                'status' => $balance >= 0 ? 'current' : 'arrears',
            ],
            'statistics' => [
                'months_since_first' => $monthsSinceFirst,
                'months_with_contributions' => $monthsWithContributions,
                'contribution_frequency' => $monthsSinceFirst > 0 ? round(($monthsWithContributions / $monthsSinceFirst) * 100, 1) : 0,
                'average_monthly' => [
                    'amount' => $averageMonthlyContribution,
                    'formatted' => $this->formatCurrency($averageMonthlyContribution),
                ],
                'expected_monthly' => [
                    'amount' => $expectedMonthlyContribution,
                    'formatted' => $this->formatCurrency($expectedMonthlyContribution),
                ],
            ],
            'first_contribution' => $firstContribution?->created_at?->format('Y-m-d'),
            'last_contribution' => $allContributions->last()?->created_at?->format('Y-m-d'),
            'total_contributions_count' => $allContributions->count(),
        ];
    }
}