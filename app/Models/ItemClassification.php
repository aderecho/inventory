<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class ItemClassification extends Model
{
    use HasFactory,SoftDeletes, LogsActivity;

    protected $fillable = [
        'classification_code',
        'classification_name',
        'status',
    ];

    public $timestamps = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('item_classification')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $verb = match ($eventName) {
                    'deleted' => $this->isForceDeleting() ? 'deleted' : 'archived',
                    default => $eventName,
                };

                return "Item Classification \"{$this->classification_name}\" ({$this->classification_code}) was {$verb}";
            });
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        if ($eventName === 'deleted' && ! $this->isForceDeleting()) {
            $activity->event = 'archived';
        }
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('classification_code', 'like', "%{$term}%")
                ->orWhere('classification_name', 'like', "%{$term}%");
        });
    }
}
