<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Annoucement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'content',
        'status',
        'banner_image',
    ];
}
