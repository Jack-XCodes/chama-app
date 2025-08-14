<?php

namespace App\Services\Exports;

use App\Models\FinancialReport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class BalanceSheetExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    private FinancialReport $report;
    private array $data;

    public function __construct(FinancialReport $report, array $data)
    {
        $this->report = $report;
        $this->data = $data;
    }

    public function array(): array
    {
        $rows = [];
        
        // Header
        $rows[] = [$this->data['title']];
        $rows[] = ['Generated on: ' . now()->format('F j, Y g:i A')];
        $rows[] = []; // Empty row

        // Assets Section
        $rows[] = ['ASSETS', '', ''];
        $rows[] = ['Current Assets', '', ''];
        
        foreach ($this->data['assets']['current_assets'] as $asset) {
            $rows[] = ['  ' . $asset['name'], $asset['amount'], $asset['formatted']];
        }
        
        $rows[] = ['Total Current Assets', 
                  $this->data['assets']['total_current_assets']['amount'], 
                  $this->data['assets']['total_current_assets']['formatted']];
        $rows[] = []; // Empty row

        $rows[] = ['Non-Current Assets', '', ''];
        foreach ($this->data['assets']['non_current_assets'] as $asset) {
            $rows[] = ['  ' . $asset['name'], $asset['amount'], $asset['formatted']];
        }
        
        $rows[] = ['Total Non-Current Assets', 
                  $this->data['assets']['total_non_current_assets']['amount'], 
                  $this->data['assets']['total_non_current_assets']['formatted']];
        $rows[] = []; // Empty row

        $rows[] = ['TOTAL ASSETS', 
                  $this->data['assets']['total_assets']['amount'], 
                  $this->data['assets']['total_assets']['formatted']];
        $rows[] = []; // Empty row

        // Liabilities Section
        $rows[] = ['LIABILITIES', '', ''];
        $rows[] = ['Current Liabilities', '', ''];
        
        foreach ($this->data['liabilities']['current_liabilities'] as $liability) {
            $rows[] = ['  ' . $liability['name'], $liability['amount'], $liability['formatted']];
        }
        
        $rows[] = ['Total Current Liabilities', 
                  $this->data['liabilities']['total_current_liabilities']['amount'], 
                  $this->data['liabilities']['total_current_liabilities']['formatted']];
        $rows[] = []; // Empty row

        $rows[] = ['Total Liabilities', 
                  $this->data['liabilities']['total_liabilities']['amount'], 
                  $this->data['liabilities']['total_liabilities']['formatted']];
        $rows[] = []; // Empty row

        // Equity Section
        $rows[] = ['EQUITY', '', ''];
        foreach ($this->data['equity']['equity_items'] as $equity) {
            $rows[] = ['  ' . $equity['name'], $equity['amount'], $equity['formatted']];
        }
        
        $rows[] = ['Total Equity', 
                  $this->data['equity']['total_equity']['amount'], 
                  $this->data['equity']['total_equity']['formatted']];
        $rows[] = []; // Empty row

        $rows[] = ['TOTAL LIABILITIES AND EQUITY', 
                  $this->data['totals']['total_liabilities_and_equity']['amount'], 
                  $this->data['totals']['total_liabilities_and_equity']['formatted']];

        return $rows;
    }

    public function headings(): array
    {
        return ['Item', 'Amount', 'Formatted'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            2 => ['font' => ['italic' => true]],
            'A:A' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
            'B:B' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
            'C:C' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
        ];
    }

    public function title(): string
    {
        return 'Balance Sheet';
    }
}