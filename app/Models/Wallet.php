<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    // Updated fillable array
    protected $fillable = [
        'user_id',
        'cash_wallet',
        'trading_wallet',   // was register_wallet
        'earning_wallet',   // was epoint_wallet
        'affiliates_wallet',// new column
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
