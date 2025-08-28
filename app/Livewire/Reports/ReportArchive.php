<?php

namespace App\Livewire\Reports;

use App\Models\FinancialReport;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ReportArchive extends Component
{
    use WithPagination;

    public $search = '';
    public $filterType = '';
    public $filterFormat = '';
    public $filterStatus = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $showFilters = false;

    public $selected = [];
    public $selectAll = false;
    public $showBulkActions = false;

    public $deletingReport = null;
    public $showDeleteModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => ''],
        'filterFormat' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->getFilteredReports()
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function updatedSelected()
    {
        $this->selectAll = false;
        $this->showBulkActions = !empty($this->selected);
    }

    protected function getFilteredReports()
    {
        return FinancialReport::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('title', 'like', '%' . $this->search . '%')
                        ->orWhereHas('generator', function ($query) {
                            $query->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->filterType, function ($query) {
                $query->where('type', $this->filterType);
            })
            ->when($this->filterFormat, function ($query) {
                $query->where('export_format', $this->filterFormat);
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterDateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->filterDateFrom);
            })
            ->when($this->filterDateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->filterDateTo);
            });
    }

    public function downloadReport(FinancialReport $report)
    {
        if ($report->status !== FinancialReport::STATUS_COMPLETED) {
            session()->flash('error', 'Report is not available for download.');
            return;
        }

        return redirect()->route('reports.download', $report->id);
    }

    public function confirmDelete(FinancialReport $report)
    {
        $this->deletingReport = $report;
        $this->showDeleteModal = true;
    }

    public function deleteReport()
    {
        if ($this->deletingReport) {
            $this->deletingReport->delete();
            $this->showDeleteModal = false;
            $this->deletingReport = null;
            session()->flash('message', 'Report deleted successfully.');
        }
    }

    public function bulkDelete()
    {
        $reports = FinancialReport::whereIn('id', $this->selected)->get();
        
        foreach ($reports as $report) {
            $report->delete();
        }

        $this->selected = [];
        $this->selectAll = false;
        $this->showBulkActions = false;
        
        session()->flash('message', count($reports) . ' reports deleted successfully.');
    }

    public function regenerateReport(FinancialReport $report)
    {
        try {
            $exportService = new \App\Services\Exports\ReportExportService();
            
            $newReport = $exportService->generateReport(
                $report->type,
                $report->start_date,
                $report->end_date,
                $report->export_format,
                null, // No specific user filter for regeneration
                $report->metadata ?? []
            );

            session()->flash('message', 'Report regenerated successfully.');
            return redirect()->route('reports.download', $newReport->id);

        } catch (\Exception $e) {
            session()->flash('error', 'Error regenerating report: ' . $e->getMessage());
        }
    }

    public function clearFilters()
    {
        $this->reset(['filterType', 'filterFormat', 'filterStatus', 'filterDateFrom', 'filterDateTo', 'search']);
    }

    public function getReportSummary()
    {
        $totalReports = FinancialReport::count();
        $completedReports = FinancialReport::where('status', FinancialReport::STATUS_COMPLETED)->count();
        $failedReports = FinancialReport::where('status', FinancialReport::STATUS_FAILED)->count();
        $generatingReports = FinancialReport::where('status', FinancialReport::STATUS_GENERATING)->count();

        $totalSize = FinancialReport::where('status', FinancialReport::STATUS_COMPLETED)->sum('file_size');
        
        return [
            'total' => $totalReports,
            'completed' => $completedReports,
            'failed' => $failedReports,
            'generating' => $generatingReports,
            'total_size' => $this->formatFileSize($totalSize),
            'success_rate' => $totalReports > 0 ? round(($completedReports / $totalReports) * 100, 1) : 0,
        ];
    }

    private function formatFileSize($bytes)
    {
        if ($bytes == 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor(log($bytes, 1024));
        
        return round($bytes / pow(1024, $factor), 2) . ' ' . $units[$factor];
    }

    public function render()
    {
        return view('livewire.reports.report-archive', [
            'reports' => $this->getFilteredReports()
                ->with(['generator'])
                ->latest()
                ->paginate(15),
            'reportTypes' => FinancialReport::getReportTypes(),
            'exportFormats' => FinancialReport::getExportFormats(),
            'statuses' => FinancialReport::getTransactionStatuses(),
            'summary' => $this->getReportSummary(),
        ]);
    }
}