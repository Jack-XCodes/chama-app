<?php

namespace App\Services\Exports;

use App\Models\FinancialReport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PaidUpMembersExport implements FromArray, WithHeadings, WithStyles, WithTitle
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

        // Summary
        $rows[] = ['SUMMARY', '', '', '', '', '', '', '', ''];
        $rows[] = ['Total Members', $this->data['summary']['member_counts']['total']];
        $rows[] = ['Current Members', $this->data['summary']['member_counts']['current'] . ' (' . $this->data['summary']['member_counts']['current_percentage'] . '%)'];
        $rows[] = ['Members in Arrears', $this->data['summary']['member_counts']['arrears'] . ' (' . $this->data['summary']['member_counts']['arrears_percentage'] . '%)'];
        $rows[] = ['Inactive Members', $this->data['summary']['member_counts']['inactive']];
        $rows[] = ['Collection Efficiency', $this->data['summary']['financial_summary']['collection_efficiency'] . '%'];
        $rows[] = []; // Empty row

        // Member Details Header
        $rows[] = ['Member', 'Email', 'Status', 'Expected Total', 'Actual Total', 'Balance', 'Compliance %', 'Risk Level', 'Last Contribution'];

        // Current Members
        if (!empty($this->data['current_members'])) {
            $rows[] = ['CURRENT MEMBERS', '', '', '', '', '', '', '', ''];
            foreach ($this->data['current_members'] as $member) {
                $rows[] = [
                    $member['member']['name'],
                    $member['member']['email'],
                    ucfirst($member['status']),
                    $member['contributions']['expected_total']['formatted'],
                    $member['contributions']['actual_total']['formatted'],
                    $member['balance']['formatted'],
                    $member['compliance']['percentage'] . '%',
                    ucfirst($member['compliance']['risk_level']),
                    $member['dates']['last_contribution'] ?? 'N/A',
                ];
            }
            $rows[] = []; // Empty row
        }

        // Members in Arrears
        if (!empty($this->data['arrears_members'])) {
            $rows[] = ['MEMBERS IN ARREARS', '', '', '', '', '', '', '', ''];
            foreach ($this->data['arrears_members'] as $member) {
                $rows[] = [
                    $member['member']['name'],
                    $member['member']['email'],
                    ucfirst($member['status']),
                    $member['contributions']['expected_total']['formatted'],
                    $member['contributions']['actual_total']['formatted'],
                    $member['balance']['formatted'],
                    $member['compliance']['percentage'] . '%',
                    ucfirst($member['compliance']['risk_level']),
                    $member['dates']['last_contribution'] ?? 'N/A',
                ];
            }
            $rows[] = []; // Empty row
        }

        // Inactive Members
        if (!empty($this->data['inactive_members'])) {
            $rows[] = ['INACTIVE MEMBERS', '', '', '', '', '', '', '', ''];
            foreach ($this->data['inactive_members'] as $member) {
                $rows[] = [
                    $member['member']['name'],
                    $member['member']['email'],
                    ucfirst($member['status']),
                    $member['contributions']['expected_total']['formatted'],
                    $member['contributions']['actual_total']['formatted'],
                    $member['balance']['formatted'],
                    $member['compliance']['percentage'] . '%',
                    ucfirst($member['compliance']['risk_level']),
                    $member['dates']['last_contribution'] ?? 'N/A',
                ];
            }
            $rows[] = []; // Empty row
        }

        // Aging Analysis
        $rows[] = ['AGING ANALYSIS', '', '', '', '', '', '', '', ''];
        $rows[] = ['Period', 'Count', 'Amount', '', '', '', '', '', ''];
        foreach ($this->data['aging_analysis'] as $period => $data) {
            $periodName = match($period) {
                '1_month' => '1 Month Behind',
                '2_months' => '2 Months Behind',
                '3_months' => '3 Months Behind',
                'over_3_months' => 'Over 3 Months Behind',
                default => ucfirst(str_replace('_', ' ', $period)),
            };
            $rows[] = [$periodName, $data['count'], $data['formatted_amount'], '', '', '', '', '', ''];
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Member', 'Email', 'Status', 'Expected Total', 'Actual Total', 'Balance', 'Compliance %', 'Risk Level', 'Last Contribution'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            2 => ['font' => ['italic' => true]],
            'A:A' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
            'D:F' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
            'G:G' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'H:H' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function title(): string
    {
        return 'Paid-up Members';
    }
}