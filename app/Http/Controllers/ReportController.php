<?php

namespace App\Http\Controllers;

use App\Models\FinancialReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Download a financial report file.
     */
    public function download(FinancialReport $report): StreamedResponse
    {
        // Check if user has permission to download reports
        $this->authorize('download', $report);

        // Check if report is completed and file exists
        if ($report->status !== FinancialReport::STATUS_COMPLETED) {
            abort(404, 'Report is not available for download.');
        }

        if (!$report->file_path || !Storage::exists($report->file_path)) {
            abort(404, 'Report file not found.');
        }

        // Get the file info
        $filePath = Storage::path($report->file_path);
        $fileName = $this->generateDownloadFilename($report);
        $mimeType = $this->getMimeType($report->export_format);

        // Return the file as a download
        return response()->download($filePath, $fileName, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Stream a financial report file for viewing.
     */
    public function view(FinancialReport $report): StreamedResponse
    {
        // Check if user has permission to view reports
        $this->authorize('view', $report);

        // Check if report is completed and file exists
        if ($report->status !== FinancialReport::STATUS_COMPLETED) {
            abort(404, 'Report is not available for viewing.');
        }

        if (!$report->file_path || !Storage::exists($report->file_path)) {
            abort(404, 'Report file not found.');
        }

        // Only allow viewing of PDF files
        if ($report->export_format !== FinancialReport::FORMAT_PDF) {
            return $this->download($report);
        }

        // Get the file info
        $filePath = Storage::path($report->file_path);
        $fileName = $this->generateDownloadFilename($report);

        // Return the file for inline viewing
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Generate a user-friendly filename for download.
     */
    private function generateDownloadFilename(FinancialReport $report): string
    {
        $type = str_replace('_', '-', $report->type);
        $dateRange = $report->start_date->format('Y-m-d') . '_to_' . $report->end_date->format('Y-m-d');
        $extension = $this->getFileExtension($report->export_format);
        
        return "{$type}-{$dateRange}.{$extension}";
    }

    /**
     * Get the MIME type for the export format.
     */
    private function getMimeType(string $format): string
    {
        return match($format) {
            FinancialReport::FORMAT_PDF => 'application/pdf',
            FinancialReport::FORMAT_EXCEL => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            FinancialReport::FORMAT_CSV => 'text/csv',
            default => 'application/octet-stream',
        };
    }

    /**
     * Get the file extension for the export format.
     */
    private function getFileExtension(string $format): string
    {
        return match($format) {
            FinancialReport::FORMAT_PDF => 'pdf',
            FinancialReport::FORMAT_EXCEL => 'xlsx',
            FinancialReport::FORMAT_CSV => 'csv',
            default => 'bin',
        };
    }
}