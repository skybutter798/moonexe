<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DirectRange extends Model
{
    // Specify the table name if it's not the plural of the model name
    protected $table = 'directranges';

    // Mass assignable fields
    protected $fillable = ['name', 'min', 'max', 'percentage'];
}
