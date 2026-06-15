<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'item_classification_id',
        'fund_source',
        'invoice',
        'supplier_id',
        'room_id',
        'item_name',
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
        'status'
    ];

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
                ->orWhereHas('supplier', function ($supplier) use ($term) {
                    $supplier->where('supplier_name', 'like', "%{$term}%");
                });
        });
    }
}
