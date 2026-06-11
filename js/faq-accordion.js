(function () {
  'use strict';
  document.querySelectorAll('.faq-section .faq-question').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = btn.closest('.faq-item');
      var isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-section .faq-item.open').forEach(function (el) {
        el.classList.remove('open');
      });
      if (!isOpen) {
        item.classList.add('open');
      }
    });
  });
})();
