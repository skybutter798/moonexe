<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Payout;
use App\Models\User;
use App\Models\MatchingRecord;
use Carbon\Carbon;

class ReferralIndexBuilder
{
    /**
     * Build referral snapshot for a user.
     * $filters = [
     *   'fromDate' => 'YYYY-MM-DD'|null,
     *   'toDate'   => 'YYYY-MM-DD'|null,
     *   'apply'    => bool
     * ]
     */
    public function build(User $user, ?array $filters = null): array
    {
        $userId = (int)$user->id;

        // Resolve filters
        $apply = (bool)($filters['apply'] ?? false);
        $fromDate = $filters['fromDate'] ?? null;
        $toDate   = $filters['toDate']   ?? null;
        $dateRangeLabel = $apply
            ? sprintf('%s to %s', $fromDate ?? '—', $toDate ?? '—')
            : 'All';

        // Use existing batch calculator from your app
        $calculator = new \App\Services\BatchUserRangeCalculator();
        $userRange  = $calculator->calculateForTree($user);

        $groupTradingMargin = (float)$userRange['total'];
        $directPercentage   = (float)$userRange['direct_percentage'];
        $matchingPercentage = (float)$userRange['matching_percentage'];

        // All downlines and “community” size
        $allDownlineIds   = $calculator->getDownlineIds($userId);
        $totalCommunity   = count($allDownlineIds);

        // My trading ROI (earning wallet, payouts)
        $myEarningQuery = Payout::where('user_id', $userId)
            ->where('status', 1)
            ->where('type', 'payout')
            ->where('wallet', 'earning')
            ->when($apply, function ($q) use ($fromDate, $toDate) {
                if ($fromDate) $q->whereDate('created_at', '>=', $fromDate);
                if ($toDate)   $q->whereDate('created_at', '<=', $toDate);
            });

        $myTotalEarning = (float)$myEarningQuery->sum('actual');
        $totalOrders    = (int)(clone $myEarningQuery)->count();

        // My direct + matching (affiliates)
        $myTotalDirect = (float) Payout::where('user_id', $userId)
            ->where('type', 'direct')
            ->where('wallet', 'affiliates')
            ->where('status', 1)
            ->when($apply, function ($q) use ($fromDate, $toDate) {
                if ($fromDate) $q->whereDate('created_at', '>=', $fromDate);
                if ($toDate)   $q->whereDate('created_at', '<=', $toDate);
            })->sum('actual');

        $myTotalMatching = (float) Payout::where('user_id', $userId)
            ->where('type', 'payout')
            ->where('wallet', 'affiliates')
            ->where('status', 1)
            ->when($apply, function ($q) use ($fromDate, $toDate) {
                if ($fromDate) $q->whereDate('created_at', '>=', $fromDate);
                if ($toDate)   $q->whereDate('created_at', '<=', $toDate);
            })->sum('actual');

        // Referral breakdown (from MatchingRecord)
        $referralQuery = MatchingRecord::select(
                'referral_group',
                \DB::raw('SUM(total_ref_contribute) as total'),
                \DB::raw('SUM(total_deposit) as count')
            )
            ->where('user_id', $userId);

        if ($apply) {
            if ($fromDate) $referralQuery->whereDate('record_date', '>=', $fromDate);
            if ($toDate)   $referralQuery->whereDate('record_date', '<=', $toDate);
        }

        $referralBreakdown = $referralQuery->groupBy('referral_group')
            ->get()
            ->map(function ($rec) {
                $u = User::find($rec->referral_group);
                return [
                    'referral_group' => (int)$rec->referral_group,
                    'referral_name'  => $u ? (string)$u->name : 'Unknown',
                    'total'          => (float)$rec->total,
                    'count'          => (float)$rec->count, // raw count (table shows int)
                ];
            })->values()->all();

        // Matching breakdown (from MatchingRecord)
        $matchingQuery = MatchingRecord::select(
                'referral_group',
                \DB::raw('SUM(total_contribute) as total'),
                \DB::raw('SUM(total_trade) as count')
            )
            ->where('user_id', $userId);

        if ($apply) {
            if ($fromDate) $matchingQuery->whereDate('record_date', '>=', $fromDate);
            if ($toDate)   $matchingQuery->whereDate('record_date', '<=', $toDate);
        }

        $matchingBreakdown = $matchingQuery->groupBy('referral_group')
            ->get()
            ->map(function ($rec) {
                $u = User::find($rec->referral_group);
                return [
                    'referral_group' => (int)$rec->referral_group,
                    'referral_name'  => $u ? (string)$u->name : 'Unknown',
                    'total'          => (float)$rec->total,
                    'count'          => (float)$rec->count,
                ];
            })->values()->all();

        // First-level downlines + computed metrics
        $firstLevelReferrals = User::where('referral', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (User $r) use ($calculator, $apply, $fromDate, $toDate) {
                $descendantIds = $calculator->getDownlineIds($r->id);
                $userIds = array_merge($descendantIds, [$r->id]);

                $downlineCount = count($descendantIds) + 1;

                $totalDirect = (float) Payout::whereIn('user_id', $userIds)
                    ->where('type', 'direct')
                    ->where('wallet', 'affiliates')
                    ->when($apply, function ($q) use ($fromDate, $toDate) {
                        if ($fromDate) $q->whereDate('created_at', '>=', $fromDate);
                        if ($toDate)   $q->whereDate('created_at', '<=', $toDate);
                    })->sum('actual');

                $totalEarning = (float) Payout::whereIn('user_id', $userIds)
                    ->where('type', 'payout')
                    ->where('wallet', 'earning')
                    ->when($apply, function ($q) use ($fromDate, $toDate) {
                        if ($fromDate) $q->whereDate('created_at', '>=', $fromDate);
                        if ($toDate)   $q->whereDate('created_at', '<=', $toDate);
                    })->sum('actual');

                $groupRoi = (float) Payout::whereIn('user_id', $userIds)
                    ->where('type', 'payout')
                    ->where('wallet', 'earning')
                    ->where('status', 1)
                    ->when($apply, function ($q) use ($fromDate, $toDate) {
                        if ($fromDate) $q->whereDate('created_at', '>=', $fromDate);
                        if ($toDate)   $q->whereDate('created_at', '<=', $toDate);
                    })->sum('actual');

                $groupMatching = (float) Payout::whereIn('user_id', $userIds)
                    ->where('type', 'payout')
                    ->where('wallet', 'affiliates')
                    ->where('status', 1)
                    ->when($apply, function ($q) use ($fromDate, $toDate) {
                        if ($fromDate) $q->whereDate('created_at', '>=', $fromDate);
                        if ($toDate)   $q->whereDate('created_at', '<=', $toDate);
                    })->sum('actual');

                $calc = $calculator->computeUserGroupTotal($r->id);

                return [
                    'id'                  => (int)$r->id,
                    'name'                => (string)$r->name,
                    'referral_code'       => (string)$r->referral_code,
                    'created_at'          => (string)$r->created_at, // will rehydrate
                    'status'              => (int)$r->status,
                    'downline_count'      => (int)$downlineCount,
                    'trading_margin'      => (float)$calc['total'],
                    'direct_percentage'   => (float)$calc['direct_percentage'],
                    'matching_percentage' => (float)$calc['matching_percentage'],
                    'total_direct'        => (float)$totalDirect,
                    'total_earning'       => (float)$totalEarning,
                    'group_matching'      => (float)$groupMatching,
                    'group_roi'           => (float)$groupRoi,
                ];
            })
            ->values()
            ->all();

        return [
            'title'               => 'My Referral Program',
            'groupTradingMargin'  => $groupTradingMargin,
            'directPercentage'    => $directPercentage,
            'matchingPercentage'  => $matchingPercentage,
            'totalCommunity'      => $totalCommunity,
            'myTotalEarning'      => $myTotalEarning,
            'myTotalDirect'       => $myTotalDirect,
            'myTotalMatching'     => $myTotalMatching,
            'totalOrders'         => $totalOrders,
            'dateRange'           => $dateRangeLabel,
            'referralBreakdown'   => $referralBreakdown,
            'matchingBreakdown'   => $matchingBreakdown,
            'firstLevelReferrals' => $firstLevelReferrals,
            'computed_at'         => now()->toIso8601String(),
        ];
    }
}