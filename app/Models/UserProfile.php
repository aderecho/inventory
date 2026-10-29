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
                    ->map(fn ($name) => self::formatNamePart($name))
                    ->filter()
                    ->join(' ');
            }
        );
    }

    /**
     * Capitalises every word of a name part, whatever case it was saved in:
     * "DELA PEÑA" -> "Dela Peña", "santos-cruz" -> "Santos-Cruz",
     * "O'BRIEN" -> "O'Brien". Multibyte-safe, so ñ and accents keep working.
     */
    public static function formatNamePart(?string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) $name));

        if ($name === '') {
            return '';
        }

        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');

        // Title case treats an apostrophe as part of the word ("O'brien").
        return preg_replace_callback(
            "/(['’])(\\p{Ll})/u",
            fn (array $match) => $match[1] . mb_strtoupper($match[2], 'UTF-8'),
            $name
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
