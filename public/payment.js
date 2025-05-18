// payment.js

// Utility
// inject entry-animation styles
const style = document.createElement('style');
style.innerHTML = `
  .block-card {
    transition: transform 0.4s ease, opacity 0.4s ease;
    transform-origin: center center;
  }
  #txTable tr {
    transition: transform 0.3s ease, opacity 0.3s ease;
    transform-origin: top center;
  }
`;
document.head.appendChild(style);


const overviewAcc = {
  volVisa:   parseFloat(randNum(2e5, 4e5)),
  volMaster: parseFloat(randNum(1.5e5, 3e5)),
  volPaypal: parseFloat(randNum(1e5, 2e5)),
  volStripe: parseFloat(randNum(1e5, 2e5)),
};

const statsAcc = {
  totalTx:  parseInt(randNum(50000, 200000, 0), 10),    // starting Tx count
  totalVol: parseFloat(randNum(1e6,   5e6)),            // starting Volume
};

function randHex(len){ 
  return Array.from({length:len},_=>Math.floor(Math.random()*16).toString(16)).join(''); 
}
function randNum(min,max,dec=2){ 
  return (Math.random()*(max-min)+min).toFixed(dec); 
}
function randTimeWithin(hours){
  const past = Date.now() - Math.random()*hours*3600*1000;
  return new Date(past).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
}

// animateValue: count from start→end and “pop” as it updates
function animateValue(el, start, end, duration = 600, formatFn) {
  let startTime = null;
  function step(ts) {
    if (!startTime) startTime = ts;
    const progress = Math.min((ts - startTime) / duration, 1);
    const current = start + (end - start) * progress;
    el.innerText = formatFn
      ? formatFn(current)
      : Math.floor(current).toLocaleString();
    // pop effect
    const scale = 1 + 0.2 * (1 - Math.abs(progress - 0.5) * 2);
    el.style.transform = `scale(${scale})`;
    if (progress < 1) {
      requestAnimationFrame(step);
    } else {
      el.style.transform = '';
    }
  }
  requestAnimationFrame(step);
}

// Top stats (flashes once at load + every interval)
function updateStats(){
  // bump ranges (e.g. 0.1%–0.5% of current)
  const txPctMin  = 0.001, txPctMax  = 0.005;
  const volPctMin = 0.002, volPctMax = 0.006;

  // — TOTAL PAYMENTS —
  const txEl = document.getElementById('statTxCount');
  const oldTx = statsAcc.totalTx;
  const newTx = oldTx + oldTx * (Math.random() * (txPctMax - txPctMin) + txPctMin);
  statsAcc.totalTx = newTx;
  animateValue(txEl, oldTx, newTx, 800);

  // — TOTAL VOLUME —
  const volEl = document.getElementById('statVolume');
  const oldVol = statsAcc.totalVol;
  const newVol = oldVol + oldVol * (Math.random() * (volPctMax - volPctMin) + volPctMin);
  statsAcc.totalVol = newVol;
  animateValue(volEl, oldVol, newVol, 800, v => `$${Math.round(v).toLocaleString()}`);

  // — SUCCESS / FAILURE (keep these random or accumulate similarly) —
  const sucEl  = document.getElementById('statSuccess');
  const newSuc = parseFloat(randNum(90,99));
  const oldSuc = parseFloat(sucEl.innerText) || 0;
  animateValue(sucEl, oldSuc, newSuc, 800, v => `${v.toFixed(2)}%`);

  const failEl  = document.getElementById('statFailure');
  const newFail = parseFloat(randNum(1,10));
  const oldFail = parseFloat(failEl.innerText) || 0;
  animateValue(failEl, oldFail, newFail, 800, v => `${v.toFixed(2)}%`);
}

// 14-day overview by payment method
function updateOverview(){
  // how much “new volume” to add each tick (e.g. 0.2%–0.5% of current)
  const bumpPctMin = 0.02;
  const bumpPctMax = 0.05;

  Object.entries(overviewAcc).forEach(([id, current]) => {
    // compute a random bump
    const pctBump = Math.random() * (bumpPctMax - bumpPctMin) + bumpPctMin;
    const bump    = current * pctBump;
    const next    = current + bump;
    overviewAcc[id] = next;                // update state

    // animate from old to new
    const el  = document.getElementById(id);
    const old = parseFloat(el.innerText.replace(/[$,]/g,'')) || current;
    animateValue(el, old, next, 800, v => `$${Math.round(v).toLocaleString()}`);
  });
}

// Settlement Batches with pop-in
function seedBatches(count = 12) {
  const c       = document.getElementById('batchesContainer');
  const methods = ['Visa','Mastercard','PayPal','Stripe','AMEX'];
  c.innerHTML   = ''; // clear old cards

  for (let i = 0; i < count; i++) {
    const id   = `BATCH-${randHex(6)}`.toUpperCase();
    const m    = methods[i % methods.length];
    const cnt  = Math.floor(randNum(1000,8000,0)).toLocaleString();
    const vol  = randNum(5e4,3e5);
    const ago  = randTimeWithin(1) + ' (UTC+8)';
    const card = document.createElement('div');
    card.className = 'block-card';

    // 1) set the “before” state
    card.style.transform = 'scale(0.5)';
    card.style.opacity   = '0';
    card.innerHTML = `
      <h4>${id}</h4>
      <p>Method: ${m}</p>
      <p>Count: ${cnt}</p>
      <p>Volume: $${vol}</p>
      <p>${ago}</p>`;

    // 2) add to DOM
    c.appendChild(card);

    // 3) force a frame, then flip to the “after” state so the transition runs
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        card.style.transform = 'scale(1)';
        card.style.opacity   = '1';
      });
    });
  }
}

