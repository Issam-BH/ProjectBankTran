<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdf;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function createCsvResponse(array $data, array $headers, string $title): StreamedResponse
    {
        $spreadsheet = $this->createSpreadsheet($data, $headers, $title);
        $writer = new Csv($spreadsheet);
        $writer->setUseBOM(true);
        $writer->setDelimiter(';');

        return $this->createResponse($writer, 'csv');
    }

    public function createXlsResponse(array $data, array $headers, string $title): StreamedResponse
    {
        $spreadsheet = $this->createSpreadsheet($data, $headers, $title);
        $writer = new Xls($spreadsheet);

        return $this->createResponse($writer, 'xls');
    }

    public function createPdfResponse(array $data, array $headers, string $title): StreamedResponse
    {
        $spreadsheet = $this->createSpreadsheet($data, $headers, $title);
        $writer = new Tcpdf($spreadsheet);
        $writer->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $writer->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

        return $this->createResponse($writer, 'pdf');
    }

    private function createSpreadsheet(array $data, array $headers, string $title): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title
        $sheet->mergeCells('A1:' . chr(ord('A') + count($headers) - 1) . '1');
        $sheet->setCellValue('A1', mb_strtoupper($title));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Extraction date
        $extractionDate = "EXTRAIT DU " . (new \DateTime())->format('d/m/Y H:i');
        $sheet->mergeCells('A2:' . chr(ord('A') + count($headers) - 1) . '2');
        $sheet->setCellValue('A2', $extractionDate);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Headers
        $sheet->fromArray($headers, null, 'A4');
        $sheet->getStyle('A4:' . chr(ord('A') + count($headers) - 1) . '4')->getFont()->setBold(true);

        // Data
        $sheet->fromArray($data, null, 'A5');

        // Auto size columns
        foreach (range('A', chr(ord('A') + count($headers) - 1)) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    private function createResponse(\PhpOffice\PhpSpreadsheet\Writer\IWriter $writer, string $extension): StreamedResponse
    {
        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );

        $filename = 'export-remises-' . date('Y-m-d') . '.' . $extension;
        $contentType = match ($extension) {
            'csv' => 'text/csv',
            'xls' => 'application/vnd.ms-excel',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };

        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
