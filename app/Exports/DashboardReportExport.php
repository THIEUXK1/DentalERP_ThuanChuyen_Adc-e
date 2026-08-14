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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Dashboard snapshot: a stack of small titled tables (KPI, doanh thu theo bác sĩ /
 * dịch vụ, lịch hẹn, thanh toán, pipeline lead), each rendered as its own card.
 */
class DashboardReportExport implements FromArray, WithColumnWidths, WithEvents, WithStrictNullComparison, WithTitle
{
    use StyledSheet;

    private const LAST_COL = 'F';

    /** Section descriptors collected while building array(): title row, header row, data span, money columns. */
    private array $sections = [];

    public function __construct(private array $data)
    {
        $this->accent = 'FF0F766E';
    }

    public function title(): string
    {
        return 'Báo cáo tổng quan';
    }

    public function columnWidths(): array
    {
        return ['A' => 34, 'B' => 26, 'C' => 20, 'D' => 18, 'E' => 16, 'F' => 20];
    }

    public function array(): array
    {
        $d = $this->data;
        $blank = array_fill(0, 6, null);

        // Rows 1-3 belong to the banner; section cards start on row 5.
        $rows = [$blank, $blank, $blank, $blank];

        $this->section($rows, 'KPI TỔNG QUAN', ['Chỉ số', 'Giá trị'], array_values(array_filter([
            ['Lịch hẹn hôm nay', $d['kpis']['todayAppts']],
            $d['canFinancial'] ? ['Doanh thu hôm nay', (float) $d['kpis']['todayRevenue']] : null,
            $d['canFinancial'] ? ['Tổng công nợ', (float) $d['kpis']['totalOutstanding']] : null,
            ['Lead mới (7 ngày)', $d['kpis']['newLeads']],
            ['Khách hàng đang hoạt động', $d['kpis']['activePatients']],
            ['Công việc Follow-up chưa xử lý', $d['pendingTasksCount']],
            ['Tỷ lệ chốt kế hoạch điều trị (%)', $d['treatmentPlanConversion']['rate']],
        ])), moneyCols: $d['canFinancial'] ? ['B'] : [], accent: 'FF0F766E');

        if ($d['canFinancial'] && count($d['revenueByDoctor']) > 0) {
            $this->section($rows, 'DOANH THU THEO BÁC SĨ (30 NGÀY)', ['Bác sĩ', 'Doanh thu'],
                array_map(fn ($r) => [$r['name'], (float) $r['revenue']], $d['revenueByDoctor']),
                moneyCols: ['B'], accent: 'FF1D4ED8', total: true);
        }

        if ($d['canFinancial'] && count($d['revenueByService']) > 0) {
            $this->section($rows, 'DOANH THU THEO DỊCH VỤ (30 NGÀY)', ['Dịch vụ', 'Doanh thu'],
                array_map(fn ($r) => [$r['name'], (float) $r['revenue']], $d['revenueByService']),
                moneyCols: ['B'], accent: 'FF7E22CE', total: true);
        }

        if (count($d['apptBreakdown']) > 0) {
            $this->section($rows, 'LỊCH HẸN THEO TRẠNG THÁI', ['Trạng thái', 'Số lượng'],
                array_map(fn ($r) => [$r['status'], $r['count']], $d['apptBreakdown']),
                accent: 'FF4338CA', total: true);
        }

        // Unlike the other sections, todayPayments arrives as a Collection (built by ->get()->map()),
        // so unwrap it before array_map().
        if ($d['canFinancial'] && count($d['todayPayments']) > 0) {
            $this->section($rows, 'THANH TOÁN TRONG NGÀY', ['Giờ', 'Khách hàng', 'Hóa đơn', 'Hình thức', 'Số tiền', 'Người thu'],
                array_map(fn ($p) => [$p['time'], $p['patient'], $p['invoice_code'], $p['method_label'], (float) $p['amount'], $p['creator']], collect($d['todayPayments'])->all()),
                moneyCols: ['E'], accent: 'FF15803D', total: true, totalCol: 4);
        }

        if (count($d['leadFunnel']) > 0) {
            $this->section($rows, 'PIPELINE LEAD (30 NGÀY)', ['Trạng thái', 'Số lượng'],
                array_map(fn ($r) => [$r['status'], $r['count']], $d['leadFunnel']),
                accent: 'FFB45309', total: true);
        }

        return $rows;
    }

    /**
     * Append one card: title strip, header row, data rows, optional total, spacer.
     *
     * @param  array<int,string>  $moneyCols  Column letters to number-format.
     * @param  int  $totalCol  Zero-based index of the column summed into the total row.
     */
    private function section(array &$rows, string $label, array $headings, array $dataRows, array $moneyCols = [], string $accent = 'FF0F766E', bool $total = false, int $totalCol = 1): void
    {
        $titleRow = count($rows) + 1;
        $rows[] = [$label];
        $rows[] = $headings;

        $firstData = count($rows) + 1;
        foreach ($dataRows as $row) {
            $rows[] = $row;
        }
        $lastData = count($rows);

        $totalRow = null;
        if ($total && $dataRows) {
            $line = array_fill(0, 6, null);
            $line[0] = 'Tổng cộng';
            $line[$totalCol] = array_sum(array_map(fn ($r) => (float) ($r[$totalCol] ?? 0), $dataRows));
            $rows[] = $line;
            $totalRow = count($rows);
        }

        $rows[] = array_fill(0, 6, null);

        $this->sections[] = [
            'title' => $titleRow,
            'header' => $titleRow + 1,
            'first' => $firstData,
            'last' => $lastData,
            'total' => $totalRow,
            'cols' => count($headings),
            'money' => $moneyCols,
            'accent' => $accent,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = self::LAST_COL;
                $d = $this->data;

                $this->banner(
                    $sheet, $last,
                    'BÁO CÁO TỔNG QUAN',
                    $d['branchName'] ?? '',
                    'Ngày '.\Carbon\Carbon::parse($d['selectedDate'])->format('d/m/Y')
                        .'    |    Xuất lúc '.now()->format('H:i d/m/Y'),
                    'FF042F2E',
                );

                foreach ($this->sections as $s) {
                    $lastCol = $this->col($s['cols'] - 1);

                    $sheet->mergeCells("A{$s['title']}:{$last}{$s['title']}");
                    $sheet->getRowDimension($s['title'])->setRowHeight(24);
                    $sheet->getStyle("A{$s['title']}:{$last}{$s['title']}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $s['accent']]],
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                    ]);

                    $sheet->getStyle("A{$s['header']}:{$lastCol}{$s['header']}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']],
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF334155']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
                    ]);

                    if ($s['last'] >= $s['first']) {
                        $this->bodyStyle($sheet, $s['first'], $s['last'], $lastCol, wrap: false);
                    }

                    if ($s['total']) {
                        $this->totalRow($sheet, $s['total'], $lastCol);
                    }

                    $bottom = $s['total'] ?? $s['last'];
                    foreach ($s['money'] as $col) {
                        $this->money($sheet, "{$col}{$s['first']}:{$col}{$bottom}");
                    }
                    $this->frame($sheet, "A{$s['title']}:{$lastCol}{$bottom}", $s['accent']);
                }

                $this->finishSheet($sheet, 'A5', paper: 'A4', footer: 'Tổng quan '.($d['branchName'] ?? ''));
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
                $sheet->setSelectedCell('A5');
            },
        ];
    }
}
