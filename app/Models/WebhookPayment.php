<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookPayment extends Model
{
    protected $table = 'webhook_payments'; // <- ADD THIS LINE

    protected $fillable = [
        'pay_id',
        'pair_id',
        'method',
        'amount',
        'status',
        'created_at',
        'updated_at',
    ];

    public $timestamps = false;
}

