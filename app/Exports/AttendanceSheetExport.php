<?php

namespace App\Exports;

use App\Exports\Concerns\StyledSheet;
use App\Models\AttendancePeriod;
use App\Models\AttendanceSymbol;
use App\Services\AttendanceSummaryService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Monthly attendance grid: one column per day (with a weekday strip and shaded
 * weekends), symbol cells tinted with the symbol's own colour, per-employee totals
 * on the right, and a legend printed underneath.
 */
class AttendanceSheetExport implements FromArray, WithColumnWidths, WithEvents, WithStrictNullComparison, WithTitle
{
    use StyledSheet;

    private const ROW_BAND = 5;
    private const ROW_HEAD = 6;
    private const ROW_WEEKDAY = 7;
    private const ROW_DATA = 8;

    /** Fixed employee columns before the day grid. */
    private const LEAD_COLS = 5;

    /** Summary columns after the day grid. */
    private const TAIL = ['Công', 'Nghỉ hưởng lương', 'Nghỉ không lương', 'OT (giờ)', 'Tổng công'];

    /** Tailwind-ish palette name => [fill, font] used for symbol cells and the legend. */
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

    private array $employees;
    private array $grid;
    private array $summaries;
    private array $symbols;
    private int $daysInMonth;
    private string $lastCol;

    public function __construct(
        private AttendancePeriod $period,
        array $employees,
        AttendanceSummaryService $summaryService
    ) {
        $this->accent = 'FF1E3A5F';
        $this->daysInMonth = $period->daysInMonth();
        $this->employees = $employees;
        $this->grid = $summaryService->buildGrid($period);
        $this->summaries = $summaryService->summarize($period);
        $this->symbols = AttendanceSymbol::activeMap();
        $this->lastCol = $this->col(self::LEAD_COLS + $this->daysInMonth + count(self::TAIL) - 1);
    }

    public function title(): string
    {
        return "CC-{$this->period->year}".str_pad((string) $this->period->month, 2, '0', STR_PAD_LEFT);
    }

    public function columnWidths(): array
    {
        $w = ['A' => 5, 'B' => 11, 'C' => 24, 'D' => 18, 'E' => 18];
        for ($d = 0; $d < $this->daysInMonth; $d++) {
            $w[$this->col(self::LEAD_COLS + $d)] = 5.5;
        }
        for ($i = 0; $i < count(self::TAIL); $i++) {
            $w[$this->col(self::LEAD_COLS + $this->daysInMonth + $i)] = 11;
        }

        return $w;
    }

    public function array(): array
    {
        $width = self::LEAD_COLS + $this->daysInMonth + count(self::TAIL);
        $blank = array_fill(0, $width, null);

        $header = ['STT', 'Mã NV', 'Họ và tên', 'Chức vụ', 'Phòng ban'];
        $weekday = array_fill(0, self::LEAD_COLS, null);
        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            $header[] = $d;
            $weekday[] = $this->weekdayLabel($d);
        }
        $header = array_merge($header, self::TAIL);
        $weekday = array_merge($weekday, array_fill(0, count(self::TAIL), null));

        $grid = [$blank, $blank, $blank, $blank, $this->bandRow($width), $header, $weekday];

        $sums = ['cong' => 0.0, 'nghi_hl' => 0.0, 'nghi_kl' => 0.0, 'ot_hours' => 0.0, 'total' => 0.0];

        foreach ($this->employees as $i => $emp) {
            $row = [$i + 1, $emp['code'], $emp['full_name'], $emp['position'] ?? '', $emp['department'] ?? ''];

            for ($d = 1; $d <= $this->daysInMonth; $d++) {
                $row[] = $this->cellSymbol($emp['id'], $d);
            }

            $s = $this->summaryFor($emp['id']);
            $row = array_merge($row, [$s['cong'], $s['nghi_hl'], $s['nghi_kl'], $s['ot_hours'], $s['total']]);
            $grid[] = $row;

            foreach ($sums as $k => $v) {
                $sums[$k] = $v + ($s[$k] ?? 0);
            }
        }

