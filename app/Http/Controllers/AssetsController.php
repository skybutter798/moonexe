<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Wallet;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\Transfer;
use App\Models\Asset;
use App\Models\User;
use App\Models\Payout;
use Illuminate\Support\Str;
use App\Models\Setting;
use Carbon\Carbon;

use App\Services\UserRangeCalculator;
use App\Services\CoinDepositService;
use App\Services\TelegramService;
use App\Events\CampaignBalanceUpdated;

use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserAssetsCache;
use App\Jobs\BuildUserAssetsCache;
use App\Services\AssetsIndexBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;


class AssetsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index(Request $request)
    {
        $user = auth()->user();
        $userId = (int) $user->id;

        $ttlSeconds = 60; // refresh cadence
        $cache = UserAssetsCache::where('user_id', $userId)->first();

        if (!$cache) {
            // First load: build synchronously, save, and show
            $payload = (new AssetsIndexBuilder())->build($user);
            $cache = UserAssetsCache::create([
                'user_id'           => $userId,
                'data'              => $payload,
                'last_refreshed_at' => now(),
            ]);
        } else {
            // Subsequent loads: return cache immediately
            $payload = $cache->data ?? [];

            // Refresh in background if stale or ?refresh=1
            $stale = !$cache->last_refreshed_at
                || now()->diffInSeconds($cache->last_refreshed_at) > $ttlSeconds;

            if ($stale || $request->boolean('refresh')) {
                BuildUserAssetsCache::dispatch($userId);
            }
        }

        // JSON endpoint: /user/assets?json=1
        if ($request->wantsJson() || $request->boolean('json')) {
            return response()->json($payload);
        }

        // ---- Rehydrate for Blade (objects/collections/paginators) ----
        $wallets = (object)($payload['wallets'] ?? [
            'cash_wallet'       => 0.0,
            'trading_wallet'    => 0.0,
            'earning_wallet'    => 0.0,
            'affiliates_wallet' => 0.0,
        ]);

        // Movements & assets: restore Carbon timestamps
        $depositRequests    = collect($payload['depositRequests'] ?? [])->map(function ($x) {
            $x = $this->normalizeItem($x);
            return (object) $x;
        });
        $withdrawalRequests = collect($payload['withdrawalRequests'] ?? [])->map(function ($x) {
            $x = $this->normalizeItem($x);
            return (object) $x;
        });
        $transferRecords    = collect($payload['transferRecords'] ?? [])->map(function ($x) {
            $x = $this->normalizeItem($x);
            return (object) $x;
        });

        $transactions       = collect($payload['transactions'] ?? [])->map(function ($x) {
            $x = $this->normalizeItem($x);
            return (object) $x;
        });

        $assets = collect($payload['assets'] ?? [])->map(function ($x) {
            $x = $this->normalizeItem($x);
            $x['currency_data'] = isset($x['currency_data']) && $x['currency_data'] ? (object) $x['currency_data'] : null;
            return (object) $x;
        });

        // Rebuild simple paginators so ->links() still works in Blade
        $payoutRecords       = $this->rehydratePaginator($payload['payoutRecords'] ?? null, $request, 'payout_page', (float)($payload['matching_percentage'] ?? 0), null);
        $directPayoutRecords = $this->rehydratePaginator($payload['directPayoutRecords'] ?? null, $request, 'direct_payout_page', null, (float)($payload['direct_percentage'] ?? 0));
        $roiRecords          = $this->rehydratePaginator($payload['roiRecords'] ?? null, $request, 'trading_page');

        return view('user.assets_v2', [
            'title'               => $payload['title'] ?? 'My Assets',
            'wallets'             => $wallets,
            'total_balance'       => (float) ($payload['total_balance'] ?? 0),
            'depositRequests'     => $depositRequests,
            'withdrawalRequests'  => $withdrawalRequests,
            'transactions'        => $transactions,
            'assets'              => $assets,
            'priceCostByCurrency' => $payload['priceCostByCurrency'] ?? [],
            'netChangeByCurrency' => $payload['netChangeByCurrency'] ?? [],
            'packages'            => collect($payload['packages'] ?? [])->map(fn ($x) => (object) $x),
            'currentPackage'      => isset($payload['currentPackage']) && $payload['currentPackage'] ? (object) $payload['currentPackage'] : null,
            'payoutRecords'       => $payoutRecords,
            'directPayoutRecords' => $directPayoutRecords,
            'roiRecords'          => $roiRecords,
        ]);
    }

    private function toCarbon(mixed $v): ?Carbon
    {
        if ($v instanceof Carbon) return $v;
        if ($v === null || $v === '') return null;
        try {
            return Carbon::parse($v);
        } catch (\Throwable $e) {
            return null;
        }
    }
    
    /**
     * Normalize a snapshot row (array|object) and restore date fields to Carbon.
     */
    private function normalizeItem($x): array
    {
        if (is_object($x)) $x = (array) $x;
    
        if (array_key_exists('created_at', $x)) {
            $x['created_at'] = $this->toCarbon($x['created_at']);
        }
        if (array_key_exists('updated_at', $x)) {
            $x['updated_at'] = $this->toCarbon($x['updated_at']);
        }
    
        return $x;
    }
    
    /**
     * Turn a plain paginator snapshot back into a paginator so Blade's ->links() works.
     * Also patches missing item fields for older snapshots.
     */
    private function rehydratePaginator(
        ?array $snap,
        Request $request,
        string $pageName,
        ?float $matchingPctForItems = null,   // adds ->profit_sharing if missing
        ?float $directPctForItems   = null    // adds ->direct_percentage if missing
    ): LengthAwarePaginator {
        $snap = $snap ?? [
            'data'         => [],
            'total'        => 0,
            'per_page'     => 10,
            'current_page' => 1,
            'last_page'    => 1,
            'page_name'    => $pageName,
        ];
    
        $items = collect($snap['data'] ?? [])->map(function ($row) use ($matchingPctForItems, $directPctForItems) {
            $row = $this->normalizeItem($row);
    
            if ($matchingPctForItems !== null && !array_key_exists('profit_sharing', $row)) {
                $row['profit_sharing'] = $matchingPctForItems;
            }
            if ($directPctForItems !== null && !array_key_exists('direct_percentage', $row)) {
                $row['direct_percentage'] = $directPctForItems;
            }
    
            return (object) $row;
        });
    
        return new LengthAwarePaginator(
            $items,
            (int) ($snap['total'] ?? 0),
            (int) ($snap['per_page'] ?? 10),
            (int) ($snap['current_page'] ?? 1),
            [
                'path'     => $request->url(),
                'pageName' => $snap['page_name'] ?? $pageName,
            ]
        );
    }
    
    public function transferTrading(Request $request)
    {
        $userId = auth()->id();
        $user = User::find($userId);
        $wallet = Wallet::where('user_id', $userId)->first();
        
        if ($user->two_fa_enabled && $user->google2fa_secret) {
            
            Log::channel('admin')->info("OTP received", [
                'user_id' => $userId,
                'otp_input' => $request->otp,
            ]);
            
            $request->validate([
                'otp' => 'required|digits:6'
            ]);
        
            $google2fa = app('pragmarx.google2fa');
            $valid = $google2fa->verifyKey($user->google2fa_secret, $request->otp);
        
            if (!$valid) {
                return redirect()->back()->withErrors(['Invalid 2FA code.']);
            }
        } else {
            return redirect()->back()->withErrors(['You must enable 2FA before performing this action.']);
        }

    
        if (!$user || !$wallet) {
            Log::channel('admin')->info("User or Wallet not found", ['user_id' => $userId]);
            return redirect()->back()->withErrors('Account or wallet not found.');
        }
    
        if ($user && $user->status == 2) {
            Log::channel('admin')->warning("Transfer attempt by ineligible user (status = 2)", ['user_id' => $userId]);
            return redirect()->back()->withErrors('Your account is not eligible to transfer any bonus. Please contact support for more information.');
        }
    
        if ($user->status == 0) {
            Log::channel('admin')->warning("Transfer attempt by deactivated user", ['user_id' => $userId]);
            return redirect()->back()->withErrors('Your account has been deactivated and cannot perform this action.');
        }
    
        Log::channel('admin')->info("User and Wallet found", ['user_id' => $userId]);
    
        // Calculate campaign bonus
        $campaignBonus = Transfer::where('user_id', $userId)
            ->where('from_wallet', 'cash_wallet')
            ->where('to_wallet', 'trading_wallet')
            ->where('status', 'Completed')
            ->where('remark', 'campaign')
            ->sum('amount');
    
        // Real balance = trading_wallet - campaign bonus
        $realBalance = $wallet->trading_wallet - $campaignBonus;
    
        if ($realBalance <= 0) {
            Log::channel('admin')->warning("Only campaign bonus available, transfer blocked", [
                'user_id' => $userId,
                'trading_wallet' => $wallet->trading_wallet,
                'campaign_bonus' => $campaignBonus
            ]);
            return redirect()->back()->withErrors('You cannot transfer campaign bonus. No real balance available.');
        }
    
        // ✅ Calculate days since account creation
        $daysSinceCreated = Carbon::parse($user->created_at)->diffInDays(Carbon::now());
    
        // ✅ Dynamic feeRate logic
        if ($daysSinceCreated > 200) {
            $feeRate = 0.00; // no fee
        } elseif ($daysSinceCreated > 100) {
            $feeRate = 0.10; // 10%
        } else {
            $feeRate = 0.20; // 20%
        }
        
        // Use only real balance
        $amount = (float) $realBalance;
        $fee = $amount * $feeRate;
        $netAmount = $amount - $fee;
    
        Log::channel('admin')->info("Fee calculated", [
            'user_id' => $userId,
            'real_balance' => $amount,
            'fee' => $fee,
            'net_amount' => $netAmount
        ]);
    
        // Update wallet balances
        $wallet->trading_wallet = 0; // Reset whole trading wallet (real + campaign)
        $wallet->cash_wallet += $netAmount;
        $wallet->save();
    
        Log::channel('admin')->info("Wallet balances updated", [
            'user_id' => $userId,
            'new_trading_wallet' => $wallet->trading_wallet,
            'new_cash_wallet' => $wallet->cash_wallet
        ]);
    
        // Deactivate user
        $user->status = 0;
        $user->save();
        
        Log::channel('admin')->info("User account deactivated after transfer", ['user_id' => $userId]);
    
        // Generate unique txid
        do {
            $txid = 't_' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Transfer::where('txid', $txid)->exists());
    
        Log::channel('admin')->info("Transaction ID generated", ['txid' => $txid]);
    
        // Record transfer
        Transfer::create([
            'user_id'     => $userId,
            'txid'        => $txid,
            'from_wallet' => 'trading_wallet',
            'to_wallet'   => 'cash_wallet',
            'amount'      => $netAmount,
            'status'      => 'Completed',
            'remark'      => number_format($fee, 2), // Optional: store fee as remark
        ]);
    
        Log::channel('admin')->info("Transfer record created", ['txid' => $txid]);
        
        // --- Referral & Top Referral ---
        $referralUser = User::find($user->referral);
        $referralName = $referralUser ? $referralUser->name : 'N/A';
        
        // Get top referral (2 levels before ID 2 if possible)
        $current = $user;
        $prev1 = null;
        $prev2 = null;
        while ($current && $current->referral && $current->referral != 2) {
            $prev2 = $prev1;
            $prev1 = User::find($current->referral);
            $current = $prev1;
            if ($current && $current->id == 2) {
                break;
            }
        }
        $topReferralName = $prev2 ? $prev2->name : ($prev1 ? $prev1->name : 'N/A');
        
        // Format Join Date
        $joinDate = Carbon::parse($user->created_at)->format('Y-m-d H:i:s');
        
        // --- Telegram message ---
        $chatId = '-1002643026089';
        $message = "<b>🚫 Account Terminated</b>\n"
                 . "User: <b>{$user->name}</b>\n"
                 . "ID: <code>{$user->id}</code>\n"
                 . "Email: <code>{$user->email}</code>\n"
                 . "Joined: <b>{$joinDate}</b>\n"
                 . "Referral: <b>{$referralName}</b>\n"
                 . "Top Referral: <b>{$topReferralName}</b>\n"
                 . "Transferred: <b>" . number_format($netAmount, 2) . " USDT</b>\n"
                 . "Fee: <b>" . number_format($fee, 2) . " USDT</b>\n"
                 . "Status: <b>Deactivated</b>";
        
        (new \App\Services\TelegramService())->sendMessage($message, $chatId);
    
        return redirect()->back()->with('success', 'Trading wallet transfer completed successfully. Your account has been deactivated. Fee deducted: ' . number_format($fee, 2) . ' USDT');
    }
    
    public function transfer(Request $request)
    {
        // 1) Validate input
        $request->validate([
            'amount'        => 'required|numeric|min:0.01',
            'transfer_type' => 'required|string|in:earning_to_cash,affiliates_to_cash',
        ]);
        //Log::debug('Requested transfer type', ['type' => $request->transfer_type]);

        $userId = auth()->id();
        $wallet = Wallet::firstOrCreate(['user_id' => $userId]);
        
        $user = auth()->user();

        // Conditionally require security_pass
        if ($user->security_pass) {
            $request->validate([
                'security_pass' => 'required|string',
            ]);
        
            if ($request->security_pass !== $user->security_pass) {
                return redirect()->back()->withErrors(['Invalid security password.']);
            }
        }


        // 2) Special reserved‑sum check for early users moving affiliates → cash
        if ($request->transfer_type === 'affiliates_to_cash' && $userId <= 202) {
            $reserved = DB::table('payouts as p')
                ->selectRaw("
                    SUM(CASE 
                          WHEN p.type = 'payout' 
                               AND o.user_id <= 202 THEN p.actual ELSE 0 END)
                    + SUM(CASE 
                          WHEN p.type = 'direct' 
                               AND t.user_id <= 202 THEN p.actual ELSE 0 END)
                    AS total_reserved
                ")
                ->leftJoin('orders as o', function($join) {
                    $join->on('p.order_id', '=', 'o.id')
                         ->where('p.type', 'payout');
                })
                ->leftJoin('transfers as t', function($join) {
                    $join->on('p.txid', '=', 't.txid')
                         ->where('p.type', 'direct');
                })
                ->where('p.user_id', $userId)
                ->where('p.wallet', 'affiliates')
                ->whereIn('p.type', ['payout','direct'])
                ->value('total_reserved') ?: 0;

            if ($wallet->affiliates_wallet <= $reserved) {
                Log::channel('admin')->warning("transfer: Affiliates below reserved", [
                    'user_id'        => $userId,
                    'balance'        => $wallet->affiliates_wallet,
                    'reserved_sum'   => $reserved,
                ]);
                return redirect()->back()
                    ->withErrors('Your affiliates balance must exceed your reserved amount before transferring.');
            }
        }

        // 3) Deduct from the chosen wallet
        $amount     = (float) $request->amount;
        $fromWallet = $request->transfer_type === 'earning_to_cash'
                    ? 'earning_wallet'
                    : 'affiliates_wallet';

        if ($wallet->{$fromWallet} < $amount) {
            Log::channel('admin')->info("Insufficient funds in {$fromWallet}", [
                'user_id'  => $userId,
                'required' => $amount,
                'available'=> $wallet->{$fromWallet},
            ]);
            return redirect()->back()
                   ->withErrors("Insufficient funds in your {$fromWallet}.");
        }

        $wallet->{$fromWallet}   -= $amount;
        $wallet->cash_wallet    += $amount;
        $wallet->save();

        Log::channel('admin')->info("transfer: Wallets updated", [
            'user_id'      => $userId,
            'from_wallet'  => $fromWallet,
            'amount'       => $amount,
            'new_cash_bal' => $wallet->cash_wallet,
        ]);

        // 4) Generate a unique txid
        do {
            $txid = 't_' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Transfer::where('txid', $txid)->exists());

        Log::channel('admin')->info("transfer: Generated txid", ['txid' => $txid]);

        // 5) Record the transfer
        Transfer::create([
            'user_id'     => $userId,
            'txid'        => $txid,
            'from_wallet' => $fromWallet,
            'to_wallet'   => 'cash_wallet',
            'amount'      => $amount,
            'status'      => 'Completed',
            'remark'      => "Transfer from {$fromWallet}",
        ]);

        Log::channel('admin')->info("transfer: Record created", ['txid' => $txid]);

        // 6) Success response
        return redirect()->back()
               ->with('success', 'Profit transfer completed successfully.');
    }
    
    public function buyPackage(Request $request)
    {
        $user = auth()->user();
        
        // Prevent deactivated or inactive users
        if ($user->status === 0) {
            Log::channel('payout')->warning("Blocked package purchase: user status = 0", ['user_id' => $user->id]);
            return redirect()->back()->withErrors('Your account has been deactivated and is no longer eligible for package activation or top-up. Any balance from unclaimed orders will be returned to your cash wallet once the order pair completes within 24 hours.');
        }

        $userId = $user->id;
        Log::channel('payout')->info("--------> Buy package initiated", ['user_id' => $userId]);
        
        $wallet = Wallet::where('user_id', $userId)->first();
        if (!$wallet) {
            return redirect()->back()->withErrors('Wallet not found.');
        }
    
        // Determine whether this is a first-time activation or a top-up.
        if (!$user->package) {
            // First-time activation using activation_amount.
            $request->validate([
                'activation_amount' => 'required|numeric|min:10',
            ]);
            $activationAmount = (float) $request->activation_amount;
            
            if ($activationAmount % 10 !== 0) {
                return redirect()->back()->withErrors('Activation amount must be in multiples of 10.');
            }
            
            // Check sufficient funds.
            if ($wallet->cash_wallet < $activationAmount) {
                return redirect()->back()->withErrors('Insufficient balance in Cash Wallet.');
            }
            
            // For first-time activation, we initially look among directranges with IDs 1, 2, 3.
            $initialRange = DB::table('directranges')
                ->whereIn('id', [1,2,3])
                ->where('min', '<=', $activationAmount)
                ->where(function($query) use ($activationAmount) {
                    $query->where('max', '>=', $activationAmount)
                          ->orWhereNull('max');
                })->first();
            
            if (!$initialRange) {
                return redirect()->back()->withErrors('Activation amount does not match any available range.');
            }
        } else {
            // Top-up for already activated user using topup_amount.
            $request->validate([
                'topup_amount' => 'required|numeric|min:10',
            ]);
            $topupAmount = (float) $request->topup_amount;
            
            if ($topupAmount % 10 !== 0) {
                return redirect()->back()->withErrors('Top-up amount must be in multiples of 10.');
            }
            
            if ($wallet->cash_wallet < $topupAmount) {
                return redirect()->back()->withErrors('Insufficient balance in Cash Wallet for top-up.');
            }
            
            // For top-ups, we use the user's current range.
            $initialRange = DB::table('directranges')->where('id', $user->package)->first();
            Log::channel('payout')->info("Using user's current range for top-up", ['range' => (array)$initialRange]);
        }
    
        // Determine the amount for transfer.
        $amount = !$user->package ? $activationAmount : $topupAmount;
        Log::channel('payout')->info("Amount determined for transfer", ['user_id' => $userId, 'amount' => $amount]);
    
        // Deduct from cash_wallet and add to trading_wallet.
        $wallet->cash_wallet -= $amount;
        $wallet->trading_wallet += $amount;
        $wallet->save();
        
        Log::channel('payout')->info("Wallet updated", [
            'user_id' => $userId,
            'new_cash_wallet' => $wallet->cash_wallet,
            'new_trading_wallet' => $wallet->trading_wallet
        ]);
    
        // Generate a unique transaction ID.
        do {
            $randomNumber = str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $txid = 't_' . $randomNumber;
        } while (Transfer::where('txid', $txid)->exists());
        
        // Record the transfer.
        $transfer = Transfer::create([
            'user_id'     => $userId,
            'txid'        => $txid,
            'from_wallet' => 'cash_wallet',
            'to_wallet'   => 'trading_wallet',
            'amount'      => $amount,
            'status'      => 'Completed',
            'remark'      => 'package',
        ]);
        
        // ↓↓↓ CAMPAIGN BALANCE DEDUCTION ↓↓↓
        /*$balanceSetting = \App\Models\Setting::where('name', 'cam_balance')->first();
        if ($balanceSetting) {
            $balanceSetting->value = max(0, $balanceSetting->value - $amount); // prevent negative
            $balanceSetting->save();
        
            Log::channel('payout')->info("✅ Campaign balance updated", [
                'user_id' => $userId,
                'new_cam_balance' => $balanceSetting->value
            ]);
        
            // Broadcast the updated balance
            event(new \App\Events\CampaignBalanceUpdated($balanceSetting->value));
            Log::channel('payout')->info("📡 CampaignBalanceUpdated event broadcasted", [
                'value' => $balanceSetting->value
            ]);
        }*/
        
        // For first-time activation: recalc the user's current group total to determine the proper direct range.
        // This assumes you have a service that calculates the total group value.
        if (!$user->package) {
            $rangeData = (new \App\Services\UserRangeCalculator())->calculate($user);
            $total = $rangeData['total'];
            Log::channel('payout')->info("Calculated group total for activation", ['user_id' => $userId, 'total' => $total]);
    
            // Now, find the matching direct range based on the total.
            $newRange = DB::table('directranges')
                ->where('min', '<=', $total)
                ->where(function($query) use ($total) {
                    $query->where('max', '>=', $total)
                          ->orWhereNull('max');
                })->first();
    
            if (!$newRange) {
                return redirect()->back()->withErrors('Your total does not match any available range.');
            }
            Log::channel('payout')->info("New range determined", ['new_range' => (array)$newRange]);
    
            // Save the new direct range id to the user.
            $user->package = $newRange->id;
            $user->save();
            Log::channel('payout')->info("User package updated", ['user_id' => $userId, 'new_package' => $newRange->id]);
        } else {
            // Optionally, update the user's range if the top-up changes their group total.
            // Example:
            $rangeData = (new \App\Services\UserRangeCalculator())->calculate($user);
            $total = $rangeData['total'];
            Log::channel('payout')->info("Calculated group total for top-up", ['user_id' => $userId, 'total' => $total]);
    
            $newRange = DB::table('directranges')
                ->where('min', '<=', $total)
                ->where(function($query) use ($total) {
                    $query->where('max', '>=', $total)
                          ->orWhereNull('max');
                })->first();
            if ($newRange && $newRange->id !== $user->package) {
                $user->package = $newRange->id;
                $user->save();
                Log::channel('payout')->info("User package updated due to top-up", ['user_id' => $userId, 'new_package' => $newRange->id]);
            }
        }
        
        // Distribute the transfer to the direct upline.
        // Note: Depending on your business logic, you can pass $initialRange or $newRange.
        $uplineDistributor = new \App\Services\UplineDistributor();
        $uplineDistributor->distributeDirect($transfer, $initialRange, $user);
        
        // --- BONUS TRANSFER LOGIC ---
        if (!empty($user->bonus)) {
            $bonusCode = strtoupper($user->bonus);
            Log::channel('payout')->info("Bonus code found", ['user_id' => $userId, 'bonus_code' => $bonusCode]);
    
            $promotion = DB::table('promotions')
                ->whereRaw('LOWER(code) = ?', [strtolower($bonusCode)])
                ->first();
            if ($promotion) {
                $multiply = $promotion->multiply;
                $bonusAmount = $amount * $multiply;
                Log::channel('payout')->info("Bonus calculated", ['user_id' => $userId, 'bonus_amount' => $bonusAmount]);
    
                $wallet->bonus_wallet += $bonusAmount;
                $wallet->save();
                Log::channel('payout')->info("Bonus wallet updated", ['user_id' => $userId, 'new_bonus_wallet' => $wallet->bonus_wallet]);
    
                do {
                    $bonusTxid = 'b_' . rand(10000, 99999);
                } while (Transfer::where('txid', $bonusTxid)->exists());
                Log::channel('payout')->info("Bonus transaction ID generated", ['bonus_txid' => $bonusTxid]);
    
                Transfer::create([
                    'user_id'     => $user->id,
                    'txid'        => $bonusTxid,
                    'from_wallet' => 'trading_wallet',
                    'to_wallet'   => 'bonus_wallet',
                    'amount'      => $bonusAmount,
                    'status'      => 'Completed',
                    'remark'      => 'bonus',
                ]);
                Log::channel('payout')->info("Bonus transfer record created", ['bonus_txid' => $bonusTxid]);
            }
        }
        // --- END BONUS LOGIC ---
        
        return redirect()->back()->with('success', 'Trade Margin top-up successfully!');
    }
    
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);
    
        $userId = auth()->id();
        Log::channel('admin')->info("--------> Deposit initiated", ['user_id' => $userId, 'amount' => $request->amount]);
    
        // Use a fixed (sample) TRC20 address for deposits (not editable in the form)
        $sampleTRC20 = 'TR9wHy8rF89a59gD3dmMPhrtPhtu6n5U5H';
    
        // Generate a unique transaction ID for the deposit.
        do {
            // Generate an 8-digit number with leading zeros if necessary
            $randomNumber = str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $txid = 'd_' . $randomNumber;
        } while (Deposit::where('txid', $txid)->exists());
        Log::channel('admin')->info("Deposit transaction ID generated", ['txid' => $txid]);
    
        // Create the deposit with status "Completed" for auto-approval.
        $deposit = Deposit::create([
            'user_id'       => $userId,
            'txid'          => $txid,
            'amount'        => $request->amount,
            'trc20_address' => $sampleTRC20,
            'status'        => 'Pending', // Set directly to Completed
        ]);
        Log::channel('admin')->info("Deposit record created", ['txid' => $txid]);
    
        // Find the user's wallet; create one if it doesn't exist.
        /*$wallet = Wallet::firstOrNew(['user_id' => $userId]);
        if (!$wallet->exists) {
            $wallet->cash_wallet = 0;
            $wallet->trading_wallet = 0;
            $wallet->earning_wallet = 0;
            $wallet->affiliates_wallet = 0;
            Log::channel('admin')->info("New wallet created for user", ['user_id' => $userId]);
        } else {
            Log::channel('admin')->info("Existing wallet retrieved", ['user_id' => $userId]);
        }
    
        // Add the deposit amount to the user's Cash Wallet.
        $wallet->cash_wallet += $deposit->amount;
        $wallet->save();
        Log::channel('admin')->info("Wallet updated with deposit", [
            'user_id' => $userId,
            'new_cash_wallet_balance' => $wallet->cash_wallet
        ]);*/
    
        return redirect()->back()->with('success', 'Deposit submitted successfully.');
    }
    
    public function coindeposit(Request $request, CoinDepositService $coinService)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);
    
        $userId = auth()->id();
        $coinService->depositToUser($userId, $data['amount'], auth()->user()->wallet_address);
    
        return redirect()->back()->with('success','Deposit submitted successfully.');
    }
    
    public function withdrawal(Request $request)
    {
        $request->validate([
            'amount'        => 'required|numeric|min:10',
            'trc20_address' => 'required|string',
        ]);
    
        $user = auth()->user();
        $userId = auth()->id();
        
        // ✅ Check 2FA if enabled
        if ($user->two_fa_enabled) {
            $request->validate([
                'otp' => 'required|digits:6',
            ]);
    
            $google2fa = new Google2FA();
            $isValidOtp = $google2fa->verifyKey($user->google2fa_secret, $request->otp);
    
            if (!$isValidOtp) {
                return back()->withErrors(['otp' => 'Invalid 2FA code.']);
            }
        }
    
        $wallet = Wallet::where('user_id', $userId)->first();
        if (!$wallet) {
            return redirect()->back()->withErrors('Wallet not found.');
        }
    
        if ($wallet->cash_wallet < $request->amount) {
            return redirect()->back()->withErrors('Insufficient balance in Cash Wallet.');
        }
    
        $feePercentage = 0.03;
        $percentageFee = round($request->amount * $feePercentage, 2);
        $fee = max($percentageFee, 7.00);
        $netAmount = $request->amount - $fee;
    
        do {
            $randomNumber = str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $txid = 'w_' . $randomNumber;
        } while (Withdrawal::where('txid', $txid)->exists());
    
        Withdrawal::create([
            'user_id'       => $userId,
            'txid'          => $txid,
            'amount'        => $netAmount,
            'fee'           => $fee,
            'trc20_address' => $request->trc20_address,
            'status'        => 'Pending',
        ]);
    
        $wallet->cash_wallet -= $request->amount;
        $wallet->save();
    
        $user = User::find($userId);
        // choose chat ID by amount
        $chatId = ((float) $request->amount >= 2000)
            ? '-1002720623603'   // ≥ 2000
            : '-1002643026089';  // < 2000
    
        // Get direct referral
        $referralUser = User::find($user->referral);
        $referralName = $referralUser ? $referralUser->name : 'N/A';
    
        // Get top referral (2 levels before ID 2 if possible)
        $current = $user;
        $prev1 = null;
        $prev2 = null;
        while ($current && $current->referral && $current->referral != 2) {
            $prev2 = $prev1;
            $prev1 = User::find($current->referral);
            $current = $prev1;
            if ($current && $current->id == 2) {
                break;
            }
        }
    
        $topReferralName = $prev2 ? $prev2->name : ($prev1 ? $prev1->name : 'N/A');
    
        $message = "<b>Withdrawal Request 🧾</b>\n"
                 . "User ID: {$user->id}\n"
                 . "Name: {$user->name}\n"
                 . "Email: {$user->email}\n"
                 . "Request: {$request->amount} USDT\n"
                 . "Fee: {$fee} USDT\n"
                 . "Net Amount: {$netAmount} USDT\n"
                 . "To Address: {$request->trc20_address}\n"
                 . "Referral: {$referralName}\n"
                 . "Top Referral: {$topReferralName}\n"
                 . "TXID: {$txid}";
    
        if (!in_array($userId, [1, 2])) {
            (new TelegramService())->sendMessage($message, $chatId);
        }

    
        return redirect()->back()->with('success', 'Withdrawal request submitted successfully.');
    }
    
    public function sendFunds(Request $request)
    {
        $user = auth()->user();
    
        // Restrict by ID if needed
        if ($user->id <= 202) {
            Log::channel('admin')->warning('sendFunds: Unauthorized user tried to send funds', [
                'user_id' => $user->id,
            ]);
            return redirect()->back()->withErrors(['You do not have permission to send funds.']);
        }
    
        // Base request validation
        $request->validate([
            'downline_email' => 'required|string',
            'amount'         => 'required|numeric|min:10',
        ]);
    
        // ✅ Enforce 2FA if enabled
        if ($user->two_fa_enabled) {
            $request->validate(['otp' => 'required|digits:6']);
            $google2fa = new Google2FA();
            if (!$google2fa->verifyKey($user->google2fa_secret, $request->otp)) {
                return redirect()->back()->withErrors(['otp' => 'Invalid 2FA code.']);
            }
        }
    
        // ✅ Enforce security password if set
        if ($user->security_pass) {
            $request->validate(['security_pass' => 'required|string']);
            if ($request->security_pass !== $user->security_pass) {
                return redirect()->back()->withErrors(['Invalid security password.']);
            }
        }
    
        $amount = (float) $request->amount;
    
        // --- 🔍 Check available balance before recalculation ---
        if ($user->wallet->cash_wallet < $amount) {
            return redirect()->back()->withErrors(['Insufficient funds in your USDT wallet.']);
        }
    
        // Get recipient
        $recipient = User::where('email', $request->downline_email)
            ->orWhere('name', $request->downline_email)
            ->first();
    
        if (!$recipient) {
            return redirect()->back()->withErrors(['The specified user does not exist.']);
        }
    
        // Ensure in same tree
        if (!User::isInSameTree($user->id, $recipient->id)) {
            return redirect()->back()->withErrors(['The specified user is not in your referral tree.']);
        }
    
        if ($user->id === $recipient->id) {
            return redirect()->back()->withErrors(['You cannot send funds to yourself.']);
        }
    
        // --- ✅ Run wallet recalculation check before transfer ---
        $negatives = $this->checkNegativeWallets([$user->id, $recipient->id]);
        if (!empty($negatives)) {
            return redirect()->back()->withErrors([
                'Wallet verification failed for user(s): ' . implode(', ', $negatives) .
                '. One or more wallets are negative — please contact admin.'
            ]);
        }
    
        // --- ✅ Perform transaction safely ---
        DB::transaction(function () use ($user, $recipient, $amount) {
            $user->wallet->cash_wallet -= $amount;
            $user->wallet->save();
    
            $recipient->wallet->cash_wallet += $amount;
            $recipient->wallet->save();
    
            do {
                $senderTxid = 's_' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            } while (Transfer::where('txid', $senderTxid)->exists());
            
            do {
                $receiverTxid = 's_' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            } while (Transfer::where('txid', $receiverTxid)->exists());

    
            Transfer::create([
                'user_id'     => $user->id,
                'txid'        => $senderTxid,
                'from_wallet' => 'cash_wallet',
                'to_wallet'   => 'cash_wallet',
                'amount'      => -abs($amount),
                'status'      => 'Completed',
                'remark'      => 'downline',
            ]);
    
            Transfer::create([
                'user_id'     => $recipient->id,
                'txid'        => $receiverTxid,
                'from_wallet' => 'cash_wallet',
                'to_wallet'   => 'cash_wallet',
                'amount'      => abs($amount),
                'status'      => 'Completed',
                'remark'      => 'downline',
            ]);
    
            Log::channel('admin')->info("sendFunds: transfer complete", [
                'sender_id'     => $user->id,
                'recipient_id'  => $recipient->id,
                'amount'        => $amount,
                'sender_txid'   => $senderTxid,
                'receiver_txid' => $receiverTxid,
            ]);
        });
    
        return redirect()->back()->with('success', 'Funds sent successfully.');
    }
    
    private function checkNegativeWallets(array $userIds): array
    {
        $negativeUsers = [];
    
        foreach ($userIds as $uid) {
            $user = User::find($uid);
            if (!$user) continue;
    
            // replicate cash wallet calculation from RecalculateWallets
            $cashParts = [
                DB::table('deposits')->where('user_id', $uid)->where('status', 'Completed')->sum('amount'),
                -DB::table('withdrawals')->where('user_id', $uid)->where('status', '!=', 'Rejected')
                    ->select(DB::raw('SUM(amount + fee) as total'))->value('total'),
                DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                    ->whereIn('from_wallet', ['affiliates_wallet', 'earning_wallet'])
                    ->where('to_wallet', 'cash_wallet')->sum('amount'),
                DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                    ->where('from_wallet', 'trading_wallet')->where('to_wallet', 'cash_wallet')->sum('amount'),
                -DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                    ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'trading_wallet')
                    ->where('remark', 'package')->sum('amount'),
                DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                    ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'cash_wallet')
                    ->where('remark', 'downline')->where('amount', '>', 0)->sum('amount'),
                DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                    ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'cash_wallet')
                    ->where('remark', 'downline')->where('amount', '<', 0)->sum('amount'),
                DB::table('transfers')->where('user_id', $uid)->where('status', 'Completed')
                    ->where('from_wallet', 'cash_wallet')->where('to_wallet', 'cash_wallet')
                    ->where('remark', 'system')->sum('amount'),
            ];
    
            $cash = array_sum($cashParts);
    
            if ($cash < 0) {
                $negativeUsers[] = "{$user->name} (#{$uid})";
                Log::channel('admin')->warning("checkNegativeWallets: user {$uid} has negative cash_wallet", [
                    'cash_wallet_calc' => $cash,
                    'parts' => $cashParts
                ]);
            }
        }
    
        return $negativeUsers;
    }

    public static function getAllDownlineIds($userId)
    {
        $downlines = User::where('referral', $userId)->pluck('id')->toArray();
    
        foreach ($downlines as $downlineId) {
            $downlines = array_merge($downlines, self::getAllDownlineIds($downlineId));
        }
    
        return $downlines;
    }
    
    public static function getUplineIds($userId)
    {
        $uplines = [];
        $current = User::find($userId);
    
        while ($current && $current->referral) {
            $uplines[] = $current->referral;
            $current = User::find($current->referral);
        }
    
        return $uplines;
    }
    
    public function claimCampaignBonus(Request $request)
    {
        $user = auth()->user();
        $userId = $user->id;
    
        Log::channel('admin')->info("🎯 BonusClaim: User {$userId} started bonus claim.");
    
        $campaignTradingBonus = \App\Models\Transfer::where('user_id', $userId)
            ->where('from_wallet', 'cash_wallet')
            ->where('to_wallet', 'trading_wallet')
            ->where('status', 'Completed')
            ->where('remark', 'campaign')
            ->sum('amount');
    
        $startMY = Carbon::createFromFormat('Y-m-d H:i:s', '2025-05-20 12:01:00', 'Asia/Kuala_Lumpur');
        $endMY   = Carbon::createFromFormat('Y-m-d H:i:s', '2025-06-11 18:20:59', 'Asia/Kuala_Lumpur');
    
        $topupraw = Transfer::where('user_id', $userId)
            ->where('remark', 'package')
            ->where('status', 'Completed')
            ->whereBetween('created_at', [$startMY, $endMY])
            ->sum('amount');
            
        // Apply multiplier or minimum only *after* confirming topup > 0
        if ($user->created_at < $startMY) {
            $topupraw = $topupraw * 1.5;
        } elseif ($topupraw < 100) {
            $topupraw = 100;
        }
        
        $topup = $topupraw - $campaignTradingBonus;
    
        
    
        // Final validation after manipulation
        if ($topup <= 0) {
            Log::channel('admin')->warning("❌ BonusClaim: Computed topup is invalid for user {$userId}.");
            return redirect()->back()->withErrors('Something went wrong while computing your bonus.');
        }
    
        // Calculate breakdown
        $totalTopup = round($topup / 1.5, 2);
        $boost = $topup - $totalTopup;
    
        Log::channel('admin')->info("🎁 BonusClaim: topup = {$topup} | totalTopup = {$totalTopup} | boost = {$boost}");
    
        // Credit trading_wallet with bonus
        $wallet = Wallet::firstOrCreate(['user_id' => $userId]);
        $wallet->trading_wallet += $topup;
        $wallet->save();
    
        do {
            $txid = 't_' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Transfer::where('txid', $txid)->exists());
    
        Transfer::create([
            'user_id'     => $userId,
            'txid'        => $txid,
            'from_wallet' => 'cash_wallet',
            'to_wallet'   => 'trading_wallet',
            'amount'      => $topup,
            'status'      => 'Completed',
            'remark'      => 'campaign',
        ]);
    
        Log::channel('admin')->info("✅ BonusClaim: Transfer created for user {$userId} | txid: {$txid} | amount: {$topup}");
    
        
        // ALWAYS return JSON for AJAX:
        return response()->json([
            'success'        => true,
            'message'        => 'Campaign bonus successfully claimed!',
            'total_topup'    => $totalTopup,
            'campaign_boost' => $boost,
            'bonus_margin'   => $topup,
        ]);
    }

}