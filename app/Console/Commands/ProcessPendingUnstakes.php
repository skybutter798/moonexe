<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Staking;
use App\Models\Wallet;
use App\Models\Transfer;

class ProcessPendingUnstakes extends Command
{
    protected $signature = 'staking:release-unstakes {--limit=200}';
    protected $description = 'Credit trading wallet for unstakes older than 24h.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        // Pick due, still-pending records
        $due = Staking::query()
            ->where('status', 'pending_unstake')
            ->where('created_at', '<=', now()->subDay())
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($due->isEmpty()) {
            $this->info('No pending unstakes to release.');
            return Command::SUCCESS;
        }

        foreach ($due as $row) {
            DB::transaction(function () use ($row) {
                // Re-load FOR UPDATE to avoid double processing
                $s = Staking::whereKey($row->id)->lockForUpdate()->first();
                if (!$s || $s->status !== 'pending_unstake') return;

                $userId = $s->user_id;
                $amount = abs((int)$s->amount); // negative stored; release positive

                $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->firstOrFail();
                $wallet->trading_wallet += $amount;
                $wallet->save();

                // Create transfer record at release time (optional but recommended)
                Transfer::create([
                    'user_id'     => $userId,
                    'txid'        => $s->txid,
                    'from_wallet' => 'staking_wallet',
                    'to_wallet'   => 'trading_wallet',
                    'amount'      => $amount,
                    'status'      => 'Completed',
                    'remark'      => 'unstake_release',
                ]);

                // Mark as released so we don't process twice
                $s->status = 'active';
                $s->save();
            });
        }

        $this->info("Released {$due->count()} unstakes.");
        return Command::SUCCESS;
    }
}
