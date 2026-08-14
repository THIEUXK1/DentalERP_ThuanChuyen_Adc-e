<?php

namespace App\Exports;

use App\Exports\Concerns\StyledSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Generic styled sheet for list screens that filter and sort entirely in the browser
 * (hóa đơn, kế hoạch điều trị, …). The page posts the rows it is currently showing
 * plus a column spec, so the workbook always matches the screen exactly instead of
 * the server re-deriving a dozen filters.
 *
 * Every cell is written with setCellValueExplicit: values arrive from the client, and
 * a text cell must never be interpreted as a formula.
 */
class TableExport implements FromArray, WithEvents, WithTitle
{
    use StyledSheet;

    private const ROW_BAND = 5;
    private const ROW_HEAD = 6;
    private const ROW_DATA = 7;

    /** Column type => [horizontal alignment, is numeric]. */
    private const TYPES = [
        'money' => [Alignment::HORIZONTAL_RIGHT, true],
        'number' => [Alignment::HORIZONTAL_CENTER, true],
        'date' => [Alignment::HORIZONTAL_CENTER, false],
        'status' => [Alignment::HORIZONTAL_CENTER, false],
        'code' => [Alignment::HORIZONTAL_CENTER, false],
        'text' => [Alignment::HORIZONTAL_LEFT, false],
    ];

    /** Chip palette shared with the rest of the app's exports. */
    private const PALETTE = [
        'green' => ['FFD1FAE5', 'FF047857'],
        'emerald' => ['FFD1FAE5', 'FF047857'],
        'teal' => ['FFCCFBF1', 'FF0F766E'],
        'blue' => ['FFDBEAFE', 'FF1D4ED8'],
        'indigo' => ['FFE0E7FF', 'FF4338CA'],
        'violet' => ['FFEDE9FE', 'FF6D28D9'],
        'purple' => ['FFF3E8FF', 'FF7E22CE'],
        'pink' => ['FFFCE7F3', 'FFBE185D'],
        'red' => ['FFFEE2E2', 'FFB91C1C'],
        'rose' => ['FFFFE4E6', 'FFBE123C'],
        'orange' => ['FFFFEDD5', 'FFC2410C'],
        'amber' => ['FFFEF3C7', 'FF92400E'],
        'yellow' => ['FFFEF9C3', 'FF854D0E'],
        'gray' => ['FFF3F4F6', 'FF4B5563'],
        'slate' => ['FFF1F5F9', 'FF475569'],
    ];

    private string $lastCol;

    /**
     * @param  array<int,array{key:string,label:string,type?:string,width?:float,total?:bool,colors?:array<string,string>}>  $columns
     * @param  array<int,array<string,mixed>>  $rows
     * @param  array<int,array{label:string,first:string,last:string,color?:string}>  $bands
     */
    public function __construct(
        private string $heading,
        private string $subtitle,
        private string $meta,
        private array $columns,
        private array $rows,
        private array $bands = [],
        private string $sheetTitle = 'Dữ liệu',
        private string $accentColor = 'FF312E81',
    ) {
        $this->accent = $accentColor;
        // Column A is a generated "STT", so the spec's columns start at B.
        $this->lastCol = $this->col(count($this->columns));
    }

    public function title(): string
    {
        return mb_substr($this->sheetTitle, 0, 31);
    }

