(function () {
  'use strict';

  function showTab(tab) {
    if (!tab) return;
    document.querySelectorAll('.admin-tabs .atab').forEach(function (b) {
      b.classList.toggle('on', b.getAttribute('data-tab') === tab);
    });
    document.querySelectorAll('.admin-section').forEach(function (s) {
      s.classList.remove('show');
    });
    var el = document.getElementById('tab-' + tab);
    if (el) el.classList.add('show');
  }

  document.querySelectorAll('.admin-tabs .atab').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var tab = btn.getAttribute('data-tab');
      showTab(tab);
      try {
        var url = new URL(window.location.href);
        if (tab === 'dashboard') {
          url.searchParams.delete('tab');
        } else {
          url.searchParams.set('tab', tab);
        }
        window.history.replaceState({}, '', url.toString());
      } catch (e) { /* */ }
    });
  });

  try {
    var initial = new URL(window.location.href).searchParams.get('tab');
    if (initial) showTab(initial);
  } catch (e2) { /* */ }
})();
