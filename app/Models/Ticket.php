<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'assigned_to', 'subject', 'message', 'status', 'priority', 'closed_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class)->whereNull('reply_id'); 
    }


    public function replies()
    {
        return $this->hasMany(TicketReply::class);
    }
}
