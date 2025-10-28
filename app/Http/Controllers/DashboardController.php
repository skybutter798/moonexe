<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

use App\Models\Wallet;
use App\Models\User;
use App\Models\Promotion;
use App\Models\Annoucement;
use App\Models\Transfer;
use App\Models\Setting;
use App\Models\Staking;
use App\Models\Payout;

use App\Services\WalletRecalculator;


class DashboardController extends Controller
{
    protected $walletRecalculator;
    
    public function __construct(WalletRecalculator $walletRecalculator)
    {
        $this->walletRecalculator = $walletRecalculator;
    }
    
    public function index()
    {
        $userId = auth()->id();
        //$this->walletRecalculator->recalculate($userId);
        $wallets = Wallet::where('user_id', $userId)->first();

        if (!$wallets) {
            $wallets = (object) [
                'cash_wallet'       => 0,
                'trading_wallet'    => 0,
                'earning_wallet'    => 0,
                'affiliates_wallet' => 0,
            ];
        }

        $assetsRecords = \App\Models\AssetsRecord::where('user_id', $userId)
            ->orderBy('record_date', 'asc')
            ->get();

        $profitRecords = \App\Models\ProfitRecord::where('user_id', $userId)
            ->orderBy('record_date', 'asc')
            ->get();

        // Calculate the total from the wallets
        $total_balance = (float)$wallets->cash_wallet +
                         (float)$wallets->trading_wallet +
                         (float)$wallets->earning_wallet +
                         (float)$wallets->affiliates_wallet;

        // Sum the 'buy' amounts from pending orders for the given user
        $pendingBuy = DB::table('orders')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->sum('buy');

        // Add the pending 'buy' amount to the total balance
        $total_balance += (float)$pendingBuy;

        $user = auth()->user();

        //$rangeData = (new \App\Services\UserRangeCalculator())->calculate($user);
        $directRanges = DB::table('directranges')->orderBy('min')->get();
        $currentRange = null;
        if ($user->package) {
            $currentRange = DB::table('directranges')->where('id', $user->package)->first();
        }

        // Check if a transfer with remark 'package' exists for this user.
        $hasPackageTransfer = DB::table('transfers')
            ->where('user_id', $userId)
            ->where('remark', 'package')
            ->exists();

        $forexRecords = \App\Models\MarketData::orderBy('symbol')->get();
        $announcements = Annoucement::where('status', 1)
            ->where('show', 1)
            ->orderBy('updated_at', 'desc')
            ->get();

        // Set the MEGADROP campaign time (converted from New York to Malaysia time)
        $startMY = Carbon::createFromFormat('Y-m-d H:i:s', '2025-05-20 12:01:00', 'Asia/Kuala_Lumpur');
        $endMY   = Carbon::createFromFormat('Y-m-d H:i:s', '2025-06-11 18:20:59', 'Asia/Kuala_Lumpur');

        // Get the user (explicit fetch)
        $user = User::find($userId);

        // Bonus funds (campaign bonus)
        $campaignTradingBonus = Transfer::where('user_id', $userId)
            ->where('from_wallet', 'cash_wallet')
            ->where('to_wallet', 'trading_wallet')
            ->where('status', 'Completed')
            ->where('remark', 'campaign')
            ->sum('amount');

        // Base sum of completed package transfers during MEGADROP
        $megadropDeposit = Transfer::where('user_id', $userId)
            ->where('status', 'Completed')
            ->where('remark', 'package')
            ->whereBetween('created_at', [$startMY, $endMY])
            ->sum('amount');

        // Apply logic based on user registration date
        if ($user && $user->created_at < $startMY) {
            $megadropDeposit = ($megadropDeposit * 1.5) - $campaignTradingBonus;
        } elseif ($user && $user->created_at >= $startMY) {
            $megadropDeposit = ($megadropDeposit * 1.0) - $campaignTradingBonus;
            if ($megadropDeposit >= 100) {
                // Don't add anything
            } else {
                $megadropDeposit = 0;
            }
        }

        $baseTopup = Transfer::where('user_id', $userId)
            ->where('status', 'Completed')
            ->where('remark', 'package')
            ->whereBetween('created_at', [$startMY, $endMY])
            ->sum('amount');

        if ($user->created_at < $startMY) {
            $topupBeforeBoost = $baseTopup;
        } else {
            $topupBeforeBoost = $megadropDeposit;
        }

        $campaignTopups = Transfer::where('user_id', $userId)
            ->where('status', 'Completed')
            ->where('remark', 'package')
            ->whereBetween('created_at', [$startMY, $endMY])
            ->orderBy('created_at')
            ->get();

        $totalStaked = Staking::where('user_id', $userId)
            ->orderByDesc('id')
            ->value('balance') ?? 0;

        $pendingUnstake = abs(
            Staking::where('user_id', $userId)
                ->where('status', 'pending_unstake')
                ->sum('amount')
        );

        $tz = 'Asia/Kuala_Lumpur';
        $now = Carbon::now($tz);

        // Current Monday 00:00 (start of this week)
        $currentMonday = $now->copy()->startOfWeek(Carbon::MONDAY);

        // Current Sunday 23:59:59 (end of this week)
        $currentSunday = $now->copy()->endOfWeek(Carbon::SUNDAY);

        $startDate = $currentMonday->toDateString();
        $endDate   = $currentSunday->toDateString();

        $totalStakeROI = DB::table('staking_logs')
            ->where('user_id', $userId)
            ->sum('daily_profit');

        $totalWeekROI = DB::table('staking_logs')
            ->where('user_id', $userId)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->sum('daily_profit');

        $stakeRoiLogs = DB::table('staking_logs')
            ->select('id', 'created_at', 'total_balance', 'daily_roi', 'daily_profit')
            ->where('user_id', $userId)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($row) use ($tz) {
                $row->created_at_nyt = Carbon::parse($row->created_at)
                    ->timezone('America/New_York')
                    ->format('d M Y, H:i');
                return $row;
            });

