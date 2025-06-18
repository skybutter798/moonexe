<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pair extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_id',
        'pair_id',
        'rate',
        'volume',
        'gate_time',
        'min_rate',
        'max_rate',
        'end_time',
        'status',
    ];

    // Cast the gate_time attribute to a datetime instance.
    protected $casts = [
        'gate_time' => 'integer',
    ];

    // If you want an accessor for remaining volume:
    /*public function getRemainingVolumeAttribute()
    {
        // For demo purposes, assume 90% of the total volume is still available.
        return round($this->volume * 0.9, 2);
    }*/
    
    public function getTradedVolumeAttribute()
    {
        // Only consider buy orders, where 'buy' is not null.
        return $this->orders->whereNotNull('buy')->sum('receive');
    }


    // Define relationships.
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function pairCurrency()
    {
        return $this->belongsTo(Currency::class, 'pair_id');
    }
    
    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class, 'pair_id');
    }
    
    public function latestWebhookPayment()
    {
        return $this->hasOne(WebhookPayment::class, 'pair_id')->latestOfMany();
    }


}
