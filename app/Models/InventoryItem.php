<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class InventoryItem extends Model
{
    use HasFactory, softDeletes, LogsActivity;
    protected $fillable = [
        'item_classification_id',
        'fund_source',
        'invoice',
        'supplier_id',
        'item_name',
        'brand',
        'model',
        'description',
        'quantity',
        'unit',
        'unit_cost',
        'total_amount',
        'pr_number',
        'po_number',
        'property_number',
        'serial_number',
        'remarks',
        'date_acquired',
        'is_private'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('inventory')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $verb = match ($eventName) {
                    'deleted' => $this->isForceDeleting() ? 'deleted' : 'archived',
                    default => $eventName,
                };

                return "Item \"{$this->item_name}\" ({$this->property_number}) was {$verb}";
            });
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        if ($eventName === 'deleted' && ! $this->isForceDeleting()) {
            $activity->event = 'archived';
        }
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function acknowledgementItems()
    {
        return $this->hasMany(
            AcknowledgementItem::class,
            'inventory_item_id'
        );
    }

    public function acknowledgementReceipts()
    {
        return $this->hasMany(AcknowledgementReceipt::class);
    }

    public function latestAcknowledgementItem()
    {
        return $this->hasOne(AcknowledgementItem::class)
            ->latestOfMany();
    }

    public function latestAcknowledgement()
    {
        return $this->hasOne(AcknowledgementReceipt::class)
            ->latestOfMany();
    }

    public function acknowledgementHistory()
    {
        return $this->hasMany(
            AcknowledgementItem::class,
            'inventory_item_id'
        )->with('accountablePerson')
            ->latest();
    }

    public function itemClassification()
    {
        return $this->belongsTo(ItemClassification::class, 'item_classification_id');
    }

    public function inventoryTransaction()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function historyLocations()
    {
        return $this->hasMany(ItemHistoryLocation::class)
            ->latest('created_at');
    }

    public function latestHistoryLocation()
    {
        return $this->hasOne(ItemHistoryLocation::class)->latestOfMany();
    }

    public function assetInspections()
    {
        return $this->hasMany(AssetInspection::class);
    }

    public function inspections()
    {
        return $this->hasMany(AssetInspection::class);
    }

    public function latestInspection()
    {
        return $this->hasOne(AssetInspection::class)->latestOfMany();
    }

    public function scopeWithInspectionCondition($query, int $conditionId)
    {
        return $query->whereHas('inspections', function ($q) use ($conditionId) {
            $q->where('asset_condition_id', $conditionId);
        });
    }

    public function scopeInspectedWithinDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereHas('inspections', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('inspection_date', [$startDate, $endDate]);
        });
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('item_name', 'like', "%{$term}%")
                ->orWhere('unit', 'like', "%{$term}%")
                ->orWhere('property_number', 'like', "%{$term}%")
                ->orWhere('serial_number', 'like', "%{$term}%")
                ->orWhere('invoice', 'like', "%{$term}%")

                // Supplier
                ->orWhereHas('supplier', function ($supplier) use ($term) {
                    $supplier->where(
                        'supplier_name',
                        'like',
                        "%{$term}%"
                    );
                })

                // Accountable Person
                ->orWhereHas(
                    'latestAcknowledgementItem.accountablePerson',
                    function ($person) use ($term) {
                        $terms = preg_split('/\s+/', trim($term));

                        foreach ($terms as $name) {
                            $person->where(function ($q) use ($name) {
                                $q->where('first_name', 'like', "%{$name}%")
                                    ->orWhere(
                                        'middle_name',
                                        'like',
                                        "%{$name}%"
                                    )
                                    ->orWhere(
                                        'last_name',
                                        'like',
                                        "%{$name}%"
                                    );
                            });
                        }
                    }
                );
        });
    }

    public function scopeSearchItemHistory($query, $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('item_name', 'like', "%{$term}%")
                ->orWhere('property_number', 'like', "%{$term}%")
                ->orWhere('serial_number', 'like', "%{$term}%")
                ->orWhereHas('latestAcknowledgementItem.accountablePerson', function ($person) use ($term) {
                    $person->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhereRaw(
                            "CONCAT(first_name, ' ', last_name) LIKE ?",
                            ["%{$term}%"]
                        );
                });
        });
    }

    public function scopeFilterByRoom($query, $roomId)
    {
        if (!$roomId) {
            return $query;
        }

        return $query->whereHas('latestHistoryLocation', function ($q) use ($roomId) {
            $q->where('room_id', $roomId);
        });
    }

    public function scopeSearchAssignedItems($query, $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('item_name', 'like', "%{$term}%")
                ->orWhere('property_number', 'like', "%{$term}%")
                ->orWhere('serial_number', 'like', "%{$term}%")
                ->orWhere('po_number', 'like', "%{$term}%")
                ->orWhere('pr_number', 'like', "%{$term}%")
                ->orWhere('invoice', 'like', "%{$term}%")
                ->orWhereHas('supplier', function ($supplier) use ($term) {
                    $supplier->where('supplier_name', 'like', "%{$term}%");
                })
                ->orWhereHas('latestAcknowledgementItem.acknowledgementReceipts', function ($receipt) use ($term) {
                    $receipt->where('category', 'like', "%{$term}%");
                });
        });
    }
}
