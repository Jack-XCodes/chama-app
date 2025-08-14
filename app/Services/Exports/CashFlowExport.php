<?php

namespace App\Services\Exports;

use App\Models\FinancialReport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CashFlowExport implements FromArray, WithHeadings, WithStyles, WithTitle
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

        // Operating Activities
        $rows[] = ['OPERATING ACTIVITIES', '', ''];
        $rows[] = ['Cash Inflows:', '', ''];
        foreach ($this->data['operating_activities']['cash_inflows'] as $key => $flow) {
            if ($key !== 'total_inflows') {
                $rows[] = ['  ' . ucfirst(str_replace('_', ' ', $key)), $flow['amount'], $flow['formatted']];
            }
        }
        $rows[] = ['Total Inflows', 
                  $this->data['operating_activities']['cash_inflows']['total_inflows']['amount'], 
                  $this->data['operating_activities']['cash_inflows']['total_inflows']['formatted']];
        $rows[] = []; // Empty row

        $rows[] = ['Cash Outflows:', '', ''];
        foreach ($this->data['operating_activities']['cash_outflows'] as $key => $flow) {
            if ($key !== 'total_outflows') {
                $rows[] = ['  ' . ucfirst(str_replace('_', ' ', $key)), $flow['amount'], $flow['formatted']];
            }
        }
        $rows[] = ['Total Outflows', 
                  $this->data['operating_activities']['cash_outflows']['total_outflows']['amount'], 
                  $this->data['operating_activities']['cash_outflows']['total_outflows']['formatted']];
        $rows[] = []; // Empty row

        $rows[] = ['Net Operating Cash Flow', 
                  $this->data['operating_activities']['net_operating_cash_flow']['amount'], 
                  $this->data['operating_activities']['net_operating_cash_flow']['formatted']];
        $rows[] = []; // Empty row

        // Investing Activities
        $rows[] = ['INVESTING ACTIVITIES', '', ''];
        $rows[] = ['Cash Outflows:', '', ''];
        foreach ($this->data['investing_activities']['cash_outflows'] as $key => $flow) {
            $rows[] = ['  ' . ucfirst(str_replace('_', ' ', $key)), $flow['amount'], $flow['formatted']];
        }
        $rows[] = ['Cash Inflows:', '', ''];
        foreach ($this->data['investing_activities']['cash_inflows'] as $key => $flow) {
            $rows[] = ['  ' . ucfirst(str_replace('_', ' ', $key)), $flow['amount'], $flow['formatted']];
        }
        $rows[] = ['Net Investing Cash Flow', 
                  $this->data['investing_activities']['net_investing_cash_flow']['amount'], 
                  $this->data['investing_activities']['net_investing_cash_flow']['formatted']];
        $rows[] = []; // Empty row

        // Financing Activities
        $rows[] = ['FINANCING ACTIVITIES', '', ''];
        $rows[] = ['Cash Outflows:', '', ''];
        foreach ($this->data['financing_activities']['cash_outflows'] as $key => $flow) {
            $rows[] = ['  ' . ucfirst(str_replace('_', ' ', $key)), $flow['amount'], $flow['formatted']];
        }
        $rows[] = ['Cash Inflows:', '', ''];
        foreach ($this->data['financing_activities']['cash_inflows'] as $key => $flow) {
            $rows[] = ['  ' . ucfirst(str_replace('_', ' ', $key)), $flow['amount'], $flow['formatted']];
        }
        $rows[] = ['Net Financing Cash Flow', 
                  $this->data['financing_activities']['net_financing_cash_flow']['amount'], 
                  $this->data['financing_activities']['net_financing_cash_flow']['formatted']];
        $rows[] = []; // Empty row

        // Net Cash Flow
        $rows[] = ['NET CASH FLOW', 
                  $this->data['net_cash_flow']['net_cash_flow']['amount'], 
                  $this->data['net_cash_flow']['net_cash_flow']['formatted']];
        $rows[] = []; // Empty row

        // Cash Summary
        $rows[] = ['CASH SUMMARY', '', ''];
        $rows[] = ['Opening Cash Balance', 
                  $this->data['cash_summary']['opening_cash_balance']['amount'], 
                  $this->data['cash_summary']['opening_cash_balance']['formatted']];
        $rows[] = ['Net Cash Flow for Period', 
                  $this->data['cash_summary']['net_cash_flow_for_period']['amount'], 
                  $this->data['cash_summary']['net_cash_flow_for_period']['formatted']];
        $rows[] = ['Closing Cash Balance', 
                  $this->data['cash_summary']['closing_cash_balance']['amount'], 
                  $this->data['cash_summary']['closing_cash_balance']['formatted']];

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
        return 'Cash Flow';
    }
}