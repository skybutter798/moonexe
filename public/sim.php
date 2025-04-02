<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- Determine Auto Distribution Mode ---
    $useAuto = isset($_POST['total_members']) && trim($_POST['total_members']) !== '' &&
               isset($_POST['total_amount']) && trim($_POST['total_amount']) !== '' &&
               isset($_POST['auto_candidates']) && trim($_POST['auto_candidates']) !== '';

    if ($useAuto) {
        // --- Input Parsing ---
        $totalMembers = intval($_POST['total_members']);
        $totalAmount = floatval($_POST['total_amount']); // Not used directly here.
        $allowedVals = array_map('floatval', explode(',', $_POST['auto_candidates']));
        rsort($allowedVals); // Highest candidate value first.
        $branches = (isset($_POST['branches']) && trim($_POST['branches']) !== '') ? intval($_POST['branches']) : 1;
        $roi_percentage = (isset($_POST['roi_percentage']) && trim($_POST['roi_percentage']) !== '')
                          ? floatval($_POST['roi_percentage'])
                          : 0.006;
        
        // --- DS Range Inputs ---
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
        
        // --- PS Range Inputs (unchanged) ---
        $ps_ranges = [
            ['low' => floatval($_POST['ps_range1_low']), 'high' => floatval($_POST['ps_range1_high']), 'percent' => floatval($_POST['ps_range1_percent'])],
            ['low' => floatval($_POST['ps_range2_low']), 'high' => floatval($_POST['ps_range2_high']), 'percent' => floatval($_POST['ps_range2_percent'])],
            ['low' => floatval($_POST['ps_range3_low']), 'high' => floatval($_POST['ps_range3_high']), 'percent' => floatval($_POST['ps_range3_percent'])],
            ['low' => floatval($_POST['ps_range4_low']), 'high' => floatval($_POST['ps_range4_high']), 'percent' => floatval($_POST['ps_range4_percent'])],
            ['low' => floatval($_POST['ps_range5_low']), 'high' => floatval($_POST['ps_range5_high']), 'percent' => floatval($_POST['ps_range5_percent'])],
            ['low' => floatval($_POST['ps_range6_low']), 'high' => floatval($_POST['ps_range6_high']), 'percent' => floatval($_POST['ps_range6_percent'])],
            ['low' => floatval($_POST['ps_range7_low']), 'high' => null, 'percent' => floatval($_POST['ps_range7_percent'])]
        ];
        
        // --- Helper Functions ---
        
        // Randomly split a total number into parts.
        function random_split($total, $parts) {
            if ($parts <= 1) return [$total];
            if ($total < $parts) {
                $split = array_fill(0, $parts, 1);
                $split[0] = $total - ($parts - 1);
                return $split;
            }
            $cuts = [];
            while (count($cuts) < ($parts - 1)) {
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
        
        // Calculate ROI.
        function calcROI($own, $roi_percentage) {
            return ($own * $roi_percentage) / 2;
        }
        
        // Get DS percentage from ranges based on a margin.
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
        
        // Get PS percentage (unchanged).
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
        
        // --- Tree Node Class ---
        class DownlineNode {
            public $margin;
            public $children = [];
            public $cumulativeMargin = 0;
            public $dsPercent = 0; // Set based on cumulative margin.
            public $psPercent = 0;
            public $roi = 0;
            public $dsValue = 0; // DS bonus accumulated.
            public $psValue = 0;
            
            public function __construct($margin) {
                $this->margin = $margin;
            }
        }
        
        // --- Tree Generation ---
        function generateRandomTree($numMembers, $allowedVals, $maxChildren = 3) {
            if ($numMembers <= 0) return null;
            $root = new DownlineNode($allowedVals[array_rand($allowedVals)]);
            $remaining = $numMembers - 1;
            $nodes = [$root];
            while ($remaining > 0 && !empty($nodes)) {
                $index = array_rand($nodes);
                $parent = $nodes[$index];
                $numChildren = rand(1, min($maxChildren, $remaining));
                for ($i = 0; $i < $numChildren; $i++) {
                    $child = new DownlineNode($allowedVals[array_rand($allowedVals)]);
                    $parent->children[] = $child;
                    $nodes[] = $child;
                    $remaining--;
                    if ($remaining <= 0) break;
                }
                if (count($parent->children) >= $maxChildren) {
                    unset($nodes[$index]);
                    $nodes = array_values($nodes);
                }
            }
            return $root;
        }
        
        // --- Cumulative Margin Calculation ---
        function computeCumulativeMargins($node) {
            $total = $node->margin;
            foreach ($node->children as $child) {
                $total += computeCumulativeMargins($child);
            }
            $node->cumulativeMargin = $total;
            return $total;
        }
        
        // --- Assign DS Percentage and ROI ---
        function assignDSPercents($node, $ds_ranges, $roi_percentage, $ps_ranges = null) {
            $node->dsPercent = getDSPercent($node->cumulativeMargin, $ds_ranges);
            $node->roi = calcROI($node->margin, $roi_percentage);
            if ($ps_ranges !== null) {
                $node->psPercent = getPSPercent($node->cumulativeMargin, $ps_ranges);
            }
            foreach ($node->children as $child) {
                assignDSPercents($child, $ds_ranges, $roi_percentage, $ps_ranges);
            }
        }
        
        // --- Bottom-Up DS Bonus Distribution ---
        // For each leaf, walk upward through its ancestry chain.
        // Let the chain be A₀ (immediate parent), A₁ (parent of A₀), A₂, etc.
        // Then add:
        // - A₀ gets: leaf margin × A₀.dsPercent
        // - For i >= 1, Aᵢ gets: leaf margin × max(0, (Aᵢ.dsPercent - Aᵢ₋₁.dsPercent))
        function distributeFromLeaves($node, $ancestors = []) {
            if (empty($node->children)) {
                $M = $node->margin;
                for ($i = 0; $i < count($ancestors); $i++) {
                    if ($i == 0) {
                        $ancestors[0]->dsValue += $M * $ancestors[0]->dsPercent;
                    } else {
                        $gap = $ancestors[$i]->dsPercent - $ancestors[$i - 1]->dsPercent;
                        if ($gap > 0) {
                            $ancestors[$i]->dsValue += $M * $gap;
                        }
                    }
                }
            } else {
                $newAncestors = $ancestors;
                array_unshift($newAncestors, $node);
                foreach ($node->children as $child) {
                    distributeFromLeaves($child, $newAncestors);
                }
            }
        }
        
        // --- HTML Tree Rendering ---
        function renderTreeList($node, &$counter) {
            $counter++;
            $userId = $counter;
            echo "<li>";
            echo "<strong>User {$userId}</strong> - ";
            echo "Own Margin: " . number_format($node->margin, 4) . ", ";
            echo "Cumulative: " . number_format($node->cumulativeMargin, 4) . ", ";
            echo "DS %: " . number_format($node->dsPercent * 100, 2) . "%, ";
            echo "DS Value: " . number_format($node->dsValue, 4);
            if (!empty($node->children)) {
                echo "<ul>";
                foreach ($node->children as $child) {
                    renderTreeList($child, $counter);
                }
                echo "</ul>";
            }
            echo "</li>";
        }
        
        // --- Build Global Tree ---
        $globalRoot = new DownlineNode($allowedVals[0]);
        $remainingMembers = $totalMembers - 1;
        $branchCounts = random_split($remainingMembers, $branches);
        foreach ($branchCounts as $branchCount) {
            $branchTree = generateRandomTree($branchCount, $allowedVals, 3);
            if ($branchTree !== null) {
                $globalRoot->children[] = $branchTree;
            }
        }
        
        // Compute cumulative margins.
        computeCumulativeMargins($globalRoot);
        // Assign DS percentages and ROI.
        assignDSPercents($globalRoot, $ds_ranges, $roi_percentage, $ps_ranges);
        // Distribute DS bonus from each leaf upward.
        distributeFromLeaves($globalRoot, []);
        
        // --- Output Results ---
        echo '<!DOCTYPE html>
<html>
<head>
  <title>MLM Platform ROI Simulation - Bottom-Up DS Bonus</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f0f0f0; margin: 0; padding: 20px; }
    .result-container { max-width: 1800px; margin: 0 auto; padding: 15px; background: #fff; border: 1px solid #ccc; border-radius: 5px; }
    h2, h3 { text-align: center; }
    ul.tree { list-style-type: none; }
    ul.tree li { margin: 5px 0; padding-left: 20px; position: relative; }
    ul.tree li:before { content: ""; position: absolute; left: 0; top: 8px; width: 10px; height: 1px; background: #000; }
    ul.tree ul { margin-left: 20px; }
  </style>
</head>
<body>
<div class="result-container">
  <h2>Global Tree Distribution Results</h2>
  <p>Total Members: ' . $totalMembers . '<br>
     Global User 0 Margin: ' . number_format($globalRoot->margin, 4) . '<br>
     Cumulative Margin: ' . number_format($globalRoot->cumulativeMargin, 4) . '<br>
     DS %: ' . number_format($globalRoot->dsPercent * 100, 2) . '%, DS Value: ' . number_format($globalRoot->dsValue, 4) . '
  </p>
  <h3>Downline Tree Structure</h3>
  <ul class="tree">';
        $counter = 0;
        renderTreeList($globalRoot, $counter);
        echo '</ul>
</div>
</body>
</html>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>MLM Platform ROI Simulation</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f7f7f7; padding: 20px; margin: 0; }
    .form-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
    .form-row { display: flex; flex-wrap: wrap; }
    .form-group { flex: 1; min-width: 250px; }
    label { display: block; margin-top: 10px; font-weight: bold; }
    input[type="text"] { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
    .table-container { display: flex; flex-wrap: nowrap; gap: 10px; margin-top: 20px; width: 100%; }
    .table-column { width: 48%; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 15px; }
    table, th, td { border: 1px solid #ddd; }
    th, td { padding: 8px; text-align: center; }
    th { background-color: #eaeaea; }
    .add-btn { margin: 5px 0; }
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
      <div class="form-row">
        <div class="form-group">
          <h2>ROI Settings</h2>
          <label>ROI Percentage (in decimal, default is 0.006):</label>
          <input type="text" name="roi_percentage" value="0.006" required>
        </div>
        <div class="form-group">
          <h2>Auto Distribute Downline</h2>
          <label>Total Members:</label>
          <input type="text" name="total_members" placeholder="e.g., 10">
          <label>Total Amount:</label>
          <input type="text" name="total_amount" placeholder="e.g., 1000000">
          <label>Candidate Values (comma separated):</label>
          <input type="text" name="auto_candidates" placeholder="e.g., 10000,3000,100">
          <label>Branches:</label>
          <input type="text" name="branches" placeholder="e.g., 2">
          <div style="margin-top: 10px;">
            <input type="submit" value="Run" style="padding: 10px 20px;">
          </div>
        </div>
      </div>
      <br><br>
      <hr>
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