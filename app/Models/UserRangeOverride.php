<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRangeOverride extends Model
{
    protected $table = 'user_range_overrides';

    protected $fillable = [
        'user_id',
        'direct_range_id',
        'matching_range_id',
        'direct_percentage_override',
        'matching_percentage_override',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'effective_from' => 'datetime',
        'effective_to'   => 'datetime',
    ];
}
