<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RoomApiService;
use App\Services\DisposalService;

class DisposalController extends Controller
{
    public function __construct(
        protected DisposalService $disposalService,
    ) {}

    public function index(Request $request, RoomApiService $roomsApi)
    {
        $roomResult = $roomsApi->fetchRooms();

        $roomsLookup = collect($roomResult['data'])
            ->keyBy('id');

        $rooms = collect($roomResult['data'])
            ->map(function ($room) {
                return [
                    'id' => $room['id'],
                    'room_name' => $room['room_name'],
                    'room_code' => $room['room_code'],
                    'description' => $room['description'],
                    'building' => $room['building'],
                    'building_name' => $room['building_name'],
                    'capacity' => $room['capacity'],
                ];
            })
            ->values();

        $search = $request->input('search');
        $costRange = $request->input('cost_range');
        $status = $request->input('status');
        $acknowledgementStatus = $request->input('acknowledgement_status');

        $items = $this->disposalService->filterAndPaginateDisposal(
            $search,
            $costRange,
            $status,
            $acknowledgementStatus,
        );

        $items->getCollection()->transform(function ($item) use ($roomsLookup) {

            $roomId = $item->latestHistoryLocation?->room_id;

            $item->room_name = isset($roomsLookup[$roomId])
                ? $roomsLookup[$roomId]['room_name']
                : 'N/A';

            $item->room_id = $roomId;

            return $item;
        });

        return inertia('Disposal/Index', [
            'rooms' => $rooms,
            'items' => $items,
        ]);
    }
}
