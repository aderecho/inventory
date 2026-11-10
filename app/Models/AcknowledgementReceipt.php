<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AcknowledgementReceipt extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'par_date',
        'accountable_persons_id',
        'issued_by_id',
        'category',
        'remarks',
        'reason_for_transfer',
        'created_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('receipt')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Receipt \"{$this->category}\" was {$eventName}");
    }

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
