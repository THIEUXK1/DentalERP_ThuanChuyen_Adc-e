<?php

namespace App\Exports;

use App\Exports\Concerns\StyledSheet;
use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Full payroll sheet: every component of the calculation (công, phụ cấp, bảo hiểm
 * hai phía, thuế TNCN, tổng kết) grouped under colour bands, with a per-department
 * strip and a grand-total row.
 */
class PayrollSheetExport implements FromArray, WithColumnWidths, WithEvents, WithStrictNullComparison, WithTitle
{
    use StyledSheet;

    private const ROW_BAND = 5;
    private const ROW_HEAD = 6;
    private const ROW_DATA = 7;

    private const LAST_COL = 'AI';

    private const BANDS = [
        ['NHÂN VIÊN', 'A', 'E', 'FF1E3A8A'],
        ['LƯƠNG & NGÀY CÔNG', 'F', 'K', 'FF0F766E'],
        ['PHỤ CẤP', 'L', 'R', 'FFB45309'],
        ['BẢO HIỂM — DOANH NGHIỆP CHI', 'S', 'V', 'FF7E22CE'],
        ['BẢO HIỂM — NHÂN VIÊN ĐÓNG', 'W', 'Z', 'FF9333EA'],
        ['THUẾ TNCN & KPCĐ', 'AA', 'AE', 'FFBE123C'],
        ['TỔNG KẾT', 'AF', 'AI', 'FF15803D'],
    ];

    private const HEADERS = [
        'STT', 'Mã NV', 'Họ và tên', 'Chức vụ', 'Phòng ban',
        'Lương chính', 'Lương đóng BH', 'Ngày công chuẩn', 'Ngày công TT', 'Tỷ lệ công', 'Lương theo công',
        'PC Cố định', 'PC Trách nhiệm', 'PC Ăn trưa', 'PC Điện thoại', 'PC Xăng xe', 'KPI / HQCV', 'Phụ cấp khác',
        'BHXH (DN)', 'BHYT (DN)', 'BHTN (DN)', 'Tổng CP DN',
        'BHXH (NV)', 'BHYT (NV)', 'BHTN (NV)', 'Tổng NV đóng',
        'TN chịu thuế', 'Số NPT', 'Giảm trừ', 'Thuế TNCN', 'Kinh phí CĐ',
        'Tổng thu nhập', 'Tổng khấu trừ', 'THỰC LĨNH', 'Ghi chú',
    ];

    /** Zero-based indexes of the money columns — number-formatted and totalled. */
    private const MONEY_COLS = [5, 6, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 28, 29, 30, 31, 32, 33];

    private array $items;

    /** 1-based sheet rows holding a department separator strip. */
    private array $deptRows = [];

    public function __construct(private Payroll $payroll)
    {
        $this->accent = 'FF1E3A8A';
        $this->items = $payroll->items()
            ->with('department')
            ->orderBy('department_name')
            ->orderBy('employee_name')
            ->get()
            ->toArray();
    }

    public function title(): string
    {
        return $this->payroll->code;
    }

    public function columnWidths(): array
    {
        $w = ['A' => 6, 'B' => 11, 'C' => 24, 'D' => 18, 'E' => 18, 'AI' => 26];
        foreach (range(5, 33) as $i) {
            $w[$this->col($i)] = in_array($i, [7, 8, 9, 27], true) ? 11 : 14;
        }

        return $w;
    }

