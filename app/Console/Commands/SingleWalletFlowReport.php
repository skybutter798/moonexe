<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

class SingleWalletFlowReport extends Command
{
    protected $signature = 'wallet:flow {user_id}';
    protected $description = 'Get detailed wallet inflow/outflow breakdown for a single user.';

    public function handle()
    {
        $uid = (int) $this->argument('user_id');
        $user = User::find($uid);
        $dateFormat = 'Y-m-d H:i';

        if (!$user) {
            $this->error("User ID $uid not found.");
            return;
        }

        $this->info("🧾 Wallet Flow Report for User ID: $uid ($user->name)");

        $totalIn = 0;
        $totalOut = 0;

        // Deposits
        $deposits = DB::table('deposits')->where('user_id', $uid)->orderByDesc('id')->get();
        $this->line("\n💳 Deposits:");
        foreach ($deposits as $d) {
            $date = Carbon::parse($d->created_at)->format($dateFormat);
            $this->line(sprintf("  - #%4d | %-10s | Amount: %10.2f | TXID: %s | Date: %s", 
                $d->id, $d->status, $d->amount, $d->external_txid, $date));
            if (strtolower($d->status) === 'completed') {
                $totalIn += $d->amount;
            }
        }

        // Withdrawals
        $withdrawals = DB::table('withdrawals')->where('user_id', $uid)->orderByDesc('id')->get();
        $totalOut = 0;
        
        $this->line("\n📉 Withdrawals:");
        foreach ($withdrawals as $w) {
            $date = Carbon::parse($w->created_at)->format($dateFormat);
            $fee  = $w->fee ?? 0;
            $gross = $w->amount + $fee;
        
            $this->line(sprintf(
                "  - #%4d | %-10s | Amount: %10.2f | Fee: %6.2f | Total: %10.2f | Date: %s", 
                $w->id, $w->status, $w->amount, $fee, $gross, $date
            ));
        
            if (strtolower($w->status) === 'completed') {
                $totalOut += $gross;
            }
        }


        // Transfers
        $transfers = DB::table('transfers')->where('user_id', $uid)->orderByDesc('id')->get();
        $this->line("\n🔁 Transfers:");
        $this->line(sprintf("%-6s %-10s %-26s %-12s %-19s %s", "ID", "Status", "From ➜ To", "Amount", "Date", "Description"));

        foreach ($transfers as $t) {
            $desc = '';
            if ($t->from_wallet === 'affiliates_wallet' && $t->to_wallet === 'cash_wallet') {
                $desc = 'Matching withdrawal';
                $totalIn += $t->amount;
            } elseif ($t->from_wallet === 'earning_wallet' && $t->to_wallet === 'cash_wallet') {
                $desc = 'ROI withdrawal';
                $totalIn += $t->amount;
            } elseif ($t->from_wallet === 'cash_wallet' && $t->to_wallet === 'cash_wallet') {
                $desc = $t->amount < 0 ? 'To Downline' : 'From Upline';
                if ($t->amount < 0) $totalOut += abs($t->amount);
                else $totalIn += $t->amount;
            } elseif ($t->from_wallet === 'cash_wallet' && $t->to_wallet === 'trading_wallet') {
                $desc = 'Trading Margin';
                $totalOut += $t->amount;
            }

            $date = Carbon::parse($t->created_at)->format($dateFormat);
            $this->line(sprintf("#%-5d %-10s %-26s %12.4f %-19s %s",
                $t->id, $t->status, "{$t->from_wallet} ➜ {$t->to_wallet}", $t->amount, $date, $desc));
        }

        // Payouts
        $payouts = DB::table('payouts')->where('user_id', $uid)->orderByDesc('id')->get();
        $this->line("\n📈 Payouts:");
        foreach ($payouts as $p) {
            $amount = property_exists($p, 'actual') ? (float) $p->actual : 0;
            $date = Carbon::parse($p->created_at)->format($dateFormat);
            $this->line(sprintf("  - #%4d | %-10s | %-16s | Amount: %10.2f | Status: %-8s | Date: %s", 
                $p->id, $p->type, $p->wallet, $amount, $p->status, $date));
            if (strtolower($p->status) === 'completed' || $p->status === '1') {
                $totalOut += $amount;
            }
        }

        // Open Orders
        $orders = DB::table('orders')
            ->where('user_id', $uid)
            ->where('status', 'Open')
            ->orderByDesc('id')
            ->get();
        $this->line("\n🕹️ Open Orders:");
        foreach ($orders as $o) {
            $date = Carbon::parse($o->created_at)->format($dateFormat);
            $this->line(sprintf("  - #%4d | Buy: %-10s | Sell: %-10s | Symbol: %-10s | Date: %s", 
                $o->id, $o->buy_amount, $o->sell_amount, $o->symbol, $date));
        }

        // Summary
        $this->line("\n📊 Summary:");
        $this->line(sprintf("  ✅ Total Inflow:  %.2f", $totalIn));
        $this->line(sprintf("  ❌ Total Outflow: %.2f", $totalOut));
        $this->line(sprintf("  💰 Net Change:    %.2f", $totalIn - $totalOut));

        $this->line("\n✅ Wallet flow report completed.");
    }
}