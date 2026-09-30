<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiClient extends Model
{
    protected $fillable = ['name', 'api_key', 'user_id', 'allowed_domains', 'is_active'];

    protected $casts = [
        'allowed_domains' => 'array',
        'is_active' => 'boolean',
    ];

    public function accessTokens(): HasMany
    {
        return $this->hasMany(AccessToken::class);
    }
}
