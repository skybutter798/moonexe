<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'c_name',
        'status',
    ];

    // Optional: Define relationships if needed
    public function pairs()
    {
        return $this->hasMany(Pair::class, 'currency_id');
    }
}
