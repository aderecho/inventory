<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ItemBorrowingItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'item_borrowing_id',
        'inventory_item_id',
        'quantity',
        'condition_out_id',
        'condition_in_id',
        'returned_at',
        'notes',
    ];

    protected $casts = [
        'returned_at' => 'datetime',
    ];

    protected $appends = ['is_returned'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('borrowing_item')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $item = $this->inventoryItem?->property_number ?? "Item #{$this->inventory_item_id}";

                return "Borrowed item {$item} was {$eventName}";
            });
    }

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(ItemBorrowing::class, 'item_borrowing_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function conditionOut(): BelongsTo
    {
        return $this->belongsTo(AssetCondition::class, 'condition_out_id');
    }

    public function conditionIn(): BelongsTo
    {
        return $this->belongsTo(AssetCondition::class, 'condition_in_id');
    }

    public function getIsReturnedAttribute(): bool
    {
        return $this->returned_at !== null;
    }
}

