<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationService
{
    public function getNotifications(User $user)
    {
        $notifications = $user->notifications()
            ->latest()
            ->take(20)
            ->get();

        $itemIds = $notifications
            ->flatMap(
                fn ($notification) => collect(
                    $notification->data['items'] ?? []
                )->pluck('id')
            )
            ->filter()
            ->unique()
            ->values();

        $statuses = InventoryItem::whereIn('id', $itemIds)
            ->pluck('approval_status', 'id');

        $notifications->transform(function ($notification) use ($statuses) {
            $data = $notification->data;

            $data['items'] = collect($data['items'] ?? [])
                ->map(function ($item) use ($statuses) {
                    $id = $item['id'] ?? null;

                    $status = $statuses->get($id)
                        ?? $statuses->get((int) $id);

                    if ($status) {
                        $item['approval_status'] = $status;
                    }

                    return $item;
                })
                ->all();

            $notification->data = $data;

            return $notification;
        });

        return [
            'notifications' => $notifications,
            'unread_count' => $user
                ->unreadNotifications()
                ->count(),
        ];
    }

    public function markAsRead(User $user, string $id): void
    {
        $notification = $user
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();
    }

    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    public function removeItems(
        User $user,
        string $id,
        array $itemIds
    ): int {
        $notification = $user
            ->notifications()
            ->findOrFail($id);

        $data = $notification->data;

        $remainingItems = collect($data['items'] ?? [])
            ->reject(
                fn ($item) => in_array(
                    $item['id'],
                    $itemIds
                )
            )
            ->values()
            ->all();

        if (empty($remainingItems)) {
            $notification->delete();
        } else {
            $notification->update([
                'data' => array_merge($data, [
                    'items' => $remainingItems,
                    'item_count' => count($remainingItems),
                ]),
            ]);
        }

        return count($remainingItems);
    }
}