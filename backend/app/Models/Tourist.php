<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tourist extends Model
{
    protected $fillable = [
        'full_name',
        'address',
        'gender',
        'contact_number',
        'email',
        'date_registered',
        'is_archived',
    ];

    protected $casts = [
        'date_registered' => 'date',
        'is_archived' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
