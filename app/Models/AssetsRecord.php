<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetsRecord extends Model
{
    use HasFactory;

    protected $table = 'assetsrecords';

    protected $fillable = [
        'user_id',
        'value',
        'record_date',
    ];
}