        if ($this->employees) {
            $totalRow = $blank;
            $totalRow[0] = 'TỔNG CỘNG';
            $totalRow[2] = count($this->employees).' nhân viên';
            $i = self::LEAD_COLS + $this->daysInMonth;
            foreach (['cong', 'nghi_hl', 'nghi_kl', 'ot_hours', 'total'] as $k) {
                $totalRow[$i++] = round($sums[$k], 1);
            }
            $grid[] = $totalRow;
        }

        // Legend: blank spacer, caption, then one row per active symbol.
        $grid[] = $blank;
        $caption = $blank;
        $caption[0] = 'CHÚ THÍCH KÝ HIỆU';
        $grid[] = $caption;
        foreach ($this->symbols as $sym) {
            $line = $blank;
            $line[0] = $sym['display'] ?? $sym['code'];
            $line[1] = $sym['label'];
            $line[3] = $sym['paid_workday'] > 0 ? 'Tính công: '.$sym['paid_workday'] : ($sym['is_overtime'] ? 'Tăng ca (giờ)' : 'Không tính công');
            $grid[] = $line;
        }

        return $grid;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = $this->lastCol;
                $p = $this->period;

                $this->banner(
                    $sheet, $last,
                    'BẢNG CHẤM CÔNG THÁNG '.str_pad((string) $p->month, 2, '0', STR_PAD_LEFT).'/'.$p->year,
                    $p->code ? 'Kỳ chấm công '.$p->code : '',
                    count($this->employees).' nhân viên    |    '.$this->daysInMonth.' ngày'
                        .'    |    Xuất lúc '.now()->format('H:i d/m/Y'),
                    'FF0B2239',
                );

                $this->bands($sheet, self::ROW_BAND, $this->bandDefs());
                $this->headerRow($sheet, self::ROW_HEAD, $last, height: 26);
                $this->styleWeekdayRow($sheet);

                if (! $this->employees) {
                    $this->emptyNotice($sheet, self::ROW_DATA, $last, 'Chưa có nhân viên nào trong kỳ chấm công này.');
                    $this->finishSheet($sheet, 'F'.self::ROW_DATA, footer: (string) $p->code);

                    return;
                }

                $first = self::ROW_DATA;
                $lastRow = $first + count($this->employees) - 1;

                $this->bodyStyle($sheet, $first, $lastRow, $last, wrap: false);
                $this->center($sheet, "A{$first}:B{$lastRow}", $this->col(self::LEAD_COLS)."{$first}:{$last}{$lastRow}");
                $sheet->getStyle("C{$first}:C{$lastRow}")->getFont()->setBold(true);

                $this->paintDayCells($sheet, $first);
                $this->shadeWeekends($sheet, $first, $lastRow);

                $totalRow = $lastRow + 1;
                $this->totalRow($sheet, $totalRow, $last);
                $this->frame($sheet, 'A'.self::ROW_BAND.":{$last}{$totalRow}");

                $this->styleLegend($sheet, $totalRow + 2);

