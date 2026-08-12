<?php

namespace App\Services;

use App\Models\InventoryItem;
use Illuminate\Http\Request;

class DisposalService
{
    public function filterAndPaginateDisposal(
        ?string $search = null,
        ?string $costRange = null,
        int|string|null $status = null,
        ?string $acknowledgementStatus = null,
        int $perPage = 10
    ) {
        return InventoryItem::with([
            'supplier',
            'latestHistoryLocation',
            'latestAcknowledgementItem.accountablePerson',
            'acknowledgementHistory.accountablePerson',
        ])
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
                !is_null($status),
                fn($query) => $query->where('status', $status)
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
}
