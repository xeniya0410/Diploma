<?php
declare(strict_types=1);
/** @var array $course @var array $payload @var int $userXp @var string $csrf */
$lessons = $payload['lessons'] ?? [];
?>
<div class="course-v4-wrap" id="course-v4-app">
  <div class="course-v4-topbar">
    <a href="<?= e(asset('courses.php')) ?>" class="btn-back" aria-label="<?= e_t('course.back') ?>">←</a>
    <div class="course-v4-title"><?= e($payload['title'] ?? $course['title']) ?></div>
    <span class="xp-pill">⭐ <span id="course-xp"><?= (int)$userXp ?></span></span>
  </div>
  <div class="coursebody">
    <div class="container">
      <p class="lesson-heading" id="lesson-heading"></p>
      <div class="ldots" id="ldots"></div>
      <div class="sdots" id="sdots"></div>
      <div class="fsc">
        <div class="fw">
          <div class="fsw"><?php require __DIR__ . '/finya_svg.php'; ?></div>
          <div class="fbub" id="cf-bub"><?= e($lessons[0]['bubbles'][0] ?? '') ?></div>
        </div>
      </div>
      <div id="course-content"></div>
    </div>
  </div>
</div>
<div class="course-v4-toast" id="course-toast" role="status"></div>
<script>
window.FINKID_COURSE = <?= json_encode([
    'courseId' => (int)$course['id'],
    'csrf' => $csrf,
    'xp' => (int)$userXp,
    'slug' => courseSlug($course),
    'lessons' => $lessons,
    'courseXp' => (int)($payload['xp'] ?? $course['xp_reward'] ?? 30),
    'lessonXp' => (int)($payload['lesson_xp'] ?? 5),
    'badge' => $payload['badge'] ?? $course['badge'] ?? null,
    'title' => $payload['title'] ?? $course['title'],
    'allLessonsDone' => (bool)($payload['allLessonsDone'] ?? false),
    'testPassed' => (bool)($payload['testPassed'] ?? false),
    'hasFinalTest' => (bool)($payload['hasFinalTest'] ?? false),
    'completedLessonIds' => $payload['completedLessonIds'] ?? [],
    'apiXp' => asset('api/award_xp.php'),
    'apiLesson' => asset('api/complete_v4_lesson.php'),
    'apiComplete' => asset('api/complete_v4_course.php'),
    'completeUrl' => asset('complete.php'),
    'coursesUrl' => asset('courses.php'),
    'finalTestUrl' => asset('course.php?id=' . (int)$course['id'] . '&view=final'),
    'i18n' => [
        'calcTitle' => __('course.calc_title'),
        'calcOk' => __('course.calc_ok'),
        'calcFail' => __('course.calc_fail'),
        'xpGained' => __('xp.gained'),
        'lessonOf' => __('course.lesson_of'),
        'btnBack' => __('course.back'),
        'btnNext' => __('course.btn_next'),
        'nextLesson' => __('course.next_lesson'),
        'toFinalTest' => __('course.to_final_test'),
        'finalGateIntro' => __('course.final_gate_intro'),
        'calcAnswerPh' => __('course.calc_answer_ph'),
        'matchTryAgain' => __('course.match_try_again'),
        'illusPlaceholder' => __('course.illus_placeholder'),
        'matchTitle' => __('course.match_title'),
        'trueFalseTitle' => __('course.truefalse_title'),
        'true' => __('course.true_label'),
        'false' => __('course.false_label'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
