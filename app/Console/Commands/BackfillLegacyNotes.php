<?php

namespace App\Console\Commands;

use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
 * hệ nên bắt buộc so tên dịch vụ. (Bản đầu từng lùi về "item đầu tiên cùng ngày" khi
 * tên lệch → ngày nhiều dịch vụ bị gán nhầm; đã bỏ, và --fix dùng để sửa lại.)
 *
 * Chính sách ghi: item chưa có notes → điền; đã có mà chưa chứa ghi chú → nối thêm; đã
 * chứa rồi → bỏ qua. Không bao giờ ghi đè mất dữ liệu cũ.
 *
 * --fix: ghép lại toàn bộ dòng bambu ↔ item trong từng ngày theo độ giống tên (ghép cặp
 * giống nhất trước, tie-break theo giờ), rồi chỉ sửa item do đợt backfill ghi
 * (updated_at trong khoảng --touched-from/--touched-to) hoặc đang trống. Item nhân viên
 * đã chỉnh tay sau đó (updated_at mới hơn) không bị đụng tới.
 */
class BackfillLegacyNotes extends Command
{
    protected $signature = 'legacy-notes:backfill
        {--parsed= : Đường dẫn file JSON đã parse (bắt buộc)}
        {--out= : Đường dẫn CSV đối soát xuất ra (bắt buộc)}
        {--dry-run : Chỉ đối soát, không ghi DB}
        {--sep= : Ký tự nối khi append (mặc định " | ")}
        {--fix : Sửa ghi chú bị gán nhầm của đợt backfill trước}
        {--touched-from=2026-09-21 : (--fix) đầu khoảng updated_at của đợt backfill}
        {--touched-to=2026-09-23 : (--fix) cuối khoảng updated_at của đợt backfill}
        {--min-sim=0.6 : (--fix) độ giống tên tối thiểu để ghép dòng bambu với item}';

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

        if ($this->option('fix')) {
            return $this->runFix($data, $outPath, $dryRun);
        }

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

    /**
     * Sửa ghi chú bị gán nhầm: ghép lại dòng bambu ↔ item trong từng ngày rồi đặt notes
     * của item đúng bằng ghi chú dòng được ghép (hoặc trống nếu dòng đó không có ghi chú).
     *
     * @param  array<string, array<int, array<string, mixed>>>  $data
     */
    private function runFix(array $data, string $outPath, bool $dryRun): int
    {
        $from = (string) $this->option('touched-from');
        $to = (string) $this->option('touched-to');
        $minSim = (float) $this->option('min-sim');

        $this->info(($dryRun ? 'DRY-RUN' : 'LIVE')." --fix | touched {$from} → {$to} | min-sim {$minSim}");
        $this->line('Bệnh nhân: '.count($data));

        $fh = fopen($outPath, 'w');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['tc1', 'item_id', 'date', 'item_name', 'matched_service', 'score', 'action', 'current_notes', 'expected_notes']);

        $stats = ['ok' => 0, 'correct' => 0, 'clear' => 0, 'fill' => 0, 'uncertain' => 0, 'skip_append' => 0, 'staff_diff' => 0, 'keep_orphan' => 0, 'written' => 0];
        $bar = $this->output->createProgressBar(count($data));

