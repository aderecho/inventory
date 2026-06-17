<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemHistoryLocation extends Model
{
    use HasFactory;

    protected $table = 'item_history_location';

    protected $fillable = [
        'inventory_item_id',
        'room_id',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
