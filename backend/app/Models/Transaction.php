<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Transaction extends Model
{
    protected $fillable = [
        'borrower_id',
        'tourist_id',
        'staff_id',
        'date_borrowed_or_visited',
        'return_date',
        'purpose',
        'arrival_time',
        'departure_time',
        'status',
        'remarks',
        'is_archived',
    ];

    protected $casts = [
        'date_borrowed_or_visited' => 'date',
        'return_date' => 'date',
        'is_archived' => 'boolean',
    ];

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Borrower::class);
    }

    public function tourist(): BelongsTo
    {
        return $this->belongsTo(Tourist::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(
            Item::class,
            'transaction_item',
            'transaction_id',
            'item_id'
        )->withTimestamps();
    }
}