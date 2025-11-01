<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\UserReferralCache;
use App\Services\ReferralIndexBuilder;
use App\Jobs\BuildUserReferralCache;
use Carbon\Carbon;

class ReferralController extends Controller
{
    private function toCarbon(mixed $v): ?Carbon
    {
        if ($v instanceof Carbon) return $v;
        if ($v === null || $v === '') return null;
        try { return Carbon::parse($v); } catch (\Throwable $e) { return null; }
    }

    private function rehydrateReferrals(array $rows): \Illuminate\Support\Collection
    {
        return collect($rows)->map(function ($x) {
            // ensure object w/ Carbon date
            $x = (array)$x;
            if (array_key_exists('created_at', $x)) {
                $x['created_at'] = $this->toCarbon($x['created_at']);
            }
            return (object)$x;
        });
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $userId = (int)$user->id;

        // ---- Resolve date filters (same semantics as your current code) ----
        $fromDate = null;
        $toDate   = null;
        $apply    = false;

        if ($request->has('filter') && $request->input('filter') && $request->input('filter') !== 'all') {
            $apply = true;
            switch ($request->input('filter')) {
                case 'today':
                    $fromDate = now()->toDateString();
                    $toDate   = now()->toDateString();
                    break;
                case 'weekly':
                    $fromDate = now()->startOfWeek()->toDateString();
                    $toDate   = now()->endOfWeek()->toDateString();
                    break;
                case 'monthly':
                    $fromDate = now()->startOfMonth()->toDateString();
                    $toDate   = now()->endOfMonth()->toDateString();
                    break;
            }
        } else {
            if ($request->filled('from')) { $apply = true; $fromDate = $request->input('from'); }
            if ($request->filled('to'))   { $apply = true; $toDate   = $request->input('to'); }
        }

        // ---- Strategy:
        // - If any filter is applied => compute live (no cache variants).
        // - Else => use cache-first with background refresh (TTL=60s).
        $builder    = new ReferralIndexBuilder();
        $ttlSeconds = 60;

        if ($apply) {
            // Live compute w/ filters
            $payload = $builder->build($user, [
                'apply'    => true,
                'fromDate' => $fromDate,
                'toDate'   => $toDate,
            ]);
        } else {
            // Cache-first
            $cache = UserReferralCache::where('user_id', $userId)->first();

            if (!$cache) {
                // First load: build synchronously, save, and show
                $payload = $builder->build($user, [
                    'apply'    => false,
                    'fromDate' => null,
                    'toDate'   => null,
                ]);
                UserReferralCache::create([
                    'user_id'           => $userId,
                    'data'              => $payload,
                    'last_refreshed_at' => now(),
                ]);
            } else {
                // Subsequent loads: use cache immediately
                $payload = $cache->data ?? [];

                // Stale? queue refresh
                $stale = !$cache->last_refreshed_at
                    || now()->diffInSeconds($cache->last_refreshed_at) > $ttlSeconds;

                if ($stale || $request->boolean('refresh')) {
                    BuildUserReferralCache::dispatch($userId);
                }
            }
        }

        // JSON endpoint: /user/referral?json=1
        if ($request->wantsJson() || $request->boolean('json')) {
            return response()->json($payload);
        }

        // ---- Rehydrate arrays → objects (for Blade) ----
        $firstLevelReferrals = $this->rehydrateReferrals($payload['firstLevelReferrals'] ?? [])->values();

        return view('user.referral_v2', [
            'title'               => $payload['title'] ?? 'My Referral Program',
            'groupTradingMargin'  => (float)($payload['groupTradingMargin'] ?? 0),
            'directPercentage'    => (float)($payload['directPercentage'] ?? 0),
            'matchingPercentage'  => (float)($payload['matchingPercentage'] ?? 0),
            'totalCommunity'      => (int)($payload['totalCommunity'] ?? 0),
            'myTotalEarning'      => (float)($payload['myTotalEarning'] ?? 0),
            'firstLevelReferrals' => $firstLevelReferrals,
            // These two were unused in the Blade totals, keep for compatibility:
            'tableTotalTradeSum'  => 0,
            'tableTotalTopupSum'  => 0,
            'myTotalDirect'       => (float)($payload['myTotalDirect'] ?? 0),
            'myTotalMatching'     => (float)($payload['myTotalMatching'] ?? 0),
            'totalOrders'         => (int)($payload['totalOrders'] ?? 0),
            'dateRange'           => (string)($payload['dateRange'] ?? 'All'),
            'referralBreakdown'   => collect($payload['referralBreakdown'] ?? [])->values(),
            'matchingBreakdown'   => collect($payload['matchingBreakdown'] ?? [])->values(),
        ]);
    }
}