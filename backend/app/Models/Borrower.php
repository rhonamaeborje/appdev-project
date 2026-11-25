<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Borrower extends Model
{
    protected $fillable = [
        'full_name',
        'address',
        'contact_number',
        'email',
        'id_presented',
        'date_registered',
        'is_archived',
    ];

    protected $casts = [
        'id_presented' => 'boolean',
        'date_registered' => 'date',
        'is_archived' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}