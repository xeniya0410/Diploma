(function () {
  'use strict';

  var list = document.getElementById('lessons-list');
  var addBtn = document.getElementById('add-lesson-btn');
  var tpl = document.getElementById('lesson-row-template');
  if (!list || !addBtn || !tpl) return;

  function updateRemoveButtons() {
    var rows = list.querySelectorAll('[data-lesson-row]');
    rows.forEach(function (row) {
      var btn = row.querySelector('[data-remove-lesson]');
      if (btn) btn.hidden = rows.length <= 1;
    });
    rows.forEach(function (row, i) {
      var titleLbl = row.querySelector('.lesson-editor-row .fg .fl');
      if (titleLbl && titleLbl.textContent.indexOf(' ') > -1) {
        var base = titleLbl.textContent.replace(/\s+\d+$/, '');
        titleLbl.textContent = base + ' ' + (i + 1);
      }
    });
  }

  function bindQuizToggle(row) {
    var chk = row.querySelector('.lesson-quiz-enable');
    var flag = row.querySelector('.lesson-quiz-flag');
    var body = row.querySelector('.lesson-quiz-body');
    if (!chk || !body) return;
    function sync() {
      body.hidden = !chk.checked;
      if (flag) flag.value = chk.checked ? '1' : '0';
    }
    chk.addEventListener('change', sync);
    sync();
  }

  function bindRemove(row) {
    if (!row) return;
    var btn = row.querySelector('[data-remove-lesson]');
    if (btn) {
      btn.addEventListener('click', function () {
        row.remove();
        updateRemoveButtons();
      });
    }
    bindQuizToggle(row);
  }

  addBtn.addEventListener('click', function () {
    var node = tpl.content.firstElementChild.cloneNode(true);
    node.querySelectorAll('input[type="text"], textarea').forEach(function (el) {
      el.value = '';
    });
    node.querySelectorAll('input[type="file"]').forEach(function (el) {
      el.value = '';
    });
    node.querySelectorAll('input[type="hidden"]').forEach(function (el) {
      if (el.name === 'lesson_id[]') el.value = '0';
      if (el.name === 'lesson_illustration_keep[]') el.value = '';
    });
    var preview = node.querySelector('.lesson-image-preview');
    if (preview) preview.remove();
    var chk = node.querySelector('.lesson-quiz-enable');
    var flag = node.querySelector('.lesson-quiz-flag');
    if (chk) chk.checked = false;
    if (flag) flag.value = '0';
    list.appendChild(node);
    bindRemove(node);
    updateRemoveButtons();
  });

  list.querySelectorAll('[data-lesson-row]').forEach(bindRemove);
  updateRemoveButtons();
})();
