(function () {
  'use strict';

  function showAuthError(form, message) {
    if (!form || !message) {
      return;
    }
    var box = form.querySelector('.auth-error-inline');
    if (!box) {
      box = document.createElement('p');
      box.className = 'auth-error auth-error-inline';
      form.insertBefore(box, form.firstChild);
    }
    box.textContent = message;
    box.style.display = 'block';
  }

  function clearAuthError(form) {
    var box = form && form.querySelector('.auth-error-inline');
    if (box) {
      box.style.display = 'none';
    }
  }

  function tr(key, fallback) {
    if (typeof window.finkidT === 'function') {
      return window.finkidT(key);
    }
    return fallback;
  }

  function parseJsonResponse(res) {
    return res.text().then(function (text) {
      var data;
      try {
        data = text ? JSON.parse(text) : {};
      } catch (e) {
        if (text.indexOf('CSRF') !== -1 || text.indexOf('csrf') !== -1 || text.indexOf('безопасности') !== -1) {
          throw new Error('csrf');
        }
        throw new Error('invalid');
      }
      if (!res.ok && !data.message) {
        data.message = tr('forms.err_server', 'Server error').replace('%d', String(res.status));
        data.ok = false;
      }
      return data;
    });
  }

  function syncFormLang(form) {
    var inp = form.querySelector('input[name="lang"]');
    if (inp && window.FINKID_I18N && window.FINKID_I18N.lang) {
      inp.value = window.FINKID_I18N.lang;
    }
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.getAttribute('data-ajax') !== '1') {
      return;
    }
    e.preventDefault();
    clearAuthError(form);
    syncFormLang(form);

    var fd = new FormData(form);
    var btn = form.querySelector('[type="submit"]');
    if (btn) {
      btn.disabled = true;
    }

    fetch(form.action, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json'
      }
    })
      .then(parseJsonResponse)
      .then(function (data) {
        if (data.ok && data.pending) {
          if (typeof closeAllModals === 'function') {
            closeAllModals();
          }
          if (typeof openModal === 'function') {
            openModal('pending');
          }
          return;
        }
        if (data.ok && data.redirect) {
          window.location.href = data.redirect;
          return;
        }
        if (data.error === 'session') {
          var sessionMsg = data.message || tr('auth.err_session', 'Could not save login. Refresh the page (F5).');
          showAuthError(form, sessionMsg);
          if (typeof showToast === 'function') {
            showToast(sessionMsg);
          }
          return;
        }
        if (data.message) {
          showAuthError(form, data.message);
          if (typeof showToast === 'function') {
            showToast(data.message);
          }
        }
      })
      .catch(function (err) {
        var msg =
          err && err.message === 'csrf'
            ? tr('forms.err_csrf', tr('auth.err_csrf', 'Session expired. Refresh the page (F5) and try again.'))
            : tr('forms.err_network', 'Could not submit the form. Check your connection.');
        showAuthError(form, msg);
        if (typeof showToast === 'function') {
          showToast(msg);
        }
      })
      .finally(function () {
        if (btn) {
          btn.disabled = false;
        }
      });
  });
})();
