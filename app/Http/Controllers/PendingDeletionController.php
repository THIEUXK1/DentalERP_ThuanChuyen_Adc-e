<?php

namespace App\Http\Controllers;

use App\Models\PendingDeletion;
use Illuminate\Http\RedirectResponse;

class PendingDeletionController extends Controller
{
    public function undo(PendingDeletion $pendingDeletion): RedirectResponse
    {
        abort_if(
            $pendingDeletion->user_id !== auth()->id() && ! auth()->user()?->can('admin'),
            403
        );

        abort_if(! $pendingDeletion->isPending(), 422, 'Không thể hoàn tác: đã xóa hoặc đã bị huỷ.');

        $pendingDeletion->update(['cancelled_at' => now()]);

        return back()->with('success', "Đã hoàn tác xóa {$pendingDeletion->label}.");
    }
}
