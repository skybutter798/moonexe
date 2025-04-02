<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'code', 
        'multiply', 
        'used', 
        'max_use',
        'amount',
    ];
}
