<?php

namespace App\Exports;

use App\Exports\Concerns\StyledSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Rich "lịch hẹn + hồ sơ bệnh nhân" workbook for Reports/DailySchedule.
 *
 * The grid is emitted whole (banner + two-tier header + data) by array(), so every
 * style rule below can address fixed row numbers instead of guessing offsets.
 */
class DailyScheduleExport implements FromArray, WithColumnWidths, WithEvents, WithStrictNullComparison, WithTitle
{
    use StyledSheet;

    /** Banner block occupies rows 1-4; the two header tiers land on 5-6, data starts at 7. */
    private const ROW_BAND = 5;
    private const ROW_HEAD = 6;
    private const ROW_DATA = 7;

    private const LAST_COL = 'Z';

    /** Column band definitions: [label, first column, last column, ARGB]. */
    private const BANDS = [
        ['LỊCH HẸN', 'A', 'H', 'FF3730A3'],
        ['PHÂN CÔNG / DỊCH VỤ', 'I', 'L', 'FF0F766E'],
        ['HỒ SƠ BỆNH NHÂN', 'M', 'V', 'FFB45309'],
        ['THÔNG TIN Y TẾ', 'W', 'Z', 'FFBE123C'],
    ];

    private const HEADERS = [
        'STT', 'Ngày', 'Thứ', 'Giờ bắt đầu', 'Giờ kết thúc', 'Thời lượng (phút)', 'Mã lịch hẹn', 'Trạng thái',
        'Bác sĩ', 'Dịch vụ', 'Ghế', 'Chi nhánh',
        'Mã BN', 'Họ và tên', 'Giới tính', 'Ngày sinh', 'Năm sinh', 'Tuổi', 'Số điện thoại', 'Email', 'Địa chỉ', 'Nguồn khách',
        'Dị ứng', 'Tiền sử bệnh', 'Liên hệ khẩn cấp', 'Ghi chú lịch hẹn',
    ];

    /** Status value => [fill, font] used to tint the "Trạng thái" cell. */
    private const STATUS_COLORS = [
        'pending' => ['FFF3F4F6', 'FF4B5563'],
        'booked' => ['FFDBEAFE', 'FF1D4ED8'],
        'confirmed' => ['FFE0E7FF', 'FF4338CA'],
        'rescheduled' => ['FFFEF3C7', 'FF92400E'],
        'arrived_early' => ['FFCCFBF1', 'FF0F766E'],
        'checked_in' => ['FFCCFBF1', 'FF0F766E'],
        'arrived_late' => ['FFFFEDD5', 'FFC2410C'],
        'no_show' => ['FFFEE2E2', 'FFB91C1C'],
        'cancelled' => ['FFE5E7EB', 'FF6B7280'],
        'in_treatment' => ['FFEDE9FE', 'FF6D28D9'],
        'completed' => ['FFD1FAE5', 'FF047857'],
    ];

    /**
     * @param  array<int,array<string,mixed>>  $rows  Normalized appointment rows.
     * @param  array<string,string>  $meta  ['range' => ..., 'filters' => ...]
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

    public function columnWidths(): array
    {
        return [
            'A' => 6,  'B' => 12, 'C' => 10, 'D' => 11, 'E' => 11, 'F' => 10, 'G' => 15, 'H' => 16,
            'I' => 22, 'J' => 28, 'K' => 12, 'L' => 18,
            'M' => 12, 'N' => 26, 'O' => 10, 'P' => 12, 'Q' => 10, 'R' => 7, 'S' => 15, 'T' => 24, 'U' => 34, 'V' => 16,
            'W' => 24, 'X' => 30, 'Y' => 22, 'Z' => 30,
        ];
    }

    public function array(): array
    {
        $width = count(self::HEADERS);
        $blank = array_fill(0, $width, null);

        // Rows 1-3 are written by banner(); keep placeholders so the grid keeps its shape.
        $grid = [$blank, $blank, $blank, $blank, $this->bandRow(), self::HEADERS];

        foreach ($this->rows as $i => $r) {
            $grid[] = [
                $i + 1,
                $r['date_label'],
                $r['weekday'],
                $r['scheduled_at'],
                $r['ends_at'],
                $r['duration_minutes'],
                $r['code'],
                $r['status_label'],
                $r['doctor'],
                $r['service'],
                $r['chair'],
                $r['branch'],
                $r['patient_code'],
                $r['patient'],
                $r['patient_gender'],
                $r['patient_dob'],
                $r['patient_birth_year'],
                $r['patient_age'],
                $r['patient_phone'],
                $r['patient_email'],
                $r['patient_address'],
                $r['patient_source'],
                $r['patient_allergies'],
                $r['patient_medical_history'],
                $r['patient_emergency_contact'],
                $r['notes'],
            ];
        }

        return $grid;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = self::LAST_COL;
                $rowCount = count($this->rows);
                $lastRow = self::ROW_DATA + max($rowCount, 1) - 1;

                $summary = collect($this->statusCounts)->map(fn ($n, $label) => "{$label}: {$n}")->implode('   •   ');

                $this->banner(
                    $sheet, $last,
                    'BÁO CÁO LỊCH HẸN & HỒ SƠ BỆNH NHÂN',
                    $this->meta['range'] ?? '',
                    trim(($this->meta['filters'] ?? '').'    |    Tổng: '.$rowCount.' lịch hẹn'
                        .($summary !== '' ? '    |    '.$summary : ''), ' |'),
                );

                $this->bands($sheet, self::ROW_BAND, self::BANDS);
                $this->headerRow($sheet, self::ROW_HEAD, $last);

                if ($rowCount === 0) {
                    $this->emptyNotice($sheet, self::ROW_DATA, $last, 'Không có lịch hẹn nào phù hợp với bộ lọc.');
                } else {
                    $this->styleBody($sheet, $lastRow);
                    $this->frame($sheet, 'A'.self::ROW_BAND.":{$last}{$lastRow}");
                }

                $this->finishSheet(
                    $sheet,
                    freezeCell: 'I'.self::ROW_DATA,
                    filterRange: "A".self::ROW_HEAD.":{$last}".self::ROW_HEAD,
                    repeatFrom: self::ROW_BAND,
                    repeatTo: self::ROW_HEAD,
                    footer: $this->meta['range'] ?? '',
                );
                $sheet->setSelectedCell('A'.self::ROW_DATA);
            },
        ];
    }

    /** Alignment, emphasis and per-row status/allergy tints for the data block. */
    private function styleBody($sheet, int $lastRow): void
    {
        $last = self::LAST_COL;
        $first = self::ROW_DATA;

        $this->bodyStyle($sheet, $first, $lastRow, $last);

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'K', 'O', 'P', 'Q', 'R'] as $c) {
            $this->center($sheet, "{$c}{$first}:{$c}{$lastRow}");
        }
        $sheet->getStyle("S{$first}:S{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle("N{$first}:N{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("D{$first}:E{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("G{$first}:G{$lastRow}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
        $sheet->getStyle("M{$first}:M{$lastRow}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');

        foreach ($this->rows as $i => $r) {
            $row = $first + $i;
            $sheet->getRowDimension($row)->setRowHeight(-1);

            [$fill, $font] = self::STATUS_COLORS[$r['status']] ?? ['FFF3F4F6', 'FF4B5563'];
            $this->chip($sheet, "H{$row}", $fill, $font);

            // Flag patients with a recorded allergy so the front desk cannot miss it.
            if (trim((string) $r['patient_allergies']) !== '') {
                $sheet->getStyle("W{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF2F2']],
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFB91C1C']],
                ]);
            }
        }
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
