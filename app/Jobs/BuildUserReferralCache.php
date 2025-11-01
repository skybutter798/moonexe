<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Models\UserReferralCache;
use App\Services\ReferralIndexBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BuildUserReferralCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        // $this->onQueue('low'); // optional
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) return;

        // Build "All" (no filter) snapshot
        $payload = (new ReferralIndexBuilder())->build($user, [
            'apply'    => false,
            'fromDate' => null,
            'toDate'   => null,
        ]);

        UserReferralCache::updateOrCreate(
            ['user_id' => $this->userId],
            [
                'data'              => $payload,
                'last_refreshed_at' => now(),
            ]
        );
    }
}