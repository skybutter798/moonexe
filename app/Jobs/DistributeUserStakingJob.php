<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class DistributeUserStakingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        \Log::info("🚀 Running DistributeUserStakingJob for user {$this->userId}");
    
        try {
            Artisan::call('staking:distribute-user', [
                'userId' => $this->userId,
            ]);
            \Log::info("✅ Finished staking distribution for user {$this->userId}");
        } catch (\Throwable $e) {
            \Log::error("❌ Job failed for user {$this->userId}: " . $e->getMessage());
        }
    }

}
