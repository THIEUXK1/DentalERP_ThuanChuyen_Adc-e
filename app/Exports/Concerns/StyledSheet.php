<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Shared look-and-feel for every workbook this app ships: dark banner block,
 * coloured header tiers, zebra-striped bordered body, money/date number formats
 * and a print setup that actually fits on paper.
 *
 * Exports build their own grid (banner rows included) and then call these helpers
 * from an AfterSheet event, so all sheets across the ERP read the same way.
 */
trait StyledSheet
{
    /** Accent used by the header tier when an export doesn't pick its own. */
    protected string $accent = 'FF312E81';

    protected const MONEY_FMT = '#,##0;[Red]-#,##0';
    protected const PERCENT_FMT = '0.0"%"';

    /**
     * Title block on rows 1-3 (each merged across the sheet) plus a thin spacer row 4.
     * Pass the same $lastCol every export uses for its widest column.
     */
    protected function banner(Worksheet $sheet, string $lastCol, string $title, string $subtitle = '', string $meta = '', string $argb = 'FF1E1B4B'): void
    {
        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('A2', $subtitle);
        $sheet->setCellValue('A3', $meta);

        foreach ([1, 2, 3] as $r) {
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        }

        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(19);
        $sheet->getRowDimension(3)->setRowHeight(17);
        $sheet->getRowDimension(4)->setRowHeight(6);

        $sheet->getStyle("A1:{$lastCol}3")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $argb]],
            'font' => ['color' => ['argb' => 'FFFFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
        $sheet->getStyle('A2')->getFont()->setSize(12)->getColor()->setARGB('FFC7D2FE');
        $sheet->getStyle('A3')->getFont()->setSize(9.5)->getColor()->setARGB('FFA5B4FC');
    }

    /**
     * Merged colour bands above the column headers.
     *
     * @param  array<int,array{0:string,1:string,2:string,3:string}>  $bands  [label, firstCol, lastCol, argb]
     */
    protected function bands(Worksheet $sheet, int $row, array $bands): void
    {
        foreach ($bands as [$label, $first, $last, $argb]) {
            $sheet->setCellValue("{$first}{$row}", $label);
            if ($first !== $last) {
                $sheet->mergeCells("{$first}{$row}:{$last}{$row}");
            }
            $sheet->getStyle("{$first}{$row}:{$last}{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $argb]],
                'font' => ['bold' => true, 'size' => 10.5, 'color' => ['argb' => 'FFFFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    /** The column-name row: solid accent fill, wrapped, centered, thin borders. */
    protected function headerRow(Worksheet $sheet, int $row, string $lastCol, ?string $argb = null, float $height = 32): void
    {
        $sheet->getRowDimension($row)->setRowHeight($height);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $argb ?? $this->accent]],
            'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF4C1D95']]],
        ]);
    }

    /** Thin borders + vertical centering + optional zebra striping over the data block. */
    protected function bodyStyle(Worksheet $sheet, int $firstRow, int $lastRow, string $lastCol, bool $zebra = true, bool $wrap = true): void
    {
        if ($lastRow < $firstRow) {
            return;
        }

        $sheet->getStyle("A{$firstRow}:{$lastCol}{$lastRow}")->applyFromArray([
            'font' => ['size' => 10],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => $wrap],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE5E7EB']]],
        ]);

        if (! $zebra) {
            return;
        }

        for ($r = $firstRow + 1; $r <= $lastRow; $r += 2) {
            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9FAFB');
        }
    }

    /** Amber "TỔNG CỘNG" strip. */
    protected function totalRow(Worksheet $sheet, int $row, string $lastCol): void
    {
        $sheet->getRowDimension($row)->setRowHeight(24);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
            'font' => ['bold' => true, 'size' => 10.5, 'color' => ['argb' => 'FF92400E']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFCD34D']],
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFD97706']],
            ],
        ]);
    }

    /** Medium accent frame around the whole table (header tiers included). */
    protected function frame(Worksheet $sheet, string $range, ?string $argb = null): void
    {
        $sheet->getStyle($range)->getBorders()->getOutline()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB($argb ?? $this->accent);
    }

    /** Thousands-separated currency format (negatives in red) over one or more ranges. */
    protected function money(Worksheet $sheet, string ...$ranges): void
    {
        foreach ($ranges as $range) {
            $sheet->getStyle($range)->getNumberFormat()->setFormatCode(self::MONEY_FMT);
            $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }

    protected function center(Worksheet $sheet, string ...$ranges): void
    {
        foreach ($ranges as $range) {
            $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    /** Tint one cell with a [fill, font] colour pair — used for status chips. */
    protected function chip(Worksheet $sheet, string $cell, string $fill, string $font): void
    {
        $sheet->getStyle($cell)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $fill]],
            'font' => ['bold' => true, 'size' => 9.5, 'color' => ['argb' => $font]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    /** Freeze panes, auto-filter, landscape fit-to-width printing with repeating headers. */
    protected function finishSheet(
        Worksheet $sheet,
        string $freezeCell,
        ?string $filterRange = null,
        int $repeatFrom = 0,
        int $repeatTo = 0,
        string $paper = 'A3',
        string $footer = '',
    ): void {
        $sheet->freezePane($freezeCell);
        if ($filterRange) {
            $sheet->setAutoFilter($filterRange);
        }
        $sheet->setSelectedCell('A1');

        $ps = $sheet->getPageSetup();
        $ps->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $ps->setPaperSize($paper === 'A4' ? PageSetup::PAPERSIZE_A4 : PageSetup::PAPERSIZE_A3);
        $ps->setFitToWidth(1);
        $ps->setFitToHeight(0);
        if ($repeatFrom && $repeatTo) {
            $ps->setRowsToRepeatAtTopByStartAndEnd($repeatFrom, $repeatTo);
        }

        $sheet->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.25)->setRight(0.25);
        $sheet->getHeaderFooter()->setOddFooter('&L'.$footer.'&RTrang &P/&N');
    }

    /** Centered italic grey note used when a report has nothing to show. */
    protected function emptyNotice(Worksheet $sheet, int $row, string $lastCol, string $text = 'Không có dữ liệu phù hợp với bộ lọc.'): void
    {
        $sheet->setCellValue("A{$row}", $text);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF9CA3AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    /** Zero-based column index → spreadsheet letter (0 => A, 26 => AA). */
    protected function col(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
    }
}
