document.addEventListener('DOMContentLoaded', function() {
  const ann = window.announcement;
  if (!ann) return;

  const key = `announcement_${ann.id}_shown`;
  if (!localStorage.getItem(key)) {
    // inject announcement content into your modal’s DOM if needed
    document.getElementById('announcementModalLabel').textContent = ann.name;
    document.querySelector('#announcementModal .modal-body').innerHTML = ann.content.replace(/\n/g,'<br>');
    
    new bootstrap.Modal(document.getElementById('announcementModal')).show();
    localStorage.setItem(key, '5');
  }
});
