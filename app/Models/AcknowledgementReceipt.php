<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcknowledgementReceipt extends Model
{
    use HasFactory;
    protected $fillable = [
        'par_date',
        'accountable_persons_id',
        'issued_by_id',
        'category',
        'remarks',
        'created_by'
    ];

    public function inventoryItems()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acknowledgementItems()
    {
        return $this->hasMany(AcknowledgementItem::class, 'acknowledgement_id');
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->whereHas('inventoryItems', function ($q) use ($term) {
            $q->where('item_name', 'like', "%{$term}%")
                ->orWhere('unit', 'like', "%{$term}%")
                ->orWhere('property_number', 'like', "%{$term}%");
        });
    }
}
