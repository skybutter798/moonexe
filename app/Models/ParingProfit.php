<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParingProfit extends Model
{
    protected $table = 'paringprofits';

    protected $fillable = [
        'name',
        'min',
        'max',
        'percentage',
    ];
}
