<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MarketData;
use Illuminate\Support\Facades\Cache;

class PersistMarketData extends Command
{
    protected $signature = 'marketdata:persist';
    protected $description = 'Persist the latest market data from cache into the database';

    public function handle()
    {
        // Retrieve data from cache
        $cachedData = Cache::get('market_data', []);
        
        if (empty($cachedData)) {
            $this->warn('No market data available in cache.');
            return 0;
        }

        foreach ($cachedData as $symbol => $data) {
            // Update or create record in DB for each symbol
            MarketData::updateOrCreate(
                ['symbol' => $symbol],
                [
                    'bid' => $data['bid'] ?? null,
                    'ask' => $data['ask'] ?? null,
                    'mid' => $data['mid'] ?? null,
                ]
            );
            $this->info("Persisted data for symbol: {$symbol}");
        }

        return 0;
    }
}
