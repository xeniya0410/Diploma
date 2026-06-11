(function () {
  'use strict';

  var root = window.FINKID_I18N;
  if (!root || !root.packs) {
    return;
  }

  function t(key) {
    var pack = root.packs[root.lang] || root.packs.ru || {};
    return pack[key] != null ? pack[key] : key;
  }

  function parseArgs(el) {
    var raw = el.getAttribute('data-i18n-args');
    if (!raw) {
      return [];
    }
    try {
      var parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (err) {
      return [];
    }
  }

  function formatMsg(template, args) {
    if (!args.length) {
      return template;
    }
    var i = 0;
    return template.replace(/%([dfs])/g, function (match) {
      if (i >= args.length) {
        return match;
      }
      return String(args[i++]);
    });
  }

  function applyTo(el) {
    var key = el.getAttribute('data-i18n');
    if (!key) {
      return;
    }
    var val = formatMsg(t(key), parseArgs(el));
    var prefix = el.getAttribute('data-i18n-prefix') || '';
    var suffix = el.getAttribute('data-i18n-suffix') || '';
    if (el.hasAttribute('data-i18n-placeholder')) {
      el.placeholder = val;
      return;
    }
    if (el.hasAttribute('data-i18n-aria')) {
      el.setAttribute('aria-label', val);
      return;
    }
    if (el.hasAttribute('data-i18n-html')) {
      el.innerHTML = prefix + val + suffix;
      return;
    }
    el.textContent = prefix + val + suffix;
  }

  function applyCourseMeta() {
    if (!root.courseMeta) {
      return;
    }
    document.querySelectorAll('[data-course-slug]').forEach(function (el) {
      var slug = el.getAttribute('data-course-slug');
      var field = el.getAttribute('data-course-field') || 'title';
      var byLang = root.courseMeta[slug];
      if (!byLang) {
        return;
      }
      var row = byLang[root.lang] || byLang.ru;
      if (row && row[field] != null) {
        var text = row[field];
        if (field === 'desc') {
          var maxAttr = el.getAttribute('data-course-max');
          var maxLen = maxAttr ? parseInt(maxAttr, 10) : 0;
          if (!isNaN(maxLen) && maxLen > 0 && text.length > maxLen) {
            text = text.slice(0, maxLen);
          }
        }
        el.textContent = text;
      }
    });
  }

  function applyWaPrefillLinks() {
    var base = document.body.getAttribute('data-wa-url');
    if (!base) {
      return;
    }
    document.querySelectorAll('[data-wa-prefill-key]').forEach(function (a) {
      var key = a.getAttribute('data-wa-prefill-key');
      if (key) {
        a.href = base + '?text=' + encodeURIComponent(t(key));
      }
    });
  }

  window.finkidT = t;

  function syncAuthFormLang() {
    if (!root || !root.lang) {
      return;
    }
    document.querySelectorAll('.auth-lang-field, input[name="lang"]').forEach(function (inp) {
      inp.value = root.lang;
    });
  }

  window.syncAuthFormLang = syncAuthFormLang;

  window.applyFinkidI18n = function () {
    document.querySelectorAll('[data-i18n]').forEach(applyTo);
    applyCourseMeta();
    applyWaPrefillLinks();
    var htmlLang = root.lang === 'kz' ? 'kk' : root.lang;
    document.documentElement.setAttribute('lang', htmlLang);
    var titleKey = document.body.getAttribute('data-title-i18n');
    if (titleKey) {
      document.title = t(titleKey);
    }
    document.querySelectorAll('.lang-switcher__btn').forEach(function (btn) {
      var on = btn.getAttribute('data-lang') === root.lang;
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    var wa = document.body.getAttribute('data-wa-label');
    if (wa) {
      document.body.setAttribute('data-wa-label', t('ai.whatsapp_btn'));
    }
    syncAuthFormLang();
    if (window.FinkidAdminDashboard && typeof window.FinkidAdminDashboard.refreshI18n === 'function') {
      window.FinkidAdminDashboard.refreshI18n();
    }
  };

  function needsReloadAfterLangChange() {
    return (
      document.getElementById('course-v4-app') ||
      window.SIM_MONTHS ||
      document.querySelector('.panel-page, .page-complete, .course-page, .page-course-v4-final')
    );
  }

  window.setFinkidLang = function (code) {
    if (!root.packs[code] || root.lang === code) {
      return;
    }
    root.lang = code;
    window.applyFinkidI18n();
    var url = document.body.getAttribute('data-set-lang-url');
    if (url) {
      var fd = new FormData();
      fd.append('lang', code);
      fetch(url, { method: 'POST', body: fd })
        .then(function () {
          if (needsReloadAfterLangChange()) {
            window.location.reload();
          }
        })
        .catch(function () {
          if (needsReloadAfterLangChange()) {
            window.location.reload();
          }
        });
      return;
    }
    if (needsReloadAfterLangChange()) {
      window.location.reload();
    }
  };

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.lang-switcher__btn');
    if (!btn) {
      return;
    }
    e.preventDefault();
    window.setFinkidLang(btn.getAttribute('data-lang'));
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.applyFinkidI18n);
  } else {
    window.applyFinkidI18n();
  }
})();
