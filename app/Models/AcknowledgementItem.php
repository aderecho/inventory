<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcknowledgementItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'acknowledgement_id',
        'inventory_item_id',
        'accountable_person_id',
        'issued_by_id',
        'status'
    ];

    public $timestamps = true;

    public function accountablePerson()
    {
        return $this->belongsTo(UserProfile::class, 'accountable_person_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(UserProfile::class, 'issued_by_id');
    }

    public function acknowledgementReceipts()
    {
        return $this->belongsTo(AcknowledgementReceipt::class, 'acknowledgement_id');
    }

    public function inventoryItems()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->whereHas('inventoryItems', function ($q) use ($term) {
            $q->where('item_name', 'like', "%{$term}%")
                ->orWhere('property_number', 'like', "%{$term}%");
        })
            ->orWhereHas('acknowledgementReceipts', function ($q) use ($term) {
                $q->where('category', 'like', "%{$term}%");
            });
    }

}
