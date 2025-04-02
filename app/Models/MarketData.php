<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketData extends Model
{
    protected $fillable = ['symbol', 'bid', 'ask', 'mid'];
}