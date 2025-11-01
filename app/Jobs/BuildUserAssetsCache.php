<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Models\UserAssetsCache;
use App\Services\AssetsIndexBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BuildUserAssetsCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;

        // If you have a dedicated queue for low-priority refreshes, uncomment:
        // $this->onQueue('low');
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $payload = (new AssetsIndexBuilder())->build($user);

        UserAssetsCache::updateOrCreate(
            ['user_id' => $this->userId],
            [
                'data'              => $payload, // cast to JSON via model
                'last_refreshed_at' => now(),
            ]
        );
    }
}