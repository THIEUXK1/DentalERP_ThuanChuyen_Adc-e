<?php

namespace App\Services;

use App\Enums\DebtStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\TreatmentItemStatus;
use App\Enums\TreatmentPlanStatus;
use App\Models\ClinicRecord;
use App\Models\DentalService;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\PatientDebt;
use App\Models\PatientInvoice;
use App\Models\PatientPayment;
use App\Models\ServiceCategory;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ClinicRecordSyncService
{
    /** service_group (lowercased) alias => canonical category/name (lowercased) to look up */
    private const SERVICE_GROUP_ALIASES = [
        'nắn chỉnh'                 => 'chỉnh nha',
        'dự kiến nắn chỉnh cô định' => 'chỉnh nha',
        'làm răng giả'              => 'răng giả',
        'cắm implan'                => 'implant',
        'hàn thẩm mỹ'               => 'hàn răng',
        'hàn răng sữa bằng fuji 9'  => 'hàn răng',
        'nước xúc miệng'            => 'nước súc miệng',
        'che thủy bảo tồn'          => 'che tủy bảo tồn',
    ];

    private array $serviceIdCache = [];
    private array $employeeIdCache = [];
    private array $patientCache = [];

    /** @var array<string, array<int, object>> patient_code|service_name(lower) => list of Thanh toán rows */
    private array $paymentIndex = [];

    /** @var array<int, object> clinic_records.id => Thanh toán row, every row seen by buildPaymentIndex() */
    private array $allPaymentRows = [];

    /** @var array<int, true> clinic_records.id of Thanh toán rows already turned into a PatientPayment */
    private array $consumedPaymentIds = [];

    public function sync(int $branchId, int $userId, ?int $limit, bool $dryRun, ?\Closure $onProgress = null, ?string $until = null): array
    {
        $stats = [
            'patients_created'          => 0,
            'plans_created'             => 0,
            'plans_skipped_existing'    => 0,
            'items_created'             => 0,
            'invoices_created'          => 0,
            'debts_created'             => 0,
            'payments_created'          => 0,
            'new_services_created'      => 0,
            'errors'                    => [],
        ];

        $this->buildPaymentIndex($until);
        $this->consumedPaymentIds = PatientPayment::query()
            ->whereNotNull('legacy_clinic_record_id')
            ->pluck('legacy_clinic_record_id')
            ->flip()
            ->map(fn () => true)
            ->all();

        $groups = ClinicRecord::query()
            ->where('record_type', 'Thủ thuật')
            ->when($until, fn ($q) => $q->whereDate('record_date', '<=', $until))
            ->select('patient_code', 'record_date')
            ->groupBy('patient_code', 'record_date')
            ->orderBy('record_date')
            ->get();

        if ($limit) {
            $groups = $groups->take($limit);
        }

        $total = $groups->count();
        $processed = 0;

        foreach ($groups as $group) {
            $processed++;
            if ($onProgress && $processed % 500 === 0) {
                $onProgress($processed, $total, $stats);
            }

            $attempt = 0;

            retry:
            try {
                DB::transaction(function () use ($group, $branchId, $userId, $dryRun, &$stats) {
                    $this->processGroup($group->patient_code, $group->record_date, $branchId, $userId, $dryRun, $stats);

                    // Force a rollback of just this group's work when previewing —
                    // avoids needing one giant transaction around the whole run.
                    if ($dryRun) {
                        throw new ClinicRecordDryRunRollback();
                    }
                });
            } catch (ClinicRecordDryRunRollback) {
                // expected — this group's changes were previewed and rolled back
            } catch (\Throwable $e) {
                // generateCode() on Patient/TreatmentPlan/etc. picks max(id)+1, which isn't
                // safe if a real user is concurrently creating records through the app —
                // retry once with a freshly computed code before giving up on this group.
                if ($attempt === 0 && $this->isDuplicateCode($e)) {
                    $attempt++;
                    goto retry;
                }

                $stats['errors'][] = "{$group->patient_code} @ {$group->record_date}: {$e->getMessage()}";

                if ($this->isConnectionLost($e)) {
                    DB::disconnect();
                    DB::reconnect();
                }
            }
        }

        // Any "Thanh toán" row that attachPayments() never claimed — either because its
        // service_name never matches a "Thủ thuật" row at all, or because the matching
        // procedure's $need was already fully covered by other payments — has no home to
        // land in. Build a placeholder plan/invoice straight from these leftover rows so
        // the payment history isn't silently dropped, regardless of whether this patient
        // has other, unrelated procedure-based plans elsewhere.
        // Match the "Y-m-d H:i:s" shape already stored in existing legacy_group_key values
        // (see processGroup()) so the exists() dedup check below keeps working across runs.
        $paymentOnlyGroups = collect($this->allPaymentRows)
            ->reject(fn ($row) => isset($this->consumedPaymentIds[$row->id]))
            ->groupBy(fn ($row) => $row->patient_code.'|'.Carbon::parse($row->record_date)->toDateTimeString())
            ->map(fn ($rows, $key) => (object) [
                'patient_code' => $rows->first()->patient_code,
                'record_date'  => Carbon::parse($rows->first()->record_date)->toDateTimeString(),
            ])
            ->sortBy('record_date')
            ->values();

        if ($limit) {
            $paymentOnlyGroups = $paymentOnlyGroups->take($limit);
        }

        $total += $paymentOnlyGroups->count();

        foreach ($paymentOnlyGroups as $group) {
            $processed++;
            if ($onProgress && $processed % 500 === 0) {
                $onProgress($processed, $total, $stats);
            }

            $attempt = 0;

            retry_payment_only:
            try {
                DB::transaction(function () use ($group, $branchId, $userId, $dryRun, &$stats) {
                    $this->processPaymentOnlyGroup($group->patient_code, $group->record_date, $branchId, $userId, $dryRun, $stats);

                    if ($dryRun) {
                        throw new ClinicRecordDryRunRollback();
                    }
                });
            } catch (ClinicRecordDryRunRollback) {
                // expected
            } catch (\Throwable $e) {
                if ($attempt === 0 && $this->isDuplicateCode($e)) {
                    $attempt++;
                    goto retry_payment_only;
                }

                $stats['errors'][] = "{$group->patient_code} @ {$group->record_date} (payment-only): {$e->getMessage()}";

                if ($this->isConnectionLost($e)) {
                    DB::disconnect();
                    DB::reconnect();
                }
            }
        }

        if ($onProgress) {
            $onProgress($processed, $total, $stats);
        }

        return $stats;
    }

    private function isConnectionLost(\Throwable $e): bool
    {
        $message = $e->getMessage();

        foreach (['no connection to the server', 'server has gone away', 'Lost connection', 'SSL connection has been closed', 'could not connect', 'connection is null', 'There is already an active transaction'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isDuplicateCode(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'duplicate key value violates unique constraint') && str_contains($message, '_code_unique');
    }

    private function processGroup(string $patientCode, string $recordDate, int $branchId, int $userId, bool $dryRun, array &$stats): void
    {
        $groupKey = "{$patientCode}|{$recordDate}";

        if (TreatmentPlan::where('legacy_group_key', $groupKey)->exists()) {
            $stats['plans_skipped_existing']++;
            return;
        }

        $items = ClinicRecord::query()
            ->where('record_type', 'Thủ thuật')
            ->where('patient_code', $patientCode)
            ->whereDate('record_date', $recordDate)
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $firstRow = $items->first();
        $createdAt = Carbon::parse($recordDate)->setTimeFromTimeString($firstRow->record_time ?: '00:00:00');

        $patient = $this->resolvePatient($patientCode, $firstRow, $branchId, $createdAt, $dryRun, $stats);

        $doctorId     = $this->resolveEmployeeId($firstRow->doctor_name);
        $consultantId = $this->resolveEmployeeId($firstRow->consultant_name);

        $subtotal = 0;
        $discount = 0;
        $amountTotal = 0;
        $paidTotal = 0;

        foreach ($items as $row) {
            $unitPrice = (int) $row->unit_price;
            $quantity  = max(1, (int) $row->quantity);

            $subtotal    += $unitPrice * $quantity;
            $discount    += (int) $row->discount;
            $amountTotal += (int) $row->amount;
            $paidTotal   += max(0, (int) $row->amount - (int) $row->remaining_debt);
        }

        $planStatus = $this->mapPlanStatus($items->pluck('status'));

        $plan = new TreatmentPlan([
            'code'             => TreatmentPlan::generateCode(),
            'legacy_group_key' => $groupKey,
            'patient_id'       => $patient->id,
            'doctor_id'        => $doctorId,
            'consultant_id'    => $consultantId,
            'branch_id'        => $branchId,
            'status'           => $planStatus->value,
            'total_amount'     => $subtotal,
            'discount_amount'  => $discount,
            'deposit_amount'   => 0,
            'created_by'       => $userId,
            'start_date'       => $recordDate,
        ]);
        $plan->created_at = $createdAt;
        $plan->updated_at = $createdAt;
        $plan->save();
        $stats['plans_created']++;

        $invoiceTotal  = $amountTotal;
        $invoiceStatus = $paidTotal >= $invoiceTotal
            ? InvoiceStatus::Paid
            : ($paidTotal > 0 ? InvoiceStatus::PartialPaid : InvoiceStatus::Sent);

        $invoice = new PatientInvoice([
            'code'              => PatientInvoice::generateCode(),
            'patient_id'        => $patient->id,
            'treatment_plan_id' => $plan->id,
            'branch_id'         => $branchId,
            'status'            => $invoiceStatus->value,
            'subtotal'          => $subtotal,
            'discount'          => $discount,
            'total'             => $invoiceTotal,
            'amount_paid'       => $paidTotal,
            'created_by'        => $userId,
        ]);
        $invoice->created_at = $createdAt;
        $invoice->updated_at = $createdAt;
        $invoice->save();
        $stats['invoices_created']++;

        $remaining = max(0, $invoiceTotal - $paidTotal);
        if ($remaining > 0) {
            $debtStatus = $paidTotal > 0 ? DebtStatus::Partial : DebtStatus::Pending;

            $debt = new PatientDebt([
                'patient_id'        => $patient->id,
                'treatment_plan_id' => $plan->id,
                'invoice_id'        => $invoice->id,
                'amount'            => $invoiceTotal,
                'paid_amount'       => $paidTotal,
                'remaining'         => $remaining,
                'status'            => $debtStatus->value,
            ]);
            $debt->created_at = $createdAt;
            $debt->updated_at = $createdAt;
            $debt->save();
            $stats['debts_created']++;
        }

        foreach ($items as $row) {
            $unitPrice = (int) $row->unit_price;
            $quantity  = max(1, (int) $row->quantity);
            $serviceId = $this->resolveServiceId($row->service_group ?: $row->service_name, $dryRun, $stats);
            $itemDoctorId = $this->resolveEmployeeId($row->doctor_name) ?? $doctorId;
            $assistantId  = $this->resolveEmployeeId($row->assistant_name);

            $item = new TreatmentPlanItem([
                'treatment_plan_id'     => $plan->id,
                'service_id'            => $serviceId,
                'name'                  => $row->service_name ?: '(không rõ)',
                'quantity'              => $quantity,
                'unit_price'            => $unitPrice,
                'subtotal'              => $unitPrice * $quantity,
                'discount'              => (int) $row->discount,
                'amount'                => (int) $row->amount,
                'status'                => $this->mapItemStatus($row->status)->value,
                'responsible_doctor_id' => $itemDoctorId,
                'assistant_doctor_id'   => $assistantId,
                'diagnosis'             => $row->diagnosis ?: null,
                'notes'                 => $row->symptoms ?: null,
            ]);
            $item->created_at = $createdAt;
            $item->updated_at = $createdAt;
            $item->save();
            $stats['items_created']++;

            $this->attachPayments($patientCode, $row, $invoice, $userId, $stats);
        }
    }

    /**
     * Placeholder plan/invoice for a "Thanh toán" row that attachPayments() couldn't
     * match to any procedure — either the patient never has a "Thủ thuật" row at all,
     * or this specific payment's service just doesn't correspond to one. Built from the
     * payment rows' own service_name so payment history is never silently dropped: any
     * payment implies there must have been a treatment plan for it.
     */
    private function processPaymentOnlyGroup(string $patientCode, string $recordDate, int $branchId, int $userId, bool $dryRun, array &$stats): void
    {
        $groupKey = "PAY|{$patientCode}|{$recordDate}";

        if (TreatmentPlan::where('legacy_group_key', $groupKey)->exists()) {
            $stats['plans_skipped_existing']++;
            return;
        }

        // Only the rows attachPayments() left unclaimed belong here — a row already
        // attached to a procedure-based invoice (possibly dated elsewhere) must not
        // also get counted into this placeholder plan, or its amount would be doubled.
        $rows = ClinicRecord::query()
            ->where('record_type', 'Thanh toán')
            ->where('patient_code', $patientCode)
            ->whereDate('record_date', $recordDate)
            ->get()
            ->reject(fn ($r) => isset($this->consumedPaymentIds[$r->id]));

        if ($rows->isEmpty()) {
            return;
        }

        $firstRow = $rows->first();
        $createdAt = Carbon::parse($recordDate)->setTimeFromTimeString($firstRow->record_time ?: '00:00:00');

        // One item per distinct service actually paid for that day (a patient could
        // pay for two different services in one visit).
        $byService = $rows->groupBy(fn ($r) => mb_strtolower(trim((string) $r->service_name)) ?: 'khác');

        $total = 0;
        foreach ($byService as $serviceRows) {
            $total += $serviceRows->sum(fn ($r) => (int) $r->collected_this_period);
        }

        if ($total <= 0) {
            return;
        }

        $patient = $this->resolvePatient($patientCode, $firstRow, $branchId, $createdAt, $dryRun, $stats);

        $plan = new TreatmentPlan([
            'code'             => TreatmentPlan::generateCode(),
            'legacy_group_key' => $groupKey,
            'patient_id'       => $patient->id,
            'branch_id'        => $branchId,
            'status'           => TreatmentPlanStatus::Completed->value,
            'total_amount'     => $total,
            'discount_amount'  => 0,
            'deposit_amount'   => 0,
            'created_by'       => $userId,
            'start_date'       => $recordDate,
            'notes'            => 'Đồng bộ từ hệ thống cũ: chỉ có dữ liệu thanh toán, không có dòng thủ thuật tương ứng.',
        ]);
        $plan->created_at = $createdAt;
        $plan->updated_at = $createdAt;
        $plan->save();
        $stats['plans_created']++;

        $invoice = new PatientInvoice([
            'code'              => PatientInvoice::generateCode(),
            'patient_id'        => $patient->id,
            'treatment_plan_id' => $plan->id,
            'branch_id'         => $branchId,
            'status'            => InvoiceStatus::Paid->value,
            'subtotal'          => $total,
            'discount'          => 0,
            'total'             => $total,
            'amount_paid'       => $total,
            'created_by'        => $userId,
        ]);
        $invoice->created_at = $createdAt;
        $invoice->updated_at = $createdAt;
        $invoice->save();
        $stats['invoices_created']++;

        foreach ($byService as $serviceRows) {
            $row = $serviceRows->first();
            $amount = $serviceRows->sum(fn ($r) => (int) $r->collected_this_period);
            if ($amount <= 0) {
                continue;
            }

            $serviceId = $this->resolveServiceId($row->service_group ?: $row->service_name, $dryRun, $stats);

            $item = new TreatmentPlanItem([
                'treatment_plan_id' => $plan->id,
                'service_id'        => $serviceId,
                'name'              => $row->service_name ?: '(không rõ)',
                'quantity'          => 1,
                'unit_price'        => $amount,
                'subtotal'          => $amount,
                'discount'          => 0,
                'amount'            => $amount,
                'status'            => TreatmentItemStatus::Completed->value,
            ]);
            $item->created_at = $createdAt;
            $item->updated_at = $createdAt;
            $item->save();
            $stats['items_created']++;

            foreach ($serviceRows as $payRow) {
                $payAmount = (int) $payRow->collected_this_period;
                if ($payAmount <= 0 || isset($this->consumedPaymentIds[$payRow->id])) {
                    continue;
                }

                $payDate = Carbon::parse($payRow->record_date)->setTimeFromTimeString($payRow->record_time ?: '00:00:00');

                $payment = new PatientPayment([
                    'invoice_id'              => $invoice->id,
                    'legacy_clinic_record_id' => $payRow->id,
                    'amount'                  => $payAmount,
                    'method'                  => $this->resolvePaymentMethod($payRow->fund_name)->value,
                    'payment_date'            => $payDate->toDateString(),
                    'created_by'              => $userId,
                ]);
                $payment->created_at = $payDate;
                $payment->updated_at = $payDate;
                $payment->save();

                $this->consumedPaymentIds[$payRow->id] = true;
                $stats['payments_created']++;
            }
        }
    }

    private function attachPayments(string $patientCode, object $row, PatientInvoice $invoice, int $userId, array &$stats): void
    {
        $need = max(0, (int) $row->amount - (int) $row->remaining_debt);
        if ($need <= 0) {
            return;
        }

        $key = $patientCode.'|'.mb_strtolower(trim((string) $row->service_name));
        $candidates = $this->paymentIndex[$key] ?? [];

        $claimed = 0;
        foreach ($candidates as $payRow) {
            if ($claimed >= $need) {
                break;
            }
            if (isset($this->consumedPaymentIds[$payRow->id])) {
                continue;
            }

            $amount = (int) $payRow->collected_this_period;
            if ($amount <= 0) {
                $this->consumedPaymentIds[$payRow->id] = true;
                continue;
            }

            $payDate = Carbon::parse($payRow->record_date)->setTimeFromTimeString($payRow->record_time ?: '00:00:00');

            $payment = new PatientPayment([
                'invoice_id'              => $invoice->id,
                'legacy_clinic_record_id' => $payRow->id,
                'amount'                  => $amount,
                'method'                  => $this->resolvePaymentMethod($payRow->fund_name)->value,
                'payment_date'            => $payDate->toDateString(),
                'created_by'              => $userId,
            ]);
            $payment->created_at = $payDate;
            $payment->updated_at = $payDate;
            $payment->save();

            $this->consumedPaymentIds[$payRow->id] = true;
            $claimed += $amount;
            $stats['payments_created']++;
        }
    }

    private function buildPaymentIndex(?string $until = null): void
    {
        $this->paymentIndex = [];
        $this->allPaymentRows = [];

        // chunkById paginates by "id > lastId", which only visits every row once if the
        // query's primary order is the id column itself. Combining it with orderBy('record_date')
        // (as this used to do) breaks that invariant and silently skips a large share of
        // rows — verified to drop ~49% of "Thanh toán" rows. Let chunkById order by id, and
        // sort each per-key payment list by date afterwards instead.
        ClinicRecord::query()
            ->where('record_type', 'Thanh toán')
            ->when($until, fn ($q) => $q->whereDate('record_date', '<=', $until))
            ->select('id', 'patient_code', 'service_name', 'record_date', 'record_time', 'collected_this_period', 'fund_name')
            ->chunkById(2000, function ($rows) {
                foreach ($rows as $row) {
                    $key = $row->patient_code.'|'.mb_strtolower(trim((string) $row->service_name));
                    $this->paymentIndex[$key][] = $row;
                    $this->allPaymentRows[$row->id] = $row;
                }
            });

        foreach ($this->paymentIndex as $key => $rows) {
            usort($rows, fn ($a, $b) => strcmp((string) $a->record_date, (string) $b->record_date));
            $this->paymentIndex[$key] = $rows;
        }
    }

    private function resolvePatient(string $patientCode, object $row, int $branchId, Carbon $createdAt, bool $dryRun, array &$stats): Patient
    {
        // In dry-run, each group's writes are rolled back individually, so a cached
        // reference from an earlier (already-rolled-back) group would point at a
        // patient id that no longer exists — always re-check the DB instead.
        if (! $dryRun && isset($this->patientCache[$patientCode])) {
            return $this->patientCache[$patientCode];
        }

        $patient = Patient::where('legacy_code', $patientCode)->first();

        if (! $patient) {
            $gender = match (trim((string) $row->gender)) {
                'Nam'   => 'male',
                'Nữ'    => 'female',
                default => null,
            };
            $dob = $row->birth_year ? Carbon::createFromDate((int) $row->birth_year, 1, 1) : null;

            $patient = new Patient([
                'code'        => Patient::generateCode(),
                'legacy_code' => $patientCode,
                'full_name'   => $row->patient_name ?: $patientCode,
                'phone'       => $row->phone ?: null,
                'gender'      => $gender,
                'dob'         => $dob,
                'source'      => $row->customer_source ?: null,
                'branch_id'   => $branchId,
                'is_active'   => true,
            ]);
            $patient->created_at = $createdAt;
            $patient->updated_at = $createdAt;
            $patient->save();
            $stats['patients_created']++;
        }

        if ($dryRun) {
            return $patient;
        }

        return $this->patientCache[$patientCode] = $patient;
    }

    private function resolveEmployeeId(?string $rawName): ?int
    {
        $name = trim((string) $rawName);
        if ($name === '') {
            return null;
        }

        if (str_contains($name, ';')) {
            $name = trim(explode(';', $name)[0]);
        }

        $key = mb_strtolower($name);
        if (array_key_exists($key, $this->employeeIdCache)) {
            return $this->employeeIdCache[$key];
        }

        $employee = Employee::whereRaw('LOWER(full_name) = ?', [$key])->first();

        return $this->employeeIdCache[$key] = $employee?->id;
    }

    private function resolveServiceId(string $rawGroup, bool $dryRun, array &$stats): int
    {
        $key = mb_strtolower(trim($rawGroup));
        if ($key === '') {
            $key = 'khác';
        }

        // Cache by the alias-resolved lookup key (not the raw text) so that different
        // spellings/casings of the same alias always share one created dental_service.
        $lookup = self::SERVICE_GROUP_ALIASES[$key] ?? $key;

        // Same rollback-vs-cache mismatch as resolvePatient(): skip the cache in dry-run.
        if (! $dryRun && isset($this->serviceIdCache[$lookup])) {
            return $this->serviceIdCache[$lookup];
        }

        $service = DentalService::whereHas('category', fn ($q) => $q->whereRaw('LOWER(name) = ?', [$lookup]))->orderBy('id')->first()
            ?? DentalService::whereRaw('LOWER(name) = ?', [$lookup])->first();

        if (! $service) {
            // dental_services no longer has a free-text "category" column — it's now a
            // category_id FK to service_categories — so find-or-create the category row first.
            $category = ServiceCategory::whereRaw('LOWER(name) = ?', [$lookup])->first()
                ?? ServiceCategory::create(['name' => $lookup, 'is_active' => true]);

            $service = DentalService::create([
                'code'          => DentalService::generateCode(),
                'name'          => $lookup,
                'category_id'   => $category->id,
                'service_group' => $lookup,
                'is_active'     => true,
            ]);
            $stats['new_services_created']++;
        }

        if ($dryRun) {
            return $service->id;
        }

        return $this->serviceIdCache[$lookup] = $service->id;
    }

    private function mapPlanStatus(\Illuminate\Support\Collection $itemStatuses): TreatmentPlanStatus
    {
        $set = $itemStatuses->map(fn ($s) => trim((string) $s))->filter()->unique();

        if ($set->contains('Đang điều trị')) {
            return TreatmentPlanStatus::InProgress;
        }

        if ($set->contains('Chưa điều trị') && ! $set->contains('Hoàn thành') && ! $set->contains('Kết thúc')) {
            return TreatmentPlanStatus::Approved;
        }

        return TreatmentPlanStatus::Completed;
    }

    private function mapItemStatus(?string $status): TreatmentItemStatus
    {
        return match (trim((string) $status)) {
            'Đang điều trị' => TreatmentItemStatus::InProgress,
            'Chưa điều trị' => TreatmentItemStatus::Pending,
            default         => TreatmentItemStatus::Completed,
        };
    }

    private function resolvePaymentMethod(?string $fundName): PaymentMethod
    {
        $f = mb_strtolower((string) $fundName);

        if (str_contains($f, 'ngân hàng') || str_contains($f, 'chuyển khoản')) {
            return PaymentMethod::Transfer;
        }

        return PaymentMethod::Cash;
    }
}
