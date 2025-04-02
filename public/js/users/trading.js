document.addEventListener('DOMContentLoaded', function() {

    // Object to store the last valid mid rates for trading cards.
    const lastValidRates = {};
    
    // Separate function for updating trading card exchange rates.
    function updateTradingCardRates(apiData) {
      const rateEls = document.querySelectorAll('.exchangeRate');
      rateEls.forEach(el => {
        // Extract symbol from element ID (assumed to be "price-<SYMBOL>")
        const symbol = el.id.replace('price-', '');
        let marketData = apiData[symbol];
    
        // If not found, try the reversed symbol (for 3-letter codes)
        if (!marketData && symbol.length === 6) {
          const reversedSymbol = symbol.slice(3) + symbol.slice(0, 3);
          marketData = apiData[reversedSymbol];
        }
    
        if (marketData && marketData.mid) {
          const mid = parseFloat(marketData.mid);
          // Store or update the last valid rate
          lastValidRates[symbol] = mid;
          el.innerText = mid.toFixed(6);
          el.classList.remove('bg-danger', 'bg-success', 'bg-secondary');
          el.classList.add('bg-secondary', 'badge-custom');
        } else {
          console.warn(`No API data found for symbol: ${symbol}`);
        }
      });
    }
    
    // Separate function for updating table matching data initially.
    function updateTableRates(apiData) {
      // Now look for matching-rate spans inside pairing cells
      const matchingEls = document.querySelectorAll('.pairing-cell .matching-rate');
      matchingEls.forEach(el => {
        
        if (el.innerHTML.trim() === 'Claim') {
          return;
        }
    
        const dataSymbol = el.getAttribute('data-symbol');
        let marketData = apiData[dataSymbol];
    
        // Try the reversed pair if needed.
        if (!marketData && dataSymbol.length === 6) {
          const reversedSymbol = dataSymbol.slice(3) + dataSymbol.slice(0, 3);
          marketData = apiData[reversedSymbol];
        }
    
        if (marketData && !isNaN(marketData.bid) && !isNaN(marketData.ask)) {
          const chooseBid = Math.random() < 0.5;
          const randomValue = chooseBid ? parseFloat(marketData.bid) : parseFloat(marketData.ask);
          el.innerText = randomValue.toFixed(6);
          el.classList.remove('bg-danger', 'bg-success');
          el.classList.add(chooseBid ? 'bg-danger' : 'bg-success');
        } else {
          console.warn(`No API data found for symbol: ${dataSymbol}`);
        }
      });
    }
    
    // Main function to fetch API data and update both parts independently.
    function fetchExchangeRates() {
      fetch('https://app.moonexe.com/api/market-data')
        .then(response => response.json())
        .then(apiData => {
          updateTradingCardRates(apiData);
          updateTableRates(apiData);
        })
        .catch(error => console.error('Error fetching exchange rates:', error));
    }
    
    function simulateExchangeRateAdjustments() {
      const now = new Date().getTime();
      for (const symbol in lastValidRates) {
        const priceEl = document.getElementById(`price-${symbol}`);
        if (!priceEl) continue;
    
        // Check if this price element is inside a card with a gate close time.
        const cardEl = priceEl.closest('.gateRow');
        if (cardEl) {
          const gateClose = parseInt(cardEl.getAttribute('data-gate-close'));
          // If the current time is past the gate close, skip updating this rate.
          if (now > gateClose) {
            continue;
          }
        }
        
        const delta = (Math.random() * (0.0005 - 0.0001) + 0.0001) * (Math.random() < 0.5 ? -1 : 1);
        lastValidRates[symbol] += delta;
        priceEl.innerText = lastValidRates[symbol].toFixed(6);
        priceEl.classList.remove('bg-danger', 'bg-success', 'bg-secondary');
        priceEl.classList.add(delta > 0 ? 'bg-success' : 'bg-danger', 'badge-custom');
      }
    
      // Get current time once for updating matching rate and ROI.
      const currentTime = new Date().getTime();
    
      // Update matching rate and ROI per row.
      const rows = document.querySelectorAll('tr');
      rows.forEach(row => {
        // Read pair end time from the row's data attribute.
        const pairEndAttr = row.getAttribute('data-pair-end');
        const pairEnd = pairEndAttr ? parseInt(pairEndAttr) : null;
        
        // Get the matching rate element (from the pairing cell).
        const matchingEl = row.querySelector('.matching-rate');
        // Get the ROI element from the est-roi cell.
        const roiEl = row.querySelector('.est-roi #rateDisplay');
    
        // Update matching rate badge (this always updates as needed).
        if (matchingEl) {
          let currentMatching = parseFloat(matchingEl.innerText.replace('%','')) || 0;
          const extraDelta = (Math.random() * (0.0005 - 0.0001) + 0.0001) * (Math.random() < 0.5 ? -1 : 1);
          const newMatching = currentMatching + extraDelta;
          matchingEl.innerText = newMatching.toFixed(6);
          matchingEl.classList.remove('bg-danger', 'bg-success', 'bg-secondary');
          // Determine the badge color based on the delta.
          const badgeClass = extraDelta > 0 ? 'bg-success' : 'bg-danger';
          matchingEl.classList.add(badgeClass, 'badge-custom');
    
          if (roiEl) {
            if (pairEnd && now < pairEnd) {
              // Get the original base ROI from the parent cell's data attribute.
              const estRoiCell = row.querySelector('.est-roi');
              const baseRoi = estRoiCell ? parseFloat(estRoiCell.getAttribute('data-roi')) : 0;
              
              // Calculate a dynamic change.
              const roiDelta = (Math.random() * (0.015 - 0.001) + 0.008) * (Math.random() < 0.5 ? -1 : 1);
              const newRoi = baseRoi + roiDelta;
              
              // Determine the badge class for the dynamic value.
              const roiBadgeClass = roiDelta > 0 ? 'bg-success' : 'bg-danger';
              
              // **Update the row's attribute so other functions can use the dynamic est_rate**
              row.setAttribute('data-dynamic-est-rate', newRoi.toFixed(2));
              
              // Update the display: static value first, then a pipe, then the dynamic value wrapped in a span.
              roiEl.innerHTML = baseRoi.toFixed(2) + " | <span class='" + roiBadgeClass + " badge-custom'>" + newRoi.toFixed(2) + "</span>";
            } else {
              // Reset to the original static view when time has expired.
              const estRoiCell = row.querySelector('.est-roi');
              if (estRoiCell) {
                // Get the original ROI string, expected in "base | dynamic" format.
                const originalData = estRoiCell.getAttribute('data-roi');
                const parts = originalData.split('|');
                if (parts.length > 1) {
                  // Reconstruct the HTML with the badge-dark class applied to the dynamic value.
                  roiEl.innerHTML = parts[0].trim() + " | <span class='badge badge-dark'>" + parts[1].trim() + "</span>";
                  // Set the dynamic-est-rate attribute for consistency.
                  row.setAttribute('data-dynamic-est-rate', parseFloat(parts[1].trim()).toFixed(2));
                } else {
                  // Fallback in case originalData is not in the expected format.
                  roiEl.textContent = originalData;
                  row.setAttribute('data-dynamic-est-rate', parseFloat(originalData).toFixed(2));
                }
                // Remove any residual dynamic badge classes.
                roiEl.classList.remove('bg-danger', 'bg-success', 'bg-secondary', 'badge-custom');
              }
            }
          }
        }
      });
    }
    
    function simulateBuyMultiplication() {
      // Get all cells that should update
      const buyCells = document.querySelectorAll('.order-buy');
      
      buyCells.forEach(cell => {
        // Retrieve the static buy value and base estimated rate from data attributes.
        const staticBuy = parseFloat(cell.getAttribute('data-buy'));
        const baseEstRate = parseFloat(cell.getAttribute('data-est-rate'));
        
        // Find the closest row to read the updated dynamic estimated rate.
        const row = cell.closest('tr');
        const dynamicEstRate = row && row.getAttribute('data-dynamic-est-rate')
                                 ? parseFloat(row.getAttribute('data-dynamic-est-rate'))
                                 : baseEstRate;
        
        // Calculate computed value using the formula: buy * (1 + (newEstRate/100))
        const computedValue = staticBuy * (1 + (dynamicEstRate / 100));
        
        // Update only the computed value display.
        const computedValueEl = cell.querySelector('.computed-value');
        if (computedValueEl) {
          computedValueEl.innerHTML = computedValue.toFixed(4) + " USDT";
          
          // Remove any existing badge classes.
          computedValueEl.classList.remove('bg-success', 'bg-danger', 'badge-custom');
          
          // Apply badge classes based on whether the dynamic rate has increased or decreased.
          if (dynamicEstRate > baseEstRate) {
            computedValueEl.classList.add('bg-success', 'badge-custom');
          } else if (dynamicEstRate < baseEstRate) {
            computedValueEl.classList.add('bg-danger', 'badge-custom');
          }
        }
      });
    }
    
    fetchExchangeRates();
    setInterval(fetchExchangeRates, 20000);
    setInterval(simulateExchangeRateAdjustments, 1000);
    setInterval(simulateBuyMultiplication, 1000);

    function updateGateCountdowns() {
        const rows = document.querySelectorAll('.gateRow');
        const now = new Date().getTime();
        rows.forEach(row => {
          const gateClose = parseInt(row.getAttribute('data-gate-close'));
          const diffInSeconds = Math.floor((gateClose - now) / 1000);
          const timerSpan = row.querySelector('.gateCloseTimer');
          if(diffInSeconds > 0) {
            const hours = Math.floor(diffInSeconds / 3600);
            const minutes = Math.floor((diffInSeconds % 3600) / 60);
            const seconds = diffInSeconds % 60;
            timerSpan.innerText = ("0" + hours).slice(-2) + ':' + ("0" + minutes).slice(-2) + ':' + ("0" + seconds).slice(-2);
          } else {
            timerSpan.innerText = '[Gate Closed]';
          }
        });
      }
      
    updateGateCountdowns();
    setInterval(updateGateCountdowns, 1000);
  
    let tradeCountdownInterval;
  
    function startTradeCountdown() {
        clearInterval(tradeCountdownInterval);
        const tradeCountdownElem = document.getElementById('tradeCountdown');
        let tradeTime = 120;
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
  
    window.showTradeDetails = function(mode, pair, pairId, btnRef, total, remain, previousRate, progressPercent, closingTimestamp) {
        
      const baseCurrency = pair.split('/')[0].trim();
      const standardizedPair = `USDT / ${baseCurrency} / USDT`;
      
      const cardContainer = btnRef.closest('.card');
      if (!cardContainer) {
        console.error("Could not find card container.");
        return;
      }
      
      const rateElem = cardContainer.querySelector('.exchangeRate');
      if (!rateElem) {
        console.error("Could not find exchangeRate element within the card.");
        return;
      }
      let lockedRate = parseFloat(rateElem.innerText);
      currentMode = mode;
      currentPair = pair;
      currentPairId = pairId;
      currentRate = lockedRate;
      totalVol = total;
      remainingVol = remain;
      const modal = document.getElementById('tradeModal');
      if (!modal) {
        console.error("tradeModal element not found.");
        return;
      }
      const modalTitle = modal.querySelector('#tradeModalLabel');
      const tradeSummary = modal.querySelector('#tradeSummary');
      const progressBar = document.getElementById('volumeProgressBar');
      const progressText = progressBar.nextElementSibling;
      const maxUsdtDisplay = modal.querySelector('#maxUsdtDisplay');
      const tabsContainer = modal.querySelector('#tradeTabsContainer');
      const volumeDisplay = modal.querySelector('#volumeDisplay');
    
      // Update the modal title to include the pair name and the gate closes countdown
      if (modalTitle) {
          modalTitle.innerHTML = `
            ${standardizedPair} <br><span id="gateCloseCountdown" class="h6 text-danger"></span>
          `;
      }
      
      // Optionally, simplify the trade summary if you no longer need the gate closes text there
      if (tradeSummary) {
        tradeSummary.innerHTML = `
          <p>PAIR : ${pair}</p>
          <p>RATE: ${lockedRate}<p>
        `;
      }
    
      if (volumeDisplay) {
        volumeDisplay.innerHTML = `<p>REMAIN VOL : ${parseFloat(remain).toFixed(2)} / ${parseFloat(total).toFixed(2)} volume</p>`;
      }
    
      startGateCloseCountdown(closingTimestamp);
      
      if (progressBar) {
          progressBar.style.width = progressPercent.toFixed(2) + '%';
          progressBar.setAttribute('aria-valuenow', progressPercent.toFixed(2));
          progressBar.classList.add("progress-bar-animated");
        }
        
        if (progressText) {
          progressText.innerText = progressPercent.toFixed(2) + '%';
        }

      
      const maxAllowed = Math.min(window.tradingBalance, remainingVol / currentRate);
      if (maxUsdtDisplay) {
        maxUsdtDisplay.textContent = parseFloat(maxAllowed).toFixed(2);
      }
      
      if (tabsContainer) {
          tabsContainer.innerHTML = `
            <form>
              <div class="mb-2">
                <label class="form-label small">Amount in USDT</label>
                <input type="number" class="form-control form-control-sm" id="buyUsdtAmount" placeholder="e.g. 500">
              </div>
              <div class="mb-2">
                <button type="button" class="btn btn-primary btn-sm" style="margin-right:5px;" onclick="fillUsdtAmount(50)">50%</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="fillUsdtAmount('max')">MAX</button>
              </div>
              <div class="mb-2">
                <label class="form-label small">
                  Estimated Receive (<span id="buyPairName">${pair.split('/')[0].trim()}</span>)
                </label>
                <input type="text" class="form-control form-control-sm" id="buyEstimatedReceive" readonly>
              </div>
              <button type="button" class="btn btn-primary w-100" onclick="confirmTrade('buy')">Trade</button>
            </form>
          `;
        }

      
      if (typeof tradeChart !== "undefined" && tradeChart) { 
        tradeChart.destroy();
      }
      if (document.getElementById('tradeChart')) {
        initChart();
      }
      if (document.getElementById('recentTradesList')) {
        populateRecentTrades();
      }
      if (document.getElementById('tradeCountdown')) {
        startTradeCountdown();
      }
    };
    
    window.fillUsdtAmount = function(option) {
      const input = document.getElementById('buyUsdtAmount');
      const displayedSymbol = document.getElementById('buyPairName')?.innerText.trim().toUpperCase() || '';
      
      const reversedSymbols = ['LKR', 'VND', 'IDR', 'COP'];
      const isReversed = reversedSymbols.includes(displayedSymbol);
      
      //   estimatedReceive = USDT amount ÷ currentRate  (so effective multiplier is 1/currentRate)
      // For reversed pairs (CAD, CHF, JPY):
      //   estimatedReceive = USDT amount × currentRate
      const effectiveRate = isReversed ? currentRate : (1 / currentRate);
      
      // Calculate maximum allowed USDT input based on available volume:
      // For standard pairs: USDT max = remainingVol × currentRate (since currentRate = USD per foreign unit)
      // For reversed pairs: USDT max = remainingVol ÷ currentRate (since currentRate = foreign units per USD)
      const maxAllowed = isReversed 
        ? Math.min(window.tradingBalance, remainingVol / currentRate)
        : Math.min(window.tradingBalance, remainingVol * currentRate);
    
      if (option === 'max') {
        input.value = maxAllowed.toFixed(2);
      } else if (option === 50) {
        input.value = (maxAllowed * 0.5).toFixed(2);
      }
      
      // Update the estimated receive field using the effective rate:
      const estimatedReceiveElem = document.getElementById('buyEstimatedReceive');
      estimatedReceiveElem.value = (parseFloat(input.value) * effectiveRate).toFixed(4);
    };
    
    document.addEventListener('input', e => {
      // Get the displayed pair symbol from the placeholder (e.g. "CHF")
      const displayedSymbol = document.getElementById('buyPairName')?.innerText.trim().toUpperCase() || '';
      const reversedSymbols = ['LKR', 'VND', 'IDR', 'COP'];
      const isReversed = reversedSymbols.includes(displayedSymbol);
      const effectiveRate = isReversed ? currentRate : (1 / currentRate);
    
      if (e.target.id === 'buyUsdtAmount') {
        let val = parseFloat(e.target.value || "0");
        const maxAllowed = isReversed 
          ? Math.min(window.tradingBalance, remainingVol / currentRate)
          : Math.min(window.tradingBalance, remainingVol * currentRate);
        
        if (val > maxAllowed) {
          val = maxAllowed;
          e.target.value = maxAllowed;
        }
        // For the buy side, estimated received foreign currency:
        document.getElementById('buyEstimatedReceive').value = (val * effectiveRate).toFixed(4);
      }
      
      if (e.target.id === 'sellAmount') {
        const val = parseFloat(e.target.value || "0");
        document.getElementById('sellEstimatedReceive').value = (val * effectiveRate).toFixed(4);
      }
    });
  
    window.confirmTrade = function(type) {
      let amount, estimatedReceive;
      if (type === 'buy') {
        amount = parseFloat(document.getElementById('buyUsdtAmount').value || "0");
        estimatedReceive = parseFloat(document.getElementById('buyEstimatedReceive').value || "0");
        if (amount <= 0) {
          alert("Enter a valid USDT amount!");
          return;
        }
      } else {
        amount = parseFloat(document.getElementById('sellAmount').value || "0");
        if (amount <= 0) {
          alert("Enter a valid amount to sell!");
          return;
        }
      }
      
      let data = {
        pair_id: currentPairId,
        order_type: type,
        amount: amount,
        estimated_receive: type === 'buy' ? estimatedReceive : undefined
      };
      
      // Insert the spinner into the DOM
      const spinnerContainer = document.getElementById('spinnerContainer');
        
    const tradePopup = document.getElementById('tradePopup');
    const tradePopupText = document.getElementById('tradePopupText');
    const tradeSpinner = document.getElementById('tradePopupSpinner');
    
    tradePopup.classList.remove('d-none');
    tradeSpinner.classList.remove('d-none');
    tradePopupText.innerHTML = 'Pairing...';
      
      // Generate a random delay between 1 and 4 seconds (1000 to 4000 ms)
      const randomDelay = Math.floor(Math.random() * 3000) + 1000;
      
      setTimeout(() => {
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
          if (result.success) {
              document.getElementById('tradePopupSpinner').style.display = 'none';
            tradePopupText.innerHTML = `<div class="text-success fw-bold">${result.message}</div>`;
            setTimeout(() => location.reload(), 2000);
          } else {
            tradePopupText.innerHTML = `<div class="text-danger fw-bold">${result.error || 'Order failed.'}</div>`;
            setTimeout(() => {
              tradePopup.classList.add('d-none');
            }, 3000);
          }
        })

        .catch(error => {
          console.error('Error:', error);
          tradePopupText.innerHTML = `<div class="text-danger fw-bold">An error occurred. Please try again.</div>`;
          setTimeout(() => {
            tradePopup.classList.add('d-none');
          }, 3000);
        });

        
        // Hide the offcanvas trade panel if open
        const offCanvasEl = document.getElementById('offcanvasTrade');
        const bsOffCanvas = bootstrap.Offcanvas.getInstance(offCanvasEl);
        bsOffCanvas.hide();
      }, randomDelay);
    };

  
    function showRandomTradeNotification() {
      // Expanded lists with more names, pairs, and actions
      const users = ["Alice", "Bob", "Charlie", "Dave", "Eve", "Frank", "Grace", "Hannah", "Ian", "Jack", "Kate"];
      const pairs = ["AUD/USD", "EUR/USD", "CHF/USD", "GBP/USD", "JPY/USD", "CAD/USD"];
      const actions = ["bought", "sold", "traded", "exchanged"];
    
      // Randomly select user, pair, and action
      const user = users[Math.floor(Math.random() * users.length)];
      const pair = pairs[Math.floor(Math.random() * pairs.length)];
      const action = actions[Math.floor(Math.random() * actions.length)];
    
      // Generate a random amount between 1000 and 10000 and a random price between 0.5 and 5.0
      const amount = (Math.random() * (10000 - 1000) + 1000).toFixed(2);
      const price = (Math.random() * (5 - 0.5) + 0.5).toFixed(3);
    
      // Construct the notification message
      const message = `${user} ${action} ${amount} units of ${pair} @ ${price} USDT`;
    
      // Display the notification
      const notif = document.getElementById("tradeNotification");
      notif.textContent = message;
      notif.style.opacity = 1;
      setTimeout(() => { notif.style.opacity = 0; }, 3000);
    }
  
    function scheduleRandomNotification() {
        const delay = Math.floor(Math.random() * (8000 - 3000 + 1)) + 300000;
        setTimeout(() => {
          showRandomTradeNotification();
          scheduleRandomNotification();
        }, delay);
     }
    scheduleRandomNotification();
      
    function startGateCloseCountdown(closingTimestamp) {
      const countdownElem = document.getElementById('gateCloseCountdown');
      function updateCountdown() {
        const now = new Date().getTime();
        const diff = Math.floor((closingTimestamp - now) / 1000);
        if (diff > 0) {
          const hours = Math.floor(diff / 3600);
          const minutes = Math.floor((diff % 3600) / 60);
          const seconds = diff % 60;
          countdownElem.textContent = "Gatetime Remaining: " +
            ("0" + hours).slice(-2) + ':' +
            ("0" + minutes).slice(-2) + ':' +
            ("0" + seconds).slice(-2);
        } else {
          countdownElem.textContent = "Gate Closed";
          clearInterval(intervalId);
        }
      }
      updateCountdown();
      const intervalId = setInterval(updateCountdown, 1000);
    }
        
    function updateOrderProgress() {
      const now = new Date().getTime();
      document.querySelectorAll('#ordersTable tbody tr').forEach(function(row) {
        // --- Update Progress Bar and Countdown ---
        const pairStart = parseInt(row.getAttribute('data-pair-start'));
        const pairEnd = parseInt(row.getAttribute('data-pair-end'));
        const progressBar = row.querySelector('.status-progress');
        let progress = 0;
        if (now >= pairEnd) {
          progress = 100;
        } else if (now <= pairStart) {
          progress = 0;
        } else {
          progress = ((now - pairStart) / (pairEnd - pairStart)) * 100;
        }
        progressBar.style.width = progress.toFixed(2) + '%';
        progressBar.setAttribute('aria-valuenow', progress.toFixed(2));
    
        // Countdown tip update
        let remainingTime = pairEnd - now;
        const progressContainer = progressBar.parentElement;
        let countdownTip = row.querySelector('.status-countdown');
        if (!countdownTip) {
          countdownTip = document.createElement('div');
          countdownTip.classList.add('status-countdown');
          countdownTip.style.fontSize = '8px';
          countdownTip.style.color = '#777';
          countdownTip.style.marginTop = '3px';
          progressContainer.insertAdjacentElement('afterend', countdownTip);
        }
        if (remainingTime <= 0) {
          countdownTip.style.display = 'block';
          countdownTip.innerText = "00:00:00";
          progressBar.style.backgroundColor = "#343a40";
        } else {
          countdownTip.style.display = 'block';
          let hours = Math.floor(remainingTime / 3600000);
          let minutes = Math.floor((remainingTime % 3600000) / 60000);
          let seconds = Math.floor((remainingTime % 60000) / 1000);
          let countdownText = ("0" + hours).slice(-2) + ':' + ("0" + minutes).slice(-2) + ':' + ("0" + seconds).slice(-2);
          countdownTip.innerText = countdownText;
          // Here you might want to use your normal progress bar color
          progressBar.style.backgroundColor = "primary";
        }
    
        // --- Update the Pairing Cell ---
        const pairingCell = row.querySelector('.pairing-cell');
        if (!pairingCell) return;
        const orderStatus = row.getAttribute('data-order-status');
    
        if (orderStatus === 'completed') {
          // If already claimed, show Completed.
          pairingCell.innerHTML = '<span class="badge bg-dark">Completed</span>';
        } else {
          if (remainingTime <= 0) {
            // Countdown finished and not yet claimed: show Claim button.
            pairingCell.innerHTML = '<button class="btn btn-sm btn-primary claim-btn">Claim</button>';
          } else {
            // While countdown is active: show matching rate.
            // Ensure the cell contains the matching rate span.
            if (!pairingCell.querySelector('.matching-rate')) {
              // If missing, add the span. (Assumes you have a data-symbol on the cell; if not, adjust accordingly.)
              let dataSymbol = pairingCell.getAttribute('data-symbol') || '';
              pairingCell.innerHTML = '<span class="matching-rate" data-symbol="' + dataSymbol + '">0.000000</span>';
            }
          }
        }
      });
    }
    setInterval(updateOrderProgress, 1000);
    
    // Notification element (you can style/position it as needed).
    const notificationEl = document.getElementById("tradeNotification");
    
    const orderEl = document.getElementById("orderNotification");
    let reloadScheduled = false;
    
    function updateUpcomingCountdowns() {
      const now = new Date().getTime();
      let countdownEnded = false;
      
      document.querySelectorAll('.card[data-trigger-timestamp]').forEach(card => {
        const triggerTime = parseInt(card.getAttribute('data-trigger-timestamp'));
        const countdownSpan = card.querySelector('.upcoming-countdown');
        const diffInSeconds = Math.floor((triggerTime - now) / 1000);
    
        if (diffInSeconds > 0) {
          const hours   = Math.floor(diffInSeconds / 3600);
          const minutes = Math.floor((diffInSeconds % 3600) / 60);
          const seconds = diffInSeconds % 60;
          countdownSpan.textContent =
            ("0" + hours).slice(-2) + ':' +
            ("0" + minutes).slice(-2) + ':' +
            ("0" + seconds).slice(-2);
        } else {
          // Fade out and remove the card when time is up.
          card.style.transition = "opacity 1s ease-out";
          card.style.opacity = "0";
          setTimeout(() => {
            card.remove();
          }, 1000);
          countdownEnded = true;
        }
      });
      
      // Show notification if any countdown ended.
      if (countdownEnded) {
        orderEl.innerHTML = `
          <span style="cursor: pointer;" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise"></i> New pair is starting, please refresh the trading page.
          </span>
        `;
        orderEl.style.opacity = 1;
    
        // Schedule a page reload after 5 seconds if not already scheduled.
        if (!reloadScheduled) {
          reloadScheduled = true;
          setTimeout(() => {
            location.reload();
          }, 5000);
        }
      } else {
        orderEl.style.opacity = 0;
      }
    }
    
    // Update countdowns every second.
    setInterval(updateUpcomingCountdowns, 1000);

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: 'd23cf1caa9971c9bcf61',
        cluster: 'ap1',
        forceTLS: true,
    });
    
    window.Echo.connector.pusher.connection.bind('connected', function() {
        console.log('Connected to Pusher');
    });

    window.Echo.channel('pair-updates')
      .listen('.OrderUpdated', (data) => {
          console.log("OrderUpdated event received:", data); // Log event data for debugging
          // Find the trading card using the pair id (assume you set a data attribute)
          const card = document.querySelector(`.gateRow[data-pair-id="${data.pairId}"]`);
          if(card) {
              // Update the card's data attributes so updateCountdowns() uses the latest numbers
              card.setAttribute('data-remaining-volume', data.remainingVolume);
              card.setAttribute('data-total-volume', data.totalVolume);
              
              // Update the volume text immediately
              const volumeTextEl = card.querySelector('.volume-text');
              volumeTextEl.innerText = `${parseFloat(data.remainingVolume).toFixed(4)} / ${parseFloat(data.totalVolume).toFixed(4)}`;
              
              // Update the progress bar.
              const progressBar = card.querySelector('.progress-bar');
              const progressText = card.querySelector('.progress-text');
              let progress = 0;
              if (data.totalVolume > 0) {
                  progress = ((data.totalVolume - data.remainingVolume) / data.totalVolume) * 100;
              }
              progressBar.style.width = progress.toFixed(2) + '%';
              progressText.innerText = progress.toFixed(2) + '%';
          }
      });

});

