<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditInvoicePayments extends Command
{
    protected $signature = 'invoices:audit-payments {--limit=50 : Max mismatched invoices to list}';
    protected $description = 'Find invoices where amount_paid does not match the sum of their patient_payments rows';

    public function handle(): int
    {
        $mismatched = DB::table('patient_invoices as pi')
            ->leftJoinSub(
                DB::table('patient_payments')->select('invoice_id', DB::raw('sum(amount) as paysum'))->groupBy('invoice_id'),
                'pp',
                'pp.invoice_id',
                '=',
                'pi.id'
            )
            ->whereRaw('pi.amount_paid <> coalesce(pp.paysum, 0)')
            ->select('pi.id', 'pi.code', 'pi.amount_paid', DB::raw('coalesce(pp.paysum, 0) as paysum'))
            ->get();

        if ($mismatched->isEmpty()) {
            $this->info('No mismatches found — every invoice.amount_paid matches the sum of its patient_payments rows.');

            return self::SUCCESS;
        }

        $totalDiff = $mismatched->sum(fn ($r) => $r->amount_paid - $r->paysum);

        $this->error("Found {$mismatched->count()} invoices where amount_paid != sum(patient_payments.amount). Total diff: ".number_format($totalDiff)." VND.");

        Log::warning('invoices:audit-payments found mismatches', [
            'count' => $mismatched->count(),
            'total_diff' => $totalDiff,
        ]);

        activity('invoice_payment_audit')
            ->withProperties([
                'count' => $mismatched->count(),
                'total_diff' => $totalDiff,
                'sample' => $mismatched->take(10)->map(fn ($r) => [
                    'invoice_id' => $r->id,
                    'code' => $r->code,
                    'amount_paid' => $r->amount_paid,
                    'paysum' => $r->paysum,
                    'diff' => $r->amount_paid - $r->paysum,
                ])->values()->all(),
            ])
            ->log("Phát hiện {$mismatched->count()} hóa đơn lệch amount_paid, tổng chênh lệch ".number_format($totalDiff)." đ");

        $limit = (int) $this->option('limit');
        $this->table(
            ['Invoice ID', 'Code', 'amount_paid', 'sum(payments)', 'diff'],
            $mismatched->take($limit)->map(fn ($r) => [
                $r->id, $r->code, number_format($r->amount_paid), number_format($r->paysum), number_format($r->amount_paid - $r->paysum),
            ])
        );

        return self::FAILURE;
    }
}
