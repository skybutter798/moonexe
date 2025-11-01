<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserReferralCache extends Model
{
    protected $table = 'user_referral_caches';

    protected $fillable = [
        'user_id',
        'data',
        'last_refreshed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'last_refreshed_at' => 'datetime',
    ];
}