(function () {
  const cfg = window.FINKID_COURSE;
  if (!cfg || !cfg.lessons || !cfg.lessons.length) return;

  const completed = new Set((cfg.completedLessonIds || []).map(Number));
  const multiLesson = cfg.hasFinalTest || cfg.lessons.length > 1;

  let curLesson = 0;
  for (let i = 0; i < cfg.lessons.length; i++) {
    const lid = Number(cfg.lessons[i].lessonId);
    if (lid && !completed.has(lid)) {
      curLesson = i;
      break;
    }
    curLesson = i;
  }

  let curStep = 0;
  let qDone = false;
  let atFinalGate =
    !!(cfg.allLessonsDone && cfg.hasFinalTest && !cfg.testPassed);
  const calcAwarded = {};

  const ldotsEl = document.getElementById('ldots');
  const dotsEl = document.getElementById('sdots');
  const contentEl = document.getElementById('course-content');
  const headingEl = document.getElementById('lesson-heading');
  const bubEl = document.getElementById('cf-bub');
  const xpEl = document.getElementById('course-xp');
  const headerXp = document.getElementById('header-xp');
  const toastEl = document.getElementById('course-toast');
  const svgEl = document.getElementById('cf-svg');

  const I18N_PACK_KEYS = {
    lessonOf: 'course.lesson_of',
    illusPlaceholder: 'course.illus_placeholder',
    xpGained: 'xp.gained',
    finalGateIntro: 'course.final_gate_intro',
    toFinalTest: 'course.to_final_test',
    calcTitle: 'course.calc_title',
    calcAnswerPh: 'course.calc_answer_ph',
    calcOk: 'course.calc_ok',
    calcFail: 'course.calc_fail',
    matchTitle: 'course.match_title',
    matchTryAgain: 'course.match_try_again',
    trueFalseTitle: 'course.truefalse_title',
    true: 'course.true_label',
    false: 'course.false_label',
    btnBack: 'course.back',
    btnNext: 'course.btn_next',
  };

  function t(key, fallback) {
    if (cfg.i18n && cfg.i18n[key]) {
      return cfg.i18n[key];
    }
    const packKey = I18N_PACK_KEYS[key];
    if (packKey && typeof window.finkidT === 'function') {
      const val = window.finkidT(packKey);
      if (val && val !== packKey) {
        return val;
      }
    }
    return fallback || key;
  }

  function lesson() {
    return cfg.lessons[curLesson];
  }

  function steps() {
    return lesson().steps || [];
  }

  function syncXp(val) {
    cfg.xp = val;
    if (xpEl) xpEl.textContent = String(val);
    if (headerXp) headerXp.textContent = String(val);
  }

  function toast(msg) {
    if (!toastEl) return;
    toastEl.textContent = msg;
    toastEl.classList.add('show');
    setTimeout(() => toastEl.classList.remove('show'), 2200);
  }

  function finyaEmo(type) {
    if (!svgEl) return;
    svgEl.classList.remove('hpy', 'scr');
    if (type) {
      svgEl.classList.add(type);
      setTimeout(() => svgEl.classList.remove(type), 1500);
    }
  }

  function esc(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function postJson(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(Object.assign({ csrf_token: cfg.csrf }, body)),
      credentials: 'same-origin',
    }).then((r) => r.json());
  }

  function renderLessonDots() {
    if (!ldotsEl || cfg.lessons.length < 2) {
      if (ldotsEl) ldotsEl.innerHTML = '';
      return;
    }
    ldotsEl.innerHTML = '';
    const canJump = cfg.allLessonsDone || atFinalGate;
    cfg.lessons.forEach((les, i) => {
      const d = document.createElement('div');
      const done = completed.has(Number(les.lessonId));
      d.className = 'ld' + (done ? ' ld-done' : '') + (i === curLesson ? ' ld-active' : '');
      d.title = les.title || '';
      if (canJump) {
        d.setAttribute('role', 'button');
        d.tabIndex = 0;
        d.addEventListener('click', () => goToLesson(i));
        d.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            goToLesson(i);
          }
        });
      }
      ldotsEl.appendChild(d);
    });
  }

  function goToLesson(index) {
    if (index < 0 || index >= cfg.lessons.length) return;
    atFinalGate = false;
    curLesson = index;
    curStep = 0;
    qDone = false;
    render();
  }

  function renderStepDots() {
    if (!dotsEl) return;
    const st = steps();
    dotsEl.innerHTML = '';
    st.forEach((_, i) => {
      const d = document.createElement('div');
      d.className = 'sd' + (i < curStep ? ' ds' : i === curStep ? ' as' : '');
      dotsEl.appendChild(d);
    });
  }

  function updateHeading() {
    if (!headingEl) return;
    const total = cfg.lessons.length;
    const n = curLesson + 1;
    headingEl.textContent = t('lessonOf', 'Урок %d из %d')
      .replace('%d', String(n))
      .replace('%d', String(total)) + ' · ' + (lesson().title || '');
  }

  function setBubble() {
    const bubs = lesson().bubbles || [];
    if (bubEl && bubs[curStep]) bubEl.textContent = bubs[curStep];
    if (curStep > 0) finyaEmo('hpy');
  }

  function illustrationHtml(les) {
    const path = les.illustration || '';
    const url = les.illustrationUrl || '';
    if (!path) return '';
    const hint = t('illusPlaceholder', 'Добавьте материал урока через редактирование курса.');
    return (
      '<div class="lesson-illus" data-illus-path="' +
      esc(path) +
      '">' +
      (url
        ? '<img src="' +
          esc(url) +
          '" alt="" class="lesson-illus-img" onerror="this.classList.add(\'hidden\');this.nextElementSibling.classList.remove(\'hidden\')">' +
          '<div class="lesson-illus-ph hidden"><span class="lesson-illus-icon">🖼️</span><p>' +
          esc(hint) +
          '</p></div>'
        : '<div class="lesson-illus-ph"><span class="lesson-illus-icon">🖼️</span><p>' +
          esc(hint) +
          '</p></div>') +
      '</div>'
    );
  }

  function awardCalcXp(stepIndex) {
    const step = steps()[stepIndex];
    const key = cfg.courseId + '_' + curLesson + '_' + stepIndex;
    if (calcAwarded[key]) return;
    const amount = step.xp || cfg.lessonXp || 5;
    postJson(cfg.apiXp, { amount: amount, course_id: cfg.courseId, step: stepIndex })
      .then((data) => {
        if (data.ok) {
          calcAwarded[key] = true;
          syncXp(data.xp);
          toast(t('xpGained', '+%d XP!').replace('%d', String(amount)));
        }
      })
      .catch(() => {});
  }

  function goNextStep() {
    const st = steps();
    if (curStep < st.length - 1) {
      curStep++;
      qDone = false;
      render();
      return;
    }
    finishLesson();
  }

  function finishLesson() {
    const les = lesson();
    const lid = Number(les.lessonId);
    if (multiLesson && lid) {
      postJson(cfg.apiLesson, { course_id: cfg.courseId, lesson_id: lid })
        .then((data) => {
          if (data.ok) syncXp(data.xp);
          if (data.ok && data.xp_gained) {
            toast(t('xpGained', '+%d XP!').replace('%d', String(data.xp_gained)));
          }
          completed.add(lid);
          if (data.all_lessons_done && cfg.hasFinalTest) {
            showFinalGate();
            return;
          }
          if (curLesson < cfg.lessons.length - 1) {
            curLesson++;
            curStep = 0;
            qDone = false;
            render();
            return;
          }
          if (cfg.hasFinalTest) showFinalGate();
        })
        .catch(() => {
          if (curLesson < cfg.lessons.length - 1) {
            curLesson++;
            curStep = 0;
            render();
          }
        });
      return;
    }
    completeSingleCourse();
  }

  function completeSingleCourse() {
    const lid = Number(lesson().lessonId);
    postJson(cfg.apiComplete, { course_id: cfg.courseId, lesson_id: lid })
      .then((data) => {
        if (data.ok && data.redirect) window.location.href = data.redirect;
        else if (data.ok) {
          window.location.href =
            cfg.completeUrl + '?course_id=' + cfg.courseId + '&xp=' + (data.xp_gained || cfg.courseXp);
        }
      })
      .catch(() => {
        window.location.href = cfg.completeUrl + '?course_id=' + cfg.courseId;
      });
  }

  function showFinalGate() {
    if (cfg.testPassed) {
      window.location.href = cfg.finalTestUrl;
      return;
    }
    atFinalGate = true;
    contentEl.innerHTML =
      '<div class="tb final-gate">' +
      '<h3>🏆</h3>' +
      '<p>' + esc(t('finalGateIntro', 'Все уроки пройдены! Остался итоговый тест.')) + '</p>' +
      courseActionsHtml(null, {
        nextHref: cfg.finalTestUrl,
        nextLabel: t('toFinalTest', 'К итоговому тесту'),
        forceBack: cfg.lessons.length > 0,
      }) +
      '</div>';
    bindCourseActions(null);
    if (headingEl) headingEl.textContent = t('toFinalTest', 'Итоговый тест');
    renderLessonDots();
    if (dotsEl) dotsEl.innerHTML = '';
    if (bubEl) bubEl.textContent = '🎓';
  }

  function canGoBack() {
    return curLesson > 0 || curStep > 0;
  }

  function goBack() {
    if (atFinalGate) {
      atFinalGate = false;
      curLesson = Math.max(0, cfg.lessons.length - 1);
      const prevSteps = cfg.lessons[curLesson].steps || [];
      curStep = prevSteps.length > 0 ? prevSteps.length - 1 : 0;
      qDone = false;
      render();
      return;
    }
    if (curStep > 0) {
      curStep--;
      qDone = false;
      render();
      return;
    }
    if (curLesson > 0) {
      curLesson--;
      const prevSteps = cfg.lessons[curLesson].steps || [];
      curStep = prevSteps.length > 0 ? prevSteps.length - 1 : 0;
      qDone = false;
      render();
    }
  }

  function courseActionsHtml(step, opts) {
    opts = opts || {};
    const showBack = !!opts.forceBack || canGoBack();
    const nextDisabled = !!opts.nextDisabled;
    const hideNext = !!opts.hideNext;
    const nextLabel =
      opts.nextLabel || (step && step.btn) || t('btnNext', 'Дальше →');
    const nextHref = opts.nextHref || '';
    let html = '<div class="course-v4-actions">';
    if (showBack) {
      html +=
        '<button type="button" class="btn btn-secondary course-v4-btn-prev" data-action="prev">' +
        esc(t('btnBack', '← Назад')) +
        '</button>';
    } else {
      html += '<span class="course-v4-actions-spacer" aria-hidden="true"></span>';
    }
    if (hideNext) {
      html += '<span class="course-v4-actions-spacer" aria-hidden="true"></span>';
    } else if (nextHref) {
      html +=
        '<a href="' +
        esc(nextHref) +
        '" class="btn btn-primary course-v4-btn-next">' +
        esc(nextLabel) +
        '</a>';
    } else {
      html +=
        '<button type="button" class="btn btn-primary course-v4-btn-next" data-action="next"' +
        (nextDisabled ? ' disabled' : '') +
        '>' +
        esc(nextLabel) +
        '</button>';
    }
    html += '</div>';
    return html;
  }

  function bindCourseActions(step) {
    const prevBtn = contentEl.querySelector('[data-action="prev"]');
    if (prevBtn) {
      prevBtn.addEventListener('click', () => goBack());
    }
    bindNext(contentEl.querySelector('[data-action="next"]'), step);
  }

  function bindNext(btn, step) {
    if (!btn) return;
    btn.addEventListener('click', () => {
      if (step && step.btn_url) {
        window.location.href = cfg.coursesUrl || step.btn_url;
        return;
      }
      goNextStep();
    });
  }

  function renderContent() {
    const step = steps()[curStep];
    if (!step) return;

    if (step.type === 'content') {
      let html = illustrationHtml(lesson());
      if (step.video_label) {
        html +=
          '<div class="vph"><div class="vplay">▶️</div><div class="vlbl">' +
          esc(step.video_label) +
          '</div><div class="vdur">' +
          esc(step.video_dur || '') +
          '</div></div>';
      }
      html += '<div class="tb">' + step.html + '</div>';
      html += courseActionsHtml(step);
      contentEl.innerHTML = html;
      bindCourseActions(step);
      return;
    }

    if (step.type === 'calc') {
      const resId = 'lr-' + curLesson + '-' + curStep;
      const inpId = 'li-' + curLesson + '-' + curStep;
      let html = illustrationHtml(lesson());
      if (step.html) html += '<div class="tb">' + step.html + '</div>';
      html +=
        '<div class="lb3"><h4>🧮 ' +
        esc(t('calcTitle', 'Помоги Фине посчитать!')) +
        '</h4><p style="font-size:.83rem;color:var(--text2);margin-bottom:.65rem;font-weight:600;">' +
        step.question +
        '</p><div class="lrow"><input class="li2" type="number" id="' +
        inpId +
        '" placeholder="' + esc(t('calcAnswerPh', 'Ответ')) + '"><button type="button" class="bch" data-check-calc>✓</button></div><div class="lr" id="' +
        resId +
        '"></div></div>';
      html += courseActionsHtml(step);
      contentEl.innerHTML = html;
      const res = document.getElementById(resId);
      const inp = document.getElementById(inpId);
      contentEl.querySelector('[data-check-calc]').addEventListener('click', () => {
        const val = parseInt(inp.value, 10);
        if (val === step.answer) {
          res.className = 'lr ok';
          res.textContent =
            '✅ ' + t('calcOk', 'Правильно! %s₸').replace('%s', String(step.answer).replace(/\B(?=(\d{3})+(?!\d))/g, ' '));
          awardCalcXp(curStep);
          finyaEmo('hpy');
        } else {
          res.className = 'lr no';
          res.textContent = '❌ ' + t('calcFail', 'Не совсем. Попробуй ещё!');
          finyaEmo('scr');
        }
      });
      bindCourseActions(step);
      return;
    }

    if (step.type === 'quiz') {
      let html = '<p class="qq">' + step.question + '</p><div class="qopts">';
      step.options.forEach((opt, i) => {
        html +=
          '<button type="button" class="qo" data-opt="' +
          i +
          '" data-correct="' +
          (opt.correct ? '1' : '0') +
          '">' +
          esc(opt.text) +
          '</button>';
      });
      html += '</div><div class="qfb" id="qfb"></div>';
      html += courseActionsHtml(step, { hideNext: true });
      contentEl.innerHTML = html;
      const fb = document.getElementById('qfb');
      contentEl.querySelectorAll('.qo').forEach((btn) => {
        btn.addEventListener('click', () => {
          if (qDone) return;
          qDone = true;
          const correct = btn.getAttribute('data-correct') === '1';
          const opt = step.options[parseInt(btn.getAttribute('data-opt'), 10)];
          contentEl.querySelectorAll('.qo').forEach((b) => {
            b.disabled = true;
          });
          btn.classList.add(correct ? 'correct' : 'wrong');
          fb.textContent = opt.feedback;
          fb.className = 'qfb show ' + (correct ? 'ok' : 'no');
          if (correct) {
            finyaEmo('hpy');
            setTimeout(() => finishLesson(), 1800);
          } else {
            finyaEmo('scr');
            setTimeout(() => {
              qDone = false;
              contentEl.querySelectorAll('.qo').forEach((b) => {
                b.disabled = false;
                b.classList.remove('wrong');
              });
              fb.classList.remove('show');
            }, 2200);
          }
        });
      });
      bindCourseActions(step);
      return;
    }

    if (step.type === 'match') {
      const pairs = step.pairs || [];
      const rights = pairs.map((p) => p.right).sort(() => Math.random() - 0.5);
      let html =
        '<h4 class="match-title">' +
        esc(step.title || t('matchTitle', 'Соедини пары')) +
        '</h4><div class="match-grid"><div class="match-col match-left">';
      pairs.forEach((p) => {
        html += '<button type="button" class="match-item" data-id="' + esc(p.id) + '" data-side="left">' + esc(p.left) + '</button>';
      });
      html += '</div><div class="match-col match-right">';
      rights.forEach((r, i) => {
        html += '<button type="button" class="match-item" data-right-idx="' + i + '" data-side="right">' + esc(r) + '</button>';
      });
      html += '</div></div></div><div class="match-fb" id="match-fb"></div>';
      html += courseActionsHtml(step, { nextDisabled: true });
      contentEl.innerHTML = html;

      const rightById = {};
      pairs.forEach((p) => {
        rightById[p.id] = p.right;
      });
      let selectedLeft = null;
      const matched = new Set();
      const fb = document.getElementById('match-fb');
      const nextBtn = contentEl.querySelector('[data-action="next"]');

      contentEl.querySelectorAll('.match-item').forEach((btn) => {
        btn.addEventListener('click', () => {
          if (btn.classList.contains('matched')) return;
          const side = btn.getAttribute('data-side');
          if (side === 'left') {
            contentEl.querySelectorAll('.match-item[data-side="left"]').forEach((b) => b.classList.remove('selected'));
            btn.classList.add('selected');
            selectedLeft = btn.getAttribute('data-id');
            return;
          }
          if (!selectedLeft) return;
          const expected = rightById[selectedLeft];
          const chosen = btn.textContent;
          if (chosen === expected) {
            btn.classList.add('matched');
            const leftBtn = contentEl.querySelector('.match-item[data-id="' + selectedLeft + '"]');
            if (leftBtn) leftBtn.classList.add('matched');
            matched.add(selectedLeft);
            fb.textContent = '✅';
            fb.className = 'match-fb ok';
            selectedLeft = null;
            contentEl.querySelectorAll('.match-item[data-side="left"]').forEach((b) => b.classList.remove('selected'));
            if (matched.size >= pairs.length) {
              nextBtn.disabled = false;
              finyaEmo('hpy');
            }
          } else {
            fb.textContent = t('matchTryAgain', '❌ Попробуй ещё');
            fb.className = 'match-fb no';
            finyaEmo('scr');
          }
        });
      });
      bindCourseActions(step);
      return;
    }

    if (step.type === 'truefalse') {
      let html =
        '<h4 class="tf-title">' +
        esc(step.title || t('trueFalseTitle', 'Верно или нет?')) +
        '</h4><div class="tf-list">';
      (step.statements || []).forEach((st, i) => {
        html +=
          '<div class="tf-row" data-idx="' +
          i +
          '"><p>' +
          esc(st.text) +
          '</p><div class="tf-btns"><button type="button" class="tf-btn" data-val="1">' +
          esc(t('true', 'Верно')) +
          '</button><button type="button" class="tf-btn" data-val="0">' +
          esc(t('false', 'Неверно')) +
          '</button></div></div>';
      });
      html += '</div>';
      html += courseActionsHtml(step, { nextDisabled: true });
      contentEl.innerHTML = html;

      const answered = new Set();
      const nextBtn = contentEl.querySelector('[data-action="next"]');
      contentEl.querySelectorAll('.tf-row').forEach((row) => {
        const idx = parseInt(row.getAttribute('data-idx'), 10);
        const st = step.statements[idx];
        row.querySelectorAll('.tf-btn').forEach((btn) => {
          btn.addEventListener('click', () => {
            if (row.classList.contains('done')) return;
            const val = btn.getAttribute('data-val') === '1';
            const ok = val === st.correct;
            row.classList.add('done', ok ? 'tf-ok' : 'tf-bad');
            row.querySelectorAll('.tf-btn').forEach((b) => (b.disabled = true));
            if (ok) finyaEmo('hpy');
            else finyaEmo('scr');
            answered.add(idx);
            if (answered.size >= step.statements.length) nextBtn.disabled = false;
          });
        });
      });
      bindCourseActions(step);
    }
  }

  function render() {
    if (atFinalGate && cfg.allLessonsDone && cfg.hasFinalTest && !cfg.testPassed) {
      showFinalGate();
      return;
    }
    renderLessonDots();
    renderStepDots();
    updateHeading();
    setBubble();
    renderContent();
  }

  render();
})();
