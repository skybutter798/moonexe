<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $fillable = [
        'user_id',
        'txid',
        'amount',
        'trc20_address',
        'status',
    ];
}
