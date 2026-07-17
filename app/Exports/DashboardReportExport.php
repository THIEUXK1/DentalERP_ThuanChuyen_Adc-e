<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DashboardReportExport implements FromArray, WithStyles, WithTitle
{
    /** 1-based row numbers of section header rows, collected while building array(). */
    private array $sectionHeaderRows = [];

    public function __construct(private array $data) {}

    public function title(): string
    {
        return 'Báo cáo tổng quan';
    }

    public function array(): array
    {
        $rows = [];
        $d = $this->data;

        $rows[] = ["BÁO CÁO TỔNG QUAN - {$d['branchName']}"];
        $rows[] = ['Ngày: '.\Carbon\Carbon::parse($d['selectedDate'])->format('d/m/Y')];
        $rows[] = [];

        $this->addSection($rows, 'KPI TỔNG QUAN', ['Chỉ số', 'Giá trị'], array_filter([
            ['Lịch hẹn', $d['kpis']['todayAppts']],
            $d['canFinancial'] ? ['Doanh thu', $d['kpis']['todayRevenue']] : null,
            $d['canFinancial'] ? ['Tổng công nợ', $d['kpis']['totalOutstanding']] : null,
            ['Lead mới (7 ngày)', $d['kpis']['newLeads']],
            ['Khách hàng đang hoạt động', $d['kpis']['activePatients']],
            ['Công việc Follow-up chưa xử lý', $d['pendingTasksCount']],
            ['Tỷ lệ chốt kế hoạch điều trị (%)', $d['treatmentPlanConversion']['rate']],
        ]));

        if ($d['canFinancial'] && count($d['revenueByDoctor']) > 0) {
            $this->addSection($rows, 'DOANH THU THEO BÁC SĨ (30 NGÀY)', ['Bác sĩ', 'Doanh thu'],
                array_map(fn ($r) => [$r['name'], $r['revenue']], $d['revenueByDoctor'])
            );
        }

        if ($d['canFinancial'] && count($d['revenueByService']) > 0) {
            $this->addSection($rows, 'DOANH THU THEO DỊCH VỤ (30 NGÀY)', ['Dịch vụ', 'Doanh thu'],
                array_map(fn ($r) => [$r['name'], $r['revenue']], $d['revenueByService'])
            );
        }

        if (count($d['apptBreakdown']) > 0) {
            $this->addSection($rows, 'LỊCH HẸN THEO TRẠNG THÁI', ['Trạng thái', 'Số lượng'],
                array_map(fn ($r) => [$r['status'], $r['count']], $d['apptBreakdown'])
            );
        }

        if ($d['canFinancial'] && count($d['todayPayments']) > 0) {
            $this->addSection($rows, 'THANH TOÁN TRONG NGÀY', ['Giờ', 'Khách hàng', 'Hóa đơn', 'Hình thức', 'Số tiền', 'Người thu'],
                array_map(fn ($p) => [$p['time'], $p['patient'], $p['invoice_code'], $p['method_label'], $p['amount'], $p['creator']], $d['todayPayments'])
            );
        }

        if (count($d['leadFunnel']) > 0) {
            $this->addSection($rows, 'PIPELINE LEAD (30 NGÀY)', ['Trạng thái', 'Số lượng'],
                array_map(fn ($r) => [$r['status'], $r['count']], $d['leadFunnel'])
            );
        }

        return $rows;
    }

    private function addSection(array &$rows, string $label, array $headings, array $dataRows): void
    {
        $this->sectionHeaderRows[] = count($rows) + 1;
        $rows[] = [$label];
        $rows[] = $headings;
        foreach ($dataRows as $row) {
            $rows[] = $row;
        }
        $rows[] = [];
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF0D9488']]],
            2 => ['font' => ['italic' => true, 'color' => ['argb' => 'FF666666']]],
        ];

        foreach ($this->sectionHeaderRows as $rowNum) {
            $styles[$rowNum] = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0D9488']],
            ];
        }

        return $styles;
    }
}
