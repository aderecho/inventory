<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ItemHistoryLocation extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'item_history_location';

    protected $fillable = [
        'inventory_item_id',
        'room_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('location')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $item = $this->inventoryItem?->property_number ?? "Item #{$this->inventory_item_id}";

                return "Location of {$item} was {$eventName}";
            });
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
