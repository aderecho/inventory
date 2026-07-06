<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmbedToken extends Model
{
    protected $fillable = ['token', 'user_id', 'allowed_domains', 'expires_at'];

    protected $casts = [
        'allowed_domains' => 'array',
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
