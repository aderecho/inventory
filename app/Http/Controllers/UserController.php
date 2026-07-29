<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use App\Services\RoomApiService;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected RoomApiService $roomsApi,
    ) {}

    public function index(Request $request)
    {
        $userId = auth()->id();

        $roomResult = $this->roomsApi->fetchRooms();

        $roomsLookup = collect($roomResult['data'])
            ->keyBy('id');

        // Search rooms from the API
        $matchingRoomIds = [];

        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));

            $matchingRoomIds = collect($roomResult['data'])
                ->filter(function ($room) use ($search) {
                    return str_contains(strtolower($room['room_name']), $search)
                        || str_contains(strtolower($room['description']), $search)
                        || str_contains(strtolower($room['building_name']), $search);
                })
                ->pluck('id')
                ->all();
        }

        $items = $this->userService->filterAndPaginateAssignedItems(
            $userId,
            $request->search,
            $request->sort,
            $request->direction ?? 'asc',
            10,
            $matchingRoomIds
        );

        $items->getCollection()->transform(function ($item) use ($roomsLookup) {

            $roomId = $item->latestHistoryLocation?->room_id;

            $item->room_id = $roomId;
            $item->room_name = $roomsLookup[$roomId]['room_name'] ?? 'N/A';
            $item->room_description = $roomsLookup[$roomId]['description'] ?? null;
            $item->building_name = $roomsLookup[$roomId]['building_name'] ?? null;

            return $item;
        });

        return inertia('Users/Dashboard', [
            'user' => $this->userService->getAuthenticatedUser(),
            'items' => $items,
            'stats' => $this->userService->getDashboardStats($userId),
            'filters' => [
                'search' => $request->search,
                'sort' => $request->sort,
                'direction' => $request->direction ?? 'asc',
            ],
        ]);
    }
}
