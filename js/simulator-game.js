(function () {
  'use strict';
  var months = window.SIM_MONTHS || [];
  if (!months.length) return;

  var state = { month: 0, money: 3500, mood: 80, save: 1500, maxMoney: 5000 };

  var elM = document.getElementById('sim-m');
  var elVm = document.getElementById('vm');
  var elVmo = document.getElementById('vmo');
  var elVs = document.getElementById('vs');
  var elBm = document.getElementById('bm');
  var elBmo = document.getElementById('bmo');
  var elBs = document.getElementById('bs');
  var elEvt = document.getElementById('evt');
  var elEvd = document.getElementById('evd');
  var elEem = document.getElementById('evem');
  var elChs = document.getElementById('simchs');
  var elRc = document.getElementById('simrc');
  var elEvbox = document.getElementById('evbox');
  var elNm = document.getElementById('nm-btn');

  function fmt(n) {
    return String(Math.max(0, Math.round(n))).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + '₸';
  }

  function updateBars() {
    if (elVm) elVm.textContent = fmt(state.money);
    if (elVmo) elVmo.textContent = Math.min(100, Math.max(0, state.mood)) + '%';
    if (elVs) elVs.textContent = fmt(state.save);
    if (elBm) elBm.style.width = Math.min(100, (state.money / state.maxMoney) * 100) + '%';
    if (elBmo) elBmo.style.width = state.mood + '%';
    if (elBs) elBs.style.width = Math.min(100, (state.save / 5000) * 100) + '%';
    if (elM) elM.textContent = String(state.month + 1);
  }

  function showEvent() {
    if (state.month >= months.length) {
      if (elEvt) elEvt.textContent = '🎉';
      if (elEvd) {
        elEvd.textContent =
          typeof window.finkidT === 'function' ? window.finkidT('sim.game_over') : 'Game complete!';
      }
      if (elChs) elChs.innerHTML = '';
      if (elRc) elRc.classList.remove('show');
      return;
    }
    var ev = months[state.month];
    if (elEem) elEem.textContent = ev.emoji;
    if (elEvt) elEvt.textContent = ev.title;
    if (elEvd) elEvd.textContent = ev.text;
    if (elRc) elRc.classList.remove('show');
    if (elEvbox) elEvbox.style.display = 'block';
    if (elChs) {
      elChs.innerHTML = '';
      ev.choices.forEach(function (c, i) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ch-btn';
        btn.textContent = c.label;
        btn.onclick = function () { pickChoice(c); };
        elChs.appendChild(btn);
      });
    }
  }

  function pickChoice(c) {
    state.money += c.money;
    state.mood = Math.min(100, Math.max(0, state.mood + c.mood));
    state.save += c.save;
    if (c.debt) state.money -= c.debt;
    updateBars();
    if (elEvbox) elEvbox.style.display = 'none';
    if (elRc) {
      elRc.classList.add('show');
      document.getElementById('rer').textContent = c.money >= 0 ? '😊' : '😟';
      document.getElementById('ret').textContent = c.tip;
      document.getElementById('simch').textContent = c.tip;
    }
  }

  if (elNm) {
    elNm.onclick = function () {
      state.month++;
      if (state.month >= months.length) {
        showEvent();
        return;
      }
      showEvent();
    };
  }

  updateBars();
  showEvent();
})();
