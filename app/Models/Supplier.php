<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'supplier_name',
        'contact_no',
        'email',
        'address',
        'status',
    ];

    public $timestamps = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('supplier')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $verb = match ($eventName) {
                    'deleted' => $this->isForceDeleting() ? 'deleted' : 'archived',
                    default => $eventName,
                };

                return "Supplier \"{$this->supplier_name}\" was {$verb}";
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
            $q->where('supplier_name', 'like', "%{$term}%")
                ->orWhere('contact_no', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('address', 'like', "%{$term}%");
        });
    }
}
