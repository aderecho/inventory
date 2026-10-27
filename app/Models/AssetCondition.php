<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssetCondition extends Model
{
    /** What the condition means to the process, not to the reader. */
    public const DISPOSITION_SERVICEABLE = 'serviceable';
    public const DISPOSITION_FOR_DISPOSAL = 'for_disposal';
    public const DISPOSITION_DISPOSED = 'disposed';

    /** Shown whenever an action needs a condition flagged as Disposed. */
    public const MISSING_DISPOSED_MESSAGE = 'No asset condition is flagged as Disposed yet. Add a condition and choose "Disposed" as what it means.';

    protected $fillable = [
        'condition_name',
        'description',
        'disposition',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function inspections(): HasMany
    {
        return $this->hasMany(AssetInspection::class);
    }

    public function latestInspection(): HasOne
    {
        return $this->hasOne(AssetInspection::class)
            ->latestOfMany('inspection_date');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('condition_name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }

    public function getInspectionCountAttribute(): int
    {
        return $this->inspections()->count();
    }

    public function isDisposed(): bool
    {
        return $this->disposition === self::DISPOSITION_DISPOSED;
    }

    public function isForDisposal(): bool
    {
        return $this->disposition === self::DISPOSITION_FOR_DISPOSAL;
    }

    /** Any condition that puts an item on the disposal path, or beyond it. */
    public function isOnDisposalPath(): bool
    {
        return in_array($this->disposition, [
            self::DISPOSITION_FOR_DISPOSAL,
            self::DISPOSITION_DISPOSED,
        ], true);
    }

    public function scopeOfDisposition($query, string $disposition)
    {
        return $query->where('disposition', $disposition);
    }
}
