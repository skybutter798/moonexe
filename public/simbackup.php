<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- Determine Mode ---
    $useAuto = isset($_POST['total_members']) && trim($_POST['total_members']) !== ''
             && isset($_POST['total_amount']) && trim($_POST['total_amount']) !== ''
             && isset($_POST['auto_candidates']) && trim($_POST['auto_candidates']) !== '';

    // New input: branches (default is 1)
    $branches = (isset($_POST['branches']) && trim($_POST['branches']) !== '')
        ? intval($_POST['branches'])
        : 1;

    // Convert candidate list to array and sort descending.
    $allowedVals = array_map('floatval', explode(',', $_POST['auto_candidates']));
    rsort($allowedVals);

    // --- Helper Functions ---
    function random_split($total, $parts) {
        if ($parts <= 1) {
            return [$total];
        }
        if ($total < $parts) {
            $split = array_fill(0, $parts, 1);
            $split[0] = $total - ($parts - 1);
            return $split;
        }
        $cuts = [];
        while(count($cuts) < ($parts - 1)) {
            $rand = rand(1, $total - 1);
            if (!in_array($rand, $cuts)) {
                $cuts[] = $rand;
            }
        }
        sort($cuts);
        $split = [];
        $prev = 0;
        foreach ($cuts as $cut) {
            $split[] = $cut - $prev;
            $prev = $cut;
        }
        $split[] = $total - $prev;
        return $split;
    }

    function calcROI($own, $roi_percentage) {
        // ROI = Own Trading Margin x ROI Percentage / 2
        return ($own * $roi_percentage) / 2;
    }
    
    function getDSPercent($own, $ranges) {
        foreach ($ranges as $range) {
            if (!is_null($range['high'])) {
                if ($own >= $range['low'] && $own <= $range['high']) {
                    return $range['percent'];
                }
            } else {
                if ($own >= $range['low']) {
                    return $range['percent'];
                }
            }
        }
        return 0;
    }
    
    function getPSPercent($total, $ranges) {
        foreach ($ranges as $range) {
            if (!is_null($range['high'])) {
                if ($total >= $range['low'] && $total <= $range['high']) {
                    return $range['percent'];
                }
            } else {
                if ($total >= $range['low']) {
                    return $range['percent'];
                }
            }
        }
        return 0;
    }
    
    // --- Start of Output ---
    echo '<!DOCTYPE html>
<html>
<head>
  <title>MoonExe ROI Simulation - Results</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f0f0;
      margin: 0;
      padding: 0;
    }
    .result-container {
      margin: 20px auto;
      padding: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
      max-width: 900px;
      background: #fff;
    }
    .result-title {
      font-size: 1.5em;
      margin-bottom: 10px;
      color: #333;
      text-align: center;
    }
    /* Flex container for side-by-side range tables */
    .table-container {
      display: flex;
      flex-wrap: nowrap;
      gap: 10px;
      margin-top: 20px;
      width: 100%;
    }
    .table-column {
      width: 48%;
    }
    .result-table {
      border-collapse: collapse;
      width: 100%;
      margin-bottom: 15px;
    }
    .result-table th, .result-table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: center;
    }
    .result-table th {
      background-color: #eaeaea;
    }
    /* Highlight style for User 0 Income card */
    .highlight {
      background-color: white;
      border: 2px solid black;
    }
  </style>
