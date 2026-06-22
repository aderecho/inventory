<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItemFile extends Model
{
    protected $fillable = [
        'acknowledgement_item_id',
        'file_group_id',
        'file_path',
        'file_type',
        'upload_by',
    ];

    public function acknowledgementItem()
    {
        return $this->belongsTo(AcknowledgementItem::class, 'acknowledgement_item_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(UserProfile::class, 'upload_by');
    }
}