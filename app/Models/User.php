<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles;
    protected $fillable = ['email', 'password', 'status'];

    protected $hidden = ['password'];

    public function accountableReceipts()
    {
        return $this->hasMany(AcknowledgementReceipt::class, 'accountable_persons_id');
    }

    public function issuedReceipts()
    {
        return $this->hasMany(AcknowledgementReceipt::class, 'issued_by_id');
    }

    public function createdReceipts()
    {
        return $this->hasMany(AcknowledgementReceipt::class, 'created_by');
    }

    public function accountablePersons()
    {
        return $this->hasMany(AccountablePerson::class);
    }

    public function userProfiles()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('email', 'like', "%{$term}%")
                ->orWhereHas('userProfiles', function ($profile) use ($term) {
                    $profile->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('middle_name', 'like', "%{$term}%")
                        ->orWhere('contact_number', 'like', "%{$term}%");
                });
        });
    }
}
