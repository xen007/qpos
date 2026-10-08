<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport extends DefaultValueBinder implements FromArray, WithCustomValueBinder, WithHeadings, WithStyles, WithCharts, WithTitle
{
    public function __construct(private array $rows, private array $columns, private string $type = '') {}
    public function title(): string { return 'QPOS'; }
    public function array(): array { return $this->rows; }
    public function headings(): array { return $this->columns; }
    public function styles(Worksheet $sheet): array { return [1 => ['font' => ['bold' => true]]]; }
    public function bindValue(Cell $cell, mixed $value): bool
    {
        // Exact DECIMAL values; user labels never become spreadsheet formulas.
        $cell->setValueExplicit((string) ($value ?? ''), DataType::TYPE_STRING);
        return true;
    }

    public function charts(): array
    {
        if ($this->type !== 'peaks') return [];
        $series = new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
            \PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_BARCHART,
            \PhpOffice\PhpSpreadsheet\Chart\DataSeries::GROUPING_CLUSTERED,
            [0], [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', 'QPOS!$C$6', null, 1)],
            [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', 'QPOS!$A$7:$A$30', null, 24)],
            [new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('Number', 'QPOS!$C$7:$C$30', null, 24)]
        );
        $series->setPlotDirection(\PhpOffice\PhpSpreadsheet\Chart\DataSeries::DIRECTION_COL);
        $chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart('hourly_profile', new \PhpOffice\PhpSpreadsheet\Chart\Title(__('reporting.peaks')), null, new \PhpOffice\PhpSpreadsheet\Chart\PlotArea(null, [$series]));
        $chart->setTopLeftPosition('F2'); $chart->setBottomRightPosition('V22');
        return [$chart];
    }
}
