<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function index()
    {
        $data = $this->notificationService
            ->getNotifications(auth()->user());

        return response()->json($data);
    }

    public function markAsRead(Request $request, string $id)
    {
        $this->notificationService
            ->markAsRead(auth()->user(), $id);

        return response()->json([
            'success' => true,
        ]);
    }

    public function markAllAsRead()
    {
        $this->notificationService
            ->markAllAsRead(auth()->user());

        return response()->json([
            'success' => true,
        ]);
    }

    public function removeItems(Request $request, string $id)
    {
        $remaining = $this->notificationService
            ->removeItems(
                auth()->user(),
                $id,
                $request->input('item_ids', [])
            );

        return response()->json([
            'success' => true,
            'remaining' => $remaining,
        ]);
    }
}