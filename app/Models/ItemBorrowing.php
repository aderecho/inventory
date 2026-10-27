<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemBorrowing extends Model
{
    public const TYPE_STAFF = 'staff';
    public const TYPE_STUDENT = 'student';
    public const TYPE_OUTSIDER = 'outsider';

    public const STATUS_BORROWED = 'borrowed';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reference_code',
        'borrower_type',
        'borrower_name',
        'borrower_user_id',
        'employee_number',
        'student_number',
        'organization',
        'contact_number',
        'notes',
        'borrowed_at',
        'due_at',
        'returned_at',
        'status',
        'released_by',
        'received_by',
        'borrowed_from_name',
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    protected $appends = ['is_overdue'];

    public function items(): HasMany
    {
        return $this->hasMany(ItemBorrowingItem::class);
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_user_id');
    }

    /** The staff member the items were borrowed from. */
    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * A ticket without a due date is never overdue - some items are lent for as
     * long as the office needs them.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_BORROWED
            && $this->due_at !== null
            && $this->due_at->isPast();
    }

    public function getBorrowerLabelAttribute(): string
    {
        return match ($this->borrower_type) {
            self::TYPE_STAFF => 'Staff',
            self::TYPE_STUDENT => 'Student',
            default => 'Outsider',
        };
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('reference_code', 'like', '%' . $term . '%')
                ->orWhere('borrower_name', 'like', '%' . $term . '%')
                ->orWhere('employee_number', 'like', '%' . $term . '%')
                ->orWhere('student_number', 'like', '%' . $term . '%')
                ->orWhere('organization', 'like', '%' . $term . '%');
        });
    }

    /** Status filter used by the page: borrowed | overdue | returned. */
    public function scopeFilterStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            self::STATUS_BORROWED => $query->where('status', self::STATUS_BORROWED)
                ->where(fn ($q) => $q->whereNull('due_at')->orWhere('due_at', '>=', now())),

            'overdue' => $query->where('status', self::STATUS_BORROWED)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now()),

            self::STATUS_RETURNED => $query->where('status', self::STATUS_RETURNED),

            default => $query,
        };
    }
}

