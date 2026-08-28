<?php

namespace App\Support;

use Carbon\Carbon;
use DateTime;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Chuẩn hoá giá trị ô Excel của hệ thống cũ (file "DuLieuHeThong") về kiểu dữ liệu
 * của bảng clinic_records. Tách khỏi Controller để lệnh artisan bù dữ liệu dùng lại
 * đúng một bộ quy tắc, tránh mỗi nơi parse một kiểu rồi sinh bản ghi trùng.
 */
final class LegacyExcelValue
{
    public static function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Excel serial number
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        // Thử các định dạng Việt Nam/thông dụng trước — Carbon::parse hiểu nhầm d/m/Y thành m/d/Y
        foreach (['d/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y', 'Y-m-d', 'Y/m/d'] as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $str);
            $errs = DateTime::getLastErrors();
            if ($dt !== false && ($errs === false || $errs['error_count'] === 0)) {
                return $dt->format('Y-m-d');
            }
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public static function time(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Excel lưu giờ là phân số của một ngày (0.29097… = 06:59). Một số ô lỗi chứa
        // cả phần ngày lẫn giờ — chỉ lấy phần thập phân để không vượt quá 24h.
        if (is_numeric($value)) {
            $frac = fmod((float) $value, 1);
            if ($frac < 0) {
                $frac += 1;
            }
            $totalSeconds = (int) round($frac * 86400);
            if ($totalSeconds >= 86400) {
                $totalSeconds = 0;
            }

            return sprintf('%02d:%02d:%02d', intdiv($totalSeconds, 3600), intdiv($totalSeconds % 3600, 60), $totalSeconds % 60);
        }

        $str = trim((string) $value);

        return preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $str) ? $str : null;
    }

    public static function birthYear(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $year = (int) $value;

        // smallint tối đa 32767 — chặn giá trị lỗi kiểu năm bị nối đôi (vd "19781978")
        // hoặc ngoài khoảng năm sinh hợp lý.
        if ($year < 1900 || $year > (int) date('Y')) {
            return null;
        }

        return $year;
    }

    /**
     * Bộ file Excel gốc bị Excel đọc nhầm ngày kiểu Mỹ khi lưu: 08/10/2018 (8/10) thành
     * serial của 10/08. Đợt import đang chạy trên production đã đảo lại, nên dữ liệu bù
     * phải áp dụng đúng quy tắc đó: ngày ≤ 12 thì hoán đổi ngày ↔ tháng, ngày > 12 giữ nguyên
     * (vì không thể là tháng nên Excel không đọc nhầm được).
     */
    public static function unswapLegacyDate(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        [$y, $m, $d] = array_pad(explode('-', $date), 3, null);
        if ($y === null || $m === null || $d === null) {
            return $date;
        }

        if ((int) $d > 12 || (int) $d < 1) {
            return $date;
        }

        return checkdate((int) $d, (int) $m, (int) $y) ? "{$y}-{$d}-{$m}" : $date;
    }
}
