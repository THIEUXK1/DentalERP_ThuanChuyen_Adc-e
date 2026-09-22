<?php

namespace App\Console\Commands;

use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\Activity;

/**
 * Bù "Ghi chú thủ thuật" từ hệ thống cũ (bambu.vn) vào treatment_plan_items.notes.
 *
 * Nguồn: file JSON đã parse từ trang chi tiết bệnh nhân của hệ thống cũ, dạng
 *   { "TC1.xxxx": [ {date, time, service, note, theid}, ... ] }
 * (ghi chú vốn KHÔNG có trong bộ Excel nên clinic_records.treatment_step_notes rỗng —
 *  phải lấy trực tiếp từ live).
 *
 * Khớp: legacy_code → treatment_plans.legacy_group_key → treatment_plan_items,
 * theo (ngày tạo item + tên dịch vụ). Thứ tự trong cùng ngày KHÔNG trùng giữa hai
 * hệ nên bắt buộc so tên dịch vụ, chỉ lùi về "chỉ theo ngày" khi không có ứng viên tên.
 *
 * Chính sách ghi: item chưa có notes → điền; đã có mà chưa chứa ghi chú → nối thêm; đã
 * chứa rồi → bỏ qua. Không bao giờ ghi đè mất dữ liệu cũ.
 */
class BackfillLegacyNotes extends Command
{
    protected $signature = 'legacy-notes:backfill
        {--parsed= : Đường dẫn file JSON đã parse (bắt buộc)}
        {--out= : Đường dẫn CSV đối soát xuất ra (bắt buộc)}
        {--dry-run : Chỉ đối soát, không ghi DB}
        {--sep= : Ký tự nối khi append (mặc định " | ")}';

    protected $description = 'Bù ghi chú thủ thuật từ hệ thống cũ vào treatment_plan_items.notes';

    /** Giới hạn độ dài cột notes (varchar 255). */
    private const NOTES_MAX = 255;

    public function handle(): int
    {
        $parsedPath = (string) $this->option('parsed');
        $outPath = (string) $this->option('out');
        $dryRun = (bool) $this->option('dry-run');
        $sep = $this->option('sep') ?: ' | ';

        if (! is_file($parsedPath)) {
            $this->error("Không tìm thấy file parsed: {$parsedPath}");

            return self::FAILURE;
        }
        if ($outPath === '') {
            $this->error('Thiếu --out cho file CSV đối soát.');

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($parsedPath), true);
        if (! is_array($data)) {
            $this->error('File parsed không phải JSON hợp lệ.');

            return self::FAILURE;
        }

        Activity::disableLogging();

        $this->info($dryRun ? 'DRY-RUN — không ghi DB.' : 'LIVE — sẽ ghi vào treatment_plan_items.notes.');
        $this->line('Bệnh nhân: '.count($data));

        $fh = fopen($outPath, 'w');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['tc1', 'patient_id', 'plan_id', 'item_id', 'date', 'service', 'action', 'current_notes', 'new_note', 'result']);

        $stats = ['fill' => 0, 'append' => 0, 'skip_dup' => 0, 'unmatched' => 0, 'truncated' => 0, 'written' => 0];
        $norm = fn ($s) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));

        foreach ($data as $tc => $rows) {
            $rows = array_values(array_filter((array) $rows, fn ($r) => ! empty($r['note'])));
            if (! $rows) {
                continue;
            }

            $plans = TreatmentPlan::where('legacy_group_key', 'like', $tc.'|%')->pluck('id');
            $patientId = DB::table('patients')->where('legacy_code', $tc)->value('id');
            $items = TreatmentPlanItem::whereIn('treatment_plan_id', $plans)
                ->selectRaw("id, treatment_plan_id, name, notes, to_char(created_at::date,'YYYY-MM-DD') as d")
                ->orderBy('id')->get();

            $used = [];
            $updates = []; // id => new_notes

            foreach ($rows as $r) {
                $note = trim((string) $r['note']);
                $cand = $items->first(fn ($it) => ! isset($used[$it->id]) && $it->d === $r['date'] && $norm($it->name) === $norm($r['service']));
                if (! $cand) {
                    $cand = $items->first(fn ($it) => ! isset($used[$it->id]) && $it->d === $r['date']);
                }

                if (! $cand) {
                    $stats['unmatched']++;
                    fputcsv($fh, [$tc, $patientId, '', '', $r['date'], $r['service'], 'UNMATCHED', '', $note, 'skip']);

                    continue;
                }

                $used[$cand->id] = true;
                $cur = (string) $cand->notes;

                if (trim($cur) === '') {
                    $action = 'fill';
                    $new = $note;
                } elseif (mb_stripos($cur, $note) !== false) {
                    $stats['skip_dup']++;
                    fputcsv($fh, [$tc, $patientId, $cand->treatment_plan_id, $cand->id, $cand->d, $cand->name, 'skip_dup', $cur, $note, 'skip']);

                    continue;
                } else {
                    $action = 'append';
                    $new = $cur.$sep.$note;
                }

                if (mb_strlen($new) > self::NOTES_MAX) {
                    $new = mb_substr($new, 0, self::NOTES_MAX);
                    $stats['truncated']++;
                }

                $stats[$action]++;
                $updates[$cand->id] = $new;
                fputcsv($fh, [$tc, $patientId, $cand->treatment_plan_id, $cand->id, $cand->d, $cand->name, $action, $cur, $note, $dryRun ? 'dry' : 'pending']);
            }

            if (! $dryRun && $updates) {
                DB::transaction(function () use ($updates, &$stats) {
                    foreach ($updates as $id => $new) {
                        TreatmentPlanItem::where('id', $id)->update(['notes' => $new]);
                        $stats['written']++;
                    }
                });
            }
        }

        fclose($fh);

        $this->newLine();
        $this->info('=== Tổng kết ===');
        $this->line("fill:      {$stats['fill']}");
        $this->line("append:    {$stats['append']}");
        $this->line("skip_dup:  {$stats['skip_dup']}");
        $this->line("unmatched: {$stats['unmatched']}");
        $this->line("truncated: {$stats['truncated']}");
        $this->line('written:   '.($dryRun ? '0 (dry-run)' : $stats['written']));
        $this->line("CSV:       {$outPath}");

        return self::SUCCESS;
    }
}
