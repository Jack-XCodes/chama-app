<?php

namespace App\Services\Exports;

use App\Models\FinancialReport;
use App\Services\Reports\BalanceSheetCalculator;
use App\Services\Reports\ProfitLossCalculator;
use App\Services\Reports\CashFlowCalculator;
use App\Services\Reports\MemberStatementCalculator;
use App\Services\Reports\PaidUpMembersCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportExportService
{
    /**
     * Generate and export a financial report.
     */
    public function generateReport(
        string $type,
        Carbon $startDate,
        Carbon $endDate,
        string $format,
        ?int $userId = null,
        array $options = []
    ): FinancialReport {
        // Create the report record
        $report = FinancialReport::create([
            'title' => $this->generateTitle($type, $startDate, $endDate),
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'export_format' => $format,
            'generated_by' => auth()->id(),
            'status' => FinancialReport::STATUS_GENERATING,
            'metadata' => $options,
            'report_data' => [], // Will be filled after calculation
        ]);

        try {
            // Calculate the report data
            $calculator = $this->getCalculator($type, $startDate, $endDate, $userId, $options);
            $reportData = $calculator->calculate();

            // Update the report with calculated data
            $report->update(['report_data' => $reportData]);

            // Generate the file
            $filePath = $this->exportToFile($report, $reportData, $format);
            $fileSize = Storage::size($filePath);
            $hash = hash_file('sha256', Storage::path($filePath));

            // Mark as completed
            $report->markCompleted($filePath, $fileSize, $hash);

        } catch (\Exception $e) {
            $report->markFailed($e->getMessage());
            throw $e;
        }

        return $report;
    }

    /**
     * Get the appropriate calculator for the report type.
     */
    private function getCalculator(string $type, Carbon $startDate, Carbon $endDate, ?int $userId = null, array $options = [])
    {
        $user = $userId ? \App\Models\User::find($userId) : null;

        return match($type) {
            FinancialReport::TYPE_BALANCE_SHEET => new BalanceSheetCalculator($startDate, $endDate, $user),
            FinancialReport::TYPE_PROFIT_LOSS => new ProfitLossCalculator($startDate, $endDate, $user),
            FinancialReport::TYPE_CASH_FLOW => new CashFlowCalculator($startDate, $endDate, $user),
            FinancialReport::TYPE_MEMBER_STATEMENT => new MemberStatementCalculator($startDate, $endDate, $user),
            FinancialReport::TYPE_PAID_UP_MEMBERS => new PaidUpMembersCalculator(
                $startDate, 
                $endDate, 
                $user,
                $options['expected_monthly_contribution'] ?? 5000,
                $options['grace_period_days'] ?? 7
            ),
            default => throw new \InvalidArgumentException("Unknown report type: {$type}"),
        };
    }

    /**
     * Export report data to file.
     */
    private function exportToFile(FinancialReport $report, array $reportData, string $format): string
    {
        $filename = $this->generateFilename($report, $format);
        $directory = 'reports/' . date('Y/m');

        return match($format) {
            FinancialReport::FORMAT_PDF => $this->exportToPdf($report, $reportData, $directory, $filename),
            FinancialReport::FORMAT_EXCEL => $this->exportToExcel($report, $reportData, $directory, $filename),
            FinancialReport::FORMAT_CSV => $this->exportToCsv($report, $reportData, $directory, $filename),
            default => throw new \InvalidArgumentException("Unknown export format: {$format}"),
        };
    }

    /**
     * Export to PDF format.
     */
    private function exportToPdf(FinancialReport $report, array $reportData, string $directory, string $filename): string
    {
        $view = $this->getPdfView($report->type);
        
        $pdf = Pdf::loadView($view, [
            'report' => $report,
            'data' => $reportData,
            'generatedAt' => now(),
            'organizationInfo' => $this->getOrganizationInfo(),
        ]);

        $pdf->setPaper('A4', 'portrait');
        $filePath = $directory . '/' . $filename . '.pdf';
        
        Storage::put($filePath, $pdf->output());
        
        return $filePath;
    }

    /**
     * Export to Excel format.
     */
    private function exportToExcel(FinancialReport $report, array $reportData, string $directory, string $filename): string
    {
        $exportClass = $this->getExcelExportClass($report->type);
        $export = new $exportClass($report, $reportData);
        
        $filePath = $directory . '/' . $filename . '.xlsx';
        Excel::store($export, $filePath);
        
        return $filePath;
    }

    /**
     * Export to CSV format.
     */
    private function exportToCsv(FinancialReport $report, array $reportData, string $directory, string $filename): string
    {
        $csvData = $this->convertToCsvData($report, $reportData);
        $content = $this->arrayToCsv($csvData);
        
        $filePath = $directory . '/' . $filename . '.csv';
        Storage::put($filePath, $content);
        
        return $filePath;
    }

    /**
     * Get PDF view name for report type.
     */
    private function getPdfView(string $type): string
    {
        return match($type) {
            FinancialReport::TYPE_BALANCE_SHEET => 'reports.pdf.balance-sheet',
            FinancialReport::TYPE_PROFIT_LOSS => 'reports.pdf.profit-loss',
            FinancialReport::TYPE_CASH_FLOW => 'reports.pdf.cash-flow',
            FinancialReport::TYPE_MEMBER_STATEMENT => 'reports.pdf.member-statement',
            FinancialReport::TYPE_PAID_UP_MEMBERS => 'reports.pdf.paid-up-members',
            default => 'reports.pdf.generic',
        };
    }

    /**
     * Get Excel export class for report type.
     */
    private function getExcelExportClass(string $type): string
    {
        return match($type) {
            FinancialReport::TYPE_BALANCE_SHEET => BalanceSheetExport::class,
            FinancialReport::TYPE_PROFIT_LOSS => ProfitLossExport::class,
            FinancialReport::TYPE_CASH_FLOW => CashFlowExport::class,
            FinancialReport::TYPE_MEMBER_STATEMENT => MemberStatementExport::class,
            FinancialReport::TYPE_PAID_UP_MEMBERS => PaidUpMembersExport::class,
            default => throw new \InvalidArgumentException("No Excel export class for type: {$type}"),
        };
    }

    /**
     * Convert report data to CSV format.
     */
    private function convertToCsvData(FinancialReport $report, array $reportData): array
    {
        return match($report->type) {
            FinancialReport::TYPE_BALANCE_SHEET => $this->balanceSheetToCsv($reportData),
            FinancialReport::TYPE_PROFIT_LOSS => $this->profitLossToCsv($reportData),
            FinancialReport::TYPE_CASH_FLOW => $this->cashFlowToCsv($reportData),
            FinancialReport::TYPE_MEMBER_STATEMENT => $this->memberStatementToCsv($reportData),
            FinancialReport::TYPE_PAID_UP_MEMBERS => $this->paidUpMembersToCsv($reportData),
            default => [['Error', 'Unknown report type']],
        };
    }

    /**
     * Convert array to CSV string.
     */
    private function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        return $content;
    }

    /**
     * Generate report title.
     */
    private function generateTitle(string $type, Carbon $startDate, Carbon $endDate): string
    {
        $typeDisplay = match($type) {
            FinancialReport::TYPE_BALANCE_SHEET => 'Balance Sheet',
            FinancialReport::TYPE_PROFIT_LOSS => 'Profit & Loss Statement',
            FinancialReport::TYPE_CASH_FLOW => 'Cash Flow Statement',
            FinancialReport::TYPE_MEMBER_STATEMENT => 'Member Statement',
            FinancialReport::TYPE_PAID_UP_MEMBERS => 'Paid-up Members Report',
            default => ucfirst(str_replace('_', ' ', $type)),
        };

        if ($type === FinancialReport::TYPE_BALANCE_SHEET) {
            return $typeDisplay . ' as of ' . $endDate->format('F j, Y');
        }

        return $typeDisplay . ' for ' . $startDate->format('F j, Y') . ' to ' . $endDate->format('F j, Y');
    }

    /**
     * Generate filename for the report.
     */
    private function generateFilename(FinancialReport $report, string $format): string
    {
        $type = str_replace('_', '-', $report->type);
        $date = $report->end_date->format('Y-m-d');
        $timestamp = now()->format('His');
        
        return "{$type}-{$date}-{$timestamp}";
    }

    /**
     * Get organization information.
     */
    private function getOrganizationInfo(): array
    {
        return [
            'name' => config('app.name', 'Chama Management System'),
            'address' => 'P.O. Box 12345, Nairobi, Kenya',
            'phone' => '+254 700 000 000',
            'email' => 'info@chama.co.ke',
            'website' => 'www.chama.co.ke',
        ];
    }

    // CSV conversion methods for each report type
    private function balanceSheetToCsv(array $data): array
    {
        $csv = [];
        $csv[] = [$data['title']];
        $csv[] = ['Generated on: ' . now()->format('F j, Y g:i A')];
        $csv[] = []; // Empty row

        // Assets
        $csv[] = ['ASSETS'];
        $csv[] = ['Current Assets'];
        foreach ($data['assets']['current_assets'] as $asset) {
            $csv[] = [$asset['name'], $asset['formatted']];
        }
        $csv[] = ['Total Current Assets', $data['assets']['total_current_assets']['formatted']];
        $csv[] = [];

        $csv[] = ['Non-Current Assets'];
        foreach ($data['assets']['non_current_assets'] as $asset) {
            $csv[] = [$asset['name'], $asset['formatted']];
        }
        $csv[] = ['Total Non-Current Assets', $data['assets']['total_non_current_assets']['formatted']];
        $csv[] = ['TOTAL ASSETS', $data['assets']['total_assets']['formatted']];
        $csv[] = [];

        // Liabilities
        $csv[] = ['LIABILITIES'];
        $csv[] = ['Current Liabilities'];
        foreach ($data['liabilities']['current_liabilities'] as $liability) {
            $csv[] = [$liability['name'], $liability['formatted']];
        }
        $csv[] = ['Total Current Liabilities', $data['liabilities']['total_current_liabilities']['formatted']];
        $csv[] = [];

        // Equity
        $csv[] = ['EQUITY'];
        foreach ($data['equity']['equity_items'] as $equity) {
            $csv[] = [$equity['name'], $equity['formatted']];
        }
        $csv[] = ['Total Equity', $data['equity']['total_equity']['formatted']];
        $csv[] = [];
        $csv[] = ['TOTAL LIABILITIES AND EQUITY', $data['totals']['total_liabilities_and_equity']['formatted']];

        return $csv;
    }

    private function profitLossToCsv(array $data): array
    {
        $csv = [];
        $csv[] = [$data['title']];
        $csv[] = ['Generated on: ' . now()->format('F j, Y g:i A')];
        $csv[] = []; // Empty row

        // Income
        $csv[] = ['INCOME'];
        foreach ($data['income']['categories'] as $category) {
            $csv[] = [$category['category'], $category['formatted']];
        }
        $csv[] = ['Total Income', $data['income']['total_income']['formatted']];
        $csv[] = [];

        // Expenses
        $csv[] = ['EXPENSES'];
        foreach ($data['expenses']['categories'] as $category) {
            $csv[] = [$category['category'], $category['formatted']];
        }
        $csv[] = ['Total Expenses', $data['expenses']['total_expenses']['formatted']];
        $csv[] = [];

        // Net Result
        $csv[] = [$data['net_result']['result_text'], $data['net_result']['net_amount']['formatted']];

        return $csv;
    }

    private function cashFlowToCsv(array $data): array
    {
        $csv = [];
        $csv[] = [$data['title']];
        $csv[] = ['Generated on: ' . now()->format('F j, Y g:i A')];
        $csv[] = []; // Empty row

        // Operating Activities
        $csv[] = ['OPERATING ACTIVITIES'];
        $csv[] = ['Cash Inflows'];
        foreach ($data['operating_activities']['cash_inflows'] as $key => $flow) {
            if ($key !== 'total_inflows') {
                $csv[] = [ucfirst(str_replace('_', ' ', $key)), $flow['formatted']];
            }
        }
        $csv[] = ['Total Inflows', $data['operating_activities']['cash_inflows']['total_inflows']['formatted']];
        $csv[] = [];

        $csv[] = ['Cash Outflows'];
        foreach ($data['operating_activities']['cash_outflows'] as $key => $flow) {
            if ($key !== 'total_outflows') {
                $csv[] = [ucfirst(str_replace('_', ' ', $key)), $flow['formatted']];
            }
        }
        $csv[] = ['Total Outflows', $data['operating_activities']['cash_outflows']['total_outflows']['formatted']];
        $csv[] = ['Net Operating Cash Flow', $data['operating_activities']['net_operating_cash_flow']['formatted']];
        $csv[] = [];

        // Add other sections similarly...
        $csv[] = ['Net Cash Flow', $data['net_cash_flow']['net_cash_flow']['formatted']];

        return $csv;
    }

    private function memberStatementToCsv(array $data): array
    {
        $csv = [];
        $csv[] = [$data['title']];
        $csv[] = ['Generated on: ' . now()->format('F j, Y g:i A')];
        $csv[] = []; // Empty row

        if (isset($data['member'])) {
            // Individual member statement
            $csv[] = ['Member: ' . $data['member']['name']];
            $csv[] = ['Email: ' . $data['member']['email']];
            $csv[] = [];

            $csv[] = ['Date', 'Description', 'Type', 'Debit', 'Credit', 'Balance'];
            foreach ($data['transaction_history'] as $transaction) {
                $csv[] = [
                    $transaction['date'],
                    $transaction['description'],
                    $transaction['type'],
                    $transaction['formatted_debit'],
                    $transaction['formatted_credit'],
                    $transaction['formatted_balance'],
                ];
            }
        } else {
            // All members statement
            $csv[] = ['Member', 'Email', 'Total Contributions', 'Total Debits', 'Total Credits', 'Closing Balance'];
            foreach ($data['member_statements'] as $statement) {
                $csv[] = [
                    $statement['member']['name'],
                    $statement['member']['email'],
                    $statement['summary']['total_contributions']['formatted'],
                    $statement['summary']['total_debits']['formatted'],
                    $statement['summary']['total_credits']['formatted'],
                    $statement['summary']['closing_balance']['formatted'],
                ];
            }
        }

        return $csv;
    }

    private function paidUpMembersToCsv(array $data): array
    {
        $csv = [];
        $csv[] = [$data['title']];
        $csv[] = ['Generated on: ' . now()->format('F j, Y g:i A')];
        $csv[] = []; // Empty row

        $csv[] = ['Member', 'Email', 'Status', 'Expected Total', 'Actual Total', 'Balance', 'Compliance %', 'Risk Level'];
        
        foreach ($data['current_members'] as $member) {
            $csv[] = [
                $member['member']['name'],
                $member['member']['email'],
                ucfirst($member['status']),
                $member['contributions']['expected_total']['formatted'],
                $member['contributions']['actual_total']['formatted'],
                $member['balance']['formatted'],
                $member['compliance']['percentage'] . '%',
                ucfirst($member['compliance']['risk_level']),
            ];
        }

        foreach ($data['arrears_members'] as $member) {
            $csv[] = [
                $member['member']['name'],
                $member['member']['email'],
                ucfirst($member['status']),
                $member['contributions']['expected_total']['formatted'],
                $member['contributions']['actual_total']['formatted'],
                $member['balance']['formatted'],
                $member['compliance']['percentage'] . '%',
                ucfirst($member['compliance']['risk_level']),
            ];
        }

        return $csv;
    }
}