document.addEventListener('DOMContentLoaded', function(){
  /********************************************
   * Dynamic Countdown Timers for Trading Gates *
   ********************************************/
  const gateRows = document.querySelectorAll('.gateRow');
  gateRows.forEach(row => {
    const seconds = parseInt(row.getAttribute('data-closing-seconds'), 10);
    row.targetTime = Date.now() + seconds * 1000;
  });
  function updateCountdownTimers() {
    gateRows.forEach(row => {
      const remaining = row.targetTime - Date.now();
      const timerSpan = row.querySelector('.gateCloseTimer');
      if (remaining > 0) {
        const hours = Math.floor(remaining / (3600 * 1000));
        const minutes = Math.floor((remaining % (3600 * 1000)) / (60 * 1000));
        const seconds = Math.floor((remaining % (60 * 1000)) / 1000);
        timerSpan.textContent = `${hours}h ${minutes}m ${seconds}s`;
      } else {
        timerSpan.textContent = "Closed";
        const tradeButton = row.querySelector('button');
        if (tradeButton) {
          tradeButton.disabled = true;
          tradeButton.classList.add('disabled');
        }
      }
    });
  }
  setInterval(updateCountdownTimers, 1000);
  updateCountdownTimers();

  /********************************************
   * Trade Details Functions & Dynamic Updates *
   ********************************************/
  let currentPair = "MYR/USD";
  let currentRate = 4.5;
  let totalVol = 10000;
  let remainingVol = 8250;
  let samplePastRates = [4.48, 4.51, 4.47, 4.53, 4.49];

  // Chart variable (using Chart.js)
  let tradeChart;
  function initChart() {
    const ctx = document.getElementById('tradeChart').getContext('2d');
    tradeChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: Array.from({length: 20}, (_, i) => i+1),
        datasets: [{
          label: 'Price',
          data: Array.from({length: 20}, () => (currentRate + (Math.random()-0.5)*0.1).toFixed(3)),
          borderColor: '#6F6BE0',
          backgroundColor: 'rgba(110,106,224,0.1)',
          fill: true,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { display: false },
          y: { beginAtZero: false }
        }
      }
    });
  }
  function updateChart() {
    if(tradeChart) {
      const newPrice = (currentRate + (Math.random()-0.5)*0.1).toFixed(3);
      tradeChart.data.labels.push(tradeChart.data.labels.length+1);
      tradeChart.data.datasets[0].data.push(newPrice);
      if(tradeChart.data.labels.length > 20) {
        tradeChart.data.labels.shift();
        tradeChart.data.datasets[0].data.shift();
      }
      tradeChart.update();
    }
  }
  setInterval(updateChart, 2000);

  // Expose functions for use in the HTML buttons
  window.showTradeDetails = function(pair, pairId, rate, total, remain, previousRate, progressPercent) {
  currentPair = pair;
  currentPairId = pairId;
  currentRate = rate;
  totalVol = total;
  remainingVol = remain;

  // Update the trade summary to include previous rate
  document.getElementById('tradeSummary').innerHTML = `
    <h6>Pair: ${pair}</h6>
    <p class="mb-0 text-white-50" style="font-size:0.9rem;">
      Rate: 1 USD = ${rate} <br>
      Total Volume: ${total}<br>
      Remaining Volume: ${remain}<br>
      Previous Rate: ${previousRate}<br>
      <span id="tradeCountdown"></span>
    </p>
  `;

  // Update the progress bar based on progressPercent
  const pb = document.getElementById('volumeProgressBar');
  pb.style.width = progressPercent + '%';
  pb.setAttribute('aria-valuenow', progressPercent);

  // Update BUY and SELL labels and reset inputs
  document.getElementById('buyPairName').textContent = pair;
  document.getElementById('sellPairName').textContent = pair;
  document.getElementById('buyUsdtAmount').value = "";
  document.getElementById('buyEstimatedReceive').value = "";
  document.getElementById('sellAmount').value = "";
  document.getElementById('sellEstimatedReceive').value = "";

  // Reinitialize chart, populate recent trades, and start countdown
  if(tradeChart) { tradeChart.destroy(); }
  initChart();
  populateRecentTrades();
  startTradeCountdown();
};


  let tradeCountdownInterval;
  
  function startTradeCountdown() {
    clearInterval(tradeCountdownInterval);
    const tradeCountdownElem = document.getElementById('tradeCountdown');
    let tradeTime = 120; // seconds for demo
    function updateTradeCountdown() {
      if(tradeTime > 0) {
        const minutes = Math.floor(tradeTime / 60);
        const seconds = tradeTime % 60;
        tradeCountdownElem.textContent = `Gate closes in: ${minutes}m ${seconds}s`;
        tradeTime--;
      } else {
        tradeCountdownElem.textContent = "Gate Closed";
        clearInterval(tradeCountdownInterval);
      }
    }
    updateTradeCountdown();
    tradeCountdownInterval = setInterval(updateTradeCountdown, 1000);
  }

  window.confirmTrade = function(type) {
      let amount;
      if (type === 'buy') {
        amount = parseFloat(document.getElementById('buyUsdtAmount').value || "0");
        if(amount <= 0) {
          alert("Enter a valid USDT amount!");
          return;
        }
      } else { // sell
        amount = parseFloat(document.getElementById('sellAmount').value || "0");
        if(amount <= 0) {
          alert("Enter a valid amount to sell!");
          return;
        }
      }
      
      // Build the data to send
      let data = {
        pair_id: currentPairId,
        order_type: type,
        amount: amount
      };
    
      // Get CSRF token from meta tag (make sure your base layout has <meta name="csrf-token" content="{{ csrf_token() }}">)
      const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    
      fetch(window.orderStoreRoute, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
            "Accept": "application/json"
          },
          body: JSON.stringify(data)
        })
    
      .then(response => response.json())
      .then(result => {
        if(result.success) {
          alert(result.message);
          // Optionally reload the page or update UI
          location.reload();
        } else {
          alert(result.error || 'Order failed.');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert("An error occurred. Please try again.");
      });
    
      // Close offcanvas after confirmation.
      const offCanvasEl = document.getElementById('offcanvasTrade');
      const bsOffCanvas = bootstrap.Offcanvas.getInstance(offCanvasEl);
      bsOffCanvas.hide();
    }

  // BUY: Calculate estimated MYR received for the given USDT amount.
    document.getElementById('buyUsdtAmount').addEventListener('input', e => {
      const val = parseFloat(e.target.value || "0");
      // Multiply USDT by the rate to get MYR.
      document.getElementById('buyEstimatedReceive').value = (val * currentRate).toFixed(4);
    });
    
    // SELL: Calculate estimated USDT received for the given MYR amount.
    document.getElementById('sellAmount').addEventListener('input', e => {
      const val = parseFloat(e.target.value || "0");
      // Divide MYR by the rate to get USDT.
      document.getElementById('sellEstimatedReceive').value = (val / currentRate).toFixed(4);
    });

  /********************************************
   * Recent Trades (Mockup) for Trade Details *
   ********************************************/
  function populateRecentTrades() {
    const recentTrades = document.getElementById('recentTradesList');
    recentTrades.innerHTML = "";
    // For demo, generate 5 random recent trades.
    for(let i = 0; i < 5; i++) {
      const li = document.createElement('li');
      const type = Math.random() > 0.5 ? "BUY" : "SELL";
      const amount = (Math.random() * 1000).toFixed(2);
      const price = (currentRate + (Math.random()-0.5)*0.1).toFixed(3);
      li.textContent = `${type} ${amount} at ${price} USDT`;
      recentTrades.appendChild(li);
    }
  }

  /********************************************
   * Simple Filter for My Exchange Orders Table *
   ********************************************/
  window.filterOrders = function() {
    const filterText = document.getElementById('orderSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#ordersTable tbody tr');
    rows.forEach(row => {
      const rowText = row.textContent.toLowerCase();
      row.style.display = rowText.includes(filterText) ? "" : "none";
    });
  }

  /********************************************
   * Random Trade Notification Popout
   ********************************************/
  function showRandomTradeNotification() {
    const users = ["Alice", "Bob", "Charlie", "Dave", "Eve", "Frank", "Grace"];
    const pairs = ["MYR/USD", "SGD/USD", "THB/USD"];
    const actions = ["bought", "sold"];
    const user = users[Math.floor(Math.random() * users.length)];
    const pair = pairs[Math.floor(Math.random() * pairs.length)];
    const action = actions[Math.floor(Math.random() * actions.length)];
    const amount = (Math.random() * (1000 - 50) + 50).toFixed(2);
    const price = (Math.random() * (5 - 0.5) + 0.5).toFixed(3);
    const message = `${user} ${action} ${amount} units of ${pair} @ ${price} USDT`;
    const notif = document.getElementById("tradeNotification");
    notif.textContent = message;
    notif.style.opacity = 1;
    // Hide the notification after 3 seconds.
    setTimeout(() => {
      notif.style.opacity = 0;
    }, 3000);
  }
  
  function scheduleRandomNotification() {
    const delay = Math.floor(Math.random() * (8000 - 3000 + 1)) + 3000;
    setTimeout(() => {
      showRandomTradeNotification();
      scheduleRandomNotification();
    }, delay);
  }
  scheduleRandomNotification();
});
