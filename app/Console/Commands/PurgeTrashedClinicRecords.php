<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dọn tàn dư của các đợt import clinic_records bị hỏng (01/07 và 04/07/2026): những
 * bản ghi đã xoá mềm, phần lớn mang ngày sai do lỗi đọc ngày kiểu Mỹ của file Excel gốc.
 *
 * An toàn:
 *  - Luôn ghi file sao lưu CSV trước khi xoá cứng.
 *  - Giữ lại bản ghi còn được patient_payments.legacy_clinic_record_id trỏ tới,
 *    để không làm mất dấu vết nguồn của phiếu thu thật.
 *  - Mặc định là dry-run; phải truyền --force mới thực sự xoá.
 */
class PurgeTrashedClinicRecords extends Command
{
    protected $signature = 'clinic-records:purge-trashed
                            {--force : Thực sự xoá cứng (mặc định chỉ báo cáo)}
                            {--backup= : Đường dẫn file CSV sao lưu (mặc định storage/app/backups)}
                            {--chunk=2000 : Số dòng xoá mỗi lượt}';

    protected $description = 'Xoá cứng các bản ghi clinic_records đã xoá mềm (tàn dư import lỗi), có sao lưu CSV';

    public function handle(): int
    {
        @ini_set('memory_limit', '1024M');

        $referenced = DB::table('patient_payments')
            ->whereNotNull('legacy_clinic_record_id')
            ->distinct()
            ->pluck('legacy_clinic_record_id')
            ->all();

        $query = DB::table('clinic_records')->whereNotNull('deleted_at');
        if ($referenced !== []) {
            $query->whereNotIn('id', $referenced);
        }

        $total = (clone $query)->count();
        $kept = DB::table('clinic_records')->whereNotNull('deleted_at')->count() - $total;

        if ($total === 0) {
            $this->info('Không có bản ghi xoá mềm nào cần dọn.');

            return self::SUCCESS;
        }

        $this->table(
            ['Năm (record_date)', 'Số dòng'],
            (clone $query)
                ->selectRaw("COALESCE(to_char(record_date,'YYYY'),'—') as nam, count(*) as tong")
                ->groupBy(DB::raw("COALESCE(to_char(record_date,'YYYY'),'—')"))
                ->orderBy('nam')->get()
                ->map(fn ($r) => [$r->nam, (string) $r->tong])
        );

        $this->info("Sẽ xoá cứng: {$total} dòng.");
        if ($kept > 0) {
            $this->warn("Giữ lại {$kept} dòng vì đang được phiếu thu (patient_payments) trỏ tới.");
        }

        if (! $this->option('force')) {
            $this->warn('[dry-run] Chưa xoá gì. Thêm --force để thực hiện.');

            return self::SUCCESS;
        }

        $backup = (string) ($this->option('backup') ?: storage_path('app/backups/clinic_records_trashed_'.now()->format('Ymd_His').'.csv'));
        $written = $this->backup(clone $query, $backup);
        $this->info("Đã sao lưu {$written} dòng vào {$backup}");

        if ($written !== $total) {
            $this->error('Số dòng sao lưu không khớp số dòng sẽ xoá — dừng lại để tránh mất dữ liệu.');

            return self::FAILURE;
        }

        $chunk = max(100, (int) $this->option('chunk'));
        $deleted = 0;
        while (true) {
            $ids = (clone $query)->orderBy('id')->limit($chunk)->pluck('id')->all();
            if ($ids === []) {
                break;
            }

            DB::transaction(fn () => DB::table('clinic_records')->whereIn('id', $ids)->delete());
            $deleted += count($ids);
            $this->line("  đã xoá {$deleted}/{$total}");
        }

        Log::channel('daily')->info('Xoá cứng tàn dư clinic_records', [
            'deleted' => $deleted,
            'kept_referenced' => $kept,
            'backup' => $backup,
        ]);

        $this->info("Hoàn tất: xoá cứng {$deleted} dòng. Khôi phục được từ file sao lưu nếu cần.");

        return self::SUCCESS;
    }

    private function backup(Builder $query, string $path): int
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fh = fopen($path, 'w');
        fwrite($fh, "\xEF\xBB\xBF");
        $written = 0;

        $query->orderBy('id')->chunk(5000, function ($rows) use ($fh, &$written) {
            foreach ($rows as $row) {
                $arr = (array) $row;
                if ($written === 0) {
                    fputcsv($fh, array_keys($arr));
                }
                fputcsv($fh, array_values($arr));
                $written++;
            }
        });

        fclose($fh);

        return $written;
    }
}
