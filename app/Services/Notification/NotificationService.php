<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Creates persistent notifications and pushes them in realtime over the user's
 * private channel.
 */
class NotificationService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function notify(User|int $user, string $type, array $payload = []): Notification
    {
        $userId = $user instanceof User ? $user->id : $user;

        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'payload' => $payload,
            'is_read' => false,
        ]);

        // Realtime push (no-op if broadcasting is set to the log/null driver).
        event(new NotificationCreated($notification));

        return $notification;
    }

    /**
     * @return LengthAwarePaginator<Notification>
     */
    public function paginateFor(User $user, int $perPage, int $page, bool $unreadOnly = false): LengthAwarePaginator
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->when($unreadOnly, fn ($q) => $q->where('is_read', false))
            ->latest()
            ->paginate(perPage: $perPage, page: $page);
    }

    public function markRead(User $user, int $notificationId): Notification
    {
        $notification = Notification::query()
            ->where('user_id', $user->id)
            ->findOrFail($notificationId);

        if (! $notification->is_read) {
            $notification->forceFill(['is_read' => true, 'read_at' => now()])->save();
        }

        return $notification;
    }

    public function markAllRead(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function unreadCount(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }
}
