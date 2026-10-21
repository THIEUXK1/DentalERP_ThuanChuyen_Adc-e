<?php

namespace App\Http\Controllers;

use App\Models\PendingDeletion;
use App\Models\TreatmentPlan;
use App\Services\TreatmentPlanService;
use Illuminate\Http\RedirectResponse;

class PendingDeletionController extends Controller
{
    public function undo(PendingDeletion $pendingDeletion, TreatmentPlanService $plans): RedirectResponse
    {
        abort_if(
            $pendingDeletion->user_id !== auth()->id() && ! auth()->user()?->can('admin'),
            403
        );

        abort_if(! $pendingDeletion->isPending(), 422, 'Không thể hoàn tác: đã xóa hoặc đã bị huỷ.');

        if ($pendingDeletion->deletable_type === TreatmentPlan::class) {
            try {
                $plans->cancelDeletion($pendingDeletion);
            } catch (\RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }
        } else {
            $pendingDeletion->update(['cancelled_at' => now()]);
        }

        return back()->with('success', "Đã hoàn tác xóa {$pendingDeletion->label}.");
    }
}
