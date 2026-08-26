<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dọn tàn dư của luồng cũ: đăng ký khám bị tạo TRƯỚC ngày khám (nhân viên bấm
 * "Đăng ký nhanh" cho lịch hẹn của ngày mai). Những bản ghi này không phản ánh
 * việc bệnh nhân có mặt tại phòng khám, nên làm sai số liệu đăng ký theo ngày.
 *
 * Mặc định chỉ xoá các ca KHÔNG có dấu hiệu đã khám thật (không hoá đơn, không
 * phiếu khám trong đúng ngày đó) — ca có dấu hiệu được giữ lại để người dùng tự quyết.
 */
class PurgePreRegisteredVisits extends Command
{
    protected $signature = 'registrations:purge-preregistered
                            {--dry-run : Chỉ đếm và hiển thị, không xoá}
                            {--include-visited : Xoá cả những ca có hoá đơn/phiếu khám cùng ngày}';

    protected $description = 'Xoá mềm đăng ký khám của ngày đã qua bị tạo trước ngày khám';

    public function handle(): int
    {
        $today = today()->toDateString();

        $query = DB::table('schedule_registrations')
            ->whereNull('deleted_at')
            ->where('registration_date', '<', $today)
            // created_at là timestamptz; đổi về giờ phòng khám trước khi so ngày.
            ->whereRaw("(created_at AT TIME ZONE 'Asia/Ho_Chi_Minh')::date < registration_date");

        if (! $this->option('include-visited')) {
            foreach (['patient_invoices', 'dental_examinations'] as $table) {
                $query->whereNotExists(function ($q) use ($table) {
                    $q->select(DB::raw(1))->from("{$table} as t")
                      ->whereColumn('t.patient_id', 'schedule_registrations.patient_id')
                      ->whereRaw("(t.created_at AT TIME ZONE 'Asia/Ho_Chi_Minh')::date = schedule_registrations.registration_date");
                });
            }
        }

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('Không có đăng ký nào cần dọn.');

            return self::SUCCESS;
        }

        $this->table(
            ['Tháng khám', 'Số đăng ký'],
            (clone $query)
                ->select(DB::raw("to_char(registration_date,'YYYY-MM') as thang"), DB::raw('count(*) as tong'))
                ->groupBy(DB::raw("to_char(registration_date,'YYYY-MM')"))
                ->orderBy('thang')->get()
                ->map(fn ($r) => [$r->thang, $r->tong])
        );

        if ($this->option('dry-run')) {
            $this->warn("[dry-run] Sẽ xoá {$count} đăng ký bị tạo trước ngày khám.");

            return self::SUCCESS;
        }

        $codes = (clone $query)->pluck('code')->all();

        // Xoá mềm bằng query builder: model event không chạy nên tự đặt deleted_at.
        $deleted = $query->update(['deleted_at' => now(), 'updated_at' => now()]);

        Log::channel('daily')->info('Dọn đăng ký khám tạo trước ngày khám', [
            'before'          => $today,
            'deleted'         => $deleted,
            'include_visited' => (bool) $this->option('include-visited'),
            'codes'           => $codes,
        ]);

        $this->info("Đã xoá {$deleted} đăng ký (xoá mềm, khôi phục được).");

        return self::SUCCESS;
    }
}
