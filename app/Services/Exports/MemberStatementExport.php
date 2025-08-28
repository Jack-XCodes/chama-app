<?php

namespace App\Services\Exports;

use App\Models\FinancialReport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class MemberStatementExport implements FromArray, WithHeadings, WithStyles, WithTitle
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

        if (isset($this->data['member'])) {
            // Individual member statement
            $rows[] = ['Member: ' . $this->data['member']['name']];
            $rows[] = ['Email: ' . $this->data['member']['email']];
            $rows[] = []; // Empty row

            // Summary
            $rows[] = ['SUMMARY', '', '', '', '', ''];
            $rows[] = ['Opening Balance', '', '', '', '', $this->data['summary']['opening_balance']['formatted']];
            $rows[] = ['Total Contributions', '', '', '', '', $this->data['summary']['total_contributions']['formatted']];
            $rows[] = ['Total Debits', '', '', '', '', $this->data['summary']['total_debits']['formatted']];
            $rows[] = ['Total Credits', '', '', '', '', $this->data['summary']['total_credits']['formatted']];
            $rows[] = ['Closing Balance', '', '', '', '', $this->data['summary']['closing_balance']['formatted']];
            $rows[] = []; // Empty row

            // Transaction History Header
            $rows[] = ['Date', 'Description', 'Type', 'Category', 'Debit', 'Credit', 'Balance'];
            
            // Transaction History
            foreach ($this->data['transaction_history'] as $transaction) {
                $rows[] = [
                    $transaction['date'],
                    $transaction['description'],
                    $transaction['type'],
                    $transaction['category'],
                    $transaction['formatted_debit'],
                    $transaction['formatted_credit'],
                    $transaction['formatted_balance'],
                ];
            }
        } else {
            // All members summary
            $rows[] = ['Member', 'Email', 'Opening Balance', 'Total Contributions', 'Total Debits', 'Total Credits', 'Closing Balance', 'Transaction Count'];
            
            foreach ($this->data['member_statements'] as $statement) {
                $rows[] = [
                    $statement['member']['name'],
                    $statement['member']['email'],
                    $statement['summary']['opening_balance']['formatted'],
                    $statement['summary']['total_contributions']['formatted'],
                    $statement['summary']['total_debits']['formatted'],
                    $statement['summary']['total_credits']['formatted'],
                    $statement['summary']['closing_balance']['formatted'],
                    $statement['summary']['transaction_count'],
                ];
            }
            
            $rows[] = []; // Empty row
            $rows[] = ['TOTALS', '', 
                      $this->data['totals']['opening_balance']['formatted'],
                      $this->data['totals']['total_contributions']['formatted'],
                      $this->data['totals']['total_debits']['formatted'],
                      $this->data['totals']['total_credits']['formatted'],
                      $this->data['totals']['closing_balance']['formatted'],
                      $this->data['totals']['member_count'] . ' members'];
        }

        return $rows;
    }

    public function headings(): array
    {
        if (isset($this->data['member'])) {
            return ['Date', 'Description', 'Type', 'Category', 'Debit', 'Credit', 'Balance'];
        } else {
            return ['Member', 'Email', 'Opening Balance', 'Total Contributions', 'Total Debits', 'Total Credits', 'Closing Balance', 'Transaction Count'];
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            2 => ['font' => ['italic' => true]],
            'A:A' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
            'E:G' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
        ];
    }

    public function title(): string
    {
        return 'Member Statement';
    }
}