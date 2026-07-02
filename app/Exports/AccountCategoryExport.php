<?php

namespace App\Exports;

use App\Models\AccountCategory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AccountCategoryExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new AccountCategoryDataSheet(),
            new AccountCategoryTemplateSheet(),
        ];
    }
}

class AccountCategoryDataSheet implements
    FromCollection,
    WithHeadings,
    WithTitle,
    WithStyles,
    ShouldAutoSize
{
    public function title(): string { return 'Existing Categories'; }

    public function collection()
    {
        return AccountCategory::orderBy('name')
            ->get()
            ->map(fn($c) => [
                $c->id,
                $c->code,
                $c->name,
                $c->budget_type,
                $c->description,
                $c->is_active ? 'Yes' : 'No',
                $c->accountCodes()->count(),
            ]);
    }

    public function headings(): array
    {
        return ['ID', 'Code', 'Name', 'Budget Type', 'Description', 'Active', 'Code Count'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getColumnDimension('A')->setVisible(false);

        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:G' . $highestRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);

        return [];
    }
}

class AccountCategoryTemplateSheet implements
    WithTitle,
    WithStyles,
    ShouldAutoSize
{
    // All valid budget_type values
    private const VALID_TYPES = ['revenue', 'expense', 'both', 'assets', 'liabilities', 'capital_expenditure'];

    public function title(): string { return 'Import Template'; }

    public function styles(Worksheet $sheet)
    {
        // Headers: A=code, B=name, C=budget_type, D=description
        $sheet->fromArray([['code', 'name', 'budget_type', 'description']], null, 'A1');

        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Sample row
        $sheet->fromArray([['OPEX', 'Operating Expenses', 'expense', 'Day-to-day operational costs']], null, 'A2');
        $sheet->getStyle('A2:D2')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF9C4']],
            'font' => ['color' => ['argb' => 'FF666666']],
        ]);

        // Data validation dropdown for budget_type (column C), rows 2–200
        $typeList = '"' . implode(',', self::VALID_TYPES) . '"';
        for ($row = 2; $row <= 200; $row++) {
            $dv = $sheet->getCell("C{$row}")->getDataValidation();
            $dv->setType(DataValidation::TYPE_LIST);
            $dv->setErrorStyle(DataValidation::STYLE_STOP);
            $dv->setAllowBlank(false);
            $dv->setShowDropDown(true);
            $dv->setShowInputMessage(true);
            $dv->setPromptTitle('Budget Type');
            $dv->setPrompt('Pick one: ' . implode(', ', self::VALID_TYPES));
            $dv->setShowErrorMessage(true);
            $dv->setErrorTitle('Invalid type');
            $dv->setError('Must be one of: ' . implode(', ', self::VALID_TYPES));
            $dv->setFormula1($typeList);
        }

        // Helper text
        $sheet->setCellValue('F1', '← Sample row (delete before uploading).');
        $sheet->setCellValue('F2', 'budget_type values: revenue | expense | both | assets | liabilities | capital_expenditure');
        $sheet->getStyle('F1:F2')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF999999'], 'size' => 10],
        ]);

        $highestRow = max(2, $sheet->getHighestDataRow());
        $sheet->getStyle("A1:D{$highestRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);

        return [];
    }
}
