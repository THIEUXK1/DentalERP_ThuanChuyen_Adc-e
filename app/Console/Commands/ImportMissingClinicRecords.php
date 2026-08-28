<?php

namespace App\Console\Commands;

use App\Support\LegacyExcelValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Bù các dòng của bộ Excel hệ thống cũ (DuLieuHeThong) bị rơi trong hai đợt import
 * ngày 01/07 và 04/07/2026.
 *
 * Cách xác định "thiếu": so khớp tập hợp (multiset) giữa file và bảng clinic_records
 * theo khoá ngày + tên KH + loại phát sinh + dịch vụ + thành tiền + thu trong kỳ.
 * Mỗi dòng Excel "tiêu thụ" một bản ghi DB cùng khoá; phần dư của Excel là phần thiếu.
 * Nhờ vậy lệnh **idempotent**: chạy lại lần hai sẽ báo 0 dòng thiếu.
 *
 * Ngày: file gốc bị Excel đọc nhầm kiểu Mỹ, đợt import trên production đã đảo lại
 * (xem LegacyExcelValue::unswapLegacyDate) — dữ liệu bù áp dụng đúng quy tắc đó.
 */
class ImportMissingClinicRecords extends Command
{
    protected $signature = 'clinic-records:import-missing
                            {--dir= : Thư mục chứa các file Excel nguồn}
                            {--dry-run : Chỉ đối chiếu và báo cáo, không ghi DB}
                            {--export= : Xuất danh sách dòng thiếu ra file CSV}';

    protected $description = 'Đối chiếu Excel hệ thống cũ với clinic_records và bù các dòng bị thiếu';

    /** Thứ tự cột trong file Excel nguồn → cột DB. */
    private const FIELDS = [
        0 => 'record_date', 1 => 'record_time', 2 => 'patient_name', 3 => 'patient_code',
        4 => 'record_type', 5 => 'service_name', 6 => 'unit_price', 7 => 'quantity',
        8 => 'discount', 9 => 'amount', 10 => 'total_collected', 11 => 'remaining_debt',
        12 => 'collected_this_period', 13 => 'fund_name', 14 => 'treatment_step',
        15 => 'treatment_step_notes', 16 => 'consultant_name', 17 => 'doctor_name',
        18 => 'assistant_name', 19 => 'birth_year', 20 => 'gender', 21 => 'phone',
        22 => 'customer_source', 23 => 'symptoms', 24 => 'diagnosis', 25 => 'service_group',
        26 => 'status',
    ];

    private const NUMERIC = [
        'unit_price', 'quantity', 'discount', 'amount',
        'total_collected', 'remaining_debt', 'collected_this_period',
    ];

    public function handle(): int
    {
        @ini_set('memory_limit', '2048M');

        $dir = rtrim((string) ($this->option('dir') ?: config('clinic_records.legacy_dir')), '/\\');
        if ($dir === '' || ! is_dir($dir)) {
            $this->error("Không tìm thấy thư mục nguồn: {$dir}");

            return self::FAILURE;
        }

        $files = glob($dir.'/*.xlsx') ?: [];
        sort($files);
        if ($files === []) {
            $this->error("Thư mục {$dir} không có file .xlsx nào.");

            return self::FAILURE;
        }

        $this->info('Đang nạp khoá đối chiếu từ clinic_records…');
        $dbKeys = $this->loadDatabaseKeys();
        $this->line('  '.array_sum($dbKeys).' bản ghi đang có (chưa xoá mềm).');

        $missing = [];
        $totalExcel = 0;

        foreach ($files as $file) {
            $rows = $this->readSheet($file);
            $fileMissing = 0;
            $fileRows = 0;

            foreach ($rows as $row) {
                $data = $this->mapRow($row);
                if ($data === null) {
                    continue;
                }

                $totalExcel++;
                $fileRows++;
                $key = $this->key($data);

                if (($dbKeys[$key] ?? 0) > 0) {
                    $dbKeys[$key]--;

                    continue;
                }

                $data['_file'] = basename($file);
                $missing[] = $data;
                $fileMissing++;
            }

            $this->line(sprintf('  %-14s %6d dòng, thiếu %d', basename($file), $fileRows, $fileMissing));
            unset($rows);
        }

        $this->newLine();
        $this->info("Excel: {$totalExcel} dòng hợp lệ — thiếu trong DB: ".count($missing));

        if ($missing === []) {
            return self::SUCCESS;
        }

        $this->table(
            ['Năm', 'Số dòng', 'Thành tiền', 'Thu trong kỳ'],
            $this->summaryByYear($missing)
        );

        if ($export = $this->option('export')) {
            $this->export($missing, (string) $export);
            $this->line("Đã xuất danh sách: {$export}");
        }

        if ($this->option('dry-run')) {
            $this->warn('[dry-run] Chưa ghi gì vào DB.');

            return self::SUCCESS;
        }

        $now = now();
        $inserted = 0;
        foreach (array_chunk($missing, 500) as $chunk) {
            $batch = array_map(function (array $r) use ($now) {
                unset($r['_file']);

                return $r + ['created_at' => $now, 'updated_at' => $now];
            }, $chunk);

            DB::transaction(fn () => DB::table('clinic_records')->insert($batch));
            $inserted += count($batch);
            $this->line("  đã chèn {$inserted}/".count($missing));
        }

        $this->info("Hoàn tất: chèn {$inserted} bản ghi.");
        $this->warn('Chạy tiếp `php artisan clinic-records:sync --dry-run` để đưa các dòng này sang patients/hoá đơn/thanh toán.');

        return self::SUCCESS;
    }

