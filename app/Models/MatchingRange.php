<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchingRange extends Model
{
    protected $table = 'matchingranges';

    protected $fillable = ['name', 'min', 'max', 'percentage'];
}
