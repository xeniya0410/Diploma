(function () {
  'use strict';
  var startBtn = document.getElementById('trial-start-btn');
  var quiz = document.getElementById('trial-quiz');
  var submitBtn = document.getElementById('trial-submit-btn');
  var result = document.getElementById('trial-result');
  var url = document.body.getAttribute('data-trial-url') || 'trial_submit.php';

  if (startBtn && quiz) {
    startBtn.onclick = function () {
      startBtn.style.display = 'none';
      quiz.style.display = 'block';
    };
  }

  if (submitBtn) {
    submitBtn.onclick = function () {
      var sel1 = document.querySelector('input[name="trial_ans1"]:checked');
      var sel2 = document.querySelectorAll('input[name="trial_ans2[]"]:checked');

      if (!sel1 || sel2.length === 0) {
        return;
      }

      var fd = new FormData();
      var csrf = document.body.getAttribute('data-csrf-token');
      if (csrf) {
        fd.append('csrf_token', csrf);
      }
      fd.append('answer1', sel1.value);
      sel2.forEach(function (cb) {
        fd.append('answer2[]', cb.value);
      });

      fetch(url, { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          result.hidden = false;
          result.className = 'trial-result ' + (data.passed ? 'flash-ok' : 'flash-err');
          result.textContent = (data.msg_key && window.finkidT)
            ? window.finkidT(data.msg_key)
            : (data.msg || '');
          if (data.passed) {
            setTimeout(function () { location.reload(); }, 1500);
          }
        });
    };
  }
})();