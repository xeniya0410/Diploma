(function () {
  'use strict';
  document.body.classList.add('test-guard-active');
  document.addEventListener('contextmenu', function (e) { e.preventDefault(); });
  document.addEventListener('copy', function (e) { e.preventDefault(); });
  document.addEventListener('cut', function (e) { e.preventDefault(); });
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'C' || e.key === 'a' || e.key === 'A' || e.key === 'u')) {
      e.preventDefault();
    }
  });
})();
