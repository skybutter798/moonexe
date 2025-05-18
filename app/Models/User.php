<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'last_login',
        'role',
        'status',
        'referral',
        'referral_code',
        'referral_link',
        'package',
        'bonus',
        'avatar',
        'wallet_address',
        'wallet_qr',
        'wallet_expired',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
    ];
    
    protected static function booted()
    {
        static::created(function ($user) {
            $user->wallet()->create([
                'cash_wallet'       => 0.00,
                'trading_wallet'    => 0.00,
                'earning_wallet'    => 0.00,
                'affiliates_wallet' => 0.00,
                'status'            => 1,
            ]);
        });
    }
    
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
    
    public function upline()
    {
        return $this->belongsTo(User::class, 'referral');
    }
    
    public function getIsAdminAttribute()
    {
        return $this->role === 'admin';
    }
    
    public function packageModel()
    {
        return $this->belongsTo(DirectRange::class, 'package');
    }
    
    public function promotion()
    {
        return $this->hasOne(\App\Models\Promotion::class, 'code', 'bonus');
    }



}
