<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Trading Section Mockup</title>
  <style>
    /* -------------------------
       Simple Reset
    --------------------------*/
    * {
      margin: 0; 
      padding: 0; 
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    body {
      display: flex;
      min-height: 100vh;
      background: #f9f9f9;
      color: #333;
    }

    /* -------------------------
       Sidebar
    --------------------------*/
    .sidebar {
      width: 220px;
      background: #fff;
      border-right: 1px solid #ddd;
      padding: 1rem;
    }
    .sidebar .brand {
      font-size: 1.2rem;
      font-weight: bold;
      margin-bottom: 2rem;
    }
    .nav {
      list-style: none;
      margin-top: 1rem;
    }
    .nav li {
      margin-bottom: 0.5rem;
    }
    .nav a {
      text-decoration: none;
      color: #333;
      padding: 0.5rem;
      display: block;
      border-radius: 4px;
      transition: background 0.2s;
    }
    .nav a:hover {
      background: #eee;
    }
    .nav a.active {
      background: #4285f4;
      color: #fff;
    }

    /* -------------------------
       Main Content
    --------------------------*/
    .main-content {
      flex: 1;
      padding: 2rem;
    }
    .header {
      margin-bottom: 2rem;
    }
    .header h1 {
      font-size: 1.5rem;
      margin-bottom: 0.5rem;
    }
    .header p {
      color: #555;
      font-size: 0.9rem;
    }

    /* Trading Table */
    .trading-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 2rem;
      background: #fff;
      border: 1px solid #ddd;
    }
    .trading-table thead {
      background: #f0f0f0;
    }
    .trading-table th,
    .trading-table td {
      padding: 0.75rem 1rem;
      text-align: left;
      border-bottom: 1px solid #eee;
    }
    .trading-table th {
      font-weight: 600;
    }
    .trading-table td:last-child {
      text-align: right;
    }
    .btn-convert {
      padding: 0.4rem 0.8rem;
      background: #4285f4;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.9rem;
    }
    .btn-convert:hover {
      background: #2c6cd5;
    }

    /* My Orders */
    .orders-container {
      margin-top: 2rem;
    }
    .orders-container h2 {
      font-size: 1.2rem;
      margin-bottom: 1rem;
    }
    .orders-table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border: 1px solid #ddd;
    }
    .orders-table th,
    .orders-table td {
      padding: 0.6rem 1rem;
      border-bottom: 1px solid #eee;
    }
    .orders-table thead {
      background: #f0f0f0;
    }

    /* -------------------------
       Modal
    --------------------------*/
    .modal-backdrop {
      position: fixed;
      top: 0; 
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.3);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 999;
    }
    .modal {
      background: #fff;
      width: 400px;
      padding: 1.5rem;
      border-radius: 6px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.3);
      position: relative;
    }
    .modal h3 {
      margin-bottom: 1rem;
    }
    .modal label {
      display: block;
      margin-top: 1rem;
      margin-bottom: 0.2rem;
      font-size: 0.9rem;
    }
    .modal input {
      width: 100%;
      padding: 0.5rem;
      margin-bottom: 0.5rem;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .modal .modal-footer {
      margin-top: 1rem;
      text-align: right;
    }
    .btn-secondary {
      background: #ccc;
      color: #333;
      border: none;
      padding: 0.4rem 0.8rem;
      border-radius: 4px;
      cursor: pointer;
      margin-right: 0.5rem;
    }
    .btn-primary {
      background: #4285f4;
      color: #fff;
      border: none;
      padding: 0.4rem 0.8rem;
      border-radius: 4px;
      cursor: pointer;
    }
    .btn-primary:hover {
      background: #2c6cd5;
    }
    .close-btn {
      position: absolute;
      top: 0.7rem;
      right: 0.7rem;
      cursor: pointer;
      font-size: 1.2rem;
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="brand">MOONPAY</div>
    <ul class="nav">
      <li><a href="#" >Dashboard</a></li>
      <li><a href="#" >Assets</a></li>
      <li><a href="#" >Orders</a></li>
      <li><a href="#" >Referral</a></li>
      <li><a href="#" >Account</a></li>
      <li><a href="#" >Setting</a></li>
      <li><a href="#" class="active">Trading</a></li>
    </ul>
  </aside>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="header">
      <h1>Currency Exchange</h1>
      <p>Convert your USDT into other currencies before the gate closes or volume is full.</p>
    </div>

    <!-- Trading Table: List of Gates -->
    <table class="trading-table">
      <thead>
        <tr>
          <th>Currency Pair</th>
          <th>Rate (1 MYR = ? USDT)</th>
          <th>Total Volume</th>
          <th>Remaining Volume</th>
          <th>Gate Closes</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <!-- Example row 1 -->
        <tr>
          <td>MYR / USD</td>
          <td>4.50</td>
          <td>10,000 MYR</td>
          <td>8,250 MYR</td>
          <td>2h 15m</td>
          <td><button class="btn-convert" onclick="openModal('MYR', 4.5, 8250)">Convert</button></td>
        </tr>
        <!-- Example row 2 -->
        <tr>
          <td>SGD / USD</td>
          <td>1.36</td>
          <td>5,000 SGD</td>
          <td>2,000 SGD</td>
          <td>4h 20m</td>
          <td><button class="btn-convert" onclick="openModal('SGD', 1.36, 2000)">Convert</button></td>
        </tr>
        <!-- Example row 3 -->
        <tr>
          <td>THB / USD</td>
          <td>0.029</td>
          <td>50,000 THB</td>
          <td>38,000 THB</td>
          <td>6h 00m</td>
          <td><button class="btn-convert" onclick="openModal('THB', 0.029, 38000)">Convert</button></td>
        </tr>
      </tbody>
    </table>

    <!-- My Orders -->
    <div class="orders-container">
      <h2>My Exchange Orders</h2>
      <table class="orders-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Pair</th>
            <th>Amount (USDT)</th>
            <th>Rate</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <!-- Example orders - static placeholders -->
          <tr>
            <td>ORD-001</td>
            <td>2025-02-09</td>
            <td>MYR/USD</td>
            <td>500</td>
            <td>4.50</td>
            <td>Completed</td>
          </tr>
          <tr>
            <td>ORD-002</td>
            <td>2025-02-08</td>
            <td>SGD/USD</td>
            <td>300</td>
            <td>1.36</td>
            <td>Pending</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- MODAL (hidden by default) -->
  <div class="modal-backdrop" id="modalBackdrop">
    <div class="modal">
      <span class="close-btn" onclick="closeModal()">×</span>
      <h3>Convert USDT to <span id="targetCurrency"></span></h3>
      <div id="modalContent">
        <!-- JS will fill in rate, etc. -->
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn-primary" onclick="confirmConvert()">Confirm</button>
      </div>
    </div>
  </div>

  <script>
    // Basic JS mockup to handle modal logic
    let currentPair = null; 
    let currentRate = 0; 
    let currentRemainingVolume = 0;

    // Show modal with pre-filled info
    function openModal(currency, rate, remainingVolume) {
      currentPair = currency;
      currentRate = rate;
      currentRemainingVolume = remainingVolume;

      document.getElementById('targetCurrency').textContent = currency;
      document.getElementById('modalContent').innerHTML = `
        <label>Rate (approx):</label>
        <input type="text" value="${rate} USDT = 1 ${currency}" disabled/>

        <label>Your USDT Balance (example):</label>
        <input type="text" value="1000 USDT" disabled/>

        <label>Amount of USDT to convert:</label>
        <input type="number" id="usdtAmount" placeholder="Enter USDT amount" min="1" max="1000"/>

        <p style="font-size:0.9rem; color:#666; margin-top:0.5rem;">
          Remaining volume for this gate: ${remainingVolume} ${currency}
        </p>
        <p style="font-size:0.9rem; color:#666;">
          Gate will close when volume is reached or time expires.
        </p>
      `;

      document.getElementById('modalBackdrop').style.display = 'flex';
    }

    // Close modal
    function closeModal() {
      document.getElementById('modalBackdrop').style.display = 'none';
    }

    // Confirm conversion (mock)
    function confirmConvert() {
      const usdtField = document.getElementById('usdtAmount');
      const amount = parseFloat(usdtField.value);

      if(!amount || amount <= 0) {
        alert("Please enter a valid USDT amount!");
        return;
      }

      // Example calculation
      const currencyReceived = (amount / currentRate).toFixed(2);

      alert(`You are converting ${amount} USDT to ~${currencyReceived} ${currentPair}.
             (Mock confirmation, no real transaction.)`);

      // Close modal
      closeModal();
      // You’d also update “My Orders” and the “Remaining Volume” in a real app...
    }
  </script>
</body>
</html>
