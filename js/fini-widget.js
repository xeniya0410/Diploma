(function () {
  'use strict';

  var msgsEl = document.getElementById('chatmsgs');
  var panel = document.getElementById('sppanel');
  var input = document.getElementById('chatinp');
  var btn = document.getElementById('spbtn');
  var apiUrl = document.body.getAttribute('data-ai-url') || '';
  var waUrl = document.body.getAttribute('data-wa-url') || '';
  var greeted = false;
  var toggleLock = false;
  var typingEl = null;
  var quickBusy = false;

  function t(key) {
    return window.finkidT ? window.finkidT(key) : key;
  }

  function addMsg(text, type) {
    if (!msgsEl) return;
    var d = document.createElement('div');
    d.className = 'msg ' + (type || 'bot');
    if (type === 'op' && text.indexOf('<a') !== -1) {
      d.innerHTML = text;
    } else {
      d.textContent = text;
    }
    msgsEl.appendChild(d);
    msgsEl.scrollTop = msgsEl.scrollHeight;
  }

  function greet() {
    if (greeted || !msgsEl) return;
    greeted = true;
    addMsg(t('ai.greeting'), 'bot');
  }

  function setOpen(open) {
    if (!panel) return;
    panel.classList.toggle('open', open);
    if (open) {
      greet();
      if (input) {
        try {
          input.focus({ preventScroll: true });
        } catch (err) {
          input.focus();
        }
      }
    }
  }

  window.toggleChat = function () {
    if (!panel || toggleLock) return;
    toggleLock = true;
    setTimeout(function () {
      toggleLock = false;
    }, 350);
    setOpen(!panel.classList.contains('open'));
  };

  function addActionLink(href, label, external) {
    if (!href) return;
    var extra = external ? ' target="_blank" rel="noopener"' : '';
    addMsg('<a href="' + href + '"' + extra + ' class="wa-link">' + label + '</a>', 'op');
  }

  function addWaButton() {
    if (!waUrl) return;
    addActionLink(waUrl, t('ai.whatsapp_btn'), true);
  }

  function botReply(text, whatsapp) {
    addMsg(text, 'bot');
    if (whatsapp) {
      addWaButton();
    }
  }

  window.qmsg = function (text) {
    if (!panel || !panel.classList.contains('open')) {
      setOpen(true);
    }
    if (input) input.value = text;
    window.sendChat();
  };

  var legacyQToTopic = {
    'ai.q_reg': 'reg',
    'ai.q_login': 'reg',
    'ai.q_cert': 'cert',
    'ai.q_support': 'support',
    'ai.q_operator': 'support',
    'ai.q_money': 'money',
    'ai.q_budget': 'budget',
    'ai.q_course': 'money',
    'ai.q_savings': 'savings',
    'ai.q_percent': 'percent'
  };

  function quickButtons() {
    return document.querySelectorAll('.qb[data-topic], .qb[data-q]');
  }

  function resolveTopic(qb) {
    var topic = qb.getAttribute('data-topic');
    if (topic) return topic;
    return legacyQToTopic[qb.getAttribute('data-q') || ''] || null;
  }

  function questionLabel(qKey, topic) {
    var text = t(qKey);
    if (text !== qKey) return text;
    return topic ? t('ai.q_' + topic) : qKey;
  }

  function syncQuickButtonLabels() {
    quickButtons().forEach(function (qb) {
      var topic = resolveTopic(qb);
      if (!topic) return;

      if (!qb.getAttribute('data-topic')) {
        qb.setAttribute('data-topic', topic);
      }

      var i18nKey = 'ai.q_' + topic;
      qb.setAttribute('data-i18n', i18nKey);
      qb.textContent = questionLabel(i18nKey, topic);
    });
  }

  function setQuickButtonsDisabled(disabled) {
    quickButtons().forEach(function (qb) {
      qb.disabled = disabled;
      qb.classList.toggle('is-busy', disabled);
    });
  }

  function showTyping() {
    if (!msgsEl) return;
    hideTyping();
    typingEl = document.createElement('div');
    typingEl.className = 'msg typing';
    typingEl.setAttribute('aria-live', 'polite');
    typingEl.textContent = t('ai.typing');
    msgsEl.appendChild(typingEl);
    msgsEl.scrollTop = msgsEl.scrollHeight;
  }

  function hideTyping() {
    if (typingEl && typingEl.parentNode) {
      typingEl.parentNode.removeChild(typingEl);
    }
    typingEl = null;
  }

  function typingDelay(text) {
    var len = (text || '').length;
    return Math.min(1800, Math.max(700, 500 + len * 12));
  }

  function deliverQuickAnswer(topic, qKey, aKey) {
    addMsg(t(aKey), 'bot');

    if (topic === 'reg' || topic === 'cert') {
      addWaButton();
    } else if (topic === 'support') {
      addWaButton();
      addActionLink(panel.getAttribute('data-support-url') || '', t('ai.support_form_btn'), false);
    }

    logChat(t(qKey), t(aKey), topic === 'reg' || topic === 'cert' || topic === 'support' ? 1 : 0);
    quickBusy = false;
    setQuickButtonsDisabled(false);
  }

  function handleQuickTopic(topic, qKeyOverride) {
    if (quickBusy || !topic) return;
    if (!panel || !panel.classList.contains('open')) {
      setOpen(true);
    }
    var qKey = qKeyOverride || ('ai.q_' + topic);
    var aKey = 'ai.a_' + topic;
    var answerText = t(aKey);

    quickBusy = true;
    setQuickButtonsDisabled(true);
    addMsg(questionLabel(qKey, topic), 'usr');
    showTyping();

    setTimeout(function () {
      hideTyping();
      deliverQuickAnswer(topic, qKey, aKey);
    }, typingDelay(answerText));
  }

  window.sendChat = function () {
    if (!input) return;
    var text = input.value.trim();
    if (!text) return;
    addMsg(text, 'usr');
    input.value = '';

    var operatorKeys = ['оператор', 'operator', 'операторға', 'связаться'];
    var low = text.toLowerCase();
    var isOp = operatorKeys.some(function (k) {
      return low.indexOf(k) !== -1;
    });

    if (isOp) {
      botReply(t('ai.a_support'), true);
      addActionLink(panel ? panel.getAttribute('data-support-url') || '' : '', t('ai.support_form_btn'), false);
      logChat(text, t('ai.a_support'), 1);
      return;
    }

    if (!apiUrl) {
      botReply(t('ai.unknown') || '…', false);
      return;
    }

    var fd = new FormData();
    fd.append('message', text);
    if (window.FINKID_I18N && window.FINKID_I18N.lang) {
      fd.append('lang', window.FINKID_I18N.lang);
    }
    fetch(apiUrl, { method: 'POST', body: fd })
      .then(function (r) {
        if (!r.ok) throw new Error('http');
        return r.json();
      })
      .then(function (data) {
        botReply(data.answer || '…', !!data.whatsapp);
        logChat(text, data.answer || '', data.whatsapp ? 1 : 0);
      })
      .catch(function () {
        var fallback = t('ai.unknown') || '…';
        botReply(fallback, true);
        logChat(text, fallback, 1);
      });
  };

  function logChat(q, a, escalated) {
    var logUrl = document.body.getAttribute('data-chat-log-url');
    if (!logUrl || !q) return;
    var fd = new FormData();
    var csrf = document.body.getAttribute('data-csrf-token');
    if (csrf) {
      fd.append('csrf_token', csrf);
    }
    fd.append('question', q);
    fd.append('answer', a || '');
    fd.append('escalated', escalated ? '1' : '0');
    fetch(logUrl, { method: 'POST', body: fd, credentials: 'same-origin' }).catch(function () {});
  }

  function bindUi() {
    syncQuickButtonLabels();

    if (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        window.toggleChat();
      });
    }

    var closeBtn = panel ? panel.querySelector('.spclose') : null;
    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        window.toggleChat();
      });
    }

    var sendBtn = panel ? panel.querySelector('.chatsend') : null;
    if (sendBtn) {
      sendBtn.addEventListener('click', function (e) {
        e.preventDefault();
        window.sendChat();
      });
    }

    if (input) {
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          window.sendChat();
        }
      });
    }

    quickButtons().forEach(function (qb) {
      qb.addEventListener('click', function (e) {
        e.preventDefault();
        var topic = resolveTopic(qb);
        if (topic) handleQuickTopic(topic, qb.getAttribute('data-q') || null);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindUi);
  } else {
    bindUi();
  }

  document.body.setAttribute('data-chat-ready', '1');
})();
