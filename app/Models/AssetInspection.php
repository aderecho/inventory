<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AssetInspection extends Model
{
    use LogsActivity;

    protected $fillable = [
        'inventory_item_id',
        'asset_condition_id',
        'inspected_by',
        'inspection_date',
        'remarks',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('inspection')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $item = $this->inventoryItem?->property_number ?? "Item #{$this->inventory_item_id}";
                $condition = $this->assetCondition?->condition_name ?? "Condition #{$this->asset_condition_id}";

                return "Inspection for {$item} ({$condition}) was {$eventName}";
            });
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function assetCondition(): BelongsTo
    {
        return $this->belongsTo(AssetCondition::class);
    }

    public function inspectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('inspection_date')
            ->orderByDesc('created_at');
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('inspection_date')
            ->orderByDesc('created_at');
    }

    public function scopeByCondition($query, int $conditionId)
    {
        return $query->where('asset_condition_id', $conditionId);
    }

    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('inspection_date', [$startDate, $endDate]);
    }

    public function scopeInspectedBy($query, int $userId)
    {
        return $query->where('inspected_by', $userId);
    }
}
