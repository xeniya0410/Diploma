<?php
declare(strict_types=1);
$trialDone = !empty($_SESSION['trial_completed']);
?>
<section id="trial" class="trial-section landing-section">
  <div class="container">
    <h2 class="landing-about__title" data-i18n="trial.title"><?= e_t('trial.title') ?></h2>
    <p class="landing-about__lead" data-i18n="trial.lead"><?= e_t('trial.lead') ?></p>

    <div class="app-card trial-card" id="trial-card">
      <?php if ($trialDone): ?>
        <p class="flash flash-ok" data-i18n="trial.pass"><?= e_t('trial.pass') ?></p>
        <p data-i18n="trial.register_cta"><?= e_t('trial.register_cta') ?></p>
        <button type="button" class="hero-main-btn" onclick="openModal('register')"
          data-i18n="trial.register_btn"><?= e_t('trial.register_btn') ?></button>
      <?php else: ?>
        <div class="trial-illus-wrap">
          <img src="<?= e(trialIntroImageUrl()) ?>" alt="" class="trial-illus" loading="lazy"
            onerror="this.onerror=null;this.src='<?= e(asset('img/courses/lesson-default.svg')) ?>'">
        </div>
        <h3 data-i18n="trial.lesson_title"><?= e_t('trial.lesson_title') ?></h3>
        <p class="trial-text" data-i18n="trial.lesson_text"><?= e_t('trial.lesson_text') ?></p>

        <div id="trial-quiz" class="trial-quiz" style="display:none">
          <div class="trial-question" data-question="1">
            <p class="trial-q" data-i18n="trial.q1"><?= e_t('trial.q1') ?></p>
            <label class="trial-option">
              <input type="radio" name="trial_ans1" value="A">
              A) <span data-i18n="trial.q1a"><?= e_t('trial.q1a') ?></span>
            </label>
            <label class="trial-option">
              <input type="radio" name="trial_ans1" value="B">
              B) <span data-i18n="trial.q1b"><?= e_t('trial.q1b') ?></span>
            </label>
            <label class="trial-option">
              <input type="radio" name="trial_ans1" value="C">
              C) <span data-i18n="trial.q1c"><?= e_t('trial.q1c') ?></span>
            </label>
          </div>

          <div class="trial-question" data-question="2">
            <p class="trial-q" data-i18n="trial.q2"><?= e_t('trial.q2') ?></p>
            <p class="trial-q-hint" data-i18n="trial.q2_hint"><?= e_t('trial.q2_hint') ?></p>
            <label class="trial-option">
              <input type="checkbox" name="trial_ans2[]" value="A">
              A) <span data-i18n="trial.q2a"><?= e_t('trial.q2a') ?></span>
            </label>
            <label class="trial-option">
              <input type="checkbox" name="trial_ans2[]" value="B">
              B) <span data-i18n="trial.q2b"><?= e_t('trial.q2b') ?></span>
            </label>
            <label class="trial-option">
              <input type="checkbox" name="trial_ans2[]" value="C">
              C) <span data-i18n="trial.q2c"><?= e_t('trial.q2c') ?></span>
            </label>
          </div>

          <button type="button" class="btn-primary" id="trial-submit-btn"
            data-i18n="trial.submit"><?= e_t('trial.submit') ?></button>
          <p id="trial-result" class="trial-result" hidden></p>
        </div>

        <button type="button" class="btn-primary" id="trial-start-btn"
          data-i18n="trial.start"><?= e_t('trial.start') ?></button>
      <?php endif; ?>
    </div>
  </div>
</section>