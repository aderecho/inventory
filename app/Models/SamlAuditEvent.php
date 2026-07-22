<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SamlAuditEvent extends Model
{
    protected $fillable = [
        'saml_configuration_id', 'event_name', 'entity_id', 'user_id',
        'request_id', 'response_id', 'ip_address', 'outcome', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];
}
