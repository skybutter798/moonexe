<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RealWallet extends Model
{
    use HasFactory;

    protected $table = 'real_wallet';

    protected $fillable = [
        'user_id',
        'cash_wallet',
        'trading_wallet',
        'earning_wallet',
        'affiliates_wallet',
        'bonus_wallet',
        'remark',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