        $allStakeRoiLogs = DB::table('staking_logs')
            ->select('id', 'created_at', 'total_balance', 'daily_roi', 'daily_profit')
            ->where('user_id', $userId)
            ->paginate(50);

        // Map with timezone conversion AFTER pagination
        $allStakeRoiLogs->getCollection()->transform(function ($row) {
            $row->created_at_nyt = Carbon::parse($row->created_at)
                ->timezone('America/New_York')
                ->format('d M Y, H:i');
            return $row;
        });

        $stakeRoiWindow = [
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ];

        $stakingHistory = Staking::where('user_id', $userId)
            ->orderBy('id', 'asc')
            ->get(['txid', 'amount', 'balance', 'created_at']);

        // Load tier values from settings
        $roi100     = (float) Setting::where('name', 'staking_roi_100')->value('value')     ?? 0.007;
        $roi1000    = (float) Setting::where('name', 'staking_roi_1000')->value('value')    ?? 0.0105;
        $roi10000   = (float) Setting::where('name', 'staking_roi_10000')->value('value')   ?? 0.014;
        $roi100000  = (float) Setting::where('name', 'staking_roi_100000')->value('value')  ?? 0.02;

        if ($totalStaked >= 100000) {
            $currentStakingRate = $roi100000;
        } elseif ($totalStaked >= 10000) {
            $currentStakingRate = $roi10000;
        } elseif ($totalStaked >= 1000) {
            $currentStakingRate = $roi1000;
        } elseif ($totalStaked >= 100) {
            $currentStakingRate = $roi100;
        } else {
            $currentStakingRate = 0;
        }

        // Real trading balance = total - campaign bonus
        $realTradingBalance = max(0, $wallets->trading_wallet - $campaignTradingBonus);

        $daysSinceCreated = $user && $user->created_at
            ? Carbon::parse($user->created_at)->diffInDays(Carbon::now())
            : 0;

        // Fee tiers: >200d => 0%, >100d => 10%, else 20%
        if ($daysSinceCreated > 200) {
            $feeRate = 0.00;
        } elseif ($daysSinceCreated > 100) {
            $feeRate = 0.10;
        } else {
            $feeRate = 0.20;
        }

        // Pre-calc estimated fee & net for display
        $estimatedFee = round($realTradingBalance * $feeRate, 2);
        $estimatedNet = round($realTradingBalance - $estimatedFee, 2);
        $campaignBalance = DB::table('settings')->where('name', 'cam_balance')->value('value') ?? 0;