// Recent Payments
function randomMerchant(){
  const structuredCodes = [
    `Mx-${randHex(4).toUpperCase()}`,
    `MP-${randHex(4).toUpperCase()}`,
    `MNPay-${randHex(3).toUpperCase()}${Math.floor(Math.random()*1000)}`,
    `Moon-${randHex(2).toUpperCase()}${Math.floor(Math.random()*100)}`
  ];
  const fullNames = [
    'Fastlane Commerce', 'NovaPay Systems', 'ByteHub Inc.', 'QwikMart Global',
    'ZenithPay', 'Hyperflow', 'CloudVend', 'CypherBank', 'Alturex Solutions',
    'Mooneta', 'MPay Logic', 'MNP Solutions', 'Moonify', 'PayNova'
  ];
  return Math.random() > 0.5
    ? structuredCodes[Math.floor(Math.random() * structuredCodes.length)]
    : fullNames[Math.floor(Math.random() * fullNames.length)];
}

function addPayment() {
  const tb      = document.getElementById('txTable');
  const methods = ['Visa','Mastercard','PayPal','Stripe','AMEX'];
  const status  = (Math.random()>0.9) ? 'Failed' : 'Paid';
  const tr      = document.createElement('tr');

  // 1) start slightly up + invisible
  tr.style.transform = 'translateY(-10px)';
  tr.style.opacity   = '0';

  // 2) fill with content
  const merchant = randomMerchant();
  tr.innerHTML = `
    <td>${randHex(8)}</td>
    <td>${methods[Math.floor(Math.random()*methods.length)]}</td>
    <td>${merchant}</td>
    <td>AC-${randHex(6)}</td>
    <td>$${randNum(10,500)}</td>
    <td class="status-${status.toLowerCase()}">${status}</td>`;

  // 3) add to DOM
  tb.appendChild(tr);

  // 4) double-rAF to kick off the transition
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      tr.style.transform = '';
      tr.style.opacity   = '1';
    });
  });
}

// And ensure your seeding wrappers call these:
function seedPayments(count = 20) {
  const tb = document.getElementById('txTable');
  tb.innerHTML = '';  // clear out old rows
  for (let i = 0; i < count; i++) addPayment();
}

// Top Methods by Volume
function seedMethods(){
  const tb      = document.getElementById('methodsTable');
  const methods = ['Visa','Mastercard','PayPal','Stripe','AMEX'];
  methods.forEach((m,i)=>{
    const vol = `$${randNum(1e5,5e5)}`;
    const txs = Math.floor(randNum(1000,8000,0)).toLocaleString();
    const tr  = document.createElement('tr');
    tr.innerHTML = `<td>${i+1}</td><td>${m}</td><td>${vol}</td><td>${txs}</td>`;
    tb.appendChild(tr);
  });
}

// Refresh functions
function refreshBatches(){
  document.getElementById('batchesContainer').innerHTML = '';
  seedBatches(12);
}
function refreshPayments(){
  document.getElementById('txTable').innerHTML = '';
  seedPayments(20);
}
function refreshMethods(){
  document.getElementById('methodsTable').innerHTML = '';
  seedMethods();
}

// INITIALIZE EVERYTHING
document.addEventListener('DOMContentLoaded', ()=>{
    updateStats();
    updateOverview();
    
    seedBatches(12);
    seedMethods();
    seedPayments(20);
    
    setInterval(updateStats,      10000);
    setInterval(updateOverview,   5000);
    setInterval(refreshBatches,   3000);
    setInterval(refreshMethods,   5000);
    setInterval(refreshPayments,  2000);
    
    initRealtimeChart();
    setInterval(updateRealtimeChart, 3000);
  
    const toggle = document.querySelector('.nav-toggle');
    const menu   = document.querySelector('nav ul');
    toggle.addEventListener('click', () => {
      menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
    });
});

// === Real-time Volume Chart ===
let realtimeChart;
let realtimeData = {
  labels: ['Visa', 'Mastercard', 'PayPal', 'Stripe', 'AMEX'],
  datasets: [{
    label: 'Processing Volume (USD)',
    data: [randNum(100000, 300000), randNum(80000, 200000), randNum(60000, 150000), randNum(70000, 180000), randNum(40000, 120000)],
    backgroundColor: '#0070ba80', // transparent blue
    borderColor: '#0070ba',
    borderWidth: 1
  }]
};

function initRealtimeChart() {
  const ctx = document.getElementById('realtimeVolumeChart').getContext('2d');
  realtimeChart = new Chart(ctx, {
    type: 'bar',
    data: realtimeData,
    options: {
      animation: {
        duration: 600
      },
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: value => `$${(value / 1000).toFixed(0)}k`
          }
        }
      },
      plugins: {
        legend: { display: false }
      }
    }
  });
}

function updateRealtimeChart() {
  // update values with new random bumps
  realtimeData.datasets[0].data = realtimeData.datasets[0].data.map(v => {
    const val = parseFloat(v);
    const bump = val * (Math.random() * 0.03 + 0.01); // 1% to 4%
    return Math.round(val + bump);
  });
  realtimeChart.update();
}