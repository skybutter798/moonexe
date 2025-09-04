<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StakingLog extends Model
{
    protected $fillable = [
        'user_id',
        'stake_date',
        'total_balance',
        'daily_roi',
        'daily_profit',
    ];
}
