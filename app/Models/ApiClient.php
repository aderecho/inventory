<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiClient extends Model
{
    protected $fillable = ['name', 'api_key', 'user_id', 'allowed_domains', 'is_active'];

    protected $casts = [
        'allowed_domains' => 'array',
        'is_active' => 'boolean',
    ];
}