</head>
<body>';

    // --- Display Input Values in Redesigned Sections ---
    if ($useAuto) {
        echo '<div class="result-container">
                <div class="result-title">Auto Distribution Inputs</div>
                <table class="result-table">
                  <tr>
                    <th>Total Members</th>
                    <th>Total Amount</th>
                    <th>Candidate Values</th>
                    <th>Branches</th>
                    <th>ROI Percentage</th>
                  </tr>
                  <tr>
                    <td>' . htmlspecialchars($_POST['total_members']) . '</td>
                    <td>' . htmlspecialchars($_POST['total_amount']) . '</td>
                    <td>' . htmlspecialchars($_POST['auto_candidates']) . '</td>
                    <td>' . htmlspecialchars($branches) . '</td>
                    <td>' . htmlspecialchars($_POST['roi_percentage']) . '</td>
                  </tr>
                </table>
              </div>';
    } else {
        echo '<div class="result-container">
                <div class="result-title">Manual Downline Inputs</div>
                <table class="result-table">
                  <tr><th>Member</th><th>Trading Margin</th></tr>';
        foreach ($_POST['downline'] as $index => $margin) {
            echo '<tr><td>Member ' . ($index + 1) . '</td><td>' . htmlspecialchars($margin) . '</td></tr>';
        }
        echo '</table>
              </div>';
    }

    // --- Side-by-side range tables in one row ---
    echo '<div class="table-container">';
    echo '<div class="table-column result-container">
            <div class="result-title">Direct Sponsor Range Table Inputs</div>
            <table class="result-table">
              <tr>
                <th>Range Low</th>
                <th>Range High</th>
                <th>Percentage</th>
              </tr>';
    for ($i = 0; $i < count($_POST['ds_range_low']); $i++) {
        $low = htmlspecialchars($_POST['ds_range_low'][$i]);
        $high = htmlspecialchars($_POST['ds_range_high'][$i]);
        $percent = htmlspecialchars($_POST['ds_range_percent'][$i]);
        echo "<tr>
                <td>$low</td>
                <td>" . ($high !== '' ? $high : "and above") . "</td>
                <td>$percent</td>
              </tr>";
    }
    echo '  </table>
          </div>';

    echo '<div class="table-column result-container">
            <div class="result-title">Profit Sharing Range Table Inputs</div>
            <table class="result-table">
              <tr>
                <th>Range</th>
                <th>Range Low</th>
                <th>Range High</th>
                <th>Percentage</th>
              </tr>';
    for ($i = 1; $i <= 7; $i++) {
        $low = htmlspecialchars($_POST["ps_range{$i}_low"]);
        $high = htmlspecialchars($_POST["ps_range{$i}_high"]);
        $percent = htmlspecialchars($_POST["ps_range{$i}_percent"]);
        echo "<tr>
                <td>Range $i</td>
                <td>$low</td>
                <td>$high</td>
                <td>$percent</td>
              </tr>";
    }
    echo '  </table>
          </div>';
    echo '</div>'; // end table-container

    // --- Processing Distribution (Auto or Manual) ---
    if ($useAuto && $branches > 1) {
        // --- Auto Distribution with Multiple Branches ---
        $totalMembers = intval($_POST['total_members']);
        $totalAmount = floatval($_POST['total_amount']);

        // Randomly split totalMembers into $branches parts.
        $membersDistribution = random_split($totalMembers, $branches);

        // Arrays to store results of each branch and branch DS/PS details.
        $branchResults = [];
        $branchDSValues = [];
        $branchPSValues = [];
        $branchCounter = 1;
        foreach ($membersDistribution as $branchMembers) {
            // Calculate branch's total amount proportionally.
            $branchAmount = ($branchMembers / $totalMembers) * $totalAmount;
            
            // --- Auto Distribution for this branch with forced top candidate ---
            $downlines = [];
            $topCandidate = $allowedVals[0];
            // Calculate required count for top candidate (at least 40% of branch members).
            $requiredTopInBranch = ceil(0.4 * $branchMembers);
            $countTopInBranch = 0;
            
            // First member gets top candidate.
            $downlines[0] = $topCandidate;
            $countTopInBranch++;
            
            // Remaining amount available for members 2..branchMembers.
            $remainingForOthers = $branchAmount - $topCandidate;
            $numOthers = $branchMembers - 1;
            
            for ($i = 1; $i < $branchMembers; $i++) {
                if ($i < $branchMembers - 1) {
                    // If we haven't reached 40% top candidate assignment, force top candidate.
                    if ($countTopInBranch < $requiredTopInBranch) {
                        $choice = $topCandidate;
                        $countTopInBranch++;
                    } else {
                        $minPossible = min($allowedVals);
                        // Reserve at least $minPossible for each remaining member.
                        $maxAllowedForThis = $remainingForOthers - (($numOthers - ($i - 1)) * $minPossible);
                        // Exclude top candidate if forced quota is already met
                        $eligible = array_filter($allowedVals, function($val) use ($maxAllowedForThis, $topCandidate, $countTopInBranch, $requiredTopInBranch) {
                            if ($countTopInBranch >= $requiredTopInBranch && $val == $topCandidate) {
                                return false;
                            }
                            return $val <= $maxAllowedForThis;
                        });
                        if (empty($eligible)) {
                            $choice = $minPossible;
                        } else {
                            $eligible = array_values($eligible);
                            $choice = $eligible[array_rand($eligible)];
                        }
                    }
                    $downlines[$i] = $choice;
                    $remainingForOthers -= $choice;
                } else {
                    // For the last member, force the minimum candidate.
                    $choice = min($allowedVals);
                    $downlines[$i] = $choice;
                    $remainingForOthers -= $choice;
                }
            }
            // Add any leftover to the first member.
            $downlines[0] += $remainingForOthers;

            // --- Calculate Cumulative Trading Margins per Member ---
            $numDownlines = count($downlines);
            $cumulativeTotals = array_fill(0, $numDownlines, 0);
            $sum = 0;
            for ($i = $numDownlines - 1; $i >= 0; $i--) {
                $sum += $downlines[$i];
                $cumulativeTotals[$i] = $sum;
            }

            // --- Prepare Direct Sponsor Range Table Inputs ---
            $ds_range_lows = array_map('floatval', $_POST['ds_range_low']);
            $ds_range_highs = $_POST['ds_range_high'];
            $ds_range_percents = array_map('floatval', $_POST['ds_range_percent']);
            $ds_ranges_branch = [];
            $countRanges = count($ds_range_lows);
            for ($i = 0; $i < $countRanges; $i++) {
                $high = trim($ds_range_highs[$i]);
                $ds_ranges_branch[] = [
                    'low'     => $ds_range_lows[$i],
                    'high'    => ($high !== '' ? floatval($high) : null),
                    'percent' => $ds_range_percents[$i]
                ];
            }

            // --- Prepare Profit Sharing Range Table Inputs ---
            $ps_ranges_branch = [
                ['low' => floatval($_POST['ps_range1_low']), 'high' => floatval($_POST['ps_range1_high']), 'percent' => floatval($_POST['ps_range1_percent'])],
                ['low' => floatval($_POST['ps_range2_low']), 'high' => floatval($_POST['ps_range2_high']), 'percent' => floatval($_POST['ps_range2_percent'])],
                ['low' => floatval($_POST['ps_range3_low']), 'high' => floatval($_POST['ps_range3_high']), 'percent' => floatval($_POST['ps_range3_percent'])],
                ['low' => floatval($_POST['ps_range4_low']), 'high' => floatval($_POST['ps_range4_high']), 'percent' => floatval($_POST['ps_range4_percent'])],
                ['low' => floatval($_POST['ps_range5_low']), 'high' => floatval($_POST['ps_range5_high']), 'percent' => floatval($_POST['ps_range5_percent'])],
                ['low' => floatval($_POST['ps_range6_low']), 'high' => floatval($_POST['ps_range6_high']), 'percent' => floatval($_POST['ps_range6_percent'])],
                ['low' => floatval($_POST['ps_range7_low']), 'high' => null, 'percent' => floatval($_POST['ps_range7_percent'])]
            ];

            $roi_percentage = (isset($_POST['roi_percentage']) && trim($_POST['roi_percentage']) !== '')
                ? floatval($_POST['roi_percentage'])
                : 0.03;

            // --- Calculations ---
            $rois = [];
            foreach ($downlines as $margin) {
                $rois[] = calcROI($margin, $roi_percentage);
            }

            $dsPercents = [];
            foreach ($cumulativeTotals as $totalMargin) {
                $dsPercents[] = getDSPercent($totalMargin, $ds_ranges_branch);
            }
            
            $dsValues = array_fill(0, $numDownlines, 0);
            for ($j = $numDownlines - 1; $j >= 0; $j--) {
                if ($j == 0) continue;
                $donorMargin = $downlines[$j];
                $currentDS = 0;
                for ($i = $j - 1; $i >= 0; $i--) {
                    $gapCalc = $dsPercents[$i] - $currentDS;
                    if ($gapCalc > 0) {
                        $dsValues[$i] += $donorMargin * $gapCalc;
                        $currentDS = $dsPercents[$i];
                    }
                }
            }

            // New PS calculation per member (ignore the last member)
            $psValues = array_fill(0, $numDownlines, 0);
            for ($i = 0; $i < $numDownlines - 1; $i++) {
                $userPS = $psPercents[$i];
                $firstDownlineROI = $rois[$i+1];     // ROI of immediate downline
                $firstDownlinePS = $psPercents[$i+1];  // PS % of immediate downline
                $sumROI = 0;
                for ($j = $i+2; $j < $numDownlines; $j++) {
                    $sumROI += $rois[$j];
                }
                $psValues[$i] = ($userPS * $firstDownlineROI) + ($sumROI * ($userPS - $firstDownlinePS));
            }
            // Last member gets no PS value:
            $psValues[$numDownlines - 1] = 0;


            $psPercents = [];
            for ($i = 0; $i < $numDownlines; $i++) {
                $psPercents[$i] = getPSPercent($cumulativeTotals[$i], $ps_ranges_branch);
            }

            $psValues = array_fill(0, $numDownlines, 0);
            for ($i = 0; $i < $numDownlines - 1; $i++) {
                $userPS = $psPercents[$i];
                $firstDownlineROI = $rois[$i+1];     // Immediate downline's ROI
                $firstDownlinePS = $psPercents[$i+1];  // Immediate downline's PS %
                
                // Sum ROI for all downlines starting from the second downline (i+2 onward)
                $sumROI = 0;
                for ($j = $i+2; $j < $numDownlines; $j++) {
                    $sumROI += $rois[$j];
                }
                $psValues[$i] = ($userPS * $firstDownlineROI) + ($sumROI * ($userPS - $firstDownlinePS));
            }
            $psValues[$numDownlines - 1] = 0; // The last member gets no PS value.


            // Save results for this branch.
            $branchResults[] = [
                'branchCounter'    => $branchCounter,
                'branchMembers'    => $branchMembers,
                'branchAmount'     => $branchAmount, // original branch amount
                'downlines'        => $downlines,
                'cumulativeTotals' => $cumulativeTotals,
                'rois'             => $rois,
                'dsPercents'       => $dsPercents,
                'dsValues'         => $dsValues,
                'psPercents'       => $psPercents,
                'psValues'         => $psValues
            ];
            $branchCounter++;
        }

        // --- Calculate User 0 Income Across All Branches ---
        // Build DS and PS ranges from POST inputs.
        $ds_range_lows = array_map('floatval', $_POST['ds_range_low']);
        $ds_range_highs = $_POST['ds_range_high'];
        $ds_range_percents = array_map('floatval', $_POST['ds_range_percent']);
        $ds_ranges = [];
        $countRanges = count($ds_range_lows);
        for ($i = 0; $i < $countRanges; $i++) {
            $high = trim($ds_range_highs[$i]);
            $ds_ranges[] = [
                'low'     => $ds_range_lows[$i],
                'high'    => ($high !== '' ? floatval($high) : null),
                'percent' => $ds_range_percents[$i]
            ];
        }
        $ps_ranges = [
            ['low' => floatval($_POST['ps_range1_low']), 'high' => floatval($_POST['ps_range1_high']), 'percent' => floatval($_POST['ps_range1_percent'])],
            ['low' => floatval($_POST['ps_range2_low']), 'high' => floatval($_POST['ps_range2_high']), 'percent' => floatval($_POST['ps_range2_percent'])],
            ['low' => floatval($_POST['ps_range3_low']), 'high' => floatval($_POST['ps_range3_high']), 'percent' => floatval($_POST['ps_range3_percent'])],
            ['low' => floatval($_POST['ps_range4_low']), 'high' => floatval($_POST['ps_range4_high']), 'percent' => floatval($_POST['ps_range4_percent'])],
            ['low' => floatval($_POST['ps_range5_low']), 'high' => floatval($_POST['ps_range5_high']), 'percent' => floatval($_POST['ps_range5_percent'])],
            ['low' => floatval($_POST['ps_range6_low']), 'high' => floatval($_POST['ps_range6_high']), 'percent' => floatval($_POST['ps_range6_percent'])],
            ['low' => floatval($_POST['ps_range7_low']), 'high' => null, 'percent' => floatval($_POST['ps_range7_percent'])]
        ];

        $user0_total_trading_margin = 0;
        $user0_ds_value = 0;
        $user0_ps_value = 0;

        // --- First, sum overall User 0 trading margin from all branches ---
        foreach ($branchResults as $branch) {
            $user0_total_trading_margin += $branch['cumulativeTotals'][0];
        }
        // --- Determine dynamic overall User 0 DS and PS percentages using helper functions ---
        $user0_ds_percent = getDSPercent($user0_total_trading_margin, $ds_ranges);
        $user0_ps_percent = getPSPercent($user0_total_trading_margin, $ps_ranges);

        // --- Now, recalc DS and PS for User 0 per branch using new logic ---
        foreach ($branchResults as $branch) {
            $downlines = $branch['downlines'];
            $numDownlines = count($downlines);

            // New DS Calculation:
            // Step 1: Get first user's DS percentage (based on its own margin)
            $first_user_ds_percent = $branch['dsPercents'][0];

            // Step 2: Calculate the gap between overall User 0 DS % and first user's DS %
            $gap = $user0_ds_percent - $first_user_ds_percent;
            if ($gap < 0) {
                $gap = 0;
            }
            
            // Step 3: For branches with at least two members, use the cumulative total starting at member 2.
            $second_user_cumulative = ($numDownlines > 1) ? $branch['cumulativeTotals'][1] : 0;
            // New branch DS value calculation:
            $branch_ds = ($downlines[0] * $user0_ds_percent) + ($second_user_cumulative * $gap);

            // New PS Calculation for User 0 Income:
            $numDownlines = count($branch['rois']);
            
            // Step 1: First user contribution:
            $user1_contrib = $branch['rois'][0] * $user0_ps_percent;
            
            // Step 2: Sum the ROIs for all other members (user 2 onward)
            $others_roi_sum = 0;
            for ($i = 1; $i < $numDownlines; $i++) {
                $others_roi_sum += $branch['rois'][$i];
            }
            
            // Step 3: Calculate the gap between overall User 0 PS % and first user's PS %
            $gap_ps = $user0_ps_percent - $branch['psPercents'][0];
            if ($gap_ps < 0) {
                $gap_ps = 0;
            }
            
            // Step 4: Multiply the sum of the others' ROI by the gap %
            $others_contrib = $others_roi_sum * $gap_ps;
            // Step 5: Total PS value for the branch:
            $branch_ps = $user1_contrib + $others_contrib;
            
            $user0_ds_value += $branch_ds;
            $user0_ps_value += $branch_ps;
            $branchDSValues[$branch['branchCounter']] = $branch_ds;
            $branchPSValues[$branch['branchCounter']] = $branch_ps;
        }

        // --- Output the User 0 Income Card (Highlighted with Branch Breakdown) ---
        echo '<div class="result-container highlight">
                <div class="result-title">User 0 Income</div>
                <table class="result-table">
                  <tr>
                    <th>Total Trading Margin</th>
                    <th>Direct Sponsor %</th>
                    <th>Total DS Value</th>
                    <th>Profit Sharing %</th>
                    <th>Total PS Value</th>
                  </tr>
                  <tr>
                    <td>' . number_format($user0_total_trading_margin, 4) . '</td>
                    <td>' . number_format($user0_ds_percent * 100, 2) . '%</td>
                    <td>' . number_format($user0_ds_value, 4) . '</td>
                    <td>' . number_format($user0_ps_percent * 100, 2) . '%</td>
                    <td>' . number_format($user0_ps_value, 4) . '</td>
                  </tr>
                </table>
                <h3>Branch Breakdown</h3>
                <table class="result-table">
                  <tr>
                    <th>Branch</th>
                      <th>Total Trading Margin</th>
                      <th>Total ROI</th>
                      <th>DS Value</th>
                      <th>PS Value</th>
                  </tr>';
                foreach ($branchResults as $branch) {
                    $branchNum = $branch['branchCounter'];
                    $branchTotalTM = $branch['cumulativeTotals'][0];
                    $branchTotalROI = array_sum($branch['rois']);
                    echo '<tr>
                            <td>Branch ' . $branchNum . '</td>
                            <td>' . number_format($branchTotalTM, 4) . '</td>
                            <td>' . number_format($branchTotalROI, 4) . '</td>
                            <td>' . number_format($branchDSValues[$branchNum], 4) . '</td>
                            <td>' . number_format($branchPSValues[$branchNum], 4) . '</td>
                          </tr>';
                }
                echo '</table>
              </div>';

        // --- Output Each Branch's Results ---
        foreach ($branchResults as $branch) {
            echo '<div class="result-container">
                    <div class="result-title">Branch ' . $branch['branchCounter'] . ' (Members: ' . $branch['branchMembers']. ')</div>
                    <table class="result-table">
                      <tr>
                        <th>Total Members</th>
                        <th>Total Trading Margin (User 1)</th>
                        <th>Candidate Values</th>
                      </tr>
                      <tr>
                        <td>' . $branch['branchMembers'] . '</td>
                        <td>' . number_format($branch['cumulativeTotals'][0], 4) . '</td>
                        <td>' . htmlspecialchars($_POST['auto_candidates']) . '</td>
                      </tr>
                    </table>
                    <table class="result-table">
                      <tr>
                        <th>Member</th>
                        <th>Own Trading Margin</th>
                        <th>Total Trading Margin</th>
                        <th>ROI</th>
                        <th>Direct Sponsor %</th>
                        <th>Direct Sponsor Value</th>
                        <th>Profit Sharing %</th>
                        <th>Profit Sharing Value</th>
                      </tr>';
            $numDownlines = count($branch['downlines']);
            for ($i = 0; $i < $numDownlines; $i++) {
                $memberLabel = $i + 1;
                echo '<tr>
                        <td>' . $memberLabel . '</td>
                        <td>' . number_format($branch['downlines'][$i], 4) . '</td>
                        <td>' . number_format($branch['cumulativeTotals'][$i], 4) . '</td>
                        <td>' . number_format($branch['rois'][$i], 4) . '</td>
                        <td>' . number_format($branch['dsPercents'][$i] * 100, 2) . '%</td>
                        <td>' . number_format($branch['dsValues'][$i], 4) . '</td>
                        <td>' . number_format($branch['psPercents'][$i] * 100, 2) . '%</td>
                        <td>' . number_format($branch['psValues'][$i], 4) . '</td>
                      </tr>';
            }
            
            $totalPS = array_sum($branch['psValues']);
            echo '<tr>
                    <td colspan="7" style="text-align:right;"><strong>Total PS Value</strong></td>
                    <td><strong>' . number_format($totalPS, 4) . '</strong></td>
                  </tr>';
            echo '  </table>
                  </div>';
        }
    } else if ($useAuto) {
        // --- Single-Branch Auto Distribution ---
        $numMembers = intval($_POST['total_members']);
        $requiredTopCount = ceil(0.2 * $numMembers);  // at least 20%
        $countTop = 0;
        
        $topCandidate = $allowedVals[0];
        $downlines[0] = $topCandidate;
        $countTop++;
        
        $totalAmount = floatval($_POST['total_amount']);
        $remainingForOthers = $totalAmount - $topCandidate;
        $numOthers = $numMembers - 1;
        
        for ($i = 1; $i < $numMembers; $i++) {
            if ($i < $numMembers - 1) {
                if ($countTop < $requiredTopCount) {
                    $choice = $topCandidate;
                    $countTop++;
                } else {
                    $minPossible = min($allowedVals);
                    $maxAllowedForThis = $remainingForOthers - (($numOthers - ($i - 1)) * $minPossible);
                    $eligible = array_filter($allowedVals, function($val) use ($maxAllowedForThis) {
                        return $val <= $maxAllowedForThis;
                    });
                    if (empty($eligible)) {
                        $choice = $minPossible;
                    } else {
                        $eligible = array_values($eligible);
                        $choice = $eligible[array_rand($eligible)];
                    }
                }
                $downlines[$i] = $choice;
                $remainingForOthers -= $choice;
            } else {
                $choice = min($allowedVals);
                $downlines[$i] = $choice;
                $remainingForOthers -= $choice;
            }
        }
        $downlines[0] += $remainingForOthers;

        $numDownlines = count($downlines);
        $cumulativeTotals = array_fill(0, $numDownlines, 0);
        $totalROI = array_sum($rois);               // Sum of all ROI values in this branch.
        $totalTradingMargin = $cumulativeTotals[0];  // Top member's Total Trading Margin.
        $sum = 0;
        for ($i = $numDownlines - 1; $i >= 0; $i--) {
            $sum += $downlines[$i];
            $cumulativeTotals[$i] = $sum;
        }

        $ds_range_lows = array_map('floatval', $_POST['ds_range_low']);
        $ds_range_highs = $_POST['ds_range_high'];
        $ds_range_percents = array_map('floatval', $_POST['ds_range_percent']);
        $ds_ranges = [];
        $countRanges = count($ds_range_lows);
        for ($i = 0; $i < $countRanges; $i++) {
            $high = trim($ds_range_highs[$i]);
            $ds_ranges[] = [
                'low'     => $ds_range_lows[$i],
                'high'    => ($high !== '' ? floatval($high) : null),
                'percent' => $ds_range_percents[$i]
            ];
        }
        $ps_ranges = [
            ['low' => floatval($_POST['ps_range1_low']), 'high' => floatval($_POST['ps_range1_high']), 'percent' => floatval($_POST['ps_range1_percent'])],
            ['low' => floatval($_POST['ps_range2_low']), 'high' => floatval($_POST['ps_range2_high']), 'percent' => floatval($_POST['ps_range2_percent'])],
            ['low' => floatval($_POST['ps_range3_low']), 'high' => floatval($_POST['ps_range3_high']), 'percent' => floatval($_POST['ps_range3_percent'])],
            ['low' => floatval($_POST['ps_range4_low']), 'high' => floatval($_POST['ps_range4_high']), 'percent' => floatval($_POST['ps_range4_percent'])],
            ['low' => floatval($_POST['ps_range5_low']), 'high' => floatval($_POST['ps_range5_high']), 'percent' => floatval($_POST['ps_range5_percent'])],
            ['low' => floatval($_POST['ps_range6_low']), 'high' => floatval($_POST['ps_range6_high']), 'percent' => floatval($_POST['ps_range6_percent'])],
            ['low' => floatval($_POST['ps_range7_low']), 'high' => null, 'percent' => floatval($_POST['ps_range7_percent'])]
        ];

        $roi_percentage = (isset($_POST['roi_percentage']) && trim($_POST['roi_percentage']) !== '')
            ? floatval($_POST['roi_percentage'])
            : 0.03;

        $rois = [];
        foreach ($downlines as $margin) {
            $rois[] = calcROI($margin, $roi_percentage);
        }

        $dsPercents = [];
        foreach ($cumulativeTotals as $totalMargin) {
            $dsPercents[] = getDSPercent($totalMargin, $ds_ranges);
        }

        $dsValues = array_fill(0, $numDownlines, 0);
        for ($j = $numDownlines - 1; $j >= 0; $j--) {
            if ($j == 0) continue;
            $donorMargin = $downlines[$j];
            $currentDS = 0;
            for ($i = $j - 1; $i >= 0; $i--) {
                $gapCalc = $dsPercents[$i] - $currentDS;
                if ($gapCalc > 0) {
                    $dsValues[$i] += $donorMargin * $gapCalc;
                    $currentDS = $dsPercents[$i];
                }
            }
        }

        $psPercents = [];
        for ($i = 0; $i < $numDownlines; $i++) {
            $psPercents[$i] = getPSPercent($cumulativeTotals[$i], $ps_ranges);
        }

        $psValues = array_fill(0, $numDownlines, 0);
        for ($j = $numDownlines - 1; $j >= 0; $j--) {
            if ($j == 0) continue;
            $donorROI = $rois[$j];
            $contribFactor = $donorROI * 1.0;
            $psValues[$j - 1] += $contribFactor * $psPercents[$j - 1];
            $currentPS = $psPercents[$j - 1];
            for ($i = $j - 2; $i >= 0; $i--) {
                if ($psPercents[$i] > $currentPS) {
                    $gapPS = $psPercents[$i] - $currentPS;
                    $psValues[$i] += $contribFactor * $gapPS;
                    $currentPS = $psPercents[$i];
                }
            }
        }

        echo '<div class="result-container">
                <div class="result-title">Auto Distribution</div>
                <table class="result-table">
                  <tr>
                    <th>Total Members</th>
                    <th>Total Amount</th>
                    <th>Candidate Values</th>
                  </tr>
                  <tr>
                    <td>' . htmlspecialchars($_POST['total_members']) . '</td>
                    <td>' . htmlspecialchars($_POST['total_amount']) . '</td>
                    <td>' . htmlspecialchars($_POST['auto_candidates']) . '</td>
                  </tr>
                </table>
                <table class="result-table">
                  <tr>
                    <th>Member</th>
                    <th>Own Trading Margin</th>
                    <th>Total Trading Margin</th>
                    <th>ROI</th>
                    <th>Direct Sponsor %</th>
                    <th>Direct Sponsor Value</th>
                    <th>Profit Sharing %</th>
                    <th>Profit Sharing Value</th>
                  </tr>';
        for ($i = 0; $i < $numDownlines; $i++) {
            $memberLabel = $i + 1;
            echo '<tr>
                    <td>' . $memberLabel . '</td>
                    <td>' . number_format($downlines[$i], 4) . '</td>
                    <td>' . number_format($cumulativeTotals[$i], 4) . '</td>
                    <td>' . number_format($rois[$i], 4) . '</td>
                    <td>' . number_format($dsPercents[$i] * 100, 4) . '%</td>
                    <td>' . number_format($dsValues[$i], 4) . '</td>
                    <td>' . number_format($psPercents[$i] * 100, 4) . '%</td>
                    <td>' . number_format($psValues[$i], 4) . '</td>
                  </tr>';
        }
        echo '  </table>
              </div>';
    } else {
        // --- Manual Mode ---
        $downlines = array_map('floatval', $_POST['downline']);
        $numDownlines = count($downlines);
        $cumulativeTotals = array_fill(0, $numDownlines, 0);
        $sum = 0;
        for ($i = $numDownlines - 1; $i >= 0; $i--) {
            $sum += $downlines[$i];
            $cumulativeTotals[$i] = $sum;
        }

        $ds_range_lows = array_map('floatval', $_POST['ds_range_low']);
        $ds_range_highs = $_POST['ds_range_high'];
        $ds_range_percents = array_map('floatval', $_POST['ds_range_percent']);
        $ds_ranges = [];
        $countRanges = count($ds_range_lows);
        for ($i = 0; $i < $countRanges; $i++) {
            $high = trim($ds_range_highs[$i]);
            $ds_ranges[] = [
                'low'     => $ds_range_lows[$i],
                'high'    => ($high !== '' ? floatval($high) : null),
                'percent' => $ds_range_percents[$i]
            ];
        }
        $ps_ranges = [
            ['low' => floatval($_POST['ps_range1_low']), 'high' => floatval($_POST['ps_range1_high']), 'percent' => floatval($_POST['ps_range1_percent'])],
            ['low' => floatval($_POST['ps_range2_low']), 'high' => floatval($_POST['ps_range2_high']), 'percent' => floatval($_POST['ps_range2_percent'])],
            ['low' => floatval($_POST['ps_range3_low']), 'high' => floatval($_POST['ps_range3_high']), 'percent' => floatval($_POST['ps_range3_percent'])],
            ['low' => floatval($_POST['ps_range4_low']), 'high' => floatval($_POST['ps_range4_high']), 'percent' => floatval($_POST['ps_range4_percent'])],
            ['low' => floatval($_POST['ps_range5_low']), 'high' => floatval($_POST['ps_range5_high']), 'percent' => floatval($_POST['ps_range5_percent'])],
            ['low' => floatval($_POST['ps_range6_low']), 'high' => floatval($_POST['ps_range6_high']), 'percent' => floatval($_POST['ps_range6_percent'])],
            ['low' => floatval($_POST['ps_range7_low']), 'high' => null, 'percent' => floatval($_POST['ps_range7_percent'])]
        ];

        $roi_percentage = (isset($_POST['roi_percentage']) && trim($_POST['roi_percentage']) !== '')
            ? floatval($_POST['roi_percentage'])
            : 0.03;

        $rois = [];
        foreach ($downlines as $margin) {
            $rois[] = calcROI($margin, $roi_percentage);
        }

        $dsPercents = [];
        foreach ($cumulativeTotals as $totalMargin) {
            $dsPercents[] = getDSPercent($totalMargin, $ds_ranges);
        }

        $dsValues = array_fill(0, $numDownlines, 0);
        for ($j = $numDownlines - 1; $j >= 0; $j--) {
            if ($j == 0) continue;
            $donorMargin = $downlines[$j];
            $currentDS = 0;
            for ($i = $j - 1; $i >= 0; $i--) {
                $gapCalc = $dsPercents[$i] - $currentDS;
                if ($gapCalc > 0) {
                    $dsValues[$i] += $donorMargin * $gapCalc;
                    $currentDS = $dsPercents[$i];
                }
            }
        }

        $psPercents = [];
        for ($i = 0; $i < $numDownlines; $i++) {
            $psPercents[$i] = getPSPercent($cumulativeTotals[$i], $ps_ranges);
        }

        $psValues = array_fill(0, $numDownlines, 0);
        for ($j = $numDownlines - 1; $j >= 0; $j--) {
            if ($j == 0) continue;
            $donorROI = $rois[$j];
            $contribFactor = $donorROI * 1.0;
            $psValues[$j - 1] += $contribFactor * $psPercents[$j - 1];
            $currentPS = $psPercents[$j - 1];
            for ($i = $j - 2; $i >= 0; $i--) {
                if ($psPercents[$i] > $currentPS) {
                    $gapPS = $psPercents[$i] - $currentPS;
                    $psValues[$i] += $contribFactor * $gapPS;
                    $currentPS = $psPercents[$i];
                }
            }
        }

        echo '<div class="result-container">
                <div class="result-title">Manual Input Distribution</div>
                <table class="result-table">
                  <tr>
                    <th>Member</th>
                    <th>Own Trading Margin</th>
                    <th>Total Trading Margin</th>
                    <th>ROI</th>
                    <th>Direct Sponsor %</th>
                    <th>Direct Sponsor Value</th>
                    <th>Profit Sharing %</th>
                    <th>Profit Sharing Value</th>
                  </tr>';
        for ($i = 0; $i < $numDownlines; $i++) {
            $memberLabel = $i + 1;
            echo '<tr>
                    <td>' . $memberLabel . '</td>
                    <td>' . number_format($downlines[$i], 4) . '</td>
                    <td>' . number_format($cumulativeTotals[$i], 4) . '</td>
                    <td>' . number_format($rois[$i], 4) . '</td>
                    <td>' . number_format($dsPercents[$i] * 100, 4) . '%</td>
                    <td>' . number_format($dsValues[$i], 4) . '</td>
                    <td>' . number_format($psPercents[$i] * 100, 4) . '%</td>
                    <td>' . number_format($psValues[$i], 4) . '</td>
                  </tr>';
        }
        echo '  </table>
              </div>';
    }
    echo '</body></html>';
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>MoonExe ROI Simulation</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f7f7f7;
      padding: 20px;
      margin: 0;
    }
    .form-container {
      max-width: 900px;
      margin: 0 auto;
      background: #fff;
      padding: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    /* Two-column layout for form fields */
    .form-row {
      display: flex;
      flex-wrap: wrap;
    }
    .form-group {
      flex: 1;
      min-width: 250px;
    }
    label {
      display: block;
      margin-top: 10px;
      font-weight: bold;
    }
    input[type="text"] {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      box-sizing: border-box;
    }
    /* Container for side-by-side tables in the form */
    .table-container {
      display: flex;
      flex-wrap: nowrap;
      gap: 10px;
      margin-top: 20px;
      width: 100%;
    }
    .table-column {
      width: 48%;
    }
    table {
      border-collapse: collapse;
      width: 100%;
      margin-bottom: 15px;
    }
    table, th, td {
      border: 1px solid #ddd;
    }
    th, td {
      padding: 8px;
      text-align: center;
    }
    th {
      background-color: #eaeaea;
    }
    .add-btn {
      margin: 5px 0;
    }
  </style>
  <script>
    function addDownline() {
      const container = document.getElementById('downlineContainer');
      const div = document.createElement('div');
      div.className = 'form-group';
      div.innerHTML = `<label>Additional Member:</label>
                        <input type="text" name="downline[]" placeholder="Enter trading margin" required>`;
      container.appendChild(div);
    }
    function addDSRange() {
      const table = document.getElementById('dsRangeTable');
      const row = document.createElement('tr');
      let cellLow = document.createElement('td');
      let inputLow = document.createElement('input');
      inputLow.type = 'text';
      inputLow.name = 'ds_range_low[]';
      inputLow.required = true;
      cellLow.appendChild(inputLow);
      row.appendChild(cellLow);
      let cellHigh = document.createElement('td');
      let inputHigh = document.createElement('input');
      inputHigh.type = 'text';
      inputHigh.name = 'ds_range_high[]';
      cellHigh.appendChild(inputHigh);
      row.appendChild(cellHigh);
      let cellPercent = document.createElement('td');
      let inputPercent = document.createElement('input');
      inputPercent.type = 'text';
      inputPercent.name = 'ds_range_percent[]';
      inputPercent.required = true;
      cellPercent.appendChild(inputPercent);
      row.appendChild(cellPercent);
      table.appendChild(row);
    }
  </script>
</head>
<body>
  <div class="form-container">
    <h1>MLM Platform ROI Simulation</h1>
    <form method="post" action="">
      <!-- Two-column layout for ROI Settings and Auto Distribution -->
      <div class="form-row">
        <div class="form-group">
          <h2>ROI Settings</h2>
          <label>ROI Percentage (in decimal, default is 0.006):</label>
          <input type="text" name="roi_percentage" value="0.006" required>
        </div>
        <div class="form-group">
          <h2>Auto Distribute Downline</h2>
          <label>Total Members:</label>
          <input type="text" name="total_members" placeholder="e.g., 4">
          <label>Total Amount:</label>
          <input type="text" name="total_amount" placeholder="e.g., 1000000">
          <label>Candidate Values (comma separated):</label>
          <input type="text" name="auto_candidates" placeholder="e.g., 10000,3000,100">
          <label>Branches:</label>
          <input type="text" name="branches" placeholder="e.g., 2 (for two separate result sets)">
          <div style="margin-top: 10px;">
            <input type="submit" value="Run" style="padding: 10px 20px;">
          </div>
        </div>
      </div>
      <br><br>
      <hr>
      <!-- Two-column layout for Manual Downline Inputs -->
      <h2>Manual Downline Inputs</h2>
      <div class="form-row" id="downlineContainer">
        <div class="form-group">
          <label>Member A (Top):</label>
          <input type="text" name="downline[]" placeholder="Enter trading margin">
        </div>
        <div class="form-group">
          <label>Member B:</label>
          <input type="text" name="downline[]" placeholder="Enter trading margin">
        </div>
        <div class="form-group">
          <label>Member C:</label>
          <input type="text" name="downline[]" placeholder="Enter trading margin">
        </div>
        <div class="form-group">
          <label>Member D:</label>
          <input type="text" name="downline[]" placeholder="Enter trading margin">
        </div>
      </div>
      <button type="button" class="add-btn" onclick="addDownline()">Add Downline Member</button>
      <br><br>
      <hr>
      <!-- Side-by-side Tables for Range Table Inputs (same row) -->
      <div class="table-container">
        <div class="table-column">
          <h2>Direct Sponsor Range Table Inputs</h2>
          <table id="dsRangeTable">
            <tr>
              <th>Range Low</th>
              <th>Range High<br>(leave blank for "and above")</th>
              <th>Percentage (in decimal)</th>
            </tr>
            <tr>
              <td><input type="text" name="ds_range_low[]" value="100" required></td>
              <td><input type="text" name="ds_range_high[]" value="999" required></td>
              <td><input type="text" name="ds_range_percent[]" value="0.03" required></td>
            </tr>
            <tr>
              <td><input type="text" name="ds_range_low[]" value="1000" required></td>
              <td><input type="text" name="ds_range_high[]" value="9999" required></td>
              <td><input type="text" name="ds_range_percent[]" value="0.05" required></td>
            </tr>
            <tr>
              <td><input type="text" name="ds_range_low[]" value="10000" required></td>
              <td><input type="text" name="ds_range_high[]" value="99999" required></td>
              <td><input type="text" name="ds_range_percent[]" value="0.08" required></td>
            </tr>
            <tr>
              <td><input type="text" name="ds_range_low[]" value="100000" required></td>
              <td><input type="text" name="ds_range_high[]" value="999999" required></td>
              <td><input type="text" name="ds_range_percent[]" value="0.10" required></td>
            </tr>
            <tr>
              <td><input type="text" name="ds_range_low[]" value="1000000" required></td>
              <td><input type="text" name="ds_range_high[]" value="4999999" required></td>
              <td><input type="text" name="ds_range_percent[]" value="0.12" required></td>
            </tr>
            <tr>
              <td><input type="text" name="ds_range_low[]" value="5000000" required></td>
              <td><input type="text" name="ds_range_high[]" value="9999999" required></td>
              <td><input type="text" name="ds_range_percent[]" value="0.15" required></td>
            </tr>
            <tr>
              <td><input type="text" name="ds_range_low[]" value="10000000" required></td>
              <td><input type="text" name="ds_range_high[]" placeholder="Leave blank for and above"></td>
              <td><input type="text" name="ds_range_percent[]" value="0.18" required></td>
            </tr>
          </table>
          <button type="button" class="add-btn" onclick="addDSRange()">Add DS Range Row</button>
        </div>
        <div class="table-column">
          <h2>Profit Sharing Range Table Inputs</h2>
          <table id="psRangeTable">
            <tr>
              <th>Range</th>
              <th>Range Low</th>
              <th>Range High</th>
              <th>Percentage (in decimal)</th>
            </tr>
            <tr>
              <td>Range 1</td>
              <td><input type="text" name="ps_range1_low" value="100" required></td>
              <td><input type="text" name="ps_range1_high" value="999" required></td>
              <td><input type="text" name="ps_range1_percent" value="0.05" required></td>
            </tr>
            <tr>
              <td>Range 2</td>
              <td><input type="text" name="ps_range2_low" value="1000" required></td>
              <td><input type="text" name="ps_range2_high" value="9999" required></td>
              <td><input type="text" name="ps_range2_percent" value="0.15" required></td>
            </tr>
            <tr>
              <td>Range 3</td>
              <td><input type="text" name="ps_range3_low" value="10000" required></td>
              <td><input type="text" name="ps_range3_high" value="99999" required></td>
              <td><input type="text" name="ps_range3_percent" value="0.25" required></td>
            </tr>
            <tr>
              <td>Range 4</td>
              <td><input type="text" name="ps_range4_low" value="100000" required></td>
              <td><input type="text" name="ps_range4_high" value="999999" required></td>
              <td><input type="text" name="ps_range4_percent" value="0.30" required></td>
            </tr>
            <tr>
              <td>Range 5</td>
              <td><input type="text" name="ps_range5_low" value="1000000" required></td>
              <td><input type="text" name="ps_range5_high" value="4999999" required></td>
              <td><input type="text" name="ps_range5_percent" value="0.40" required></td>
            </tr>
            <tr>
              <td>Range 6</td>
              <td><input type="text" name="ps_range6_low" value="5000000" required></td>
              <td><input type="text" name="ps_range6_high" value="9999999" required></td>
              <td><input type="text" name="ps_range6_percent" value="0.50" required></td>
            </tr>
            <tr>
              <td>Range 7</td>
              <td><input type="text" name="ps_range7_low" value="10000000" required></td>
              <td><!-- Leave blank for and above --></td>
              <td><input type="text" name="ps_range7_percent" value="0.60" required></td>
            </tr>
          </table>
        </div>
      </div>
      <br>
      <input type="submit" value="Calculate">
    </form>
  </div>
</body>
</html>
