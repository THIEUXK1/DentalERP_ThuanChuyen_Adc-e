<?php

namespace App\Console\Commands;

use App\Services\ClinicRecordSyncService;
use Illuminate\Console\Command;
use Spatie\Activitylog\Facades\Activity;

class SyncClinicRecordsCommand extends Command
{
    protected $signature = 'clinic-records:sync
        {--dry-run : Preview changes without writing to the database}
        {--branch=2 : Branch ID to assign to migrated records}
        {--user=1 : User ID to attribute created_by to}
        {--limit= : Only process this many (patient, date) groups, for testing}
        {--until= : Only process legacy records dated on or before this date (YYYY-MM-DD); leaves anything after it untouched}';

    protected $description = 'Sync clinic_records (legacy system data) into patients/treatment_plans/invoices/payments/debts';

    public function handle(ClinicRecordSyncService $service): int
    {
        @ini_set('memory_limit', '1024M');

        // Skip activity_log writes for every patient/plan/invoice created — this is
        // a bulk historical import, not user-driven activity worth auditing, and
        // skipping it cuts a meaningful chunk of round-trips to the remote DB.
        Activity::disableLogging();

        $dryRun  = (bool) $this->option('dry-run');
        $branch  = (int) $this->option('branch');
        $user    = (int) $this->option('user');
        $limit   = $this->option('limit') ? (int) $this->option('limit') : null;
        $until   = $this->option('until') ?: null;

        $this->info($dryRun ? 'Running in DRY-RUN mode (no data will be written).' : 'Running LIVE — data will be written.');
        $this->info("Branch ID: {$branch}, User ID (created_by): {$user}".($limit ? ", limit: {$limit} groups" : '').($until ? ", until: {$until}" : ''));

        $stats = $service->sync($branch, $user, $limit, $dryRun, function (int $processed, int $total, array $stats) {
            $this->line("  … {$processed}/{$total} groups — patients:{$stats['patients_created']} plans:{$stats['plans_created']} items:{$stats['items_created']} payments:{$stats['payments_created']} errors:".count($stats['errors']));
        }, $until);

        $this->newLine();
        $this->info('=== Summary ===');
        $this->line("Patients created:        {$stats['patients_created']}");
        $this->line("Treatment plans created:  {$stats['plans_created']}");
        $this->line("Plans skipped (existing): {$stats['plans_skipped_existing']}");
        $this->line("Treatment items created:  {$stats['items_created']}");
        $this->line("Invoices created:         {$stats['invoices_created']}");
        $this->line("Debts created:            {$stats['debts_created']}");
        $this->line("Payments created:         {$stats['payments_created']}");
        $this->line("New dental_services made: {$stats['new_services_created']}");

        if (! empty($stats['errors'])) {
            $this->warn(count($stats['errors']).' group(s) failed:');
            foreach (array_slice($stats['errors'], 0, 30) as $err) {
                $this->error("  {$err}");
            }
            if (count($stats['errors']) > 30) {
                $this->warn('  … and '.(count($stats['errors']) - 30).' more.');
            }
        }

        return self::SUCCESS;
    }
}
