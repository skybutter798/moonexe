<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $fillable = [
        'user_id',
        'txid',
        'from_wallet',
        'to_wallet',
        'amount',
        'status',
        'remark',
    ];
}
