<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevBonus extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'package_id', 'level', 'amount', 'target_user', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user');
    }
}
