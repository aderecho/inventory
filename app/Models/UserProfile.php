<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class UserProfile extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'user_id',
        'employee_number',
        'title_name',
        'first_name',
        'middle_name',
        'last_name',
        'ext_name',
        'primary_unit_division_department',
        'employee_primary_unit_college',
        'contact_number',
    ];
    protected $appends = ['full_name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accountable_person()
    {
        return $this->hasMany(AcknowledgementItem::class, 'accountable_person_id');
    }

    public function issued_by_id()
    {
        return $this->hasMany(AcknowledgementItem::class, 'issued_by_id');
    }

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: function () {
                return collect([
                    $this->first_name,
                    $this->middle_name,
                    $this->last_name,
                ])
                    ->filter()
                    ->map(fn($name) => ucfirst(strtolower(trim($name))))
                    ->join(' ');
            }
        );
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
