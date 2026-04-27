<?php

namespace App\Services\Api;

use App\Models\InventoryItem;

class InventoryApiService
{
    public function getInventory()
    {
        return InventoryItem::with([
            'supplier',
            'itemClassification'
        ])->paginate(10);
    }

    public function getById($id)
    {
        return InventoryItem::with([
            'supplier',
            'itemClassification'
        ])->findOrFail($id);
    }
}