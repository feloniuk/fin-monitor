<?php

namespace App\Exports;

use App\Helpers\CurrencyHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SaldoReportExport implements WithMultipleSheets
{
    public function __construct(
        private array $accountReports,
        private array $summaryReport,
        private string $dateFrom,
        private string $dateTo,
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->summaryReport as $summary) {
            $sheets[] = new SaldoSummarySheet($summary, $this->dateFrom, $this->dateTo);
        }

        foreach ($this->accountReports as $report) {
            $sheets[] = new SaldoAccountSheet($report, $this->dateFrom, $this->dateTo);
        }

        return $sheets;
    }
}

class SaldoSummarySheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(
        private array $summary,
        private string $dateFrom,
        private string $dateTo,
    ) {}

    public function title(): string
    {
        $code = CurrencyHelper::code($this->summary['currency_code']);
        return "Зведено ({$code})";
    }

    public function array(): array
    {
        $cc = $this->summary['currency_code'];
        $symbol = CurrencyHelper::symbol($cc);

        $rows = [];
        $rows[] = ["Зведений звіт по рахунках ({$symbol})", '', '', '', ''];
        $rows[] = ["Період: {$this->dateFrom} — {$this->dateTo}", '', '', '', ''];
        $rows[] = ["Рахунків: {$this->summary['account_count']}", '', '', '', ''];
        $rows[] = [''];
        $rows[] = ['Період', 'Вхідний залишок', 'Доходи', 'Витрати', 'Вихідний залишок'];

        foreach ($this->summary['periods'] as $row) {
            $rows[] = [
                $row['period'],
                $row['opening_balance'] / 100,
                $row['income'] / 100,
                abs($row['expense']) / 100,
                $row['closing_balance'] / 100,
            ];
        }

        $rows[] = [''];
        $rows[] = ['Поточний залишок (з API)', '', '', '', $this->summary['current_balance'] / 100];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->array());
        $headerRow = 5;

        $sheet->getStyle("A1")->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle("A{$headerRow}:E{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:E{$headerRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4F46E5');
        $sheet->getStyle("A{$headerRow}:E{$headerRow}")->getFont()->getColor()->setRGB('FFFFFF');

        $sheet->getStyle("B6:E{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("A{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("E{$lastRow}")->getFont()->setBold(true);

        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 20, 'B' => 22, 'C' => 18, 'D' => 18, 'E' => 22];
    }
}

class SaldoAccountSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(
        private array $report,
        private string $dateFrom,
        private string $dateTo,
    ) {}

    public function title(): string
    {
        return mb_substr($this->report['account_name'], 0, 31);
    }

    public function array(): array
    {
        $cc = $this->report['currency_code'];
        $symbol = CurrencyHelper::symbol($cc);

        $rows = [];
        $rows[] = [$this->report['account_name'], '', '', '', ''];
        $rows[] = ['Валюта:', CurrencyHelper::code($cc) . " ({$symbol})", '', '', ''];
        $rows[] = ['Період:', "{$this->dateFrom} — {$this->dateTo}", '', '', ''];
        $rows[] = ['Транзакцій за період:', $this->report['transaction_count'], '', '', ''];
        $rows[] = ['Поточний залишок:', $this->report['current_balance'] / 100, '', '', ''];
        $rows[] = [''];
        $rows[] = ['Період', 'Вхідний залишок', 'Доходи', 'Витрати', 'Вихідний залишок'];

        foreach ($this->report['periods'] as $row) {
            $rows[] = [
                $row['period'],
                $row['opening_balance'] / 100,
                $row['income'] / 100,
                abs($row['expense']) / 100,
                $row['closing_balance'] / 100,
            ];
        }

        $rows[] = [''];
        $rows[] = ['Поточний залишок (з API)', '', '', '', $this->report['current_balance'] / 100];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $rows = $this->array();
        $lastRow = count($rows);
        $headerRow = 7;

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        // Meta labels bold
        foreach ([2, 3, 4, 5] as $r) {
            $sheet->getStyle("A{$r}")->getFont()->setBold(true);
        }

        // Meta values: currency/current balance as number format
        $sheet->getStyle('B5')->getNumberFormat()->setFormatCode('#,##0.00');

        // Table header
        $sheet->getStyle("A{$headerRow}:E{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:E{$headerRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('6B7280');
        $sheet->getStyle("A{$headerRow}:E{$headerRow}")->getFont()->getColor()->setRGB('FFFFFF');

        // Data cells as numbers
        $dataStart = $headerRow + 1;
        $dataEnd = $lastRow - 2;
        if ($dataEnd >= $dataStart) {
            $sheet->getStyle("B{$dataStart}:E{$dataEnd}")->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // Footer row
        $sheet->getStyle("A{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("E{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("E{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');

        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 22, 'B' => 22, 'C' => 18, 'D' => 18, 'E' => 22];
    }
}
