<?php

namespace App\Services\Api;

use App\Models\InventoryItem;

class InventoryApiService
{
    public function getInventory()
    {
        return InventoryItem::with('latestHistoryLocation', 'itemClassification')->get();
    }

    public function getById($id)
    {
        return InventoryItem::with('latestHistoryLocation', 'itemClassification')->findOrFail($id);
    }
}