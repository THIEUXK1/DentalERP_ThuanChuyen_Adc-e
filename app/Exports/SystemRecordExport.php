<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SystemRecordExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private array $rows) {}

    public function title(): string
    {
        return 'Dữ liệu hệ thống';
    }

    public function headings(): array
    {
        return [
            'Ngày', 'Mã KH', 'Tên khách hàng', 'SĐT', 'Loại', 'Diễn giải',
            'Đơn giá', 'SL', 'Khuyến mại', 'Thành tiền',
            'Chứng từ', 'Bác sĩ', 'Tư vấn', 'Trợ thủ', 'Chi nhánh', 'Trạng thái',
        ];
    }

    public function array(): array
    {
        return array_map(fn ($r) => [
            \Carbon\Carbon::parse($r['record_date'])->format('d/m/Y'),
            $r['patient_code'],
            $r['patient_name'],
            $r['phone'],
            $r['record_type_label'],
            $r['description'],
            $r['unit_price'],
            $r['quantity'],
            $r['discount'],
            $r['amount'],
            $r['reference_code'],
            $r['doctor_name'],
            $r['consultant_name'],
            $r['assistant_name'],
            $r['branch_name'],
            $r['status_label'],
        ], $this->rows);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0D9488']]],
        ];
    }
}
