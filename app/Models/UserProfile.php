<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserProfile extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'first_name', 'middle_name', 'last_name', 'contact_number', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('middle_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('contact_number', 'like', "%{$term}%");
        });
    }
}
