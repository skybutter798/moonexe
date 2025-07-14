<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transfer;
use App\Models\Order;
use App\Models\Payout;
use App\Models\MatchingRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReferralController extends Controller
{

    private function findTopReferral($userId, $mainUserId)
    {
        $user = User::find($userId);
        if (!$user) {
            return null;
        }
        // If the user's referral is the main user, then this user is a direct referral.
        if ($user->referral == $mainUserId) {
            return $user;
        }
        // Otherwise, continue tracing up the referral chain.
        while ($user && $user->referral != $mainUserId) {
            $user = User::find($user->referral);
        }
        return $user; // Will be null if not found.
    }

    public function index(Request $request)
    {
        // Determine filter dates based on request parameters
        $fromDate = null;
        $toDate = null;
        $applyDateFilter = false;
        
        if ($request->has('filter') && $request->input('filter') && $request->input('filter') !== 'all') {
            $applyDateFilter = true;
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
            // Use custom date range if provided
            if ($request->filled('from')) {
                $applyDateFilter = true;
                $fromDate = $request->input('from');
            }
            if ($request->filled('to')) {
                $applyDateFilter = true;
                $toDate = $request->input('to');
            }
        }
        
        // ----------------------------------
        // Updated Calculation Using Service
        // ----------------------------------
        $calculator = new \App\Services\BatchUserRangeCalculator();
        $userRange = $calculator->calculateForTree(auth()->user());

        $groupTradingMargin = $userRange['total'];
        $directPercentage   = $userRange['direct_percentage'];
        $matchingPercentage = $userRange['matching_percentage'];

        // ---------------------------
        // Cards (Community Summary) - date-filtered
        // ---------------------------
        $allDownlineIds = $calculator->getDownlineIds(auth()->id());
        $communityUserIds = array_merge($allDownlineIds, [auth()->id()]);
        
        $myEarningQuery = Payout::where('user_id', auth()->id())
                        ->where('status', '1')
                        ->where('type', 'payout')
                        ->where('wallet', 'earning')
                        ->when($applyDateFilter, function ($query) use ($fromDate, $toDate) {
                            if ($fromDate) {
                                $query->whereDate('created_at', '>=', $fromDate);
                            }
                            if ($toDate) {
                                $query->whereDate('created_at', '<=', $toDate);
                            }
                            return $query;
                        });
        
        $myTotalEarning = $myEarningQuery->sum('actual');
        $totalOrders = (clone $myEarningQuery)->count();
        $dateRange = $applyDateFilter ? "$fromDate to $toDate" : "All";
        
        $totalCommunity = count($allDownlineIds);
        
        $myTotalDirect = Payout::where('user_id', auth()->id())
                        ->where('type', 'direct')
                        ->where('wallet', 'affiliates')
                        ->where('status', 1)
                        ->when($applyDateFilter, function($query) use ($fromDate, $toDate) {
                            if ($fromDate) {
                                $query->whereDate('created_at', '>=', $fromDate);
                            }
                            if ($toDate) {
                                $query->whereDate('created_at', '<=', $toDate);
                            }
                            return $query;
                        })
                        ->sum('actual');
                        
        $myTotalMatching = Payout::where('user_id', auth()->id())
                        ->where('type', 'payout')
                        ->where('wallet', 'affiliates')
                        ->where('status', 1)
                        ->when($applyDateFilter, function($query) use ($fromDate, $toDate) {
                            if ($fromDate) {
                                $query->whereDate('created_at', '>=', $fromDate);
                            }
                            if ($toDate) {
                                $query->whereDate('created_at', '<=', $toDate);
                            }
                            return $query;
                        })
                        ->sum('actual');
        
        // ---------------------------
        // Referral Breakdown using MatchingRecord table
        // (Referral = total_ref_contribute and total_deposit)
        // ---------------------------
        $referralQuery = MatchingRecord::select(
            'referral_group',
            \DB::raw('SUM(total_ref_contribute) as total'),
            \DB::raw('SUM(total_deposit) as count')
        )
        ->where('user_id', auth()->id());
        
        if ($applyDateFilter) {
            if ($fromDate) {
                $referralQuery->whereDate('record_date', '>=', $fromDate);
            }
            if ($toDate) {
                $referralQuery->whereDate('record_date', '<=', $toDate);
            }
        }
        
        $referralBreakdown = $referralQuery->groupBy('referral_group')
            ->get()
            ->map(function($record) {
                $referralUser = User::find($record->referral_group);
                return [
                    'referral_name' => $referralUser ? $referralUser->name : 'Unknown',
                    'total'         => $record->total,
                    'count'         => $record->count,
                ];
            });
        
        // ---------------------------
        // Matching Breakdown using MatchingRecord table
        // (Matching = total_contribute and total_trade)
        // ---------------------------
        $matchingQuery = MatchingRecord::select(
            'referral_group',
            \DB::raw('SUM(total_contribute) as total'),
            \DB::raw('SUM(total_trade) as count')
        )
        ->where('user_id', auth()->id());
        
        if ($applyDateFilter) {
            if ($fromDate) {
                $matchingQuery->whereDate('record_date', '>=', $fromDate);
            }
            if ($toDate) {
                $matchingQuery->whereDate('record_date', '<=', $toDate);
            }
        }
        
        $matchingBreakdown = $matchingQuery->groupBy('referral_group')
            ->get()
            ->map(function($record) {
                $referralUser = User::find($record->referral_group);
                return [
                    'referral_name' => $referralUser ? $referralUser->name : 'Unknown',
                    'total'         => $record->total,
                    'count'         => $record->count,
                ];
            });
        
        // ---------------------------
        // Table (First Level Downline Summary)
        // ---------------------------
        // Show all direct referrals regardless of date filter
        $firstLevelReferrals = User::where('referral', auth()->id())
                                   ->orderBy('created_at', 'desc')
                                   ->get();
                                   
        foreach ($firstLevelReferrals as $referral) {
            // Calculate metrics for each referral using date filters
            $descendantIds = $calculator->getDownlineIds($referral->id);
            $userIds = array_merge($descendantIds, [$referral->id]);
            
            // Community count: count of all downline referrals (plus self if needed)
            $referral->downline_count = count($descendantIds) + 1;
            
            // Calculate Total Direct from all downline using payouts->actual (filtered by date)
            $referral->total_direct = Payout::whereIn('user_id', $userIds)
                ->where('type', 'direct')
                ->where('wallet', 'affiliates')
                ->when($applyDateFilter, function($query) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $query->whereDate('created_at', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $query->whereDate('created_at', '<=', $toDate);
                    }
                    return $query;
                })
                ->sum('actual');
            
            // Calculate Total Earning (for ROI calculation) from payouts with wallet "earning"
            $referral->total_earning = Payout::whereIn('user_id', $userIds)
                ->where('type', 'payout')
                ->where('wallet', 'earning')
                ->when($applyDateFilter, function($query) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $query->whereDate('created_at', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $query->whereDate('created_at', '<=', $toDate);
                    }
                    return $query;
                })
                ->sum('actual');
            
            // Calculate Total Payout (for Group Direct) from payouts with wallet "affiliates"
            $referral->total_payout = Payout::whereIn('user_id', $userIds)
                ->where('type', 'payout')
                ->where('wallet', 'affiliates')
                ->when($applyDateFilter, function($query) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $query->whereDate('created_at', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $query->whereDate('created_at', '<=', $toDate);
                    }
                    return $query;
                })
                ->sum('actual');
                
            // Calculate Group ROI: sum of downline payouts where wallet = "earning" and status = 1
            $referral->group_roi = Payout::whereIn('user_id', $userIds)
                ->where('type', 'payout')
                ->where('wallet', 'earning')
                ->where('status', 1)
                ->when($applyDateFilter, function($query) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $query->whereDate('created_at', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $query->whereDate('created_at', '<=', $toDate);
                    }
                    return $query;
                })
                ->sum('actual');
            
            // Calculate Group Matching: sum of downline payouts where wallet = "affiliates" and status = 1
            $referral->group_matching = Payout::whereIn('user_id', $userIds)
                ->where('type', 'payout')
                ->where('wallet', 'affiliates')
                ->where('status', 1)
                ->when($applyDateFilter, function($query) use ($fromDate, $toDate) {
                    if ($fromDate) {
                        $query->whereDate('created_at', '>=', $fromDate);
                    }
                    if ($toDate) {
                        $query->whereDate('created_at', '<=', $toDate);
                    }
                    return $query;
                })
                ->sum('actual');

            // Calculate Trading Margin and Percentages using the UserRangeCalculator service
            $referralCalculation = $calculator->computeUserGroupTotal($referral->id);

            $referral->trading_margin = $referralCalculation['total'];
            $referral->direct_percentage = $referralCalculation['direct_percentage'];
            $referral->matching_percentage = $referralCalculation['matching_percentage'];
        }

        $tableTotalTradeSum = $firstLevelReferrals->sum('total_trade');
        $tableTotalTopupSum = $firstLevelReferrals->sum('total_topup');
        
        return view('user.referral_v2', [
            'title'               => 'My Referral Program',
            'groupTradingMargin'  => $groupTradingMargin,
            'directPercentage'    => $directPercentage,
            'matchingPercentage'  => $matchingPercentage,
            'totalCommunity'      => $totalCommunity,
            'myTotalEarning'      => $myTotalEarning,
            'firstLevelReferrals' => $firstLevelReferrals,
            'tableTotalTradeSum'  => $tableTotalTradeSum,
            'tableTotalTopupSum'  => $tableTotalTopupSum,
            'myTotalDirect'       => $myTotalDirect,
            'myTotalMatching'     => $myTotalMatching,
            'totalOrders'         => $totalOrders,
            'dateRange'           => $dateRange,
            'referralBreakdown'   => $referralBreakdown,
            'matchingBreakdown'   => $matchingBreakdown,
        ]);
    }
}
