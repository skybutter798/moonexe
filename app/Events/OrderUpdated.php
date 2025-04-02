<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $pairId;
    public $remainingVolume;
    public $totalVolume;
    
    /**
     * Create a new event instance.
     */
    public function __construct($pairId, $remainingVolume, $totalVolume)
    {
        $this->pairId = $pairId;
        $this->remainingVolume = $remainingVolume;
        $this->totalVolume = $totalVolume;
    }
    
    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        // You can use a public channel or a private channel.
        return new Channel('pair-updates');
    }

    public function broadcastAs()
    {
        return 'OrderUpdated';
    }
}
