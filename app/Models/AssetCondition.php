<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssetCondition extends Model
{
    protected $fillable = [
        'condition_name',
        'description',
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
}