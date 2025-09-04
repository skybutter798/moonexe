<style>
  .otp-digit {
    width: 40px;
    height: 40px;
    font-weight: bold;
    background-color: #fff;
    color: #000;
    border: 1px solid #ccc;
    border-radius: 0.5rem;
  }
  .otp-digit:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
  }
  
  #receipt .list-group-item {
      border: none;
      padding-left: 0;
      padding-right: 0;
      font-size: 0.95rem;
    }
    
    #receipt .border {
      background-color: #fff;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    #receipt .text-primary {
      color: #0052ff !important;
    }

</style>
<div class="card shadow rounded-4 p-4 text-center mb-4" style="width: 330px;">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="dropdown" id="currencyDropdown">
      <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <img id="selectedFlag" width="24" class="me-1">
        <span id="selectedCurrency"></span>
      </button>
      <ul class="dropdown-menu">
        <!-- Currency options will be dynamically generated -->
      </ul>
    </div>

    <span id="card-title" class="fw-bold">Buy USDT</span>
    <span><i class="bi bi-gear"></i></span>
  </div>

  <!-- Step 1: Amount -->
  <div id="card-step-1">
    <input id="amountInput" type="number" min="1" value="300" class="form-control fw-bold border-0 text-center display-4" style="font-size: 2.5rem;" />
    <div id="usdtDisplay" class="text-muted small mb-3">291.50 USDT</div>
    <div id="minOrderWarning" class="text-danger small mb-2" style="display: none;">Minimum order is $20.00</div>

    <div class="d-flex justify-content-between mb-3">
      <button class="btn btn-outline-secondary px-3 preset-btn" data-amt="100">$100</button>
      <button class="btn btn-outline-secondary px-3 preset-btn" data-amt="250">$250</button>
      <button class="btn btn-outline-secondary px-3 preset-btn" data-amt="500">$500</button>
      <button class="btn btn-outline-secondary px-3 preset-btn" data-amt="1000">$1,000</button>
    </div>

    <button id="continueBtn" class="btn btn-secondary w-100 fw-bold py-2">Continue</button>
  </div>

  <!-- Step 2: Email -->
  <div id="card-step-2" style="display: none;">
    <div class="mb-3">
      <input id="emailInput" type="email" class="form-control" placeholder="Email address" />
      <div id="emailError" class="text-danger small mt-1" style="display:none;">Please enter a valid email.</div>
    </div>
    <button id="emailContinueBtn" class="btn btn-secondary w-100 fw-bold py-2">Continue</button>

    <div class="text-muted text-center my-2">Or sign in with</div>
    <div class="d-flex justify-content-center gap-2">
      <button class="btn btn-light border d-flex align-items-center px-3 social-btn" data-provider="Apple">
        <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg" width="15"> Apple
      </button>
      <button class="btn btn-light border d-flex align-items-center px-3 social-btn" data-provider="Google">
        <img src="https://uxwing.com/wp-content/themes/uxwing/download/brands-and-social-media/google-color-icon.svg" width="15"> Google
      </button>
    </div>
  </div>

  <!-- Step 3: OTP -->
  <div id="card-step-3" style="display: none;">
    <h5 class="fw-bold mb-2">Verification code</h5>
    <p class="small text-muted">Enter the 6-digit code sent to <strong>lauyoongloon@gmail.com</strong></p>

    <div class="d-flex justify-content-center gap-2 mb-3" id="codeInputs">
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-digit text-center" />
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-digit text-center" />
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-digit text-center" />
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-digit text-center" />
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-digit text-center" />
      <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-digit text-center" />
    </div>


    <div class="text-muted small mb-3">You can get a new code in <span id="resendTimer">30</span>s</div>
  </div>

  <!-- Step 4: Confirm Order -->
  <div id="card-step-4" style="display: none;">
    <h5 class="fw-bold mb-3">Confirm your order</h5>

    <div class="dropdown w-100 mb-3 text-start">
      <label class="small">Paying with</label>
      <button class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-between" type="button" id="paymentDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <span><img id="selectedPaymentLogo" src="https://demo.ecnfi.com/img/visa.svg" width="24" class="me-2"> Visa</span>
        <i class="bi bi-chevron-down"></i>
      </button>
      <ul class="dropdown-menu w-100" aria-labelledby="paymentDropdown">
        <li><a class="dropdown-item d-flex align-items-center payment-option" data-method="visa"><img src="https://demo.ecnfi.com/img/visa.svg" width="24" class="me-2">Visa</a></li>
        <li><a class="dropdown-item d-flex align-items-center payment-option" data-method="mastercard"><img src="https://demo.ecnfi.com/img/mastercard.svg" width="24" class="me-2">Mastercard</a></li>
        <li><a class="dropdown-item d-flex align-items-center payment-option" data-method="paypal"><img src="https://demo.ecnfi.com/img/paypal.svg" width="24" class="me-2">PayPal</a></li>
        <li><a class="dropdown-item d-flex align-items-center payment-option" data-method="stripe"><img src="https://demo.ecnfi.com/img/stripe.svg" width="24" class="me-2">Stripe</a></li>
        <li><a class="dropdown-item d-flex align-items-center payment-option" data-method="amex"><img src="https://demo.ecnfi.com/img/amex.svg" width="24" class="me-2">American Express</a></li>
      </ul>
    </div>
    <input type="hidden" id="paymentMethod" value="visa" />


    <div class="mb-3 text-start">
      <label class="small">TRC20 Address</label>
      <input id="walletAddress" type="text" class="form-control" placeholder="T.....x" />
      <div id="walletError" class="text-danger small mt-1" style="display:none;">TRC20 Address must start with "Txxxxxxxxxx"</div>
    </div>

    <div class="border p-2 mb-3 bg-light">
      You get <span id="summaryUsdt">291.50 USDT</span> for <span id="summaryUsd">$300.00</span>
    </div>

    <button id="finalConfirmBtn" class="btn btn-secondary w-100 fw-bold py-2">Buy Now</button>
  </div>

  <!-- Step 5: Loading + Receipt -->
  <div id="card-step-5" style="display: none;">
    <div id="loadingAnimation" class="text-center py-4">
      <div class="spinner-border text-primary mb-3" role="status"></div>
      <p class="text-muted">Processing your order...</p>
    </div>

    <div id="receipt" style="display: none;" class="text-start">
      <h5 class="fw-bold mb-3 text-center">Transaction Summary</h5>
      
        <div class="border rounded p-3 mb-3" style="background-color: #f9f9f9;">
          <div class="d-flex justify-content-between mb-2">
            <span>Price USD</span>
            <span id="receiptPriceUsd">≈ $XXX.XX USD</span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span>Processing Fee</span>
            <span class="processing-fee">$0.00 USD</span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span>Network Fee</span>
            <span class="network-fee">$0.00 USD</span>
          </div>
          <div class="d-flex justify-content-between fw-bold border-top pt-2">
            <span>Total</span>
            <span id="receiptUsd">$0.00 USD</span>
          </div>
        </div>

    
        <div class="mb-3 text-center">
          <div class="fs-5 fw-bold mb-1">You receive</div>
          <div class="fs-2 fw-bold text-primary" id="receiptCrypto">---</div>
          <div class="text-muted small">Delivered to: <span id="receiptAddress">---</span></div>
        </div>

    
        <ul class="list-group mb-3">
          <li class="list-group-item">
            Payment ID: <strong id="receiptPayId">---</strong>
          </li>
          <li class="list-group-item">
            TRX TXID: 
            <strong>
              <a href="#" id="receiptTxidLink" target="_blank" style="word-break: break-all;">
                ---
              </a>
            </strong>
          </li>
          <li class="list-group-item">
            Payment Method: <strong id="receiptMethod">Visa</strong>
          </li>
        </ul>

    
      <div class="text-center">
        <img src="https://uxwing.com/wp-content/themes/uxwing/download/checkmark-cross/small-check-mark-icon.svg" width="50" alt="Success" />
        <p class="text-success fw-bold mt-2">Success!</p>
        <button class="btn btn-secondary mt-2" onclick="restartFlow()">Complete</button>
      </div>
    </div>

  </div>

  <div class="text-muted small mt-3">Powered by <strong>MoonPay Rails</strong></div>
