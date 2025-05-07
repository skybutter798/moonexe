// Utility
function randHex(len){ 
  return Array.from({length:len},_=>Math.floor(Math.random()*16).toString(16)).join(''); 
}
function randNum(min,max,dec=2){ 
  return (Math.random()*(max-min)+min).toFixed(dec); 
}

// Top stats (flashes once at load + every interval)
function updateStats(){
  document.getElementById('statTxCount').innerText = Math.floor(randNum(50000,200000,0)).toLocaleString();
  document.getElementById('statVolume').innerText  = `$${randNum(1e6,5e6)}`;
  document.getElementById('statSuccess').innerText = randNum(90,99) + '%';
  document.getElementById('statFailure').innerText = randNum(1,10) + '%';
}

// 14-day overview by payment method
function updateOverview(){
  document.getElementById('volVisa').innerText    = `$${randNum(2e5,4e5)}`;
  document.getElementById('volMaster').innerText  = `$${randNum(1.5e5,3e5)}`;
  document.getElementById('volPaypal').innerText  = `$${randNum(1e5,2e5)}`;
  document.getElementById('volStripe').innerText  = `$${randNum(1e5,2e5)}`;
}

// Seed a bunch of settlement batches
function seedBatches(count=12){
  const c = document.getElementById('batchesContainer');
  const methods = ['Visa','Mastercard','PayPal','Stripe','AMEX'];
  for(let i=0; i<count; i++){
    const id   = `BATCH-${randHex(6)}`.toUpperCase();
    const m    = methods[i % methods.length];
    const cnt  = Math.floor(randNum(1000,8000,0));
    const vol  = randNum(5e4,3e5);
    const ago  = `${randNum(1,59,0)}m ago`;
    const card = document.createElement('div');
    card.className = 'block-card';
    card.innerHTML = `
      <h4>${id}</h4>
      <p>Method: ${m}</p>
      <p>Count: ${cnt.toLocaleString()}</p>
      <p>Volume: $${vol}</p>
      <p>${ago}</p>`;
    c.appendChild(card);
  }
}

// Add a single payment row
function addPayment(){
  const tb      = document.getElementById('txTable');
  const methods = ['Visa','Mastercard','PayPal','Stripe','AMEX'];
  const status  = (Math.random()>0.9) ? 'Failed' : 'Paid';
  const tr      = document.createElement('tr');
  tr.innerHTML = `
    <td>${randHex(8)}</td>
    <td>${methods[Math.floor(Math.random()*methods.length)]}</td>
    <td>M${randHex(5)}</td>
    <td>C${randHex(5)}</td>
    <td>$${randNum(10,500)}</td>
    <td class="status-${status.toLowerCase()}">${status}</td>`;
  tb.appendChild(tr);
}

// Seed many recent payments
function seedPayments(count=20){
  for(let i=0; i<count; i++) addPayment();
}

// Seed the “Top Methods by Volume” table
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

// INITIALIZE EVERYTHING
document.addEventListener('DOMContentLoaded', ()=>{
  updateStats();
  updateOverview();

  seedBatches(12);    // 12 dummy batches
  seedMethods();      // fill top-methods
  seedPayments(20);   // 20 dummy payments

  // update small stuff on intervals
  setInterval(updateStats,    8000);
  setInterval(updateOverview, 6000);
});