    /** The sheet is written cell by cell in AfterSheet; this only reserves the grid. */
    public function array(): array
    {
        return [[null]];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = $this->lastCol;
                $count = count($this->rows);

                $this->banner($sheet, $last, $this->heading, $this->subtitle, $this->meta, $this->darken());

                if ($this->bands) {
                    $this->bands($sheet, self::ROW_BAND, array_map(
                        fn ($b) => [$b['label'], $b['first'], $b['last'], $b['color'] ?? $this->accent],
                        $this->bands,
                    ));
                }

                $this->writeHeader($sheet);
                $this->headerRow($sheet, self::ROW_HEAD, $last);

                $sheet->getColumnDimension('A')->setWidth(6);
                foreach ($this->columns as $i => $c) {
                    $sheet->getColumnDimension($this->col($i + 1))->setWidth((float) ($c['width'] ?? 18));
                }

                if ($count === 0) {
                    $this->emptyNotice($sheet, self::ROW_DATA, $last);
                    $this->finishSheet($sheet, 'A'.self::ROW_DATA, footer: $this->subtitle);

                    return;
                }

                $this->writeRows($sheet);

                $firstRow = self::ROW_DATA;
                $lastRow = $firstRow + $count - 1;
                $this->bodyStyle($sheet, $firstRow, $lastRow, $last, wrap: false);
                $this->center($sheet, "A{$firstRow}:A{$lastRow}");
                $this->applyColumnStyles($sheet, $firstRow, $lastRow);

                $bottom = $lastRow;
                if ($this->hasTotals()) {
                    $bottom = $this->writeTotals($sheet, $lastRow + 1);
                }

                $this->frame($sheet, 'A'.(self::ROW_BAND).":{$last}{$bottom}");

                $this->finishSheet(
                    $sheet,
                    freezeCell: 'C'.self::ROW_DATA,
                    filterRange: 'A'.self::ROW_HEAD.":{$last}".self::ROW_HEAD,
                    repeatFrom: $this->bands ? self::ROW_BAND : self::ROW_HEAD,
                    repeatTo: self::ROW_HEAD,
                    footer: $this->subtitle,
                );
                $sheet->setSelectedCell('A'.self::ROW_DATA);
            },
        ];
    }

    private function writeHeader($sheet): void
    {
        $sheet->setCellValueExplicit('A'.self::ROW_HEAD, 'STT', DataType::TYPE_STRING);
        foreach ($this->columns as $i => $c) {
            $sheet->setCellValueExplicit($this->col($i + 1).self::ROW_HEAD, (string) $c['label'], DataType::TYPE_STRING);
        }
    }

    private function writeRows($sheet): void
    {
        foreach ($this->rows as $r => $row) {
            $line = self::ROW_DATA + $r;
            $sheet->setCellValueExplicit("A{$line}", $r + 1, DataType::TYPE_NUMERIC);

            foreach ($this->columns as $i => $c) {
                $ref = $this->col($i + 1).$line;
                $value = $row[$c['key']] ?? null;
                $type = $c['type'] ?? 'text';

                if ($value === null || $value === '') {
                    continue;
                }

                if (self::TYPES[$type][1] ?? false) {
                    $sheet->setCellValueExplicit($ref, (float) $value, DataType::TYPE_NUMERIC);

                    continue;
                }

                $sheet->setCellValueExplicit($ref, (string) $value, DataType::TYPE_STRING);

                if ($type === 'status' && ! empty($c['colors'])) {
                    $palette = self::PALETTE[$c['colors'][(string) $value] ?? 'gray'] ?? self::PALETTE['gray'];
                    $this->chip($sheet, $ref, ...$palette);
                }
            }
        }
    }

    private function applyColumnStyles($sheet, int $firstRow, int $lastRow): void
    {
        foreach ($this->columns as $i => $c) {
            $col = $this->col($i + 1);
            $type = $c['type'] ?? 'text';
            [$align] = self::TYPES[$type] ?? self::TYPES['text'];

            $sheet->getStyle("{$col}{$firstRow}:{$col}{$lastRow}")->getAlignment()->setHorizontal($align);

            if ($type === 'money') {
                $this->money($sheet, "{$col}{$firstRow}:{$col}{$lastRow}");
            }
            if (! empty($c['bold'])) {
                $sheet->getStyle("{$col}{$firstRow}:{$col}{$lastRow}")->getFont()->setBold(true);
            }
        }
    }

    private function hasTotals(): bool
    {
        foreach ($this->columns as $c) {
            if (! empty($c['total'])) {
                return true;
            }
        }

        return false;
    }

    private function writeTotals($sheet, int $row): int
    {
        $sheet->setCellValueExplicit("A{$row}", 'TỔNG', DataType::TYPE_STRING);
        $labelled = false;

        foreach ($this->columns as $i => $c) {
            $col = $this->col($i + 1);

            if (empty($c['total'])) {
                // Put the row count in the first non-summed column so the strip reads clearly.
                if (! $labelled && ($c['type'] ?? 'text') === 'text') {
                    $sheet->setCellValueExplicit("{$col}{$row}", count($this->rows).' dòng', DataType::TYPE_STRING);
                    $labelled = true;
                }

                continue;
            }

            $sum = array_sum(array_map(fn ($r) => (float) ($r[$c['key']] ?? 0), $this->rows));
            $sheet->setCellValueExplicit("{$col}{$row}", $sum, DataType::TYPE_NUMERIC);
            if (($c['type'] ?? '') === 'money') {
                $this->money($sheet, "{$col}{$row}");
            }
        }

        $this->totalRow($sheet, $row, $this->lastCol);

        return $row;
    }

    /** Banner fill: a deeper shade of the caller's accent, matching the built-in reports. */
    private function darken(): string
    {
        return match ($this->accentColor) {
            'FF0F766E', 'FF115E59' => 'FF042F2E',
            'FF1E3A8A', 'FF1E3A5F' => 'FF0C1E4A',
            'FFB45309' => 'FF422006',
            default => 'FF1E1B4B',
        };
    }
}
