<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'eshare',
        'max_payout',
        'profit',
        'status',
        'remark',
    ];
    
    public function packageDetail()
    {
        // Adjust the foreign key if needed. For example, if the column is named 'package_id':
        return $this->belongsTo(Package::class, 'package');
    }
}
