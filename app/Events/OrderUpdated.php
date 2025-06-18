<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

        // ✅ Log to default laravel.log
        /*Log::info('🔔 OrderUpdated event fired', [
            'pair_id' => $pairId,
            'remaining_volume' => $remainingVolume,
            'total_volume' => $totalVolume,
        ]);*/
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('pair-updates');
    }

    public function broadcastAs()
    {
        return 'OrderUpdated';
    }
}