document.addEventListener('click', function (e) {
  if (e.target.classList.contains('claim-btn') && !e.target.disabled) {
    const row = e.target.closest('tr');
    const orderId = row.getAttribute('data-order-id');
    if (!orderId) return alert('Order ID missing');

    const tradePopup = document.getElementById('tradePopup');
    const tradePopupText = document.getElementById('tradePopupText');
    const tradeSpinner = document.getElementById('tradePopupSpinner');
    const tradeButtons = document.getElementById('tradePopupButtons');

    // Show loading popup
    tradePopup.classList.remove('d-none');
    tradeSpinner.style.display = 'inline-block';
    tradePopupText.innerText = 'Porcessing payout...';
    tradeButtons.classList.add('d-none');

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    fetch(window.orderClaimRoute, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrfToken,
        "Accept": "application/json"
      },
      body: JSON.stringify({ order_id: orderId })
    })
    .then(res => res.json())
    .then(result => {
      tradeSpinner.style.display = 'none';
    
      if (result.success) {
        // Extract details from the row
        const orderID = row.querySelector('td:nth-child(1)')?.innerText || '-';
        const pair = row.querySelector('td:nth-child(3)')?.innerText || '-';
        const estRate = row.querySelector('.est-roi')?.getAttribute('data-roi')?.split('|')[1]?.trim() || '-';
        const returnProfit = row.querySelector('.computed-value')?.innerText || '-';
    
        // Build detail HTML
        const detailsHTML = `
          <div id="tradeOrderDetails" class="mt-3 text-start small border-top pt-2">
            <div><strong>Order ID:</strong> ${orderID}</div>
            <div><strong>Pair:</strong> ${pair}</div>
            <div><strong>Actual Profit:</strong> ${estRate}%</div>
            <div><strong>Return Profit:</strong> ${returnProfit}</div>
          </div>
        `;
    
        // Update popup content
        tradePopupText.innerHTML = `
          <div class="text-success fw-bold">${result.message}</div>
          ${detailsHTML}
        `;
    
        // Show buttons
        tradeButtons.classList.remove('d-none');
    
        // Update the claim button visually
        e.target.innerText = "Done";
        e.target.classList.remove("btn-primary");
        e.target.classList.add("btn-success");
        e.target.disabled = true;
    
        // Mark as completed
        row.setAttribute('data-order-status', 'completed');
    
        // Button actions
        const goBtn = document.getElementById('goToWalletBtn');
        const stayBtn = document.getElementById('stayHereBtn');
    
        goBtn.onclick = function () {
          window.location.href = result.redirect_url;
        };
    
        stayBtn.onclick = function () {
          tradePopup.classList.add('d-none');
        };
    
      } else {
        tradePopupText.innerHTML = `<div class="text-danger fw-bold">${result.error || "Claim failed."}</div>`;
        setTimeout(() => {
          tradePopup.classList.add('d-none');
        }, 3000);
      }
    })


    .catch(err => {
      console.error(err);
      tradeSpinner.style.display = 'none';
      tradePopupText.innerHTML = `<div class="text-danger fw-bold">Something went wrong. Please try again.</div>`;
      setTimeout(() => {
        tradePopup.classList.add('d-none');
      }, 3000);
    });
  }
});
