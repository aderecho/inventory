<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ApiClient extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'api_key', 'user_id', 'allowed_domains', 'is_active'];

    protected $casts = [
        'allowed_domains' => 'array',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('api_client')
            ->logOnly(['name', 'user_id', 'allowed_domains', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "API client \"{$this->name}\" was {$eventName}");
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(AccessToken::class);
    }
}
