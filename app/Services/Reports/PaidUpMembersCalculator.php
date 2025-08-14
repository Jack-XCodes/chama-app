<?php

namespace App\Services\Reports;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class PaidUpMembersCalculator extends BaseReportCalculator
{
    private float $expectedMonthlyContribution;
    private int $gracePeriodDays;

    public function __construct(Carbon $startDate, Carbon $endDate, ?User $user = null, float $expectedMonthlyContribution = 5000, int $gracePeriodDays = 7)
    {
        parent::__construct($startDate, $endDate, $user);
        $this->expectedMonthlyContribution = $expectedMonthlyContribution;
        $this->gracePeriodDays = $gracePeriodDays;
    }

    public function getTitle(): string
    {
        return 'Paid-up Members Report as of ' . $this->endDate->format('F j, Y');
    }

    public function calculate(): array
    {
        $members = $this->getAllMembersWithContributions();
        
        $currentMembers = $members->filter(function ($member) {
            return $member['status'] === 'current';
        });

        $arrearsMembers = $members->filter(function ($member) {
            return $member['status'] === 'arrears';
        });

        $inactiveMembers = $members->filter(function ($member) {
            return $member['status'] === 'inactive';
        });

        return [
            'title' => $this->getTitle(),
            'summary' => $this->calculateSummary($members),
            'current_members' => $currentMembers->values(),
            'arrears_members' => $arrearsMembers->values(),
            'inactive_members' => $inactiveMembers->values(),
            'contribution_settings' => [
                'expected_monthly_contribution' => [
                    'amount' => $this->expectedMonthlyContribution,
                    'formatted' => $this->formatCurrency($this->expectedMonthlyContribution),
                ],
                'grace_period_days' => $this->gracePeriodDays,
            ],
            'aging_analysis' => $this->calculateAgingAnalysis($arrearsMembers),
            'metadata' => $this->getMetadata(),
        ];
    }

    private function getAllMembersWithContributions(): \Illuminate\Support\Collection
    {
        return User::whereHas('transactions', function ($query) {
            $query->where('type', Transaction::TYPE_PAYMENT);
        })
        ->with(['transactions' => function ($query) {
            $query->where('type', Transaction::TYPE_PAYMENT)
                ->whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
                ->orderBy('created_at');
        }])
        ->get()
        ->map(function ($user) {
            return $this->calculateMemberStatus($user);
        })
        ->sortBy([
            ['status', 'asc'], // current first, then arrears, then inactive
            ['balance.amount', 'desc'], // within each status, sort by balance
        ]);
    }

    private function calculateMemberStatus(User $user): array
    {
        $contributions = $user->transactions;
        
        if ($contributions->isEmpty()) {
            return $this->createMemberRecord($user, 'inactive', 0, 0, 0, null, null);
        }

        $firstContribution = $contributions->first();
        $lastContribution = $contributions->last();
        
        // Calculate expected contributions from first contribution to end date
        $monthsSinceFirst = $firstContribution->created_at->diffInMonths($this->endDate) + 1;
        $expectedTotal = $monthsSinceFirst * $this->expectedMonthlyContribution;
        $actualTotal = $contributions->sum('amount');
        $balance = $actualTotal - $expectedTotal;

        // Determine current status
        $status = $this->determineMemberStatus($user, $lastContribution, $balance);

        return $this->createMemberRecord(
            $user, 
            $status, 
            $actualTotal, 
            $expectedTotal, 
            $balance,
            $firstContribution->created_at,
            $lastContribution->created_at
        );
    }

    private function determineMemberStatus(User $user, Transaction $lastContribution, float $balance): string
    {
        $daysSinceLastContribution = $lastContribution->created_at->diffInDays($this->endDate);
        $expectedDaysBetweenContributions = 30; // Monthly contributions
        $gracePeriodDays = $this->gracePeriodDays;

        // Check if member is inactive (no contribution in last 3 months)
        if ($daysSinceLastContribution > 90) {
            return 'inactive';
        }

        // Check if member is in arrears
        if ($balance < 0 && abs($balance) >= $this->expectedMonthlyContribution * 0.5) {
            return 'arrears';
        }

        // Check if member is overdue for current month
        $daysSinceMonthStart = $this->endDate->day;
        $isOverdue = $daysSinceLastContribution > ($expectedDaysBetweenContributions + $gracePeriodDays);
        
        if ($isOverdue && $daysSinceMonthStart > ($expectedDaysBetweenContributions + $gracePeriodDays)) {
            return 'arrears';
        }

        return 'current';
    }

    private function createMemberRecord(User $user, string $status, float $actualTotal, float $expectedTotal, float $balance, ?Carbon $firstContribution, ?Carbon $lastContribution): array
    {
        $daysSinceLastContribution = $lastContribution ? $lastContribution->diffInDays($this->endDate) : null;
        
        return [
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'status' => $status,
            'contributions' => [
                'actual_total' => [
                    'amount' => $actualTotal,
                    'formatted' => $this->formatCurrency($actualTotal),
                ],
                'expected_total' => [
                    'amount' => $expectedTotal,
                    'formatted' => $this->formatCurrency($expectedTotal),
                ],
                'count' => $user->transactions->count(),
            ],
            'balance' => [
                'amount' => $balance,
                'formatted' => $this->formatCurrency($balance),
                'status' => $balance >= 0 ? 'credit' : 'debit',
            ],
            'dates' => [
                'first_contribution' => $firstContribution?->format('Y-m-d'),
                'last_contribution' => $lastContribution?->format('Y-m-d'),
                'days_since_last' => $daysSinceLastContribution,
            ],
            'compliance' => [
                'percentage' => $expectedTotal > 0 ? round(($actualTotal / $expectedTotal) * 100, 1) : 0,
                'months_behind' => $balance < 0 ? ceil(abs($balance) / $this->expectedMonthlyContribution) : 0,
                'risk_level' => $this->calculateRiskLevel($status, $balance, $daysSinceLastContribution),
            ],
        ];
    }

