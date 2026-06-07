<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    public function notify(User $user, NotificationType $type, string $title, string $message, ?string $link = null): AppNotification
    {
        return AppNotification::create([
            'user_id' => $user->id,
            'type' => $type->value,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
        ]);
    }

    public function markRead(AppNotification $notification): void
    {
        $notification->update(['is_read' => true]);
    }

    public function markAllRead(User $user): void
    {
        AppNotification::where('user_id', $user->id)->where('is_read', false)->update(['is_read' => true]);
    }
}
