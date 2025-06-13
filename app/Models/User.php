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
        'two_fa_enabled',
        'security_pass',
        'google2fa_secret',
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
            // Set default package to 1
            $user->package = 1;
            $user->save();
    
            // Create wallet with 0 balances first
            $wallet = $user->wallet()->create([
                'cash_wallet'       => 0.00,
                'trading_wallet'    => 0.00,
                'earning_wallet'    => 0.00,
                'affiliates_wallet' => 0.00,
                'status'            => 1,
            ]);
    
            // Campaign registration window
            $startMY = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', '2025-05-20 11:01:00', 'Asia/Kuala_Lumpur');
            $endMY   = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', '2025-06-11 18:20:59', 'Asia/Kuala_Lumpur');
    
            // Convert to same timezone as created_at
            $createdAt = $user->created_at->copy()->timezone('Asia/Kuala_Lumpur');
    
            if ($createdAt->between($startMY, $endMY)) {
                // Give 100 trading_wallet bonus
                $wallet->trading_wallet = 100.00;
                $wallet->save();
    
                // Create transfer record
                $txid = 'b_' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    
                \App\Models\Transfer::create([
                    'user_id'     => $user->id,
                    'txid'        => $txid,
                    'from_wallet' => 'cash_wallet',
                    'to_wallet'   => 'trading_wallet',
                    'amount'      => 100.00,
                    'status'      => 'Completed',
                    'remark'      => 'campaign',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
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
    
    public static function getAllDownlineIds($userId)
    {
        $downlines = User::where('referral', $userId)->pluck('id')->toArray();
    
        foreach ($downlines as $downlineId) {
            $downlines = array_merge($downlines, self::getAllDownlineIds($downlineId));
        }
    
        return $downlines;
    }
    
    public static function getUplineIds($userId)
    {
        $uplines = [];
        $current = User::find($userId);
    
        while ($current && $current->referral) {
            $uplines[] = $current->referral;
            $current = User::find($current->referral);
        }
    
        return $uplines;
    }
    
    public static function isInSameTree($fromId, $toId)
    {
        return in_array($toId, self::getAllDownlineIds($fromId)) || in_array($toId, self::getUplineIds($fromId));
    }


}
