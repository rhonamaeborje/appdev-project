<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TouristSpot extends Model
{
    protected $fillable = [
        'spot_name',
        'address',
        'description',
        'image_path',
        'is_archived',
    ];
}