    public function array(): array
    {
        $width = count(self::HEADERS);
        $blank = array_fill(0, $width, null);
        $grid = [$blank, $blank, $blank, $blank, $this->bandRow(), self::HEADERS];

        $totals = array_fill(0, $width, 0.0);
        $dept = null;
        $stt = 0;

        foreach ($this->items as $item) {
            if (($item['department_name'] ?? '') !== $dept) {
                $dept = $item['department_name'] ?? '';
                $row = $blank;
                $row[0] = '▎ '.($dept !== '' ? $dept : 'Chưa phân phòng ban');
                $grid[] = $row;
                $this->deptRows[] = count($grid);
            }

            $stt++;
            $line = [
                $stt,
                $item['employee_code'],
                $item['employee_name'],
                $item['position_name'] ?? '',
                $item['department_name'] ?? '',
                (float) $item['base_salary'],
                (float) ($item['insurance_salary'] ?? 0),
                (float) $item['standard_working_days'],
                (float) $item['actual_working_days'],
                (float) $item['workday_ratio'],
                (float) $item['salary_by_workday'],
                (float) $item['fixed_allowance'],
                (float) $item['responsibility_allowance'],
                (float) $item['lunch_allowance'],
                (float) $item['phone_allowance'],
                (float) $item['travel_allowance'],
                (float) $item['performance_kpi_amount'],
                (float) $item['other_allowance'],
                (float) $item['company_social_insurance'],
                (float) $item['company_health_insurance'],
                (float) $item['company_unemployment_insurance'],
                (float) $item['total_company_insurance'],
                (float) $item['employee_social_insurance'],
                (float) $item['employee_health_insurance'],
                (float) $item['employee_unemployment_insurance'],
                (float) $item['total_employee_insurance'],
                (float) $item['taxable_income'],
                (int) ($item['dependents_count'] ?? 0),
                (float) $item['family_deduction'] + (float) $item['dependent_deduction'],
                (float) $item['personal_income_tax'],
                (float) $item['union_fee_amount'],
                (float) $item['gross_income'],
                (float) $item['total_deductions'],
                (float) $item['net_salary'],
                $item['note'] ?? '',
            ];
            $grid[] = $line;

            foreach (self::MONEY_COLS as $c) {
                $totals[$c] += (float) $line[$c];
            }
        }

        if ($this->items) {
            $totalRow = $blank;
            $totalRow[0] = 'TỔNG CỘNG';
            $totalRow[2] = count($this->items).' nhân viên';
            foreach (self::MONEY_COLS as $c) {
                $totalRow[$c] = $totals[$c];
            }
            $grid[] = $totalRow;
        }

        return $grid;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = self::LAST_COL;
                $p = $this->payroll;

                // Data block spans employees + one strip per department; the totals row follows.
                $bodyRows = count($this->items) + count($this->deptRows);
                $firstRow = self::ROW_DATA;
                $lastRow = $firstRow + max($bodyRows, 1) - 1;

                $this->banner(
                    $sheet, $last,
                    'BẢNG LƯƠNG '.mb_strtoupper($p->code),
                    'Kỳ lương tháng '.str_pad((string) $p->month, 2, '0', STR_PAD_LEFT).'/'.$p->year
                        .($p->branch?->name ? ' — '.$p->branch->name : ''),
                    // Summed from the items rather than payroll.total_net_salary so the banner
                    // can never disagree with the rows printed underneath it.
                    count($this->items).' nhân viên    |    Tổng thực lĩnh: '
                        .number_format(array_sum(array_column($this->items, 'net_salary'))).' đ'
                        .'    |    Xuất lúc '.now()->format('H:i d/m/Y'),
                    'FF0C1E4A',
                );

                $this->bands($sheet, self::ROW_BAND, self::BANDS);
                $this->headerRow($sheet, self::ROW_HEAD, $last, height: 38);

                if (! $this->items) {
                    $this->emptyNotice($sheet, self::ROW_DATA, $last, 'Bảng lương chưa có nhân viên nào.');
                    $this->finishSheet($sheet, 'A'.self::ROW_DATA, footer: $p->code);

                    return;
                }

                $this->bodyStyle($sheet, $firstRow, $lastRow, $last, zebra: false, wrap: false);

                foreach (self::MONEY_COLS as $c) {
                    $col = $this->col($c);
                    $this->money($sheet, "{$col}{$firstRow}:{$col}{$lastRow}");
                }
                foreach (['A', 'B', 'H', 'I', 'J', 'AB'] as $c) {
                    $this->center($sheet, "{$c}{$firstRow}:{$c}{$lastRow}");
                }
                $sheet->getStyle("J{$firstRow}:J{$lastRow}")->getNumberFormat()->setFormatCode('0.0%');
                $sheet->getStyle("C{$firstRow}:C{$lastRow}")->getFont()->setBold(true);
                $sheet->getStyle("AH{$firstRow}:AH{$lastRow}")->getFont()->setBold(true)->getColor()->setARGB('FF047857');

                // Zebra only over real employee rows so department strips stay distinct.
                $stripe = false;
                for ($r = $firstRow; $r <= $lastRow; $r++) {
                    if (in_array($r, $this->deptRows, true)) {
                        $sheet->mergeCells("A{$r}:{$last}{$r}");
                        $sheet->getStyle("A{$r}:{$last}{$r}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E7FF']],
                            'font' => ['bold' => true, 'size' => 10.5, 'color' => ['argb' => 'FF3730A3']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                        ]);
                        $sheet->getRowDimension($r)->setRowHeight(20);
                        $stripe = false;

                        continue;
                    }
                    if ($stripe) {
                        $sheet->getStyle("A{$r}:{$last}{$r}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9FAFB');
                    }
                    $stripe = ! $stripe;
                }

                $totalRow = $lastRow + 1;
                $this->totalRow($sheet, $totalRow, $last);
                foreach (self::MONEY_COLS as $c) {
                    $col = $this->col($c);
                    $this->money($sheet, "{$col}{$totalRow}");
                }
                $this->frame($sheet, 'A'.self::ROW_BAND.":{$last}{$totalRow}");

                $this->finishSheet(
                    $sheet,
                    freezeCell: 'F'.self::ROW_DATA,
                    filterRange: 'A'.self::ROW_HEAD.":{$last}".self::ROW_HEAD,
                    repeatFrom: self::ROW_BAND,
                    repeatTo: self::ROW_HEAD,
                    footer: $p->code.' — tháng '.$p->month.'/'.$p->year,
                );
                $sheet->setSelectedCell('A'.self::ROW_DATA);
            },
        ];
    }

    private function bandRow(): array
    {
        $row = array_fill(0, count(self::HEADERS), null);
        foreach (self::BANDS as [$label, $firstCol]) {
            $row[\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($firstCol) - 1] = $label;
        }

        return $row;
    }
}
