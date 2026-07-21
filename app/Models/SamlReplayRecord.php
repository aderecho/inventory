<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SamlReplayRecord extends Model
{
    protected $fillable = ['assertion_id', 'response_id', 'request_id', 'issuer', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];
}