        $pendingUnstakesList = Staking::where('user_id', $userId)
            ->where('status', 'pending_unstake')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($row) {
                return [
                    'id'      => $row->id,
                    'release' => Carbon::parse($row->created_at)
                        ->addDay() // 24h after request
                        ->timezone('Asia/Kuala_Lumpur')
                        ->toIso8601String(),
                    'amount'  => abs($row->amount),
                ];
            });

        // -------------------- CLAIMABILITY (for auto-claim) --------------------
        $lastMonday  = $now->copy()->startOfWeek(Carbon::MONDAY)->subWeek();
        $lastSunday  = $lastMonday->copy()->endOfWeek(Carbon::SUNDAY);

        $claimableWeekROI = DB::table('staking_logs')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$lastMonday, $lastSunday])
            ->sum('daily_profit');

        // Generate same txid key as in distribute command
        $shortWeekKey = $lastMonday->format('y') . 'W' . $lastMonday->format('W');
        $txid         = "ws_{$shortWeekKey}_{$userId}";

        // Check if already claimed
        $alreadyClaimed = Payout::where('txid', $txid)->exists();
        
        // Find latest weekly-staking payout txid (prefix ws_)
        $latestWeekTxid = \App\Models\Payout::where('user_id', $userId)
            ->where(function ($q) {
                // Prefer REGEXP for unambiguous prefix match; fallback LIKE with escaped underscore
                $q->whereRaw("txid REGEXP '^ws_'")
                  ->orWhere('txid', 'like', 'ws\\_%');
            })
            ->orderByDesc('created_at')
            ->value('txid');


        // Allow anytime if claimable & not claimed
        $canClaim = $claimableWeekROI > 0 && !$alreadyClaimed;

        // -------------------- CLAIM WINDOW (Mon→Sun, hour override) --------------------
        // Defaults: 12:00 → 12:00
        $defaultHour = 12;

        // Test override: for user #3, use 10:00 instead of 12:00 (controlled by env)
        $testingOn  = (bool) env('AUTO_CLAIM_TESTING', false);
        $testUserId = 4;
        $testHour   = 10;

        $windowHour = ($testingOn && $userId === $testUserId) ? $testHour : $defaultHour;

        $weekMondayStart = $now->copy()->startOfWeek(Carbon::MONDAY)->setTime($windowHour, 0, 0);
        $weekSundayEnd   = $now->copy()->startOfWeek(Carbon::MONDAY)->addDays(6)->setTime($windowHour, 0, 0);

        // Broad-compatible check (works if betweenIncluded() isn't available)
        $isInClaimWindow = $now->greaterThanOrEqualTo($weekMondayStart) && $now->lessThanOrEqualTo($weekSundayEnd);

        // Log for testing visibility
        if ($testingOn && $userId === $testUserId) {
            Log::info("Auto-claim TEST window", [
                'user_id'      => $userId,
                'window_hour'  => $windowHour,
                'monday_start' => $weekMondayStart->toDateTimeString(),
                'sunday_end'   => $weekSundayEnd->toDateTimeString(),
                'now'          => $now->toDateTimeString(),
                'in_window'    => $isInClaimWindow,
                'can_claim'    => $canClaim,
                'txid'         => $txid,
            ]);
        }

        // -------------------- SERVER-SIDE AUTO-CLAIM WITH CACHE GUARD --------------------
        $shouldAutoClaim =
            $isInClaimWindow
            && $canClaim
            && !session('success')
            && !session('stake_success')
            && !session('unstake_success')
            && (bool) env('AUTO_CLAIM_ENABLED', true); // global feature flag

        if ($shouldAutoClaim) {
            // only allow once per user+txid for 15 mins
            $cacheKey = "autoclaim:{$userId}:{$txid}";
            if (Cache::add($cacheKey, true, now()->addMinutes(15))) {
                // double-check race
                if (!Payout::where('txid', $txid)->exists()) {
                    \App\Jobs\DistributeUserStakingJob::dispatch($userId);
                    session()->flash('success', 'Your claim request has been queued. Please refresh in a moment to see the update.');
                }
            }
        }

        // ----------------------------- VIEW DATA -----------------------------
        $data = [
            'title'                 => 'Dashboard',
            'wallets'               => $wallets,
            'total_balance'         => $total_balance,
            'directRanges'          => $directRanges,
            'currentRange'          => $currentRange,
            'hasPackageTransfer'    => $hasPackageTransfer,
            'user'                  => $user,
            //'rangeData'           => $rangeData,
            'pendingBuy'            => $pendingBuy,
            'assetsRecords'         => $assetsRecords,
            'profitRecords'         => $profitRecords,
            'forexRecords'          => $forexRecords,
            'megadropDeposit'       => $megadropDeposit,
            'realTradingBalance'    => $realTradingBalance,
            'campaignTradingBonus'  => $campaignTradingBonus,
            'campaignBalance'       => $campaignBalance,
            'campaignTopups'        => $campaignTopups,
            'topupBeforeBoost'      => $topupBeforeBoost,
            'totalStaked'           => $totalStaked,
            'pendingUnstake'        => $pendingUnstake,
            'currentStakingRate'    => $currentStakingRate,
        ];

        $data['tradermadeApiKey']    = config('services.tradermade.key');
        $data['announcements']       = $announcements;
        $data['daysSinceCreated']    = $daysSinceCreated;
        $data['feeRate']             = $feeRate;
        $data['estimatedFee']        = $estimatedFee;
        $data['estimatedNet']        = $estimatedNet;
        $data['totalWeekROI']        = $totalWeekROI;
        $data['totalStakeROI']       = $totalStakeROI;
        $data['stakeRoiLogs']        = $stakeRoiLogs;
        $data['stakingHistory']      = $stakingHistory;
        $data['allStakeRoiLogs']     = $allStakeRoiLogs;
        $data['pendingUnstakesList'] = $pendingUnstakesList;

        // Claim-related flags for UI / debugging (optional)
        $data['canClaim']           = $canClaim;
        $data['claimableWeekROI']   = $claimableWeekROI;
        $data['alreadyClaimed']     = $alreadyClaimed;
        $data['isInClaimWindow']    = $isInClaimWindow;
        $data['txid']               = $txid;
        $data['latestWeekTxid'] = $latestWeekTxid;


        if ($user->isAdmin) {
            return view('admin.dashboard', $data);
        } else {
            return view('user.dashboard_v2', $data);
        }
    }
    
    public function applyPromotion(Request $request)
    {
        $request->validate([
            'promotion_code' => 'required|string|exists:promotions,code',
        ]);
    
        // Look up the promotion (code stored in uppercase)
        $promotion = \App\Models\Promotion::where('code', strtoupper($request->promotion_code))->first();
        
        if ($promotion) {
            // Check if the promotion can still be used
            if ($promotion->used >= $promotion->max_use) {
                return redirect()->back()->withErrors(['promotion_code' => 'This promotion code has reached its maximum usage.']);
            }
            
            // Update the user's bonus field with the promotion code.
            $user = auth()->user();
            $user->bonus = $promotion->code;
            $user->save();
            
            // Increment the promotion's used count.
            $promotion->increment('used');
            
            // Update the user's wallet bonus_wallet by adding the promotion's amount.
            $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
            if ($wallet) {
                // Get the promotion amount, defaulting to 0 if null.
                $amount = $promotion->amount ?? 0;
                // Add the promotion amount to bonus_wallet (4 decimal places).
                $wallet->bonus_wallet = $wallet->bonus_wallet + $amount;
                $wallet->save();
    
                // Create a record in transfers table for the bonus transfer.
                \App\Models\Transfer::create([
                    'user_id'     => $user->id,
                    'txid'        => 'b_' . rand(10000, 99999),
                    'from_wallet' => 'trading_wallet',
                    'to_wallet'   => 'bonus_wallet',
                    'amount'      => $amount,
                    'status'      => 'Completed',
                    'remark'      => 'bonus',
                ]);
            }
            
            return redirect()->back()->with('success', 'Promotion code applied successfully!');
        }
        
        return redirect()->back()->withErrors(['promotion_code' => 'Invalid promotion code.']);
    }
    
    
    public function showAnnouncements(Request $request)
    {
        $query = Annoucement::query()
            ->where('show', 1)
            ->orderBy('created_at', 'desc');
    
        $announcements = $query->get();
    
        // Log announcement IDs and names where show = 1
        Log::info('[Announcement] Visible records:', $announcements->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'show' => $item->show,
            ];
        })->toArray());
    
        return view('user.announcements', compact('announcements'));
    }

}
