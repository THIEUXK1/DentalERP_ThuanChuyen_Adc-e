<?php

namespace App\Console\Commands;

use App\Models\PendingDeletion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExecutePendingDeletions extends Command
{
    protected $signature = 'pending-deletions:execute';
    protected $description = 'Hard-delete records whose grace period has expired';

    public function handle(): void
    {
        $due = PendingDeletion::query()
            ->whereNull('cancelled_at')
            ->whereNull('executed_at')
            ->where('execute_at', '<=', now())
            ->get();

        foreach ($due as $pending) {
            $model = $pending->deletable;

            if ($model) {
                Log::channel('daily')->info('PendingDeletion executed', [
                    'type'    => $pending->deletable_type,
                    'id'      => $pending->deletable_id,
                    'label'   => $pending->label,
                    'reason'  => $pending->reason,
                    'user_id' => $pending->user_id,
                ]);

                $model->delete();
            }

            $pending->update(['executed_at' => now()]);
        }
    }
}