    /** @return array<string,int> khoá đối chiếu → số bản ghi đang có */
    private function loadDatabaseKeys(): array
    {
        $keys = [];

        DB::table('clinic_records')
            ->whereNull('deleted_at')
            ->select('record_date', 'patient_name', 'record_type', 'service_name', 'amount', 'collected_this_period')
            ->orderBy('id')
            ->chunk(10000, function ($rows) use (&$keys) {
                foreach ($rows as $r) {
                    $key = $this->key([
                        'record_date' => substr((string) $r->record_date, 0, 10),
                        'patient_name' => $r->patient_name,
                        'record_type' => $r->record_type,
                        'service_name' => $r->service_name,
                        'amount' => (int) $r->amount,
                        'collected_this_period' => (int) $r->collected_this_period,
                    ]);
                    $keys[$key] = ($keys[$key] ?? 0) + 1;
                }
            });

        return $keys;
    }

    /** @return array<int,array<int,mixed>> */
    private function readSheet(string $file): array
    {
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        array_shift($rows); // dòng tiêu đề

        return $rows;
    }

    /**
     * @param  array<int,mixed>  $row
     * @return array<string,mixed>|null
     */
    private function mapRow(array $row): ?array
    {
        $data = [];
        foreach (self::FIELDS as $idx => $field) {
            $val = $row[$idx] ?? null;
            $data[$field] = ($val !== null && $val !== '') ? $val : null;
        }

        if (! $data['patient_name'] && ! $data['record_date']) {
            return null;
        }

        $data['record_date'] = LegacyExcelValue::unswapLegacyDate(LegacyExcelValue::date($data['record_date']));
        $data['record_time'] = LegacyExcelValue::time($data['record_time']);
        $data['birth_year'] = LegacyExcelValue::birthYear($data['birth_year']);

        foreach (self::NUMERIC as $f) {
            $data[$f] = is_numeric($data[$f]) ? (int) $data[$f] : 0;
        }

        foreach (['patient_name', 'patient_code', 'record_type', 'service_name', 'fund_name', 'treatment_step',
            'consultant_name', 'doctor_name', 'assistant_name', 'gender', 'phone', 'customer_source', 'service_group', 'status'] as $f) {
            if ($data[$f] !== null) {
                $data[$f] = (string) $data[$f];
            }
        }

        return $data;
    }

    /** @param array<string,mixed> $d */
    private function key(array $d): string
    {
        $norm = fn ($v) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) ($v ?? ''))));

        return implode('|', [
            (string) $d['record_date'],
            $norm($d['patient_name']),
            $norm($d['record_type']),
            $norm($d['service_name']),
            (int) $d['amount'],
            (int) $d['collected_this_period'],
        ]);
    }

    /**
     * @param  array<int,array<string,mixed>>  $missing
     * @return array<int,array<int,string>>
     */
    private function summaryByYear(array $missing): array
    {
        $by = [];
        foreach ($missing as $m) {
            $y = substr((string) $m['record_date'], 0, 4) ?: '—';
            $by[$y]['n'] = ($by[$y]['n'] ?? 0) + 1;
            $by[$y]['a'] = ($by[$y]['a'] ?? 0) + (int) $m['amount'];
            $by[$y]['c'] = ($by[$y]['c'] ?? 0) + (int) $m['collected_this_period'];
        }
        ksort($by);

        return array_map(
            fn ($y, $v) => [$y, (string) $v['n'], number_format($v['a']), number_format($v['c'])],
            array_keys($by),
            $by
        );
    }

    /** @param array<int,array<string,mixed>> $missing */
    private function export(array $missing, string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fh = fopen($path, 'w');
        fwrite($fh, "\xEF\xBB\xBF"); // BOM để Excel đọc đúng tiếng Việt
        fputcsv($fh, array_merge(['file'], array_values(self::FIELDS)));
        foreach ($missing as $m) {
            $file = $m['_file'];
            unset($m['_file']);
            fputcsv($fh, array_merge([$file], array_values($m)));
        }
        fclose($fh);
    }
}
