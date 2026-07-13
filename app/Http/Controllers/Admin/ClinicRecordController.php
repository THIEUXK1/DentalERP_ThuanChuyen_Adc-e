<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClinicRecord;
use App\Support\RowRangeReadFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClinicRecordController extends Controller
{
    // Mapping: Excel column index (0-based) → DB field
    private const COLUMNS = [
        ['header' => 'Ngày (*)',                'field' => 'record_date',            'sample' => '2026-01-15'],
        ['header' => 'Giờ',                     'field' => 'record_time',            'sample' => '08:30'],
        ['header' => 'Tên khách hàng (*)',       'field' => 'patient_name',           'sample' => 'Nguyễn Văn A'],
        ['header' => 'Mã KH',                   'field' => 'patient_code',           'sample' => 'KH001'],
        ['header' => 'Loại phát sinh (*)',       'field' => 'record_type',            'sample' => 'Thủ thuật'],
        ['header' => 'Dịch vụ / thủ thuật',     'field' => 'service_name',           'sample' => 'Cạo vôi răng'],
        ['header' => 'Đơn giá',                 'field' => 'unit_price',             'sample' => 200000],
        ['header' => 'SL',                      'field' => 'quantity',               'sample' => 1],
        ['header' => 'Khuyến mại',              'field' => 'discount',               'sample' => 0],
        ['header' => 'Thành tiền',              'field' => 'amount',                 'sample' => 200000],
        ['header' => 'Tổng đồng thu',           'field' => 'total_collected',        'sample' => 200000],
        ['header' => 'Còn nợ',                  'field' => 'remaining_debt',         'sample' => 0],
        ['header' => 'Thu kỳ này',              'field' => 'collected_this_period',  'sample' => 200000],
        ['header' => 'Nguồn quỹ',               'field' => 'fund_name',              'sample' => 'Tiền mặt'],
        ['header' => 'Bước tiến trình',         'field' => 'treatment_step',         'sample' => ''],
        ['header' => 'Nội dung tiến trình',     'field' => 'treatment_step_notes',   'sample' => ''],
        ['header' => 'Tư vấn',                  'field' => 'consultant_name',        'sample' => 'Trần Thị B'],
        ['header' => 'Bác sĩ',                  'field' => 'doctor_name',            'sample' => 'BS. Nguyễn C'],
        ['header' => 'Trợ thủ',                 'field' => 'assistant_name',         'sample' => ''],
        ['header' => 'Năm sinh',                'field' => 'birth_year',             'sample' => 1990],
        ['header' => 'Giới tính',               'field' => 'gender',                 'sample' => 'Nam'],
        ['header' => 'Điện thoại',              'field' => 'phone',                  'sample' => '0901234567'],
        ['header' => 'Nguồn khách',             'field' => 'customer_source',        'sample' => ''],
        ['header' => 'Triệu chứng',             'field' => 'symptoms',               'sample' => ''],
        ['header' => 'Chẩn đoán',               'field' => 'diagnosis',              'sample' => ''],
        ['header' => 'Nhóm thủ thuật',          'field' => 'service_group',          'sample' => ''],
        ['header' => 'Trạng thái',              'field' => 'status',                 'sample' => ''],
    ];

    public function index(Request $request): Response
    {
        $query = ClinicRecord::query()
            ->when($request->search, function ($q, $v) {
                $q->where(function ($q2) use ($v) {
                    $q2->where('patient_name', 'like', "%{$v}%")
                       ->orWhere('patient_code', 'like', "%{$v}%")
                       ->orWhere('service_name', 'like', "%{$v}%")
                       ->orWhere('doctor_name', 'like', "%{$v}%")
                       ->orWhere('phone', 'like', "%{$v}%");
                });
            })
            ->when($request->record_type, fn ($q, $v) => $q->where('record_type', $v))
            ->when($request->year, function ($q, $v) {
                if ($v === 'unknown') {
                    $q->whereDate('record_date', '>', now()->toDateString());
                } else {
                    $q->whereYear('record_date', $v);
                }
            })
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('record_date', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('record_date', '<=', $v))
            ->orderByDesc('record_date')
            ->orderByDesc('id');

        $perPage = $request->per_page === 'all'
            ? $query->count()
            : (in_array((int) $request->per_page, [20, 50, 100]) ? (int) $request->per_page : 50);

        return Inertia::render('Admin/ClinicRecords/Index', [
            'records'      => $query->paginate(max($perPage, 1))->withQueryString(),
            'filters'      => $request->only(['search', 'record_type', 'year', 'date_from', 'date_to', 'per_page']),
            'record_types' => ClinicRecord::distinct()->pluck('record_type')->filter()->values(),
            'years'        => ClinicRecord::whereNotNull('record_date')
                ->selectRaw('DISTINCT EXTRACT(YEAR FROM record_date) as y')
                ->orderByDesc('y')
                ->pluck('y')
                ->map(fn ($y) => (int) $y)
                ->values(),
            'unknown_year_count' => ClinicRecord::whereDate('record_date', '>', now()->toDateString())->count(),
            'total_all'    => ClinicRecord::count(),
        ]);
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        // select_all=true: xóa tất cả bản ghi khớp filter hiện tại
        if ($request->boolean('select_all')) {
            $query = ClinicRecord::query()
                ->when($request->search, function ($q, $v) {
                    $q->where(function ($q2) use ($v) {
                        $q2->where('patient_name', 'like', "%{$v}%")
                           ->orWhere('patient_code', 'like', "%{$v}%")
                           ->orWhere('service_name', 'like', "%{$v}%")
                           ->orWhere('doctor_name', 'like', "%{$v}%")
                           ->orWhere('phone', 'like', "%{$v}%");
                    });
                })
                ->when($request->record_type, fn ($q, $v) => $q->where('record_type', $v))
                ->when($request->year, function ($q, $v) {
                    if ($v === 'unknown') {
                        $q->whereDate('record_date', '>', now()->toDateString());
                    } else {
                        $q->whereYear('record_date', $v);
                    }
                })
                ->when($request->date_from, fn ($q, $v) => $q->whereDate('record_date', '>=', $v))
                ->when($request->date_to, fn ($q, $v) => $q->whereDate('record_date', '<=', $v));

            $count = $query->count();
            $query->delete();

            return back()->with('success', "Đã xóa tất cả {$count} bản ghi.");
        }

        // Xóa theo danh sách IDs
        $ids = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:clinic_records,id',
        ])['ids'];

        ClinicRecord::whereIn('id', $ids)->delete();

        return back()->with('success', 'Đã xóa ' . count($ids) . ' bản ghi.');
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dữ liệu');

        // Header row — styled
        $col = 1;
        foreach (self::COLUMNS as $def) {
            $sheet->setCellValueByColumnAndRow($col, 1, $def['header']);
            $col++;
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count(self::COLUMNS));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Sample row
        $col = 1;
        foreach (self::COLUMNS as $def) {
            $sheet->setCellValueByColumnAndRow($col, 2, $def['sample']);
            $col++;
        }

        // Auto-width
        foreach (range(1, count(self::COLUMNS)) as $colIdx) {
            $sheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
        }

        $tmpDir = storage_path('app/import_tmp');
        if (! is_dir($tmpDir)) mkdir($tmpDir, 0755, true);

        $path = $tmpDir . '/template_' . time() . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, 'mau_bang_ghi_phong_kham.xlsx')->deleteFileAfterSend(true);
    }

    public function previewImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:51200',
        ]);

        $tmpDir = storage_path('app/import_tmp');
        if (! is_dir($tmpDir)) mkdir($tmpDir, 0755, true);

        @ini_set('memory_limit', '1024M');

        $tempId  = Str::uuid()->toString();
        $ext     = strtolower($request->file('file')->getClientOriginalExtension());
        $xlsPath = "{$tmpDir}/{$tempId}.{$ext}";
        $request->file('file')->move($tmpDir, "{$tempId}.{$ext}");

        // Chỉ đọc thông tin kích thước sheet (nhẹ, không load hết dữ liệu ô)
        // để chia nhỏ việc đọc theo từng khối dòng, tránh tràn bộ nhớ với file lớn.
        try {
            $reader    = IOFactory::createReaderForFile($xlsPath);
            $reader->setReadDataOnly(true);
            $sheetInfo = $reader->listWorksheetInfo($xlsPath)[0] ?? null;
            $totalRows = (int) ($sheetInfo['totalRows'] ?? 0);
        } catch (\Throwable $e) {
            @unlink($xlsPath);
            return response()->json(['error' => 'Không đọc được file: ' . $e->getMessage()], 422);
        }

        if ($totalRows < 2) {
            @unlink($xlsPath);
            return response()->json(['error' => 'File rỗng.'], 422);
        }

        $headers       = [];
        $numericFields = ['unit_price', 'quantity', 'discount', 'amount', 'total_collected', 'remaining_debt', 'collected_this_period'];
        $processedRows = [];
        $previewRaw    = [];  // raw values cho preview table (giữ nguyên định dạng gốc)

        $chunkSize = 3000;

        try {
            for ($start = 2; $start <= $totalRows; $start += $chunkSize) {
                $end = min($start + $chunkSize - 1, $totalRows);

                $reader = IOFactory::createReaderForFile($xlsPath);
                $reader->setReadDataOnly(true);
                $reader->setReadFilter(new RowRangeReadFilter($start, $end));
                $spreadsheet = $reader->load($xlsPath);
                $chunkArr    = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                if (empty($headers)) {
                    $headers = $chunkArr[0] ?? [];
                }

                for ($r = $start; $r <= $end; $r++) {
                    $row = $chunkArr[$r - 1] ?? null;
                    if ($row === null) continue;

                    // Bỏ qua dòng hoàn toàn trống
                    $hasContent = false;
                    foreach ($row as $cell) {
                        if ($cell !== null && $cell !== '') { $hasContent = true; break; }
                    }
                    if (! $hasContent) continue;

                    $data = [];
                    foreach (self::COLUMNS as $idx => $def) {
                        $val = $row[$idx] ?? null;
                        $data[$def['field']] = ($val !== null && $val !== '') ? $val : null;
                    }

                    // Bỏ qua dòng thiếu cả tên lẫn ngày
                    if (! $data['patient_name'] && ! $data['record_date']) continue;

                    // Parse date — Excel serial hoặc chuỗi (d/m/Y, Y-m-d, …)
                    if ($data['record_date'] !== null) {
                        $data['record_date'] = self::parseDate($data['record_date']);
                    }

                    // Parse time — Excel lưu giờ là phân số thập phân (0.29097... = 06:59)
                    if ($data['record_time'] !== null) {
                        $data['record_time'] = self::parseTime($data['record_time']);
                    }

                    foreach ($numericFields as $f) {
                        $data[$f] = is_numeric($data[$f] ?? null) ? (int) $data[$f] : 0;
                    }

                    $data['birth_year'] = self::parseBirthYear($data['birth_year']);

                    $processedRows[] = $data;

                    // Giữ 20 dòng đầu để preview (dùng $data đã parse để hiện ngày đúng, không bị số serial)
                    if (count($previewRaw) < 20) {
                        $previewRaw[] = array_values($data);
                    }
                }

                unset($chunkArr);
            }
        } catch (\Throwable $e) {
            @unlink($xlsPath);
            return response()->json(['error' => 'Không đọc được file: ' . $e->getMessage()], 422);
        }

        @unlink($xlsPath); // Excel không cần nữa sau khi đọc xong

        if (empty($processedRows)) {
            return response()->json(['error' => 'Không tìm thấy dòng dữ liệu hợp lệ trong file.'], 422);
        }

        // Lưu toàn bộ dữ liệu đã xử lý vào JSON — importChunk chỉ cần đọc JSON
        $jsonPath = "{$tmpDir}/{$tempId}.json";
        file_put_contents($jsonPath, json_encode($processedRows, JSON_UNESCAPED_UNICODE));

        return response()->json([
            'temp_id' => $tempId,
            'total'   => count($processedRows),
            'headers' => $headers,
            'preview' => $previewRaw,
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'temp_id' => ['required', 'string', 'regex:/^[a-f0-9\-]+\.(xlsx|xls)$/i'],
        ]);

        $path = storage_path('app/import_tmp/' . $request->temp_id);

        if (! file_exists($path)) {
            return back()->with('error', 'File import đã hết hạn. Vui lòng tải lại file và thử lại.');
        }

        try {
            $spreadsheet = IOFactory::load($path);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            @unlink($path);
            return back()->with('error', 'Không đọc được file: ' . $e->getMessage());
        }

        if (empty($rows)) {
            @unlink($path);
            return back()->with('error', 'File rỗng.');
        }

        array_shift($rows); // Remove header row

        $numericFields = ['unit_price', 'quantity', 'discount', 'amount', 'total_collected', 'remaining_debt', 'collected_this_period'];
        $inserted = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            $data = [];
            foreach (self::COLUMNS as $idx => $def) {
                $val = $row[$idx] ?? null;
                $data[$def['field']] = ($val !== null && $val !== '') ? $val : null;
            }

            // Skip completely empty rows
            if (! $data['patient_name'] && ! $data['record_date']) {
                $skipped++;
                continue;
            }

            // Dùng chung parseDate() với previewImport() — Carbon::parse() cũ hiểu nhầm d/m/Y thành m/d/Y, gây lệch legacy_group_key và tạo bản ghi trùng.
            if ($data['record_date'] !== null) {
                $data['record_date'] = self::parseDate($data['record_date']);
            }

            foreach ($numericFields as $f) {
                $data[$f] = is_numeric($data[$f] ?? null) ? (int) $data[$f] : 0;
            }

            $data['birth_year'] = self::parseBirthYear($data['birth_year']);

            ClinicRecord::create($data);
            $inserted++;
        }

        @unlink($path);

        $msg = "Đã import {$inserted} bản ghi thành công.";
        if ($skipped > 0) $msg .= " Bỏ qua {$skipped} dòng trống/lỗi.";

        return back()->with('success', $msg);
    }

    private static function parseBirthYear(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) return null;

        $year = (int) $value;

        // smallint tối đa 32767 — chặn giá trị lỗi kiểu năm bị nối đôi (vd "19781978")
        // hoặc ngoài khoảng năm sinh hợp lý.
        if ($year < 1900 || $year > (int) date('Y')) return null;

        return $year;
    }

    private static function parseTime(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;

        // Excel time fraction: 0.0–1.0 (phần thập phân của ngày).
        // Một số file lỗi chứa cả phần ngày (số nguyên) lẫn giờ trong cùng ô —
        // chỉ lấy phần thập phân để tránh giá trị giờ vượt quá 24h.
        if (is_numeric($value)) {
            $frac = fmod((float) $value, 1);
            if ($frac < 0) $frac += 1;
            $totalSeconds = (int) round($frac * 86400);
            if ($totalSeconds >= 86400) $totalSeconds = 0;
            $h = intdiv($totalSeconds, 3600);
            $m = intdiv($totalSeconds % 3600, 60);
            $s = $totalSeconds % 60;
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }

        $str = trim((string) $value);
        // Chấp nhận H:MM hoặc HH:MM hoặc HH:MM:SS
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $str)) {
            return $str;
        }

        return null;
    }

    private static function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;

        // Excel serial number
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {}
        }

        $str = trim((string) $value);
        if ($str === '') return null;

        // Try Vietnamese/common formats explicitly (Carbon::parse mis-interprets d/m/Y as m/d/Y)
        foreach (['d/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y', 'Y-m-d', 'Y/m/d'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $str);
            $errs = \DateTime::getLastErrors();
            if ($dt !== false && ($errs === false || $errs['error_count'] === 0)) {
                return $dt->format('Y-m-d');
            }
        }

        // Fallback: Carbon generic parse
        try {
            return \Carbon\Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function importChunk(Request $request): JsonResponse
    {
        $v = $request->validate([
            'temp_id' => ['required', 'string', 'regex:/^[a-f0-9\-]+$/i'],
            'offset'  => 'required|integer|min:0',
            'limit'   => 'required|integer|min:1|max:2000',
        ]);

        $jsonPath = storage_path('app/import_tmp/' . $v['temp_id'] . '.json');

        if (! file_exists($jsonPath)) {
            return response()->json(['error' => 'Phiên import đã hết hạn. Vui lòng tải lại file.'], 422);
        }

        $allRows = json_decode(file_get_contents($jsonPath), true);
        if (! is_array($allRows)) {
            @unlink($jsonPath);
            return response()->json(['error' => 'Dữ liệu import bị lỗi. Vui lòng tải lại file.'], 422);
        }

        $total  = count($allRows);
        $chunk  = array_slice($allRows, $v['offset'], $v['limit']);
        $isDone = ($v['offset'] + $v['limit']) >= $total;

        if (! empty($chunk)) {
            $now   = now()->toDateTimeString();
            $batch = array_map(fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now], $chunk);
            ClinicRecord::insert($batch);
        }

        if ($isDone) {
            @unlink($jsonPath);
        }

        $processed = min($v['offset'] + count($chunk), $total);

        return response()->json([
            'inserted'  => count($chunk),
            'processed' => $processed,
            'total'     => $total,
            'done'      => $isDone,
        ]);
    }
}
