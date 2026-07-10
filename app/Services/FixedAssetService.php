<?php

namespace App\Services;

use App\Enums\FixedAssetStatus;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Services\Hkd\HkdJournalService;
use Illuminate\Support\Facades\DB;

class FixedAssetService
{
    public function create(array $data): FixedAsset
    {
        $monthly = (int) ceil($data['acquisition_cost'] / $data['useful_life_months']);

        return FixedAsset::createWithCode([
            ...$data,
            'monthly_depreciation'    => $monthly,
            'accumulated_depreciation' => 0,
            'net_book_value'          => $data['acquisition_cost'],
            'status'                  => FixedAssetStatus::Active,
        ]);
    }

    /**
     * Run monthly depreciation for all active assets.
     * Idempotent: skips assets already depreciated for the period.
     */
    public function runMonthlyDepreciation(string $period): array
    {
        $assets = FixedAsset::where('status', FixedAssetStatus::Active)
            ->where(fn ($q) => $q->whereNull('last_depreciation_period')
                ->orWhere('last_depreciation_period', '<', $period))
            ->get();

        $processed = 0;
        $skipped   = 0;

        foreach ($assets as $asset) {
            // Double-check via unique constraint — skip if already exists
            $exists = FixedAssetDepreciation::where('fixed_asset_id', $asset->id)
                ->where('period', $period)->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $amount = min($asset->monthly_depreciation, $asset->net_book_value);

            DB::transaction(function () use ($asset, $period, $amount) {
                $dep = FixedAssetDepreciation::create([
                    'fixed_asset_id'       => $asset->id,
                    'period'               => $period,
                    'amount'               => $amount,
                    'accumulated_before'   => $asset->accumulated_depreciation,
                    'net_book_value_after' => $asset->net_book_value - $amount,
                ]);

                $newAccumulated = $asset->accumulated_depreciation + $amount;
                $newNetBook     = $asset->net_book_value - $amount;

                $asset->update([
                    'accumulated_depreciation' => $newAccumulated,
                    'net_book_value'           => $newNetBook,
                    'last_depreciation_period' => $period,
                    'status' => $newNetBook <= 0
                        ? FixedAssetStatus::FullyDepreciated
                        : FixedAssetStatus::Active,
                ]);

                // Ghi sổ chi phí khấu hao TT152
                app(HkdJournalService::class)->postDepreciation($dep);
            });

            $processed++;
        }

        return ['processed' => $processed, 'skipped' => $skipped];
    }

    public function getSchedule(FixedAsset $asset): array
    {
        $posted = $asset->depreciations->keyBy('period');
        $schedule = [];

        $accumulated = 0;
        $netBook     = $asset->acquisition_cost;
        $startYear   = (int) $asset->acquisition_date->format('Y');
        $startMonth  = (int) $asset->acquisition_date->format('m');

        for ($i = 0; $i < $asset->useful_life_months; $i++) {
            $month  = (($startMonth - 1 + $i) % 12) + 1;
            $year   = $startYear + (int) floor(($startMonth - 1 + $i) / 12);
            $period = sprintf('%04d-%02d', $year, $month);

            $amount      = min($asset->monthly_depreciation, $netBook);
            $accumulated += $amount;
            $netBook     -= $amount;

            $schedule[] = [
                'period'          => $period,
                'amount'          => $amount,
                'accumulated'     => $accumulated,
                'net_book_value'  => max(0, $netBook),
                'posted'          => isset($posted[$period]),
            ];

            if ($netBook <= 0) {
                break;
            }
        }

        return $schedule;
    }

    public function dispose(FixedAsset $asset): void
    {
        $asset->update(['status' => FixedAssetStatus::Disposed]);
    }
}
