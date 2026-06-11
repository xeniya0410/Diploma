(function () {
  'use strict';

  function qs(sel) {
    return document.querySelector(sel);
  }

  function qsa(sel) {
    return document.querySelectorAll(sel);
  }

  window.toggleSidebar = function () {
    var sb = qs('#sidebar');
    var ov = qs('#sidebarOverlay');
    if (sb) sb.classList.toggle('open');
    if (ov) ov.classList.toggle('visible');
  };

  window.openModal = function (name) {
    var modal = qs('#modal-' + name);
    var backdrop = qs('#modal-backdrop');
    if (modal) modal.classList.add('open');
    if (backdrop) backdrop.classList.add('open');
    document.body.style.overflow = 'hidden';
    if (typeof window.syncAuthFormLang === 'function') {
      window.syncAuthFormLang();
    }
    if (typeof window.applyFinkidI18n === 'function') {
      window.applyFinkidI18n();
    }
  };

  window.closeModal = function (name) {
    var modal = qs('#modal-' + name);
    if (modal) modal.classList.remove('open');
    var anyOpen = document.querySelector('.mo.open');
    if (!anyOpen) {
      var backdrop = qs('#modal-backdrop');
      if (backdrop) backdrop.classList.remove('open');
      document.body.style.overflow = '';
    }
  };

  window.closeAllModals = function () {
    qsa('.mo.open').forEach(function (m) { m.classList.remove('open'); });
    var backdrop = qs('#modal-backdrop');
    if (backdrop) backdrop.classList.remove('open');
    document.body.style.overflow = '';
  };

  window.switchModal = function (name) {
    closeAllModals();
    openModal(name);
  };

  window.showToast = function (msg) {
    var t = qs('#toast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function () { t.classList.remove('show'); }, 3500);
  };

  function updateRolePanels(role) {
    var teacherX = qs('#teacher-x');
    var adminX = qs('#admin-x');
    var ageGroup = qs('#age-group');
    var certNote = qs('#cert-note-student');
    var emailHint = qs('#email-hint-student');
    if (teacherX) teacherX.classList.toggle('show', role === 'teacher');
    if (adminX) adminX.classList.toggle('show', role === 'admin');
    if (ageGroup) ageGroup.style.display = role === 'student' ? '' : 'none';
    if (certNote) certNote.style.display = role === 'student' ? '' : 'none';
    if (emailHint) emailHint.style.display = role === 'student' ? '' : 'none';
  }

  function bindRoleTabs() {
    var roleCards = qs('#auth-role-cards');
    var roleInput = qs('#register-role');
    if (!roleCards || !roleInput) return;

    roleCards.addEventListener('click', function (e) {
      var card = e.target.closest('.rtab');
      if (!card) return;
      qsa('#auth-role-cards .rtab').forEach(function (c) { c.classList.remove('on'); });
      card.classList.add('on');
      var role = card.getAttribute('data-role') || 'student';
      roleInput.value = role;
      updateRolePanels(role);
    });

    updateRolePanels(roleInput.value || 'student');

    var form = qs('#register-form');
    if (form) {
      form.addEventListener('submit', function () {
        var onCard = roleCards.querySelector('.rtab.on');
        if (onCard) {
          roleInput.value = onCard.getAttribute('data-role') || 'student';
          updateRolePanels(roleInput.value);
        }
      });
    }
  }

  function syncAge() {
    var sel = qs('#ra');
    var hidden = qs('#age-hidden');
    if (sel && hidden) {
      sel.addEventListener('change', function () {
        hidden.value = sel.value;
        hidden.name = 'age';
      });
      hidden.value = sel.value;
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var params = new URLSearchParams(window.location.search);
    var auth = params.get('auth');
    if (auth === 'login' || auth === 'register' || auth === 'pending') {
      openModal(auth === 'pending' ? 'pending' : auth);
    }
    bindRoleTabs();
    syncAge();
  });
})();
