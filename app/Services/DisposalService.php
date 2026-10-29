<?php

namespace App\Services;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DisposalService
{
    /**
     * The disposal module is the home of disposed property: nothing that is
     * still in service belongs in this list.
     */
    public function filterAndPaginateDisposal(
        ?string $search = null,
        ?string $costRange = null,
        int|string|null $assetConditionId = null,
        ?string $acknowledgementStatus = null,
        int $perPage = 10
    ) {
        return InventoryItem::with([
            'supplier',
            'latestHistoryLocation',
            'latestInspection.assetCondition',
            'latestAcknowledgementItem.accountablePerson',
            'acknowledgementHistory.accountablePerson',
        ])
            ->where('status', InventoryItem::STATUS_DISPOSED)
            ->when(
                $search,
                fn($query, $search) =>
                $query->search($search)
            )
            ->when($costRange, function ($query, $costRange) {
                [$min, $max] = array_pad(explode('-', $costRange), 2, null);

                $min = $min !== '' ? $min : null;
                $max = $max !== '' ? $max : null;

                if ($min !== null && $max !== null) {
                    $query->whereBetween('unit_cost', [(float) $min, (float) $max]);
                } elseif ($min !== null) {
                    $query->where('unit_cost', '>=', (float) $min);
                } elseif ($max !== null) {
                    $query->where('unit_cost', '<=', (float) $max);
                }
            })
            ->when(
                filled($assetConditionId),
                fn($query) => $query->whereHas('latestInspection', function ($q) use ($assetConditionId) {
                    $q->where('asset_condition_id', $assetConditionId);
                })
            )
            ->when($acknowledgementStatus, function ($query, $acknowledgementStatus) {
                if ($acknowledgementStatus === 'with_acknowledgement') {
                    $query->whereHas('latestAcknowledgementItem');
                }

                if ($acknowledgementStatus === 'without_acknowledgement') {
                    $query->whereDoesntHave('latestAcknowledgementItem');
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Archives (soft deletes) a disposed item, the same as archiving from the
     * inventory, so it can still be restored or force deleted from the item
     * archive. Only disposed property can be archived from here.
     */
    public function archive(InventoryItem $item): void
    {
        if ($item->status !== InventoryItem::STATUS_DISPOSED) {
            throw ValidationException::withMessages([
                'item' => 'Only disposed items can be archived from the Disposal module.',
            ]);
        }

        $item->delete();
    }

    /**
     * Archives the disposed items among the given ids, one at a time so each
     * keeps its model events and activity log entry. Anything not disposed
     * (or already archived) is left alone; returns how many were archived.
     */
    public function batchArchive(array $ids): int
    {
        $items = InventoryItem::whereIn('id', $ids)
            ->where('status', InventoryItem::STATUS_DISPOSED)
            ->get();

        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                $item->delete();
            }
        });

        return $items->count();
    }
}