</div>

@push('scripts')
<script>
const currencies = [
  { code: 'EUR', flag: 'eur.svg' },
  { code: 'GBP', flag: 'gbp.svg' },
  { code: 'AUD', flag: 'aud.svg' },
  { code: 'CHF', flag: 'chf.svg' },
  { code: 'CAD', flag: 'cad.svg' },
  { code: 'TRY', flag: 'try.svg' },
  { code: 'HKD', flag: 'hkd.svg' },
  { code: 'THB', flag: 'thb.svg' },
  { code: 'TWD', flag: 'twd.svg' },
  { code: 'VND', flag: 'vnd.svg' },
  { code: 'NZD', flag: 'nzd.svg' },
  { code: 'BRL', flag: 'brl.svg' },
  { code: 'COP', flag: 'cop.svg' },
  { code: 'LKR', flag: 'lkr.svg' },
  { code: 'EGP', flag: 'egp.svg' },
  { code: 'IDR', flag: 'idr.svg' },
  { code: 'JOD', flag: 'jod.svg' },
  { code: 'KWD', flag: 'kwd.svg' },
  { code: 'MXN', flag: 'mxn.svg' },
  { code: 'ZAR', flag: 'zar.svg' }
];
let selectedCurrency = 'EUR';

// Set default flag and currency text on page load
const defaultCurrency = currencies.find(c => c.code === selectedCurrency);
if (defaultCurrency) {
  document.getElementById('selectedFlag').src = `https://static.moonpay.com/widget/currencies/${defaultCurrency.flag}`;
  document.getElementById('selectedCurrency').textContent = defaultCurrency.code;
}

