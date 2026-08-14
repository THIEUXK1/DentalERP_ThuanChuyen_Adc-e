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
 * "Dữ liệu hệ thống" workbook — the unified service + payment log, with the money
 * columns totalled and both the record type and the status rendered as colour chips.
 */
class SystemRecordExport implements FromArray, WithColumnWidths, WithEvents, WithStrictNullComparison, WithTitle
{
    use StyledSheet;

    private const ROW_BAND = 5;
    private const ROW_HEAD = 6;
    private const ROW_DATA = 7;

    private const LAST_COL = 'T';

    private const BANDS = [
        ['CHỨNG TỪ', 'A', 'D', 'FF0F766E'],
        ['KHÁCH HÀNG', 'E', 'I', 'FFB45309'],
        ['NỘI DUNG', 'J', 'K', 'FF4338CA'],
        ['GIÁ TRỊ', 'L', 'O', 'FF15803D'],
        ['PHÂN CÔNG / TRẠNG THÁI', 'P', 'T', 'FF7E22CE'],
    ];

    private const HEADERS = [
        'STT', 'Ngày', 'Giờ', 'Loại',
        'Mã KH', 'Tên khách hàng', 'Số điện thoại', 'Nguồn khách', 'Chi nhánh',
        'Diễn giải', 'Ghi chú',
        'Đơn giá', 'SL', 'Khuyến mại', 'Thành tiền',
        'Chứng từ', 'Bác sĩ', 'Tư vấn', 'Trợ thủ', 'Trạng thái',
    ];

    /** @param array<string,string> $meta ['range' => ..., 'filters' => ...] */
    public function __construct(private array $rows, private array $meta = [])
    {
        $this->accent = 'FF115E59';
    }

    public function title(): string
    {
        return 'Dữ liệu hệ thống';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6, 'B' => 12, 'C' => 8, 'D' => 13,
            'E' => 12, 'F' => 26, 'G' => 15, 'H' => 15, 'I' => 18,
            'J' => 34, 'K' => 26,
            'L' => 14, 'M' => 6, 'N' => 13, 'O' => 15,
            'P' => 15, 'Q' => 20, 'R' => 20, 'S' => 20, 'T' => 16,
        ];
    }

    public function array(): array
    {
        $blank = array_fill(0, count(self::HEADERS), null);
        $grid = [$blank, $blank, $blank, $blank, $this->bandRow(), self::HEADERS];

        foreach ($this->rows as $i => $r) {
            $grid[] = [
                $i + 1,
                \Carbon\Carbon::parse($r['record_date'])->format('d/m/Y'),
                $r['record_time'],
                $r['record_type_label'],
                $r['patient_code'],
                $r['patient_name'],
                $r['phone'],
                $r['source'],
                $r['branch_name'],
                $r['description'],
                $r['notes'],
                $this->num($r['unit_price']),
                $r['quantity'],
                $this->num($r['discount']),
                $this->num($r['amount']),
                $r['reference_code'],
                $r['doctor_name'],
                $r['consultant_name'],
                $r['assistant_name'],
                $r['status_label'],
            ];
        }

        if ($this->rows) {
            $grid[] = array_merge(
                ['TỔNG CỘNG', null, null, count($this->rows).' dòng', null, null, null, null, null, null, null],
                [null, null, $this->sum('discount'), $this->sum('amount')],
                [null, null, null, null, null],
            );
        }

        return $grid;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = self::LAST_COL;
                $count = count($this->rows);
                $lastRow = self::ROW_DATA + max($count, 1) - 1;

                $this->banner(
                    $sheet, $last,
                    'DỮ LIỆU HỆ THỐNG — THỦ THUẬT & THANH TOÁN',
                    $this->meta['range'] ?? '',
                    trim(($this->meta['filters'] ?? '').'    |    '.$count.' dòng    |    Tổng thu: '
                        .number_format($this->sum('amount')).' đ', ' |'),
                    'FF042F2E',
                );

                $this->bands($sheet, self::ROW_BAND, self::BANDS);
                $this->headerRow($sheet, self::ROW_HEAD, $last);

                if ($count === 0) {
                    $this->emptyNotice($sheet, self::ROW_DATA, $last);
                    $this->finishSheet($sheet, 'A'.self::ROW_DATA, footer: $this->meta['range'] ?? '');

                    return;
                }

                $first = self::ROW_DATA;
                $this->bodyStyle($sheet, $first, $lastRow, $last);
                $this->money($sheet, "L{$first}:L{$lastRow}", "N{$first}:N{$lastRow}", "O{$first}:O{$lastRow}");
                foreach (['A', 'B', 'C', 'D', 'E', 'M', 'P', 'T'] as $c) {
                    $this->center($sheet, "{$c}{$first}:{$c}{$lastRow}");
                }
                $sheet->getStyle("F{$first}:F{$lastRow}")->getFont()->setBold(true);
                $sheet->getStyle("O{$first}:O{$lastRow}")->getFont()->setBold(true);

                foreach ($this->rows as $i => $r) {
                    $row = $first + $i;

                    $isService = ($r['record_type'] ?? '') === 'service';
                    $this->chip($sheet, "D{$row}", ...($isService ? ['FFE0E7FF', 'FF4338CA'] : ['FFD1FAE5', 'FF047857']));
                    $this->chip($sheet, "T{$row}", ...$this->statusColor($r));
                }

                $totalRow = $lastRow + 1;
                $this->totalRow($sheet, $totalRow, $last);
                $this->money($sheet, "N{$totalRow}:O{$totalRow}");
                $this->frame($sheet, 'A'.self::ROW_BAND.":{$last}{$totalRow}");

                $this->finishSheet(
                    $sheet,
                    freezeCell: 'E'.self::ROW_DATA,
                    filterRange: 'A'.self::ROW_HEAD.":{$last}".self::ROW_HEAD,
                    repeatFrom: self::ROW_BAND,
                    repeatTo: self::ROW_HEAD,
                    footer: $this->meta['range'] ?? '',
                );
                $sheet->setSelectedCell('A'.self::ROW_DATA);
            },
        ];
    }

    /** Payments read green; service rows follow their treatment-item status. */
    private function statusColor(array $r): array
    {
        if (($r['record_type'] ?? '') !== 'service') {
            return ['FFD1FAE5', 'FF047857'];
        }

        return match ($r['status'] ?? '') {
            'completed' => ['FFD1FAE5', 'FF047857'],
            'in_progress' => ['FFEDE9FE', 'FF6D28D9'],
            'planned' => ['FFDBEAFE', 'FF1D4ED8'],
            'cancelled' => ['FFFEE2E2', 'FFB91C1C'],
            default => ['FFF3F4F6', 'FF4B5563'],
        };
    }

    private function num($v): ?float
    {
        return $v === null || $v === '' ? null : (float) $v;
    }

    private function sum(string $key): float
    {
        return array_sum(array_map(fn ($r) => (float) ($r[$key] ?? 0), $this->rows));
    }

    private function bandRow(): array
    {
        $row = array_fill(0, count(self::HEADERS), null);
        foreach (self::BANDS as [$label, $firstCol]) {
            $row[ord($firstCol) - ord('A')] = $label;
        }

        return $row;
    }
}
