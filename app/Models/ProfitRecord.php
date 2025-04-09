<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitRecord extends Model
{
    use HasFactory;

    protected $table = 'profitrecords';

    protected $fillable = [
        'user_id',
        'value',
        'record_date',
    ];
}