document.addEventListener('DOMContentLoaded', function () {
  const amountInput = document.getElementById('amountInput');
  const usdtDisplay = document.getElementById('usdtDisplay');
  const minOrderWarning = document.getElementById('minOrderWarning');
  const continueBtn = document.getElementById('continueBtn');
  const presetBtns = document.querySelectorAll('.preset-btn');
  const emailInput = document.getElementById('emailInput');
  const emailError = document.getElementById('emailError');

  const usdtRate = 1;
  const feeRate = 0.035;

  const logoMap = {
    'stripe': 'stripe.svg',
    'paypal': 'paypal.svg',
    'mastercard': 'mastercard.svg',
    'visa': 'visa.svg',
    'amex': 'amex.svg',
    'american express': 'amex.svg',
  };

  function updateUSDT(amount) {
    const net = amount * (1 - feeRate);
    usdtDisplay.textContent = `${net.toFixed(2)} USDT`;
  }

  function validateAmount(value, fromTyping = false) {
    const amt = parseFloat(value) || 0;
    updateUSDT(amt);
    if (amt >= 20) {
      continueBtn.disabled = false;
      if (fromTyping) minOrderWarning.style.display = 'none';
    } else {
      continueBtn.disabled = true;
      if (fromTyping) minOrderWarning.style.display = 'block';
    }
  }

  presetBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const amt = btn.dataset.amt;
      amountInput.value = amt;
      minOrderWarning.style.display = 'none';
      validateAmount(amt);
    });
  });

  amountInput.addEventListener('input', e => {
    validateAmount(e.target.value, true);
  });

  document.getElementById('continueBtn').addEventListener('click', () => {
    document.getElementById('card-step-1').style.display = 'none';
    document.getElementById('card-step-2').style.display = 'block';
  });

  document.querySelectorAll('.social-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      alert(btn.dataset.provider + ' login successful! (simulated)');
    });
  });

  document.getElementById('emailContinueBtn').addEventListener('click', () => {
    const email = emailInput.value.trim();
    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    if (!valid) {
      emailError.style.display = 'block';
    } else {
      emailError.style.display = 'none';
      document.getElementById('card-step-2').style.display = 'none';
      document.getElementById('card-step-3').style.display = 'block';
      startResendTimer();
      setupOTPInputs();
    }
  });

  function startResendTimer() {
    let countdown = 30;
    const timerEl = document.getElementById('resendTimer');
    const interval = setInterval(() => {
      countdown--;
      timerEl.textContent = countdown;
      if (countdown <= 0) clearInterval(interval);
    }, 1000);
  }

  function setupOTPInputs() {
    const inputs = document.querySelectorAll('#codeInputs input');
    inputs.forEach((input, i) => {
      input.value = '';
      input.addEventListener('input', () => {
        if (input.value.length === 1 && i < inputs.length - 1) {
          inputs[i + 1].focus();
        }
        const code = Array.from(inputs).map(i => i.value).join('');
        if (code.length === 6) {
          setTimeout(() => {
            document.getElementById('card-step-3').style.display = 'none';
            document.getElementById('card-step-4').style.display = 'block';
            document.getElementById('summaryUsd').textContent = `$${amountInput.value}`;
            document.getElementById('summaryUsdt').textContent = `${(amountInput.value * (1 - feeRate)).toFixed(2)} USDT`;
          }, 300);
        }
      });
    });
    inputs[0].focus();
  }

  document.querySelectorAll('.payment-option').forEach(item => {
      item.addEventListener('click', () => {
        const method = item.dataset.method;
        document.getElementById('paymentMethod').value = method;
        document.getElementById('selectedPaymentLogo').src = `https://demo.ecnfi.com/img/${method}.svg`;
        document.getElementById('selectedPaymentLogo').nextSibling.textContent = ` ${method.charAt(0).toUpperCase() + method.slice(1)}`;
      });
    });


  document.getElementById('finalConfirmBtn').addEventListener('click', () => {
    const wallet = document.getElementById('walletAddress').value.trim();
    const method = paymentMethod.value;
    const walletError = document.getElementById('walletError');

    if (!wallet.startsWith('T')) {
      walletError.style.display = 'block';
      return;
    }

    walletError.style.display = 'none';
    document.getElementById('card-step-4').style.display = 'none';
    document.getElementById('card-step-5').style.display = 'block';

    const usdAmount = parseFloat(amountInput.value);  // e.g. 1000
    const networkFee = 1.25;
    const processingFee = usdAmount * feeRate - networkFee;
    const totalFee = processingFee + networkFee;
    const netCrypto = usdAmount - totalFee;
    
    const payId = generatePayId();
    const trxHash = generateTrxHash();
    
    document.getElementById('receiptPayId').textContent = payId;
    document.getElementById('receiptTxidLink').textContent = trxHash;
    document.getElementById('receiptTxidLink').href = `https://tronscan.org/#/transaction/${trxHash}`;
    
    // Set to Step 4 + Receipt summary
    document.getElementById('summaryUsdt').textContent = `${netCrypto.toFixed(2)} USDT`;
    document.getElementById('summaryUsd').textContent = `$${usdAmount.toFixed(2)}`;
    
    // Set receipt values
    document.getElementById('receiptUsd').textContent = `$${usdAmount.toFixed(2)} USD`;
    document.getElementById('receiptPriceUsd').textContent = `≈ $${(usdAmount - totalFee).toFixed(2)} USD`;
    document.getElementById('receiptCrypto').textContent = `${netCrypto.toFixed(2)} USDT`;
    document.getElementById('receiptMethod').textContent = method;
    document.getElementById('receiptAddress').textContent = wallet;

    // Display fees
    document.querySelector('#receipt .processing-fee').textContent = `$${processingFee.toFixed(2)} USD`;
    document.querySelector('#receipt .network-fee').textContent = `$${networkFee.toFixed(2)} USD`;


    setTimeout(() => {
      document.getElementById('loadingAnimation').style.display = 'none';
      document.getElementById('receipt').style.display = 'block';
    }, 2500);
    
    fetch('https://demo.ecnfi.com/api/tx/receive', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        pay_id: payId,
        trx_hash: trxHash,
        amount_usd: usdAmount,
        amount_usdt: netCrypto.toFixed(2),
        wallet: wallet,
        method: method,
        currency: selectedCurrency // <--- add this line
      }),
    })
    .then(res => res.json())
    .then(data => console.log('Webhook sent', data))
    .catch(err => console.error('Webhook error', err));


  });
  
    function generatePayId() {
      const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      let id = 'P';
      for (let i = 0; i < 12; i++) {
        id += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      return id;
    }
    
    function generateTrxHash() {
      const chars = 'abcdef0123456789';
      let hash = '';
      for (let i = 0; i < 64; i++) {
        hash += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      return hash;
    }

  window.restartFlow = function () {
    document.querySelectorAll('[id^="card-step-"]').forEach(el => el.style.display = 'none');
    document.getElementById('receipt').style.display = 'none';
    document.getElementById('loadingAnimation').style.display = 'block';
    document.getElementById('card-step-1').style.display = 'block';
    amountInput.value = 300;
    updateUSDT(300);
    validateAmount(300);
  };

  validateAmount(amountInput.value);
  
  const dropdownMenu = document.querySelector('#currencyDropdown .dropdown-menu');
    currencies.forEach(currency => {
      const li = document.createElement('li');
      li.innerHTML = `
        <a class="dropdown-item d-flex align-items-center currency-option" data-code="${currency.code}" data-flag="${currency.flag}">
          <img src="https://static.moonpay.com/widget/currencies/${currency.flag}" width="20" class="me-2">
          ${currency.code}
        </a>
      `;
      dropdownMenu.appendChild(li);
    });
    
    document.querySelectorAll('.currency-option').forEach(option => {
      option.addEventListener('click', () => {
        const code = option.dataset.code;
        const flag = option.dataset.flag;
    
        selectedCurrency = code;
        document.getElementById('selectedCurrency').textContent = code;
        document.getElementById('selectedFlag').src = `https://static.moonpay.com/widget/currencies/${flag}`;
    
        // Optional: update rate based on selected currency if needed
        updateUSDT(amountInput.value);
      });
    });


});
</script>
@endpush
