// File: public/js/users/dashboard_v2.js
document.addEventListener('DOMContentLoaded', () => {
  // ────────────────────────────────────────────────
  // 1️⃣ Announcement modal (only if no flash)
  // ────────────────────────────────────────────────
  const ann = window.announcement;
  if (ann && !window.hasFlashMessage) {
    const key = `announcement_${ann.id}_shown`;
    if (!localStorage.getItem(key)) {
      // inject into modal
      document.getElementById('announcementModalLabel').textContent = ann.name;
      document.querySelector('#announcementModal .modal-body')
              .innerHTML = ann.content.replace(/\n/g, '<br>');
      new bootstrap.Modal(document.getElementById('announcementModal')).show();
      localStorage.setItem(key, '1');
    }
  }

  // ────────────────────────────────────────────────
  // 2️⃣ Flash toast (success or error)
  // ────────────────────────────────────────────────
  if (window.hasFlashMessage) {
    const toastEl = document.getElementById('flashToast');
    if (toastEl) new bootstrap.Toast(toastEl, { delay: 6000 }).show();
  }

  // ────────────────────────────────────────────────
  // 3️⃣ Top-up form validation
  // ────────────────────────────────────────────────
  const pkgForm = document.querySelector('form.package-form');
  if (pkgForm) {
    pkgForm.addEventListener('submit', e => {
      const val = parseFloat(pkgForm.querySelector('input[name="topup_amount"]').value);
      if (!Number.isInteger(val) || val < 10 || val % 10 !== 0) {
        e.preventDefault();
        alert("Top-up must be an integer ≥ 10 and in multiples of 10.");
      }
    });
  }

  // ────────────────────────────────────────────────
  // 4️⃣ Your existing “Load More” + market-data + charts + everything else...
  //    (copy/paste from your old file here, inside this same DOMContentLoaded)
  // ────────────────────────────────────────────────
  // … rest of your code unchanged …
});
