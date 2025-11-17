<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Wallet;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\Transfer;
use App\Models\Asset;
use App\Models\Payout;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssetsIndexBuilder
{
    /**
     * @param User $user
     * @param int  $perPage
     * @param array $opts  e.g. ['trading_page'=>2, 'payout_page'=>3, 'direct_payout_page'=>1]
     */
    public function build(User $user, int $perPage = 10, array $opts = []): array
    {
        $userId = (int)$user->id;

        // Read explicit page overrides first; fall back to current request; default 1
        $tradingPage      = max(1, (int)($opts['trading_page']       ?? request()->input('trading_page', 1)));
        $payoutPage       = max(1, (int)($opts['payout_page']        ?? request()->input('payout_page', 1)));
        $directPayoutPage = max(1, (int)($opts['direct_payout_page'] ?? request()->input('direct_payout_page', 1)));

        // Wallets -> plain array
        $w = Wallet::where('user_id', $userId)->first();
        $wallets = $w ? $this->walletToArray($w) : [
            'cash_wallet'       => 0.0,
            'trading_wallet'    => 0.0,
            'earning_wallet'    => 0.0,
            'affiliates_wallet' => 0.0,
        ];

        $total_balance = (float)$wallets['cash_wallet']
            + (float)$wallets['trading_wallet']
            + (float)$wallets['earning_wallet']
            + (float)$wallets['affiliates_wallet'];

        // ROI (earning) paginator -> explicit page param
        $roi = Payout::with(['order.pair.currency'])
            ->where('user_id', $userId)
            ->where('type', 'payout')
            ->where('wallet', 'earning')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'trading_page', $tradingPage);

        $roiData = [];
        foreach ($roi as $item) {
            $roiData[] = [
                'id'         => (int)$item->id,
                'user_id'    => (int)$item->user_id,
                'order_id'   => (int)$item->order_id,
                'txid'       => (string)$item->txid,
                'total'      => (string)$item->total,
                'actual'     => (string)$item->actual,
                'type'       => (string)$item->type,
                'wallet'     => (string)$item->wallet,
                'status'     => (int)$item->status,
                'created_at' => (string)$item->created_at,
                'updated_at' => (string)$item->updated_at,
                'cname'      => ($item->order && $item->order->pair && $item->order->pair->currency)
                    ? (string)$item->order->pair->currency->c_name
                    : 'N/A',
                'order'      => $item->order ? [
                    'id'        => (int)$item->order->id,
                    'pair_id'   => (int)$item->order->pair_id,
                    'txid'      => (string)$item->order->txid,
                    'buy'       => (string)$item->order->buy,
                    'sell'      => $item->order->sell === null ? null : (string)$item->order->sell,
                    'receive'   => (string)$item->order->receive,
                    'status'    => (string)$item->order->status,
                    'earning'   => (string)$item->order->earning,
                    'created_at'=> (string)$item->order->created_at,
                    'updated_at'=> (string)$item->order->updated_at,
                    'pair'      => $item->order->pair ? [
                        'id'        => (int)$item->order->pair->id,
                        'currency'  => $item->order->pair->currency ? [
                            'id'    => (int)$item->order->pair->currency->id,
                            'c_name'=> (string)$item->order->pair->currency->c_name,
                        ] : null,
                    ] : null,
                ] : null,
            ];
        }
        $roiPayload = [
            'data'         => $roiData,
            'current_page' => $roi->currentPage(),
            'per_page'     => $roi->perPage(),
            'total'        => $roi->total(),
            'last_page'    => $roi->lastPage(),
            'page_name'    => $roi->getPageName(),
        ];

        // Movements (unchanged)
        $depositRequests = Deposit::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($d) {
                return [
                    'id'                       => (int)$d->id,
                    'user_id'                  => (int)$d->user_id,
                    'txid'                     => (string)$d->txid,
                    'amount'                   => (string)$d->amount,
                    'fee'                      => (string)($d->fee ?? '0'),
                    'trc20_address'            => (string)$d->trc20_address,
                    'status'                   => (string)$d->status,
                    'created_at'               => $this->toCarbon($d->created_at),
                    'updated_at'               => $this->toCarbon($d->updated_at),
                    'type'                     => 'Deposit',
                    'transaction_description'  => 'Deposit',
                    'transaction_amount'       => '+' . number_format((float)$d->amount, 4),
                    'remark'                   => '',
                ];
            })
            ->values()
            ->all();

        $withdrawalRequests = Withdrawal::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($w) {
                $rowType  = $w->status === 'Completed' ? 'Withdrawal' : null;
                $desc     = $w->status === 'Completed' ? 'Withdraw' : null;
                $amountTx = $w->status === 'Completed' ? '-' . number_format((float)$w->amount, 4) : null;

                return [
                    'id'                       => (int)$w->id,
                    'user_id'                  => (int)$w->user_id,
                    'txid'                     => (string)$w->txid,
                    'amount'                   => (string)$w->amount,
                    'fee'                      => (string)($w->fee ?? '0'),
                    'trc20_address'            => (string)$w->trc20_address,
                    'status'                   => (string)$w->status,
                    'created_at'               => $this->toCarbon($w->created_at),
                    'updated_at'               => $this->toCarbon($w->updated_at),
                    'type'                     => $rowType,
                    'transaction_description'  => $desc,
                    'transaction_amount'       => $amountTx,
                    'remark'                   => '',
                ];
            })
            ->values()
            ->all();

        $transferRecords = Transfer::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                $desc = null;
                if (
                    $t->status === 'Completed' &&
                    $t->from_wallet === 'cash_wallet' &&
                    $t->to_wallet === 'cash_wallet' &&
                    $t->remark === 'downline'
                ) {
                    $desc = 'USDT → Downline';
                } else {
                    $map = [
                        'earning_wallet'    => 'Trade Profit',
                        'affiliates_wallet' => 'Affiliates',
                        'cash_wallet'       => 'USDT',
                        'trading_wallet'    => 'Trade Margin',
                        'staking_wallet'    => 'Staking_wallet',
                    ];
                    $from = $map[$t->from_wallet] ?? ucfirst($t->from_wallet);
                    $to   = $map[$t->to_wallet] ?? ucfirst($t->to_wallet);
                    $desc = "{$from} → {$to}";
                }

                return [
                    'id'                       => (int)$t->id,
                    'user_id'                  => (int)$t->user_id,
                    'txid'                     => (string)$t->txid,
                    'from_wallet'              => (string)$t->from_wallet,
                    'to_wallet'                => (string)$t->to_wallet,
                    'amount'                   => (string)$t->amount,
                    'status'                   => (string)$t->status,
                    'remark'                   => (string)$t->remark,
                    'created_at'               => $this->toCarbon($t->created_at),
                    'updated_at'               => $this->toCarbon($t->updated_at),
                    'type'                     => 'Transfer',
                    'transaction_description'  => $desc,
                    'transaction_amount'       => number_format((float)$t->amount, 4),
                ];
            })
            ->values()
            ->all();

        $transactions = collect($depositRequests)
            ->merge($withdrawalRequests)
            ->merge($transferRecords)
            ->filter(fn($x) => ($x['type'] ?? null) !== null)
            ->sortByDesc('created_at')
            ->map(fn($x) => (object) $x)
            ->values()
            ->all();

        $assets = Asset::with('currencyData')->where('user_id', $userId)->get()
            ->map(fn($a) => (object) [
                'id'         => (int)$a->id,
                'user_id'    => (int)$a->user_id,
                'currency'   => (string)$a->currency,
                'amount'     => (string)$a->amount,
                'status'     => (string)$a->status,
                'created_at' => $this->toCarbon($a->created_at),
                'updated_at' => $this->toCarbon($a->updated_at),
                'currency_data' => $a->currencyData ? (object) [
                    'id'     => (int)$a->currencyData->id,
                    'c_name' => (string)$a->currencyData->c_name,
                    'status' => (int)$a->currencyData->status,
                    'timezone' => (int)($a->currencyData->timezone ?? 0),
                ] : null,
            ])
            ->values()
            ->all();

        $priceCostByCurrency = DB::table('orders')
            ->join('pairs', 'orders.pair_id', '=', 'pairs.id')
            ->join('currencies', 'pairs.currency_id', '=', 'currencies.id')
            ->select('currencies.c_name as currency', DB::raw('AVG(orders.buy) as avg_price'))
            ->where('orders.user_id', $userId)
            ->where('orders.buy', '>', 0)
            ->groupBy('currencies.c_name')
            ->pluck('avg_price', 'currency')
            ->map(fn($v) => (float)$v)
            ->toArray();

        $today = now()->startOfDay();
        $changeResults = DB::table('orders')
            ->join('pairs', 'orders.pair_id', '=', 'pairs.id')
            ->join('currencies', 'pairs.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.c_name as currency',
                DB::raw("SUM(orders.buy) as total_buy"),
                DB::raw("SUM(orders.sell) as total_sell")
            )
            ->where('orders.user_id', $userId)
            ->where('orders.created_at', '>=', $today)
            ->groupBy('currencies.c_name')
            ->get();

        $netChangeByCurrency = [];
        foreach ($changeResults as $row) {
            $netChangeByCurrency[(string)$row->currency] =
                (float)$row->total_buy - (float)$row->total_sell;
        }

        $packages = DB::table('packages')->where('status', '1')->get()
            ->map(function ($p) {
                $arr = (array) $p;
                if (!array_key_exists('profit_sharing', $arr)) {
                    $arr['profit_sharing'] = $arr['profit'] ?? null;
                }
                return $arr;
            })
            ->values()
            ->all();

        $currentPackage = null;
        if ($user->package) {
            $cp = DB::table('packages')->where('id', $user->package)->first();
            if ($cp) {
                $currentPackage = (array) $cp;
                if (!array_key_exists('profit_sharing', $currentPackage)) {
                    $currentPackage['profit_sharing'] = $currentPackage['profit'] ?? null;
                }
            }
        }

        // Affiliates payouts -> explicit page
        $payout = Payout::where('user_id', $userId)
            ->where('wallet', 'affiliates')
            ->where('type', 'payout')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'payout_page', $payoutPage);

        $payoutData = [];
        foreach ($payout as $p) {
            $order = Order::find($p->order_id);
            $payoutData[] = [
                'id'              => (int)$p->id,
                'user_id'         => (int)$p->user_id,
                'order_id'        => (int)$p->order_id,
                'txid'            => (string)$p->txid,
                'total'           => (string)$p->total,
                'actual'          => (string)$p->actual,
                'type'            => (string)$p->type,
                'wallet'          => (string)$p->wallet,
                'status'          => (int)$p->status,
                'created_at'      => (string)$p->created_at,
                'updated_at'      => (string)$p->updated_at,
                'buy'             => $order ? number_format((float)$order->buy, 4) : '0.0000',
                'earning'         => $order ? number_format((float)$order->earning, 4) : '0.0000',
            ];
        }
        $payoutPayload = [
            'data'         => $payoutData,
            'current_page' => $payout->currentPage(),
            'per_page'     => $payout->perPage(),
            'total'        => $payout->total(),
            'last_page'    => $payout->lastPage(),
            'page_name'    => $payout->getPageName(),
        ];

        // Direct payouts -> explicit page (and fix perPage())
        $direct = Payout::where('user_id', $userId)
            ->where('wallet', 'affiliates')
            ->where('type', 'direct')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'direct_payout_page', $directPayoutPage);

        $directData = [];
        foreach ($direct as $p) {
            $tr = Transfer::where('txid', $p->txid)->first();
            $directData[] = [
                'id'              => (int)$p->id,
                'user_id'         => (int)$p->user_id,
                'order_id'        => (int)$p->order_id,
                'txid'            => (string)$p->txid,
                'total'           => (string)$p->total,
                'actual'          => (string)$p->actual,
                'type'            => (string)$p->type,
                'wallet'          => (string)$p->wallet,
                'status'          => (int)$p->status,
                'created_at'      => (string)$p->created_at,
                'updated_at'      => (string)$p->updated_at,
                'deposit_txid'    => $tr ? (string)$tr->txid : 'N/A',
                'deposit_amount'  => $tr ? number_format((float)$tr->amount, 4) : '0.0000',
            ];
        }
        $directPayload = [
            'data'         => $directData,
            'current_page' => $direct->currentPage(),
            'per_page'     => $direct->perPage(), // <- fixed
            'total'        => $direct->total(),
            'last_page'    => $direct->lastPage(),
            'page_name'    => $direct->getPageName(),
        ];

        $range = (new \App\Services\UserRangeCalculator())->calculate($user);

        return [
            'title'               => 'My Assets',
            'wallets'             => $wallets,
            'total_balance'       => (float)$total_balance,
            'depositRequests'     => $this->asStd($depositRequests),
            'withdrawalRequests'  => $this->asStd($withdrawalRequests),
            'transferRecords'     => $this->asStd($transferRecords),
            'transactions'        => $transactions,
            'assets'              => $assets,
            'priceCostByCurrency' => $priceCostByCurrency,
            'netChangeByCurrency' => $netChangeByCurrency,
            'packages'            => $packages,
            'currentPackage'      => $currentPackage,
            'payoutRecords'       => $payoutPayload,
            'directPayoutRecords' => $directPayload,
            'roiRecords'          => $roiPayload,
            'direct_percentage'   => (float)$range['direct_percentage'],
            'matching_percentage' => (float)$range['matching_percentage'],
            'computed_at'         => now()->toIso8601String(),
        ];
    }


    private function walletToArray(Wallet $w): array
    {
        return [
            'cash_wallet'       => (float)$w->cash_wallet,
            'trading_wallet'    => (float)$w->trading_wallet,
            'earning_wallet'    => (float)$w->earning_wallet,
            'affiliates_wallet' => (float)$w->affiliates_wallet,
        ];
    }

    private function toCarbon($v): ?Carbon
    {
        if ($v instanceof Carbon) { return $v; }
        if ($v === null || $v === '') { return null; }
        try { return Carbon::parse($v); } catch (\Throwable $e) { return null; }
    }

    private function asStd(array $rows): array
    {
        return array_map(function ($x) {
            // make sure created_at/updated_at are Carbon if present as strings
            if (isset($x['created_at']) && !($x['created_at'] instanceof Carbon)) {
                $x['created_at'] = $this->toCarbon($x['created_at']);
            }
            if (isset($x['updated_at']) && !($x['updated_at'] instanceof Carbon)) {
                $x['updated_at'] = $this->toCarbon($x['updated_at']);
            }
            // guarantee remark key (Blade prints it)
            if (!array_key_exists('remark', $x)) {
                $x['remark'] = '';
            }
            return (object)$x;
        }, $rows);
    }
}