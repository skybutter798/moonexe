<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;

class CampaignBalanceUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $newBalance;

    public function __construct($newBalance)
    {
        $this->newBalance = $newBalance;
    }

    public function broadcastOn()
    {
        \Log::info('📡 Broadcasting on campaign-channel');
        return new Channel('campaign-channel'); // public channel
    }

    public function broadcastAs()
    {
        return 'balance.updated';
    }
    
    public function broadcastWith()
    {
        return ['newBalance' => $this->newBalance];
    }

}
