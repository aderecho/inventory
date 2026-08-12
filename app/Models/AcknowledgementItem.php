<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class AcknowledgementItem extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'acknowledgement_id',
        'inventory_item_id',
        'accountable_person_id',
        'issued_by_id',
        'status'
    ];

    public $timestamps = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('acknowledgement')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $verb = match ($eventName) {
                    'created' => 'assigned',
                    'updated' => 'updated',
                    'deleted' => 'removed',
                    default => $eventName,
                };

                $item = $this->inventoryItems?->item_name ?? 'Unknown Item';
                $propertyNumber = $this->inventoryItems?->property_number ?? 'N/A';

                $person = $this->accountablePerson?->full_name
                    ?? trim(
                        ($this->accountablePerson?->first_name ?? '') . ' ' .
                            ($this->accountablePerson?->last_name ?? '')
                    );

                $receipt = $this->acknowledgementReceipts?->receipt_number
                    ?? $this->acknowledgementReceipts?->category
                    ?? "Receipt #{$this->acknowledgement_id}";

                return "{$person} {$verb} Item \"{$item}\" ({$propertyNumber}) under {$receipt} receipt";
            });
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        if ($eventName === 'deleted') {
            $activity->event = 'removed';
        }
    }

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

    public function file()
    {
        return $this->hasOne(InventoryItemFile::class, 'acknowledgement_item_id');
    }

    public function files()
    {
        return $this->hasMany(InventoryItemFile::class, 'acknowledgement_item_id');
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
