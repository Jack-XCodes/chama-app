<?php

namespace App\Services\Exports;

use App\Models\FinancialReport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ProfitLossExport implements FromArray, WithHeadings, WithStyles, WithTitle
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

        // Income Section
        $rows[] = ['INCOME', '', ''];
        foreach ($this->data['income']['categories'] as $category) {
            $rows[] = ['  ' . $category['category'], $category['amount'], $category['formatted']];
        }
        $rows[] = ['Total Income', 
                  $this->data['income']['total_income']['amount'], 
                  $this->data['income']['total_income']['formatted']];
        $rows[] = []; // Empty row

        // Expenses Section
        $rows[] = ['EXPENSES', '', ''];
        foreach ($this->data['expenses']['categories'] as $category) {
            $rows[] = ['  ' . $category['category'], $category['amount'], $category['formatted']];
        }
        $rows[] = ['Total Expenses', 
                  $this->data['expenses']['total_expenses']['amount'], 
                  $this->data['expenses']['total_expenses']['formatted']];
        $rows[] = []; // Empty row

        // Net Result
        $rows[] = [$this->data['net_result']['result_text'], 
                  $this->data['net_result']['net_amount']['amount'], 
                  $this->data['net_result']['net_amount']['formatted']];
        $rows[] = ['Profit Margin', '', $this->data['net_result']['margin_percentage'] . '%'];

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
        return 'Profit & Loss';
    }
}