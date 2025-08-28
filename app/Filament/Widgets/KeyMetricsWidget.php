<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Document;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class KeyMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $currentBalance = Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->sum('amount');

        $monthlyContributions = Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->where('type', Transaction::TYPE_PAYMENT)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $lastMonthContributions = Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->where('type', Transaction::TYPE_PAYMENT)
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('amount');

        $contributionChange = $lastMonthContributions ? 
            (($monthlyContributions - $lastMonthContributions) / $lastMonthContributions) * 100 : 
            0;

        $pendingReconciliations = Transaction::where('status', Transaction::STATUS_PENDING)
            ->orWhere('status', Transaction::STATUS_REQUIRES_VERIFICATION)
            ->count();

        $recentDocuments = Document::where('created_at', '>=', now()->subDays(30))->count();

        $activeMembers = User::whereHas('transactions', function ($query) {
            $query->where('created_at', '>=', now()->subDays(30));
        })->count();

        $totalMembers = User::count();
        $participationRate = $totalMembers ? ($activeMembers / $totalMembers) * 100 : 0;

        return [
            Stat::make('Current Balance', 'KES ' . number_format($currentBalance, 2))
                ->description($currentBalance >= 0 ? 'Positive Balance' : 'Negative Balance')
                ->descriptionIcon($currentBalance >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($currentBalance >= 0 ? 'success' : 'danger')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3, $currentBalance])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => '$dispatch("open-modal", { id: "view-balance-details" })',
                ]),

            Stat::make('Monthly Contributions', 'KES ' . number_format($monthlyContributions, 2))
                ->description($contributionChange >= 0 ? 
                    number_format(abs($contributionChange), 1) . '% increase' : 
                    number_format(abs($contributionChange), 1) . '% decrease')
                ->descriptionIcon($contributionChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($contributionChange >= 0 ? 'success' : 'danger')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3, $monthlyContributions])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => '$dispatch("open-modal", { id: "view-contributions-details" })',
                ]),

            Stat::make('Pending Reconciliations', $pendingReconciliations)
                ->description($pendingReconciliations > 0 ? 'Needs attention' : 'All clear')
                ->descriptionIcon($pendingReconciliations > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle')
                ->color($pendingReconciliations > 0 ? 'warning' : 'success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => 'redirectToReconciliations',
                ]),

            Stat::make('Recent Documents', $recentDocuments)
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-document')
                ->color('info')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => 'redirectToDocuments',
                ]),

            Stat::make('Member Participation', number_format($participationRate, 1) . '%')
                ->description($activeMembers . ' active members')
                ->descriptionIcon('heroicon-m-user-group')
                ->color($participationRate >= 70 ? 'success' : ($participationRate >= 50 ? 'warning' : 'danger'))
                ->chart([
                    $participationRate,
                    100 - $participationRate,
                ])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => 'redirectToMembers',
                ]),
        ];
    }

    public function redirectToReconciliations(): void
    {
        $this->redirect(route('treasurer.payments'));
    }

    public function redirectToDocuments(): void
    {
        $this->redirect(route('documents.index'));
    }

    public function redirectToMembers(): void
    {
        $this->redirect(route('admin.members'));
    }
}