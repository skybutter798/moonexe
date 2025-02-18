<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'txid',
        'amount',
        'trc20_address',
        'status',
    ];
}