                $this->finishSheet(
                    $sheet,
                    freezeCell: $this->col(self::LEAD_COLS).self::ROW_DATA,
                    filterRange: null,
                    repeatFrom: self::ROW_BAND,
                    repeatTo: self::ROW_WEEKDAY,
                    footer: 'Chấm công '.$p->month.'/'.$p->year,
                );
                $sheet->setSelectedCell('A'.self::ROW_DATA);
            },
        ];
    }

    /** Grey "T2 … CN" strip under the day numbers, weekends in red. */
    private function styleWeekdayRow($sheet): void
    {
        $row = self::ROW_WEEKDAY;
        $sheet->getRowDimension($row)->setRowHeight(16);
        $sheet->getStyle("A{$row}:{$this->lastCol}{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEFF6FF']],
            'font' => ['size' => 8.5, 'bold' => true, 'color' => ['argb' => 'FF64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            if ($this->isWeekend($d)) {
                $cell = $this->col(self::LEAD_COLS + $d - 1).$row;
                $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFDC2626');
            }
        }
    }

    /** Tint each filled day cell with its symbol colour. */
    private function paintDayCells($sheet, int $firstRow): void
    {
        foreach ($this->employees as $i => $emp) {
            $row = $firstRow + $i;
            for ($d = 1; $d <= $this->daysInMonth; $d++) {
                $cell = $this->grid[$emp['id']][$this->dateKey($d)] ?? null;
                if (! $cell || ! $cell['symbol']) {
                    continue;
                }
                [$fill, $font] = self::PALETTE[$this->symbols[$cell['symbol']]['color'] ?? 'gray'] ?? self::PALETTE['gray'];
                $ref = $this->col(self::LEAD_COLS + $d - 1).$row;
                $sheet->getStyle($ref)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $fill]],
                    'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => $font]],
                ]);
            }
        }
    }

    /** Light blue wash over Saturday/Sunday columns, drawn under the symbol tints. */
    private function shadeWeekends($sheet, int $firstRow, int $lastRow): void
    {
        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            if (! $this->isWeekend($d)) {
                continue;
            }
            $col = $this->col(self::LEAD_COLS + $d - 1);
            for ($r = $firstRow; $r <= $lastRow; $r++) {
                $current = $sheet->getStyle("{$col}{$r}")->getFill();
                if ($current->getFillType() === Fill::FILL_SOLID
                    && $current->getStartColor()->getARGB() !== 'FFFFFFFF'
                    && $current->getStartColor()->getARGB() !== 'FFF9FAFB') {
                    continue; // a symbol already claimed this cell
                }
                $sheet->getStyle("{$col}{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
            }
        }
    }

    private function styleLegend($sheet, int $captionRow): void
    {
        $sheet->getStyle("A{$captionRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF1E3A5F']],
        ]);

        $row = $captionRow + 1;
        foreach ($this->symbols as $sym) {
            [$fill, $font] = self::PALETTE[$sym['color'] ?? 'gray'] ?? self::PALETTE['gray'];
            $this->chip($sheet, "A{$row}", $fill, $font);
            $sheet->getStyle("B{$row}")->getFont()->setSize(10);
            $sheet->getStyle("D{$row}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
            $row++;
        }
    }

    /** Display symbol for one employee/day, marking overtime worked on a normal day. */
    private function cellSymbol(int $employeeId, int $day): string
    {
        $cell = $this->grid[$employeeId][$this->dateKey($day)] ?? null;
        if (! $cell) {
            return '';
        }

        $symbol = $cell['symbol'] === 'O' ? 'Ô' : $cell['symbol'];
        if ($cell['overtime_hours'] > 0 && $cell['symbol'] === 'X') {
            $symbol = 'X+OT';
        }

        return (string) $symbol;
    }

    private function summaryFor(int $employeeId): array
    {
        return $this->summaries[$employeeId] ?? ['cong' => 0, 'nghi_hl' => 0, 'nghi_kl' => 0, 'ot_hours' => 0, 'total' => 0];
    }

    private function dateKey(int $day): string
    {
        return sprintf('%d-%02d-%02d', $this->period->year, $this->period->month, $day);
    }

    private function weekdayLabel(int $day): string
    {
        $dow = (int) date('w', mktime(0, 0, 0, $this->period->month, $day, $this->period->year));

        return ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][$dow];
    }

    private function isWeekend(int $day): bool
    {
        $dow = (int) date('w', mktime(0, 0, 0, $this->period->month, $day, $this->period->year));

        return $dow === 0 || $dow === 6;
    }

    /** @return array<int,array{0:string,1:string,2:string,3:string}> */
    private function bandDefs(): array
    {
        $dayFirst = $this->col(self::LEAD_COLS);
        $dayLast = $this->col(self::LEAD_COLS + $this->daysInMonth - 1);
        $sumFirst = $this->col(self::LEAD_COLS + $this->daysInMonth);

        return [
            ['NHÂN VIÊN', 'A', 'E', 'FF1E3A5F'],
            ['NGÀY TRONG THÁNG '.str_pad((string) $this->period->month, 2, '0', STR_PAD_LEFT).'/'.$this->period->year, $dayFirst, $dayLast, 'FF0F766E'],
            ['TỔNG HỢP', $sumFirst, $this->lastCol, 'FFB45309'],
        ];
    }

    private function bandRow(int $width): array
    {
        $row = array_fill(0, $width, null);
        $row[0] = 'NHÂN VIÊN';
        $row[self::LEAD_COLS] = 'NGÀY TRONG THÁNG';
        $row[self::LEAD_COLS + $this->daysInMonth] = 'TỔNG HỢP';

        return $row;
    }
}
