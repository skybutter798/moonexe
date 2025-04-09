<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchingRecord extends Model
{
    protected $table = 'matchingrecords'; // Explicitly set the table name

    protected $fillable = [
        'user_id',
        'referral_group',
        'total_contribute',
        'total_trade',
        'total_ref_contribute',
        'total_deposit',
        'ref_balance',
        'matching_balance',
        'record_date',
    ];
}
