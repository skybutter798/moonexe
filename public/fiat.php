<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SETTLEMENT LEDGER (Fiat Edition)</title>
  <style>
    /* =============== RESET & GLOBAL =============== */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: "Helvetica Neue", Arial, sans-serif; background: #f8f9fb; color: #333; }
    a { text-decoration: none; color: inherit; }
    ul { list-style: none; }
    .container { max-width: 1280px; margin: 0 auto; padding: 20px; }

    /* =============== HEADER & NAV =============== */
    header { background: #fff; padding: 12px 0; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
    .nav-inner { display: flex; align-items: center; justify-content: space-between; }
    .logo { font-size: 1.4rem; font-weight: bold; color: #0070ba; }
    nav ul { display: flex; gap: 24px; font-size: 0.9rem; }
    nav li { padding: 6px 0; position: relative; cursor: pointer; }
    nav li:hover, nav li.active { color: #0070ba; }
    nav li.active::after {
      content: ""; position: absolute; bottom: -6px; left: 0; right: 0;
      height: 2px; background: #0070ba;
    }

    /* =============== SEARCH & TRENDING =============== */
    .search-box { margin: 24px 0; text-align: center; }
    .search-box input {
      width: 60%; max-width: 600px; padding: 10px 14px;
      border: 1px solid #ddd; border-radius: 24px; font-size: 0.95rem;
      transition: border-color .2s;
    }
    .search-box input:focus { outline: none; border-color: #0070ba; }
    .trending { margin-top: 8px; font-size: 0.85rem; color: #666; }
    .trending a {
      display: inline-block; margin-left: 8px; padding: 2px 6px;
      background: #fff; border: 1px solid #0070ba; border-radius: 12px;
      color: #0070ba; font-weight: bold; transition: background .2s;
    }
    .trending a:hover { background: #0070ba; color: #fff; }

    /* =============== STATS CARDS =============== */
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 16px; }
    .stat-card {
      background: #fff; padding: 16px 20px; border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05); text-align: center;
    }
    .stat-card h2 { font-size: 1.6rem; margin-bottom: 4px; }
    .stat-card p { font-size: 0.85rem; color: #777; }

    /* =============== OVERVIEW PANEL =============== */
    .trx-panel {
      display: flex; flex-wrap: wrap; background: #fff; margin: 24px 0;
      border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); overflow: hidden;
    }
    .trx-info { flex: 1 1 320px; padding: 24px; }
    .trx-info h2 { font-size: 1.5rem; margin-bottom: 12px; }
    .trx-info .change {
      font-size: 0.9rem; padding: 2px 6px; border-radius: 4px;
      background: rgba(0,112,186,0.1); color: #0070ba; margin-left: 8px;
    }
    .trx-stats { display: flex; flex-wrap: wrap; gap: 16px; margin-top: 16px; }
    .trx-stats div { font-size: 0.85rem; color: #555; }
    .chart { flex: 0 0 360px; background: #eef1f6;
      display: flex; align-items: center; justify-content: center;
      color: #99a; font-size: 0.9rem; min-height: 140px;
    }

    /* =============== SECTION HEADINGS =============== */
    .section { margin-bottom: 32px; }
    .section h3 { font-size: 1.1rem; margin-bottom: 12px; color: #333; }

    /* =============== BATCHES SCROLLER =============== */
    .blocks {
      display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px;
    }
    .block-card {
      flex: 0 0 180px; background: #fff; padding: 14px;
      border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }
    .block-card h4 { font-size: 0.95rem; color: #0070ba; margin-bottom: 6px; }
    .block-card p { font-size: 0.8rem; color: #555; line-height: 1.4; }

    /* =============== TRANSACTIONS TABLE =============== */
    table {
      width: 100%; border-collapse: collapse; background: #fff;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden;
    }
    th, td {
      padding: 12px 16px; font-size: 0.85rem; border-bottom: 1px solid #eee;
    }
    th { background: #fafafa; color: #555; text-align: left; }
    tr:last-child td { border-bottom: none; }
    tbody tr:hover { background: #f5faff; }
    .status-paid { color: green; font-weight: bold; }
    .status-fail { color: red; font-weight: bold; }

    /* =============== TOP METHODS =============== */
    .two-col { display: flex; flex-wrap: wrap; gap: 24px; }
    .methods-table, .usdt-panel {
      background: #fff; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      flex: 1 1 480px; overflow: hidden; padding: 16px;
    }
    .methods-table table { width: 100%; border-collapse: collapse; }
    .methods-table th, .methods-table td {
      padding: 10px 12px; font-size: 0.85rem; border-bottom: 1px solid #eee;
    }
    .methods-table th { background: #fafafa; color: #555; }
    .methods-table tr:last-child td { border-bottom: none; }
    .usdt-panel { text-align: center; color: #99a; min-height: 180px; }

    /* =============== ANALYTICS CHARTS =============== */
    .charts-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr));
      gap: 24px; margin-bottom: 40px;
    }
    .chart-card {
      background: #fff; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      overflow: hidden;
    }
    .chart-card .title {
      padding: 12px 16px; font-size: 0.9rem; color: #333; border-bottom: 1px solid #eee;
    }
    .chart-card .chart-body {
      padding: 20px; background: #eef1f6; text-align: center; color: #99a;
      min-height: 140px;
    }

    /* =============== FOOTER =============== */
    footer {
      background: #fff; margin-top: 40px; padding: 24px 20px 12px;
      box-shadow: 0 -1px 4px rgba(0,0,0,0.05);
    }
    .footer-inner { display: flex; flex-wrap: wrap; gap: 40px; max-width: 1280px; margin: 0 auto; }
    .footer-col { flex: 1 1 200px; }
    .footer-col h4 { font-size: 0.9rem; margin-bottom: 8px; color: #555; }
    .footer-col a { display: block; font-size: 0.85rem; color: #666; margin-bottom: 4px; }
    .footer-bottom {
      border-top: 1px solid #eee; margin-top: 16px; padding-top: 12px;
      text-align: center; font-size: 0.75rem; color: #999;
    }
    
    .nav-toggle {
      display: none;
      background: none;
      border: none;
      font-size: 1.6rem;
    }
    @media (max-width: 768px) {
      .nav-toggle {
        display: block;
        margin-left: auto;
      }
    }
  </style>
  <!--<style>
    /* ===== Mobile Responsive (≤768px) ===== */
    @media (max-width: 768px) {
      /* Header & Nav */
      .nav-inner {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
      }
      nav ul {
        display: flex;
        flex-direction: column;
        width: 100%;
        gap: 0;
      }
      nav li {
        width: 100%;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
      }
      nav li.active::after { display: none; }
    
      /* Search */
      .search-box input {
        width: 100%;
        max-width: 100%;
      }
    
      /* Stats cards: stack */
      .stats {
        grid-template-columns: 1fr;
      }
    
      /* Overview panel: column */
      .trx-panel {
        flex-direction: column;
      }
      .trx-panel .chart {
        order: -1;   /* show chart on top */
        width: 100%;
        margin-bottom: 16px;
      }
      .trx-info {
        padding: 16px;
      }
    
      /* Settlement Batches scroller stays—but shrink cards */
      .block-card {
        flex: 0 0 140px;
      }
    
      /* Tables: overflow horizontally */
      table {
        display: block;
        overflow-x: auto;
      }
    
      /* Top Methods + USDT panel: stack */
      .two-col {
        flex-direction: column;
      }
      .methods-table,
      .usdt-panel {
        width: 100%;
      }
    
      /* Analytics charts: one-column grid */
      .charts-grid {
        grid-template-columns: 1fr;
      }
    
      /* Footer: stack columns */
      .footer-inner {
        flex-direction: column;
        gap: 24px;
      }
    }
  </style>-->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <!-- HEADER -->
  <header>
    <div class="container nav-inner">
      <div class="logo">SETTLEMENT LEDGER</div>
      <nav>
        <ul>
          <li class="active">Overview</li>
          <li>Batches</li>
          <li>Transactions</li>
          <li>Analytics</li>
          <li>More</li>
        </ul>
      </nav>
    </div>
  </header>

  <div class="container">
    <!-- SEARCH + TRENDING -->
    <div class="search-box">
      <input type="text" placeholder="Search by Batch / Txn ID / Merchant / App Crypt">
      <div class="trending">
        Trending Methods:
        <a>Visa</a><a>Mastercard</a><a>PayPal</a><a>Stripe</a><a>AMEX</a>
      </div>
    </div>

    <!-- TOP STATS -->
    <div class="stats">
      <div class="stat-card">
        <h2 id="statTxCount">—</h2><p>Total Payments</p>
      </div>
      <div class="stat-card">
        <h2 id="statVolume">—</h2><p>Total Volume (USD)</p>
      </div>
      <div class="stat-card">
        <h2 id="statSuccess">—%</h2><p>Success Rate</p>
      </div>
      <div class="stat-card">
        <h2 id="statFailure">—%</h2><p>Failure Rate</p>
      </div>
    </div>

    <!-- OVERVIEW -->
    <div class="trx-panel">
      <div class="trx-info">
        <h2>Last 14 Days Payments
          <span id="statChange" class="change">(dummy)</span>
        </h2>
        <div class="trx-stats">
          <div>Visa: <strong id="volVisa">—</strong></div>
          <div>Mastercard: <strong id="volMaster">—</strong></div>
          <div>PayPal: <strong id="volPaypal">—</strong></div>
          <div>Stripe: <strong id="volStripe">—</strong></div>
        </div>
      </div>
      <div class="chart">[ 14d Volume Chart ]</div>
    </div>

    <!-- BATCHES -->
    <div class="section">
      <h3>Settlement Batches</h3>
      <div class="blocks" id="batchesContainer"></div>
    </div>

    <!-- TRANSACTIONS -->
    <div class="section">
      <h3>Recent Payments</h3>
      <table>
        <thead>
          <tr>
            <th>Pay ID</th>
            <th>Method</th>
            <th>Merchant</th>
            <th>App Crypt</th>
            <th>Amount</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="txTable"></tbody>
      </table>
    </div>

    <!-- TOP METHODS -->
    <div class="section">
      <h3>Top Methods by Volume</h3>
      <div class="two-col">
        <div class="methods-table">
          <table>
            <thead>
              <tr><th>#</th><th>Method</th><th>Vol (24h)</th><th>Txns</th></tr>
            </thead>
            <tbody id="methodsTable"></tbody>
          </table>
        </div>
        <div class="usdt-panel">
          <canvas id="realtimeVolumeChart" height="160"></canvas>
        </div>
      </div>
    </div>

    <!-- ANALYTICS CHARTS -->
    <div class="section">
      <h3>Analytics Charts</h3>
      <div class="charts-grid">
        <div class="chart-card"><div class="title">Payments Over Time</div><div class="chart-body">[ Chart ]</div></div>
        <div class="chart-card"><div class="title">Avg. Payment Amount</div><div class="chart-body">[ Chart ]</div></div>
        <div class="chart-card"><div class="title">Success vs Failure</div><div class="chart-body">[ Chart ]</div></div>
        <div class="chart-card"><div class="title">Peak Hours</div><div class="chart-body">[ Chart ]</div></div>
      </div>
    </div>
  </div>

  <!-- FOOTER -->
  <footer>
    <div class="footer-inner">
      <div class="footer-col"><h4>About</h4><a>Privacy Policy</a><a>Terms of Service</a><a>API & Docs</a></div>
      <div class="footer-col"><h4>Methods</h4><a>Visa</a><a>Mastercard</a><a>PayPal</a><a>Stripe</a><a>AMEX</a></div>
      <div class="footer-col"><h4>Support</h4><a>Contact Us</a><a>Help Center</a></div>
    </div>
    <div class="footer-bottom">© 2025 Paymentscan · Demo Only</div>
  </footer>

  <script src="payment.js"></script>
</body>
</html>