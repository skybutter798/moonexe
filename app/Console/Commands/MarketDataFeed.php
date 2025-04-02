<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class MarketDataFeed extends Command
{
    protected $signature = 'marketdata:feed';
    protected $description = 'Fetches live market data via API and caches it';

    public function handle()
    {
        // Retrieve the API key from the .env file
        $apiKey = env('TRADERMADE_API_KEY');
        
        if (!$apiKey) {
            $this->error("TRADERMADE_API_KEY is not set in your .env file.");
            return;
        }
        
        // List of currencies that will normally be formatted as {currency}USD.
        // For those that should be inverted, add them to $invertedCurrencies.
        $currencies = [
            'AUD', 'BRL', 'CHF', 'EUR', 'HKD', 'LKR', 'NZD', 'THB', 
            'TWD', 'VND', 'CAD', 'COP', 'EGP', 'GBP', 'IDR', 'JOD', 
            'KWD', 'MXN', 'TRY', 'ZAR'
        ];
        
        // Define which currencies need to be inverted (i.e. USD comes first)
        $invertedCurrencies = ['LKR', 'VND', 'IDR', 'COP'];
        
        // Map each currency to its corresponding pair format.
        $currencyPairs = array_map(function($currency) use ($invertedCurrencies) {
            if (in_array($currency, $invertedCurrencies)) {
                return 'USD' . $currency;
            }
            return $currency . 'USD';
        }, $currencies);
        
        // Convert the pairs array to a comma-separated string.
        $currencyString = implode(',', $currencyPairs);
        
        $url = "https://marketdata.tradermade.com/api/v1/live?currency={$currencyString}&api_key={$apiKey}";
        
        $this->info("Fetching market data from {$url}");
        
        // Make the API request using Laravel's HTTP client
        $response = Http::get($url);
    
        if ($response->successful()) {
            $data = $response->json();
            
            if (isset($data['quotes'])) {
                $cachedData = Cache::get('market_data', []);
                
                // Loop through each quote and update the cache with the latest data
                foreach ($data['quotes'] as $quote) {
                    // Construct the symbol from base and quote currencies
                    $symbol = $quote['base_currency'] . $quote['quote_currency'];
                    $cachedData[$symbol] = $quote;
                    $this->info("Updated cache for symbol: " . $symbol);
                }
                
                // Cache the updated market data for 1 minute (adjust as needed)
                Cache::put('market_data', $cachedData, now()->addMinutes(1));
                $this->info("Market data cached successfully.");
            } else {
                $this->error("Unexpected response structure: " . json_encode($data));
            }
        } else {
            $this->error("Failed to fetch data. HTTP Status: " . $response->status());
        }
    }

}
