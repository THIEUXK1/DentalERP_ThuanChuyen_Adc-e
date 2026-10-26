<?php

namespace App\Exports;

use App\Exports\Concerns\StyledSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * File Excel lịch hẹn cho Reports/DailySchedule — bản gọn theo yêu cầu lễ tân:
 * chỉ Họ tên, Năm sinh, SĐT, Ghi chú (bản cũ 26 cột quá rối mắt khi in/gọi điện).
 * Cột "Ngày" chỉ thêm khi xuất nhiều ngày, nếu không sẽ không biết lịch thuộc ngày nào.
 */
class DailyScheduleExport implements FromArray, WithColumnWidths, WithEvents, WithStrictNullComparison, WithTitle
{
    use StyledSheet;

    /** Banner chiếm dòng 1-4; tiêu đề cột ở dòng 5, dữ liệu từ dòng 6. */
    private const ROW_HEAD = 5;

    private const ROW_DATA = 6;

    /**
     * @param  array<int,array<string,mixed>>  $rows  Normalized appointment rows.
     * @param  array<string,mixed>  $meta  ['range' => ..., 'filters' => ..., 'multi_day' => bool]
     * @param  array<string,int>  $statusCounts  Label => count, rendered in the banner.
     */
    public function __construct(
        private array $rows,
        private array $meta = [],
        private array $statusCounts = [],
    ) {}

    public function title(): string
    {
        return 'Lịch hẹn';
    }

    private function multiDay(): bool
    {
        return (bool) ($this->meta['multi_day'] ?? false);
    }

    /** @return array<int,string> */
    private function headers(): array
    {
        return $this->multiDay()
            ? ['STT', 'Ngày', 'Họ và tên', 'Năm sinh', 'Số điện thoại', 'Ghi chú lịch hẹn']
            : ['STT', 'Họ và tên', 'Năm sinh', 'Số điện thoại', 'Ghi chú lịch hẹn'];
    }

    private function lastCol(): string
    {
        return $this->col(count($this->headers()) - 1);
    }

    public function columnWidths(): array
    {
        return $this->multiDay()
            ? ['A' => 6, 'B' => 12, 'C' => 30, 'D' => 10, 'E' => 15, 'F' => 55]
            : ['A' => 6, 'B' => 30, 'C' => 10, 'D' => 15, 'E' => 55];
    }

    public function array(): array
    {
        $blank = array_fill(0, count($this->headers()), null);

        // Dòng 1-4 do banner() ghi; giữ chỗ để lưới không lệch.
        $grid = [$blank, $blank, $blank, $blank, $this->headers()];

        foreach ($this->rows as $i => $r) {
            $grid[] = array_values(array_filter([
                $i + 1,
                $this->multiDay() ? $r['date_label'] : false,
                $r['patient'],
                $r['patient_birth_year'],
                $r['patient_phone'],
                $r['notes'],
            ], fn ($v) => $v !== false));
        }

        return $grid;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = $this->lastCol();
                $rowCount = count($this->rows);
                $lastRow = self::ROW_DATA + max($rowCount, 1) - 1;

                $summary = collect($this->statusCounts)->map(fn ($n, $label) => "{$label}: {$n}")->implode('   •   ');

                $this->banner(
                    $sheet, $last,
                    'DANH SÁCH LỊCH HẸN',
                    $this->meta['range'] ?? '',
                    trim(($this->meta['filters'] ?? '').'    |    Tổng: '.$rowCount.' lịch hẹn'
                        .($summary !== '' ? '    |    '.$summary : ''), ' |'),
                );

                $this->headerRow($sheet, self::ROW_HEAD, $last);

                if ($rowCount === 0) {
                    $this->emptyNotice($sheet, self::ROW_DATA, $last, 'Không có lịch hẹn nào phù hợp với bộ lọc.');
                } else {
                    $this->styleBody($sheet, $lastRow);
                    $this->frame($sheet, 'A'.self::ROW_HEAD.":{$last}{$lastRow}");
                }

                $this->finishSheet(
                    $sheet,
                    freezeCell: 'A'.self::ROW_DATA,
                    filterRange: 'A'.self::ROW_HEAD.":{$last}".self::ROW_HEAD,
                    repeatFrom: self::ROW_HEAD,
                    repeatTo: self::ROW_HEAD,
                    paper: 'A4',
                    footer: $this->meta['range'] ?? '',
                );
                $sheet->setSelectedCell('A'.self::ROW_DATA);
            },
        ];
    }

    private function styleBody($sheet, int $lastRow): void
    {
        $last = $this->lastCol();
        $first = self::ROW_DATA;
        $nameCol = $this->multiDay() ? 'C' : 'B';
        $yearCol = $this->multiDay() ? 'D' : 'C';

        $this->bodyStyle($sheet, $first, $lastRow, $last);

        $this->center($sheet, "A{$first}:A{$lastRow}", "{$yearCol}{$first}:{$yearCol}{$lastRow}");
        if ($this->multiDay()) {
            $this->center($sheet, "B{$first}:B{$lastRow}");
        }
        $sheet->getStyle("{$nameCol}{$first}:{$nameCol}{$lastRow}")->getFont()->setBold(true);

        for ($row = $first; $row <= $lastRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(-1);
        }
    }
}
