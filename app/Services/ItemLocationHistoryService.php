<?php

namespace App\Services;

use App\Models\InventoryItem;

class ItemLocationHistoryService
{
    public function filterAndPaginateHistory(
        ?string $search = null,
        ?string $acknowledgementStatus = null,
        ?int $roomId = null,
        int $perPage = 10
    ) {
        return InventoryItem::with([
            'latestHistoryLocation',
            'latestAcknowledgementItem.accountablePerson',
        ])
            // Only the number of moves is shown in the list; the modal fetches
            // the rows themselves.
            ->withCount('historyLocations')
            ->when(
                $search,
                fn($query, $search) => $query->searchItemHistory($search)
            )
             ->when($acknowledgementStatus, function ($query, $acknowledgementStatus) {
                if ($acknowledgementStatus === 'with_acknowledgement') {
                    $query->whereHas('latestAcknowledgementItem');
                }

                if ($acknowledgementStatus === 'without_acknowledgement') {
                    $query->whereDoesntHave('latestAcknowledgementItem');
                }
            })
            ->when($roomId, function ($query, $roomId) {
                $query->whereHas('latestHistoryLocation', function ($q) use ($roomId) {
                    $q->where('room_id', $roomId);
                });
            })
            ->whereHas('historyLocations') // only items that have location history
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getItemWithHistory(int $id): InventoryItem
    {
        return InventoryItem::with([
            'itemClassification',
            'latestHistoryLocation',
            'historyLocations',
            'latestAcknowledgementItem.accountablePerson',
        ])
            ->findOrFail($id);
    }
}