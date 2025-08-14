<?php

namespace App\Livewire\Reports;

use App\Models\FinancialReport;
use App\Models\User;
use App\Services\Exports\ReportExportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Rule;

class ReportGenerator extends Component
{
    #[Rule('required|string')]
    public $reportType = '';

    #[Rule('required|date')]
    public $startDate = '';

    #[Rule('required|date|after_or_equal:startDate')]
    public $endDate = '';

    #[Rule('required|string')]
    public $exportFormat = 'pdf';

    #[Rule('nullable|exists:users,id')]
    public $selectedUserId = null;

    #[Rule('numeric|min:1000')]
    public $expectedMonthlyContribution = 5000;

    #[Rule('integer|min:1|max:30')]
    public $gracePeriodDays = 7;

    public $isGenerating = false;
    public $generatedReport = null;
    public $showPreview = false;
    public $previewData = null;

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function updatedReportType()
    {
        // Reset user selection when changing report type
        if (!in_array($this->reportType, [FinancialReport::TYPE_MEMBER_STATEMENT])) {
            $this->selectedUserId = null;
        }

        // Set appropriate date ranges for different report types
        if ($this->reportType === FinancialReport::TYPE_BALANCE_SHEET) {
            $this->startDate = now()->startOfYear()->format('Y-m-d');
            $this->endDate = now()->format('Y-m-d');
        } elseif (in_array($this->reportType, [
            FinancialReport::TYPE_PROFIT_LOSS,
            FinancialReport::TYPE_CASH_FLOW
        ])) {
            $this->startDate = now()->startOfMonth()->format('Y-m-d');
            $this->endDate = now()->format('Y-m-d');
        }
    }

    public function generatePreview()
    {
        $this->validate();

        try {
            $exportService = new ReportExportService();
            $calculator = $this->getCalculator();
            $this->previewData = $calculator->calculate();
            $this->showPreview = true;
        } catch (\Exception $e) {
            session()->flash('error', 'Error generating preview: ' . $e->getMessage());
        }
    }

    public function generateReport()
    {
        $this->validate();
        $this->isGenerating = true;

        try {
            $exportService = new ReportExportService();
            
            $options = [];
            if ($this->reportType === FinancialReport::TYPE_PAID_UP_MEMBERS) {
                $options = [
                    'expected_monthly_contribution' => $this->expectedMonthlyContribution,
                    'grace_period_days' => $this->gracePeriodDays,
                ];
            }

            $this->generatedReport = $exportService->generateReport(
                $this->reportType,
                Carbon::parse($this->startDate),
                Carbon::parse($this->endDate),
                $this->exportFormat,
                $this->selectedUserId,
                $options
            );

            session()->flash('message', 'Report generated successfully!');
            $this->dispatch('report-generated', $this->generatedReport->id);

        } catch (\Exception $e) {
            session()->flash('error', 'Error generating report: ' . $e->getMessage());
        } finally {
            $this->isGenerating = false;
        }
    }

    public function downloadReport()
    {
        if ($this->generatedReport) {
            return redirect()->route('reports.download', $this->generatedReport->id);
        }
    }

    public function resetForm()
    {
        $this->reset(['reportType', 'selectedUserId', 'generatedReport', 'showPreview', 'previewData']);
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->exportFormat = 'pdf';
        $this->expectedMonthlyContribution = 5000;
        $this->gracePeriodDays = 7;
    }

    public function setQuickDateRange(string $range)
    {
        $now = now();
        
        match($range) {
            'this_month' => [
                $this->startDate = $now->copy()->startOfMonth()->format('Y-m-d'),
                $this->endDate = $now->format('Y-m-d')
            ],
            'last_month' => [
                $this->startDate = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d'),
                $this->endDate = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d')
            ],
            'this_quarter' => [
                $this->startDate = $now->copy()->startOfQuarter()->format('Y-m-d'),
                $this->endDate = $now->format('Y-m-d')
            ],
            'last_quarter' => [
                $this->startDate = $now->copy()->subQuarter()->startOfQuarter()->format('Y-m-d'),
                $this->endDate = $now->copy()->subQuarter()->endOfQuarter()->format('Y-m-d')
            ],
            'this_year' => [
                $this->startDate = $now->copy()->startOfYear()->format('Y-m-d'),
                $this->endDate = $now->format('Y-m-d')
            ],
            'last_year' => [
                $this->startDate = $now->copy()->subYear()->startOfYear()->format('Y-m-d'),
                $this->endDate = $now->copy()->subYear()->endOfYear()->format('Y-m-d')
            ],
            default => null
        };
    }

    private function getCalculator()
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        $user = $this->selectedUserId ? User::find($this->selectedUserId) : null;

        return match($this->reportType) {
            FinancialReport::TYPE_BALANCE_SHEET => new \App\Services\Reports\BalanceSheetCalculator($startDate, $endDate, $user),
            FinancialReport::TYPE_PROFIT_LOSS => new \App\Services\Reports\ProfitLossCalculator($startDate, $endDate, $user),
            FinancialReport::TYPE_CASH_FLOW => new \App\Services\Reports\CashFlowCalculator($startDate, $endDate, $user),
            FinancialReport::TYPE_MEMBER_STATEMENT => new \App\Services\Reports\MemberStatementCalculator($startDate, $endDate, $user),
            FinancialReport::TYPE_PAID_UP_MEMBERS => new \App\Services\Reports\PaidUpMembersCalculator(
                $startDate, 
                $endDate, 
                $user,
                $this->expectedMonthlyContribution,
                $this->gracePeriodDays
            ),
            default => throw new \InvalidArgumentException("Unknown report type: {$this->reportType}"),
        };
    }

    public function render()
    {
        return view('livewire.reports.report-generator', [
            'reportTypes' => FinancialReport::getReportTypes(),
            'exportFormats' => FinancialReport::getExportFormats(),
            'users' => User::orderBy('name')->get(),
        ]);
    }
}