<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferType extends Model
{
    protected $fillable = [
        'name',
    ];

    public function acknowledgementItems()
    {
        return $this->belongsToMany(
            AcknowledgementItem::class,
            'acknowledgement_item_transfer_type',
            'transfer_type_id',
            'acknowledgement_item_id'
        )->withTimestamps();
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where('name', 'like', "%{$term}%");
    }
}
