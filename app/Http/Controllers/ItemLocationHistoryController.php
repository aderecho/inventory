<?php

namespace App\Http\Controllers;

use App\Services\ItemLocationHistoryService;
use App\Services\RoomApiService;
use Illuminate\Http\Request;

class ItemLocationHistoryController extends Controller
{
    public function __construct(
        protected ItemLocationHistoryService $historyService,
        protected RoomApiService $roomsApi
    ) {}

    public function index(Request $request)
    {
        $roomResult = $this->roomsApi->fetchRooms();

        $roomsLookup = collect($roomResult['data'])->keyBy('id');

        $rooms = collect($roomResult['data'])
            ->map(fn($room) => [
                'id'            => $room['id'],
                'room_name'     => $room['room_name'],
                'description'   => $room['description'],
                'building_name' => $room['building_name'],
                'capacity'      => $room['capacity'],
            ])
            ->values();

        $search = $request->input('search');
        $status = $request->input('status');

        $items = $this->historyService->filterAndPaginateHistory(
            search: $search,
            status: $status,
        );

        $items->getCollection()->transform(function ($item) use ($roomsLookup) {
            $roomId = $item->latestHistoryLocation?->room_id;

            $item->room_name = isset($roomsLookup[$roomId])
                ? $roomsLookup[$roomId]['room_name']
                : 'N/A';

            $item->room_id = $roomId;

            // Enrich each history entry with room details
            $item->historyLocations->each(function ($history) use ($roomsLookup) {
                $room = $roomsLookup[$history->room_id] ?? null;
                $history->room_name     = $room['room_name']     ?? 'N/A';
                $history->building_name = $room['building_name'] ?? 'N/A';
                $history->description   = $room['description']   ?? 'N/A';
            });

            return $item;
        });

        return inertia('ItemHistory/Index', [
            'rooms' => $rooms,
            'items' => $items,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $roomResult = $this->roomsApi->fetchRooms();

        $roomsLookup = collect($roomResult['data'])->keyBy('id');

        $item = $this->historyService->getItemWithHistory($id);

        $roomId = $item->latestHistoryLocation?->room_id;

        $item->room_name = isset($roomsLookup[$roomId])
            ? $roomsLookup[$roomId]['room_name']
            : 'N/A';

        $item->room_id = $roomId;

        $item->historyLocations->each(function ($history) use ($roomsLookup) {
            $room = $history->room_id ? ($roomsLookup[$history->room_id] ?? null) : null;
            $history->room_name     = $room['room_name']     ?? 'N/A';
            $history->building_name = $room['building_name'] ?? 'N/A';
            $history->description   = $room['description']   ?? 'N/A';
        });

        return inertia('ItemHistory/Show', [
            'item' => $item,
        ]);
    }
}