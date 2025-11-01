<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAssetsCache extends Model
{
    protected $table = 'user_assets_cache';

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