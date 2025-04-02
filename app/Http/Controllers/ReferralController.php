<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transfer;
use App\Models\Order;
use App\Models\Payout;
use App\Services\UserRangeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReferralController extends Controller
{
    // Recursive function updated with optional date filters
    private function getAllDownlineIds($userId, $fromDate = null, $toDate = null)
    {
        $query = User::where('referral', $userId);
        
        // Apply date filters if provided
        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }
        
        $downlines = $query->get();
        $ids = [];
        
        foreach ($downlines as $downline) {
            $ids[] = $downline->id;
            // Recursively merge the descendant IDs with the same date filters
            $ids = array_merge($ids, $this->getAllDownlineIds($downline->id, $fromDate, $toDate));
        }
        
        return $ids;
    }
    
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
        
        if ($request->has('filter') && $request->input('filter') && $request->input('filter') !== 'all') {
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
                $fromDate = $request->input('from');
            }
            if ($request->filled('to')) {
                $toDate = $request->input('to');
            }
        }
        
        // ----------------------------------
        // Updated Calculation Using Service
        // ----------------------------------
        $calculator = new UserRangeCalculator();
        $userRange  = $calculator->calculate(auth()->user());
        $groupTradingMargin = $userRange['total'];
        $directPercentage   = $userRange['direct_percentage'];
        $matchingPercentage = $userRange['matching_percentage'];

        // ---------------------------
        // Cards (Community Summary) - date-filtered
        // ---------------------------
        $allDownlineIds = $this->getAllDownlineIds(auth()->id(), $fromDate, $toDate);
        $communityUserIds = array_merge($allDownlineIds, [auth()->id()]);
        
        $myEarningQuery = Payout::where('user_id', auth()->id())
                        ->where('status', '1')
                        ->where('type', 'payout')
                        ->where('wallet', 'earning')
                        ->when($fromDate, function ($query) use ($fromDate) {
                            return $query->whereDate('created_at', '>=', $fromDate);
                        })
                        ->when($toDate, function ($query) use ($toDate) {
                            return $query->whereDate('created_at', '<=', $toDate);
                        });
                        
        if ($fromDate) {
            $myEarningQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $myEarningQuery->whereDate('created_at', '<=', $toDate);
        }
        
        $myTotalEarning = $myEarningQuery->sum('actual');
        $totalOrders = (clone $myEarningQuery)->count();
        $dateRange = ($fromDate || $toDate) ? "$fromDate to $toDate" : "All";
        
        $totalCommunity = count($allDownlineIds);
        
        $myTotalDirect = Payout::where('user_id', auth()->id())
                        ->where('type', 'direct')
                        ->where('wallet', 'affiliates')
                        ->where('status', 1)
                        ->when($fromDate, function($query) use ($fromDate) {
                            return $query->whereDate('created_at', '>=', $fromDate);
                        })
                        ->when($toDate, function($query) use ($toDate) {
                            return $query->whereDate('created_at', '<=', $toDate);
                        })
                        ->sum('actual');
                        
        $myTotalMatching = Payout::where('user_id', auth()->id())
                        ->where('type', 'payout')
                        ->where('wallet', 'affiliates')
                        ->where('status', 1)
                        ->when($fromDate, function($query) use ($fromDate) {
                            return $query->whereDate('created_at', '>=', $fromDate);
                        })
                        ->when($toDate, function($query) use ($toDate) {
                            return $query->whereDate('created_at', '<=', $toDate);
                        })
                        ->sum('actual');
        
        $referralBreakdown = collect();
        $directPayouts = Payout::with('transfer')
                        ->where('user_id', auth()->user()->id)
                        ->where('type', 'direct')
                        ->where('wallet', 'affiliates')
                        ->when($fromDate, function ($query) use ($fromDate) {
                            return $query->whereDate('created_at', '>=', $fromDate);
                        })
                        ->when($toDate, function ($query) use ($toDate) {
                            return $query->whereDate('created_at', '<=', $toDate);
                        })
                        ->get();

                        
        $matchingBreakdown = collect();
        $matchingPayouts = Payout::with('order')
                        ->where('user_id', auth()->user()->id)
                        ->where('type', 'payout')
                        ->where('wallet', 'affiliates')
                        ->when($fromDate, function ($query) use ($fromDate) {
                            return $query->whereDate('created_at', '>=', $fromDate);
                        })
                        ->when($toDate, function ($query) use ($toDate) {
                            return $query->whereDate('created_at', '<=', $toDate);
                        })
                        ->get();


        // ---------------------------
        // Table (First Level Downline Summary)
        // ---------------------------
        // Show all direct referrals regardless of date filter
        $firstLevelReferrals = User::where('referral', auth()->id())
                                   ->orderBy('created_at', 'desc')
                                   ->get();
                                   
        $firstLevelIds = $firstLevelReferrals->pluck('id')->toArray();
        
        $groupedReferral = [];
        $referralTreeCount = [];
        foreach ($directPayouts as $payout) {
            $transfer = $payout->transfer;
            if ($transfer) {
                $topReferralUser = $this->findTopReferral($transfer->user_id, auth()->id());
                if ($topReferralUser) {
                    $referralName = $topReferralUser->name;
        
                    // If we haven’t already calculated the tree count, do so now.
                    if (!isset($referralTreeCount[$referralName])) {
                        // Get all downline IDs for the top referral.
                        $treeIds = $this->getAllDownlineIds($topReferralUser->id);
                        // Count the referral itself plus its downlines.
                        $referralTreeCount[$referralName] = count($treeIds) + 1;
                    }
        
                    if (isset($groupedReferral[$referralName])) {
                        $groupedReferral[$referralName]['total'] += $payout->actual;
                        // Do not increment count here—use the unique tree count.
                    } else {
                        $groupedReferral[$referralName] = [
                            'referral_name' => $referralName,
                            'total'         => $payout->actual,
                            'count'         => $referralTreeCount[$referralName],
                        ];
                    }
                }
            }
        }
        $referralBreakdown = collect(array_values($groupedReferral));
        
        $groupedMatching = [];
        foreach ($matchingPayouts as $payout) {
            // Look up the corresponding order using order_id.
            $order = $payout->order;
            
            if ($order) {
                // Trace up the referral chain to find the first-level referral under the main user.
                $topReferralUser = $this->findTopReferral($order->user_id, auth()->id());
                
                if ($topReferralUser) {
                    $referralName = $topReferralUser->name;
                    
                    // Initialize the group if it doesn't exist.
                    if (!isset($groupedMatching[$referralName])) {
                        $groupedMatching[$referralName] = [
                            'total'  => $payout->actual,
                            'orders' => [$order->id] // Track unique orders.
                        ];
                    } else {
                        // Add the payout amount.
                        $groupedMatching[$referralName]['total'] += $payout->actual;
                        // Only add if this order hasn't been recorded yet.
                        if (!in_array($order->id, $groupedMatching[$referralName]['orders'])) {
                            $groupedMatching[$referralName]['orders'][] = $order->id;
                        }
                    }
                }
            }
        }
        
        // Convert the grouped results into a collection for the view.
        $matchingBreakdown = collect();
        foreach ($groupedMatching as $referralName => $data) {
            $matchingBreakdown->push([
                'referral_name' => $referralName,
                'total'         => $data['total'],
                'count'         => count($data['orders']), // Count of unique orders.
            ]);
        }
        
        foreach ($firstLevelReferrals as $referral) {
            // Calculate metrics for each referral using date filters
            $descendantIds = $this->getAllDownlineIds($referral->id, $fromDate, $toDate);
            $userIds = array_merge($descendantIds, [$referral->id]);
            
            // Community count: count of all downline referrals (plus self if needed)
            $referral->downline_count = count($descendantIds) + 1;
            
            // Calculate Total Direct from all downline using payouts->actual (filtered by date)
            $referral->total_direct = Payout::whereIn('user_id', $userIds)
                ->where('type', 'direct')
                ->where('wallet', 'affiliates')
                ->when($fromDate, function($query) use ($fromDate) {
                    return $query->whereDate('created_at', '>=', $fromDate);
                })
                ->when($toDate, function($query) use ($toDate) {
                    return $query->whereDate('created_at', '<=', $toDate);
                })
                ->sum('actual');
            
            // Calculate Total Earning (for ROI calculation) from payouts with wallet "earning"
            $referral->total_earning = Payout::whereIn('user_id', $userIds)
                ->where('type', 'payout')
                ->where('wallet', 'earning')
                ->when($fromDate, function($query) use ($fromDate) {
                    return $query->whereDate('created_at', '>=', $fromDate);
                })
                ->when($toDate, function($query) use ($toDate) {
                    return $query->whereDate('created_at', '<=', $toDate);
                })
                ->sum('actual');
            
            // Calculate Total Payout (for Group Direct) from payouts with wallet "affiliates"
            $referral->total_payout = Payout::whereIn('user_id', $userIds)
                ->where('type', 'payout')
                ->where('wallet', 'affiliates')
                ->when($fromDate, function($query) use ($fromDate) {
                    return $query->whereDate('created_at', '>=', $fromDate);
                })
                ->when($toDate, function($query) use ($toDate) {
                    return $query->whereDate('created_at', '<=', $toDate);
                })
                ->sum('actual');
                
            // Calculate Group ROI: sum of downline payouts where wallet = "earning" and status = 1
            $referral->group_roi = Payout::whereIn('user_id', $userIds)
                ->where('type', 'payout')
                ->where('wallet', 'earning')
                ->where('status', 1)
                ->when($fromDate, function($query) use ($fromDate) {
                    return $query->whereDate('created_at', '>=', $fromDate);
                })
                ->when($toDate, function($query) use ($toDate) {
                    return $query->whereDate('created_at', '<=', $toDate);
                })
                ->sum('actual');
            
            // Calculate Group Matching: sum of downline payouts where wallet = "affiliates" and status = 1
            $referral->group_matching = Payout::whereIn('user_id', $userIds)
                ->where('type', 'payout')
                ->where('wallet', 'affiliates')
                ->where('status', 1)
                ->when($fromDate, function($query) use ($fromDate) {
                    return $query->whereDate('created_at', '>=', $fromDate);
                })
                ->when($toDate, function($query) use ($toDate) {
                    return $query->whereDate('created_at', '<=', $toDate);
                })
                ->sum('actual');

            // Calculate Trading Margin and Percentages using the UserRangeCalculator service
            $referralCalculation = $calculator->calculate($referral);
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
            'totalOrders'    => $totalOrders,
            'dateRange'      => $dateRange,
            'referralBreakdown' => $referralBreakdown,
            'matchingBreakdown' => $matchingBreakdown,
        ]);
    }
    
    
}
