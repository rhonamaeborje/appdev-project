<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'item_name',
        'category',
        'condition_before',
        'condition_after',
        'availability_status',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];
}