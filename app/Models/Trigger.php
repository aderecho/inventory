<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trigger extends Model
{
    protected $table = 'trigger';

    protected $fillable = [
        'date',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => 'integer',
    ];
}