<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SamlConfiguration extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name', 'slug', 'mode', 'entity_id', 'sso_url', 'acs_url', 'slo_url',
        'x509_cert', 'signing_algo', 'default_relay_state', 'attribute_release',
        'require_signed_requests', 'sign_responses', 'sign_assertions', 'status',
        'is_active', 'metadata_xml', 'notes', 'last_used_at',
    ];

    protected $casts = [
        'attribute_release' => 'array',
        'require_signed_requests' => 'boolean',
        'sign_responses' => 'boolean',
        'sign_assertions' => 'boolean',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('saml_configuration')
            ->logOnly([
                'name', 'slug', 'mode', 'entity_id', 'sso_url', 'acs_url', 'slo_url',
                'signing_algo', 'default_relay_state', 'attribute_release',
                'require_signed_requests', 'sign_responses', 'sign_assertions',
                'status', 'is_active', 'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "SAML configuration \"{$this->name}\" was {$eventName}");
    }
}