    private function calculateRiskLevel(string $status, float $balance, ?int $daysSinceLastContribution): string
    {
        if ($status === 'inactive') {
            return 'high';
        }

        if ($status === 'arrears') {
            $monthsBehind = ceil(abs($balance) / $this->expectedMonthlyContribution);
            if ($monthsBehind >= 3) {
                return 'high';
            } elseif ($monthsBehind >= 2) {
                return 'medium';
            } else {
                return 'low';
            }
        }

        if ($daysSinceLastContribution && $daysSinceLastContribution > 45) {
            return 'medium';
        }

        return 'low';
    }

    private function calculateSummary(\Illuminate\Support\Collection $members): array
    {
        $totalMembers = $members->count();
        $currentMembers = $members->where('status', 'current')->count();
        $arrearsMembers = $members->where('status', 'arrears')->count();
        $inactiveMembers = $members->where('status', 'inactive')->count();

        $totalExpected = $members->sum('contributions.expected_total.amount');
        $totalActual = $members->sum('contributions.actual_total.amount');
        $totalBalance = $members->sum('balance.amount');

        $complianceRate = $totalExpected > 0 ? round(($totalActual / $totalExpected) * 100, 1) : 0;

        // Risk analysis
        $highRiskMembers = $members->where('compliance.risk_level', 'high')->count();
        $mediumRiskMembers = $members->where('compliance.risk_level', 'medium')->count();
        $lowRiskMembers = $members->where('compliance.risk_level', 'low')->count();

        return [
            'member_counts' => [
                'total' => $totalMembers,
                'current' => $currentMembers,
                'arrears' => $arrearsMembers,
                'inactive' => $inactiveMembers,
                'current_percentage' => $totalMembers > 0 ? round(($currentMembers / $totalMembers) * 100, 1) : 0,
                'arrears_percentage' => $totalMembers > 0 ? round(($arrearsMembers / $totalMembers) * 100, 1) : 0,
            ],
            'financial_summary' => [
                'total_expected' => [
                    'amount' => $totalExpected,
                    'formatted' => $this->formatCurrency($totalExpected),
                ],
                'total_actual' => [
                    'amount' => $totalActual,
                    'formatted' => $this->formatCurrency($totalActual),
                ],
                'total_balance' => [
                    'amount' => $totalBalance,
                    'formatted' => $this->formatCurrency($totalBalance),
                ],
                'compliance_rate' => $complianceRate,
                'collection_efficiency' => $complianceRate,
            ],
            'risk_analysis' => [
                'high_risk' => $highRiskMembers,
                'medium_risk' => $mediumRiskMembers,
                'low_risk' => $lowRiskMembers,
                'high_risk_percentage' => $totalMembers > 0 ? round(($highRiskMembers / $totalMembers) * 100, 1) : 0,
            ],
        ];
    }

    private function calculateAgingAnalysis(\Illuminate\Support\Collection $arrearsMembers): array
    {
        $aging = [
            '1_month' => ['count' => 0, 'amount' => 0],
            '2_months' => ['count' => 0, 'amount' => 0],
            '3_months' => ['count' => 0, 'amount' => 0],
            'over_3_months' => ['count' => 0, 'amount' => 0],
        ];

        foreach ($arrearsMembers as $member) {
            $monthsBehind = $member['compliance']['months_behind'];
            $balanceAmount = abs($member['balance']['amount']);

            if ($monthsBehind == 1) {
                $aging['1_month']['count']++;
                $aging['1_month']['amount'] += $balanceAmount;
            } elseif ($monthsBehind == 2) {
                $aging['2_months']['count']++;
                $aging['2_months']['amount'] += $balanceAmount;
            } elseif ($monthsBehind == 3) {
                $aging['3_months']['count']++;
                $aging['3_months']['amount'] += $balanceAmount;
            } else {
                $aging['over_3_months']['count']++;
                $aging['over_3_months']['amount'] += $balanceAmount;
            }
        }

        // Format amounts
        foreach ($aging as $key => $data) {
            $aging[$key]['formatted_amount'] = $this->formatCurrency($data['amount']);
        }

        return $aging;
    }

    /**
     * Get members who need follow-up.
     */
    public function getMembersNeedingFollowUp(): array
    {
        $members = $this->getAllMembersWithContributions();
        
        return [
            'urgent_follow_up' => $members->where('compliance.risk_level', 'high')->values(),
            'moderate_follow_up' => $members->where('compliance.risk_level', 'medium')->values(),
            'gentle_reminder' => $members->where('status', 'current')
                ->filter(function ($member) {
                    return $member['dates']['days_since_last'] > 30;
                })->values(),
        ];
    }
}