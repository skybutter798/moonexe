<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id', // added for linking to users table id
        'total',
        'actual',
        'type',
        'wallet',
        'status',
        'txid',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
    
    public function order() {
        return $this->belongsTo(Order::class, 'order_id');
    }
    
    public function transfer()
    {
        return $this->hasOne(Transfer::class, 'txid', 'txid');
    }

}
