<?php

namespace App\Services\Api;

use App\Models\InventoryItem;

class InventoryApiService
{
    public function getInventory()
    {
        return InventoryItem::all();
    }

    public function getById($id)
    {
        return InventoryItem::all()->findOrFail($id);
    }
}