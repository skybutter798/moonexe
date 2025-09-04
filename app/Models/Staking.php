<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Staking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'txid',
        'amount',
        'interest',
        'balance',
        'status',
    ];

    // Define relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
