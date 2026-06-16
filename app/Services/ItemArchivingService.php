<?php

namespace App\Services;

use App\Models\InventoryItem;

class ItemArchivingService
{
    public function filterAndPaginateArchive(
        ?string $search = null,
        int|string|null $status = null,
        int $perPage = 10
    ) {
        return InventoryItem::onlyTrashed()
            ->with(['itemClassification', 'supplier'])
            ->when($search, fn($query, $search) => $query->search($search))
            ->when(
                filled($status),
                fn($query) => $query->where('status', (int) $status)
            )
            ->orderBy('deleted_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }
}