        foreach ($data as $tc => $rows) {
            $bar->advance();
            $rows = array_values(array_filter((array) $rows, fn ($r) => ! empty($r['date'])));
            if (! $rows) {
                continue;
            }

            $plans = TreatmentPlan::where('legacy_group_key', 'like', $tc.'|%')->pluck('id');
            $items = DB::table('treatment_plan_items')->whereIn('treatment_plan_id', $plans)
                ->selectRaw("id, name, notes, updated_at, to_char(created_at,'YYYY-MM-DD') as d, to_char(created_at,'HH24:MI:SS') as t")
                ->get();

            $rowsByDate = collect($rows)->groupBy('date');
            $updates = [];

            foreach ($items->groupBy('d') as $date => $dayItems) {
                $dayRows = ($rowsByDate[$date] ?? collect())->values()->all();
                $assign = $this->assignByName($dayItems->all(), $dayRows, $minSim);
                // Ghi chú bambu cùng ngày nhưng dòng đó không có item nào ở hệ mới (hệ mới gộp
                // ít dòng hơn) — item đang mang ghi chú này thì giữ, xoá đi là mất hẳn dữ liệu.
                $assignedIdx = array_flip(array_map(fn ($a) => $a[2], $assign));
                $orphanNotes = [];
                foreach ($dayRows as $ri => $r) {
                    if (! isset($assignedIdx[$ri]) && trim((string) ($r['note'] ?? '')) !== '') {
                        $orphanNotes[] = (string) $r['note'];
                    }
                }

                foreach ($dayItems as $it) {
                    $touched = $it->updated_at >= $from && $it->updated_at < $to;
                    $cur = trim((string) $it->notes);
                    [$row, $score] = $assign[$it->id] ?? [null, 0.0, null];

                    // Chỉ đụng item do đợt backfill ghi, hoặc đang trống (ghi chú đúng có thể đã bị đẩy sang item khác).
                    // Item nhân viên đã sửa tay thì giữ nguyên, chỉ báo nếu vẫn lệch bambu để rà.
                    if (! $touched && $cur !== '') {
                        if ($row !== null && ! $this->sameNote($cur, trim((string) ($row['note'] ?? '')))) {
                            $stats['staff_diff']++;
                            fputcsv($fh, [$tc, $it->id, $date, $it->name, $row['service'], round($score, 2), 'staff_diff', $cur, (string) ($row['note'] ?? '')]);
                        }

                        continue;
                    }

                    if ($row === null) {
                        if ($touched) {
                            $stats['uncertain']++;
                            fputcsv($fh, [$tc, $it->id, $date, $it->name, '', '', 'uncertain', $cur, '']);
                        }

                        continue;
                    }

                    $expected = trim((string) ($row['note'] ?? ''));
                    if ($this->sameNote($cur, $expected)) {
                        $stats['ok']++;

                        continue;
                    }
                    if ($cur !== '' && collect($orphanNotes)->contains(fn ($o) => $this->sameNote($cur, $o))) {
                        $stats['keep_orphan']++;
                        fputcsv($fh, [$tc, $it->id, $date, $it->name, $row['service'], round($score, 2), 'keep_orphan', $cur, $expected]);

                        continue;
                    }
                    // Hai item từng được nối (append) sau dữ liệu cũ — để rà tay, không tự cắt.
                    if ($touched && str_contains($cur, ' | ')) {
                        $stats['skip_append']++;
                        fputcsv($fh, [$tc, $it->id, $date, $it->name, $row['service'], round($score, 2), 'skip_append', $cur, $expected]);

                        continue;
                    }

                    $action = $cur === '' ? 'fill' : ($expected === '' ? 'clear' : 'correct');
                    $stats[$action]++;
                    $updates[$it->id] = $expected === '' ? null : mb_substr($expected, 0, self::NOTES_MAX);
                    fputcsv($fh, [$tc, $it->id, $date, $it->name, $row['service'], round($score, 2), $action, $cur, $expected]);
                }
            }

            if (! $dryRun && $updates) {
                DB::transaction(function () use ($updates, &$stats) {
                    foreach ($updates as $id => $notes) {
                        TreatmentPlanItem::where('id', $id)->update(['notes' => $notes]);
                        $stats['written']++;
                    }
                });
            }
        }

        $bar->finish();
        fclose($fh);

        $this->newLine(2);
        $this->info('=== Tổng kết --fix ===');
        foreach (['ok', 'correct', 'clear', 'fill', 'uncertain', 'skip_append', 'staff_diff', 'keep_orphan'] as $k) {
            $this->line(str_pad($k.':', 13).$stats[$k]);
        }
        $this->line('written:     '.($dryRun ? '0 (dry-run)' : $stats['written']));
        $this->line("CSV:         {$outPath}");

        return self::SUCCESS;
    }

    /**
     * Ghép item ↔ dòng bambu cùng ngày: tính độ giống tên mọi cặp, nhận cặp giống nhất trước.
     * Tên trùng nhau (vd nhiều dòng "ghi chú") thì phân xử bằng độ gần của giờ.
     *
     * @return array<int, array{0: array<string, mixed>, 1: float, 2: int}> item_id => [row, score, row index]
     */
    private function assignByName(array $items, array $rows, float $minSim): array
    {
        $pairs = [];
        foreach ($items as $it) {
            foreach ($rows as $ri => $r) {
                $sim = $this->similarity((string) $it->name, (string) $r['service']);
                if ($sim < $minSim) {
                    continue;
                }
                $pairs[] = [$sim + 0.05 * $this->timeCloseness((string) $it->t, (string) ($r['time'] ?? '')), $it->id, $ri, $sim];
            }
        }
        usort($pairs, fn ($a, $b) => $b[0] <=> $a[0]);

        $out = [];
        $usedRows = [];
        foreach ($pairs as [, $itemId, $ri, $sim]) {
            if (isset($out[$itemId]) || isset($usedRows[$ri])) {
                continue;
            }
            $out[$itemId] = [$rows[$ri], $sim, $ri];
            $usedRows[$ri] = true;
        }

        return $out;
    }

    private function similarity(string $a, string $b): float
    {
        $a = $this->normName($a);
        $b = $this->normName($b);
        if ($a === '' || $b === '') {
            return 0.0;
        }
        if ($a === $b) {
            return 1.0;
        }
        similar_text($a, $b, $pct);

        return $pct / 100;
    }

    /** Bỏ dấu, chữ thường, chỉ giữ chữ/số — để "răng giả sứ thường r44" ≈ "Răng giả sứ thường 44". */
    private function normName(string $s): string
    {
        $s = strtolower(Str::ascii($s));

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $s));
    }

    /** 1 khi cùng giờ, giảm dần về 0 khi lệch tới 10 phút. */
    private function timeCloseness(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }
        $diff = abs(strtotime("1970-01-01 $a") - strtotime("1970-01-01 $b"));

        return max(0.0, 1 - min($diff, 600) / 600);
    }

    private function sameNote(string $a, string $b): bool
    {
        $n = fn ($s) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)));

        return $n($a) === $n($b);
    }
}
