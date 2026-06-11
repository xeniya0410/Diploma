<?php
declare(strict_types=1);
/** @var array $course @var array $payload @var array $prog @var string $csrf */
$finalQs = getFinalQuestions($pdo, (int)$course['id']);
?>
<div class="course-v4-wrap course-v4-final-wrap">
  <div class="course-v4-topbar">
    <a href="<?= e(asset('course.php?id=' . (int)$course['id'])) ?>" class="btn-back" aria-label="<?= e_t('course.back') ?>">←</a>
    <div class="course-v4-title"><span data-i18n-prefix="🏆 " data-i18n="course.final_test"><?= e_t('course.final_test') ?></span></div>
  </div>
  <div class="coursebody">
    <div class="container">
      <?php if ($flashOk = flash('quiz_ok')): ?>
        <p class="flash flash-ok"><?= e($flashOk) ?></p>
      <?php endif; ?>
      <?php if ($flashErr = flash('quiz_err')): ?>
        <p class="flash flash-err"><?= e($flashErr) ?></p>
      <?php endif; ?>

      <?php if ((int)$prog['test_passed'] === 1): ?>
        <div class="app-card final-done-card">
          <h2 data-i18n="course.final_test"><?= e_t('course.final_test') ?></h2>
          <p class="badge-success"<?= i18n_attrs('profile.test_passed', [(string)$prog['test_score']]) ?>><?= e(__f('profile.test_passed', (string)$prog['test_score'])) ?></p>
          <p data-i18n="course.cert_in_profile"><?= e_t('course.cert_in_profile') ?></p>
          <a href="<?= e(asset('profile.php')) ?>" class="btn btn-primary" data-i18n="profile.certificates"><?= e_t('profile.certificates') ?></a>
        </div>
      <?php elseif (count($finalQs) === 0): ?>
        <p class="flash flash-err" data-i18n="course.final_no_questions"><?= e_t('course.final_no_questions') ?></p>
      <?php else: ?>
        <div class="app-card final-intro-card">
          <h2><?= e($payload['title'] ?? $course['title']) ?></h2>
          <p data-i18n="course.final_intro"><?= e_t('course.final_intro') ?></p>
        </div>
        <?php
        $formAction  = asset('course.php?id=' . (int)$course['id'] . '&view=final');
        $submitLabel = __('course.submit_final');
        $submitLabelKey = 'course.submit_final';
        $quizType    = 'final';
        $questions   = $finalQs;
        require __DIR__ . '/quiz_form.php';
        ?>
      <?php endif; ?>
    </div>
  </div>
</div>
