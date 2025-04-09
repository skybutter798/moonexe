<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordCheck extends Model
{
    // Specify the table name if it doesn't follow Laravel's pluralization convention
    protected $table = 'recordchecks';

    // Specify the fillable fields for mass assignment
    protected $fillable = ['name', 'time'];

    // If you are only managing created_at and not updated_at, disable the default timestamps:
    public $timestamps = false;

    // Alternatively, if you later decide to use both created_at and updated_at,
    // remove the $timestamps property and use $table->timestamps() in your migration.
}
