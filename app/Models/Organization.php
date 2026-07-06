<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organization extends Model
{
    protected $fillable = ['name', 'short_code', 'description'];

    public function userProfiles()
    {
        return $this->belongsToMany(UserProfile::class, 'organization_user_profile', 'organization_id', 'user_profile_id')
            ->withTimestamps();
    }
}