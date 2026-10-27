<?php

namespace App\Http\Controllers;

use App\Models\AssetCondition;
use App\Models\InventoryItem;
use App\Services\PdfRenderService;
use App\Services\PropertyCardService;
use App\Services\RoomApiService;
use Illuminate\Http\Request;

class PropertyCardController extends Controller
{
    public function __construct(
        protected PropertyCardService $propertyCards,
    ) {}

    public function index(Request $request, RoomApiService $roomsApi)
    {
        $request->validate([
            'search'              => ['nullable', 'string', 'max:255'],
            'room_id'             => ['nullable'],
            'asset_condition_id'  => ['nullable'],
        ]);

        $rooms = collect($roomsApi->fetchRooms()['data'] ?? [])
            ->map(fn (array $room) => [
                'id'        => $room['id'],
                'room_name' => $room['room_name'] ?? '',
                'building_name' => $room['building_name'] ?? '',
            ])
            ->values();

        $items = $this->propertyCards->filterAndPaginate(
            $request->search,
            $request->room_id,
            $request->asset_condition_id,
        );

        $lookup = $rooms->keyBy('id');

        $items->getCollection()->transform(function (InventoryItem $item) use ($lookup) {
            $roomId = $item->latestHistoryLocation?->room_id;

            $item->room_name = $roomId
                ? ($lookup[$roomId]['room_name'] ?? 'N/A')
                : 'N/A';

            return $item;
        });

        return inertia('PropertyCard/Index', [
            'items'           => $items,
            'rooms'           => $rooms,
            'assetConditions' => AssetCondition::orderBy('condition_name')
                ->get(['id', 'condition_name']),
        ]);
    }

    /** The card itself, used by the on-screen modal. */
    public function show(InventoryItem $item)
    {
        return response()->json([
            'card' => $this->propertyCards->cardFor($item),
        ]);
    }

    /** The same card laid out like the printed Property Card form. */
    public function pdf(InventoryItem $item)
    {
        $pdf = app(PdfRenderService::class)->render('prints.property_card', [
            'card' => $this->propertyCards->cardFor($item),
        ]);

        $number = $item->property_number ?: 'item-' . $item->id;
        $fileName = 'PROPERTY_CARD_'
            . preg_replace('/[^A-Za-z0-9._-]/', '_', $number)
            . '.pdf';

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}

