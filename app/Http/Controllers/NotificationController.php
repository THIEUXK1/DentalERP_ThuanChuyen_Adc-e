<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $svc) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Notifications/Index', [
            'notifications' => AppNotification::where('user_id', $user->id)
                ->orderByDesc('id')
                ->paginate(30)
                ->through(fn ($n) => [
                    'id' => $n->id,
                    'type' => $n->type->value,
                    'type_label' => $n->type->label(),
                    'type_color' => $n->type->color(),
                    'title' => $n->title,
                    'message' => $n->message,
                    'link' => $n->link,
                    'is_read' => $n->is_read,
                    'created_at' => $n->created_at->diffForHumans(),
                ]),
            'unreadCount' => AppNotification::where('user_id', $user->id)->unread()->count(),
        ]);
    }

    public function markRead(AppNotification $notification): RedirectResponse
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }
        $this->svc->markRead($notification);

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->svc->markAllRead($request->user());

        return back()->with('success', 'Đã đánh dấu tất cả đã đọc.');
    }
}
