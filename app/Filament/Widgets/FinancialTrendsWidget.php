<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class FinancialTrendsWidget extends ChartWidget
{
    protected static ?string $heading = 'Financial Trends';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '15s';

    protected int $viewData = 6; // Default to 6 months
    protected string $chartType = 'monthly'; // Default to monthly view

    protected function getData(): array
    {
        return match($this->chartType) {
            'monthly' => $this->getMonthlyData(),
            'category' => $this->getCategoryData(),
            'cashflow' => $this->getCashFlowData(),
            default => $this->getMonthlyData(),
        };
    }

    protected function getMonthlyData(): array
    {
        $months = collect(range(0, $this->viewData - 1))
            ->map(fn ($month) => now()->subMonths($month))
            ->reverse();

        $contributions = $months->map(fn ($month) => 
            Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
                ->where('type', Transaction::TYPE_PAYMENT)
                ->whereBetween('created_at', [
                    $month->startOfMonth(),
                    $month->endOfMonth(),
                ])
                ->sum('amount')
        );

        $expenses = $months->map(fn ($month) => 
            abs(Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
                ->whereIn('type', [Transaction::TYPE_EXPENSE, Transaction::TYPE_BANK_CHARGE])
                ->whereBetween('created_at', [
                    $month->startOfMonth(),
                    $month->endOfMonth(),
                ])
                ->sum('amount'))
        );

        return [
            'datasets' => [
                [
                    'label' => 'Member Contributions',
                    'data' => $contributions->toArray(),
                    'borderColor' => '#10B981',
                    'backgroundColor' => '#10B98140',
                    'fill' => true,
                ],
                [
                    'label' => 'Expenses',
                    'data' => $expenses->toArray(),
                    'borderColor' => '#EF4444',
                    'backgroundColor' => '#EF444440',
                    'fill' => true,
                ],
            ],
            'labels' => $months->map(fn ($month) => $month->format('M Y'))->toArray(),
        ];
    }

    protected function getCategoryData(): array
    {
        $categories = Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
            ->whereIn('type', [Transaction::TYPE_EXPENSE, Transaction::TYPE_BANK_CHARGE])
            ->where('created_at', '>=', now()->subMonths($this->viewData))
            ->with('category')
            ->get()
            ->groupBy('category.name')
            ->map(fn ($transactions) => abs($transactions->sum('amount')))
            ->sortDesc();

        $colors = [
            '#10B981', // Emerald
            '#3B82F6', // Blue
            '#F59E0B', // Amber
            '#EC4899', // Pink
            '#8B5CF6', // Purple
            '#EF4444', // Red
            '#14B8A6', // Teal
            '#F97316', // Orange
            '#6366F1', // Indigo
            '#84CC16', // Lime
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Expense Categories',
                    'data' => $categories->values()->toArray(),
                    'backgroundColor' => collect($colors)->take($categories->count())->toArray(),
                ],
            ],
            'labels' => $categories->keys()->toArray(),
        ];
    }

    protected function getCashFlowData(): array
    {
        $days = collect(range(0, 29))
            ->map(fn ($day) => now()->subDays($day))
            ->reverse();

        $inflow = $days->map(fn ($day) => 
            Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
                ->where('amount', '>', 0)
                ->whereDate('created_at', $day)
                ->sum('amount')
        );

        $outflow = $days->map(fn ($day) => 
            abs(Transaction::whereIn('status', [Transaction::STATUS_APPROVED, Transaction::STATUS_VERIFIED])
                ->where('amount', '<', 0)
                ->whereDate('created_at', $day)
                ->sum('amount'))
        );

        $balance = collect();
        $runningBalance = 0;
        foreach ($inflow->keys() as $key) {
            $runningBalance += $inflow[$key] - $outflow[$key];
            $balance->push($runningBalance);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Cash Inflow',
                    'data' => $inflow->toArray(),
                    'type' => 'bar',
                    'backgroundColor' => '#10B981',
                ],
                [
                    'label' => 'Cash Outflow',
                    'data' => $outflow->toArray(),
                    'type' => 'bar',
                    'backgroundColor' => '#EF4444',
                ],
                [
                    'label' => 'Running Balance',
                    'data' => $balance->toArray(),
                    'type' => 'line',
                    'borderColor' => '#3B82F6',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $days->map(fn ($day) => $day->format('M j'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return match($this->chartType) {
            'monthly' => 'line',
            'category' => 'doughnut',
            'cashflow' => 'bar',
            default => 'line',
        };
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => $this->chartType !== 'category' ? [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return 'KES ' + value.toLocaleString(); }",
                    ],
                ],
            ] : [],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'view' => [
                'label' => 'Time Range',
                'options' => [
                    3 => '3 Months',
                    6 => '6 Months',
                    12 => '12 Months',
                ],
            ],
            'type' => [
                'label' => 'Chart Type',
                'options' => [
                    'monthly' => 'Monthly Trends',
                    'category' => 'Category Breakdown',
                    'cashflow' => 'Cash Flow',
                ],
            ],
        ];
    }

    protected function filterFormSchema(): array
    {
        return [
            Forms\Components\Select::make('view')
                ->options([
                    3 => '3 Months',
                    6 => '6 Months',
                    12 => '12 Months',
                ])
                ->default(6)
                ->reactive(),
            Forms\Components\Select::make('type')
                ->options([
                    'monthly' => 'Monthly Trends',
                    'category' => 'Category Breakdown',
                    'cashflow' => 'Cash Flow',
                ])
                ->default('monthly')
                ->reactive(),
        ];
    }
}