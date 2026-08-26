<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Đăng ký khám chỉ có ý nghĩa trong đúng ngày hôm đó: hết ngày là coi như đã xong,
 * không còn ai "đang chờ" nữa. Nếu không chốt lại, các dòng chưa được lễ tân bấm
 * trạng thái sẽ nằm mãi ở "Đang chờ" và đồng hồ chờ chạy sang hàng trăm giờ.
 */
class CloseDayRegistrations extends Command
{
    protected $signature = 'registrations:close-day
                            {--dry-run : Chỉ đếm và hiển thị, không ghi vào database}';

    protected $description = 'Chốt các đăng ký khám của những ngày đã qua thành "Hoàn thành"';

    /** Các trạng thái còn dang dở; "cancelled" đã là kết thúc nên giữ nguyên. */
    private const OPEN_STATUSES = ['pending', 'in_treatment'];

    public function handle(): int
    {
        $today = today()->toDateString();

        $query = DB::table('schedule_registrations')
            ->whereNull('deleted_at')
            ->whereIn('status', self::OPEN_STATUSES)
            ->where('registration_date', '<', $today);

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('Không có đăng ký nào cần chốt.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("[dry-run] Sẽ chốt {$count} đăng ký của các ngày trước {$today}.");

            $this->table(
                ['Ngày khám', 'Số đăng ký'],
                (clone $query)->select('registration_date', DB::raw('count(*) as total'))
                    ->groupBy('registration_date')->orderBy('registration_date')
                    ->get()->map(fn ($r) => [$r->registration_date, $r->total])
            );

            return self::SUCCESS;
        }

        // Cập nhật thẳng bằng query builder: hook updating() của model không chạy,
        // nên phải tự xoá pending_since để đồng hồ chờ ngừng đếm.
        $updated = $query->update([
            'status'        => 'completed',
            'pending_since' => null,
            'updated_at'    => now(),
        ]);

        Log::channel('daily')->info('Chốt đăng ký khám cuối ngày', [
            'before' => $today,
            'closed' => $updated,
        ]);

        $this->info("Đã chốt {$updated} đăng ký thành \"Hoàn thành\".");

        return self::SUCCESS;
    }
}
