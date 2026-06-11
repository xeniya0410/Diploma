<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/v4_courses.php';
requireAuth();

$userId   = (int)$_SESSION['user_id'];
$courseId = (int)($_GET['id'] ?? 0);
$lessonId = (int)($_GET['lesson'] ?? 0);
$view     = $_GET['view'] ?? '';

$courseSt = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
$courseSt->execute([$courseId]);
$course = $courseSt->fetch();
if (!$course) {
    redirect('courses.php');
}

$free = (int)$course['is_free'] === 1;
if (!$free && !isAuth()) {
    redirect('index.php?auth=login');
}

$lessonsSt = $pdo->prepare('SELECT * FROM lessons WHERE course_id = ? ORDER BY sort_order, id');
$lessonsSt->execute([$courseId]);
$lessons = $lessonsSt->fetchAll();

$isV4 = courseUsesV4Ui($course, $lessons);

if ($isV4) {
    require_once __DIR__ . '/includes/teacher_courses.php';
    repairLessonIllustrationPaths($pdo);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isV4 && $view === 'final') {
    verifyCsrf();
    $quizType = $_POST['quiz_type'] ?? '';
    if ($quizType === 'final') {
        $prog = courseProgress($pdo, $userId, $courseId);
        if (!$prog['all_lessons_done']) {
            flash('quiz_err', __('flash.lessons_first'));
            redirect('course.php?id=' . $courseId);
        }
        $questions = getFinalQuestions($pdo, $courseId);
        $result    = gradeAnswers($pdo, $questions, $userId, $_POST['answers'] ?? []);
        $pdo->prepare(
            'INSERT INTO user_progress (user_id, course_id, test_passed, test_score, updated_at)
             VALUES (?,?,?, ?, NOW())
             ON DUPLICATE KEY UPDATE test_passed=VALUES(test_passed), test_score=VALUES(test_score), updated_at=NOW()'
        )->execute([$userId, $courseId, $result['passed'] ? 1 : 0, $result['score']]);
        if ($result['passed']) {
            generateCertificateFile($pdo, $userId, $courseId);
            $v4 = getV4CourseBySlug(courseSlug($course));
            $_SESSION['v4_complete'] = [
                'course_id'   => $courseId,
                'title'       => courseLocalizedTitle($course),
                'xp_gained'   => 0,
                'badge'       => $v4['badge'] ?? $course['badge'] ?? null,
                'certificate' => true,
            ];
            flash('quiz_ok', __('flash.final_ok'));
            redirect('complete.php?course_id=' . $courseId);
        }
        flash('quiz_err', __f('flash.final_err', PASS_PERCENT));
        redirect('course.php?id=' . $courseId . '&view=final');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isV4) {
    verifyCsrf();
    $quizType = $_POST['quiz_type'] ?? '';
    $answers  = $_POST['answers'] ?? [];

    if ($quizType === 'lesson' && $lessonId > 0) {
        $questions = getLessonQuestions($pdo, $lessonId);
        $result    = gradeAnswers($pdo, $questions, $userId, $answers);
        $passed    = $result['passed'] ? 1 : 0;
        $pdo->prepare(
            'INSERT INTO lesson_quiz_results (user_id, lesson_id, score, passed, completed_at)
             VALUES (?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE score=VALUES(score), passed=VALUES(passed), completed_at=NOW()'
        )->execute([$userId, $lessonId, $result['score'], $passed]);
        if ($result['passed']) {
            $pdo->prepare(
                'INSERT IGNORE INTO lesson_completions (user_id, lesson_id, completed_at) VALUES (?,?,NOW())'
            )->execute([$userId, $lessonId]);
            flash('quiz_ok', __f('flash.quiz_ok', $result['score']));
        } else {
            flash('quiz_err', __f('flash.quiz_err', PASS_PERCENT, $result['score']));
        }
        redirect('course.php?id=' . $courseId . '&lesson=' . $lessonId);
    }

    if ($quizType === 'final') {
        $prog = courseProgress($pdo, $userId, $courseId);
        if (!$prog['all_lessons_done']) {
            flash('quiz_err', __('flash.lessons_first'));
            redirect('course.php?id=' . $courseId);
        }
        $questions = getFinalQuestions($pdo, $courseId);
        $result    = gradeAnswers($pdo, $questions, $userId, $answers);
        $pdo->prepare(
            'INSERT INTO user_progress (user_id, course_id, test_passed, test_score, updated_at)
             VALUES (?,?,?, ?, NOW())
             ON DUPLICATE KEY UPDATE test_passed=VALUES(test_passed), test_score=VALUES(test_score), updated_at=NOW()'
        )->execute([$userId, $courseId, $result['passed'] ? 1 : 0, $result['score']]);
        if ($result['passed']) {
            generateCertificateFile($pdo, $userId, $courseId);
            flash('quiz_ok', __('flash.final_ok'));
        } else {
            flash('quiz_err', __f('flash.final_err', PASS_PERCENT));
        }
        redirect('course.php?id=' . $courseId . '&view=final');
    }
}

if ($isV4) {
    $payload = resolveCourseV4Payload($pdo, $course, $lessons, $userId);
    if ($payload === []) {
        redirect('courses.php');
    }
    $userXp = getUserXp($pdo, $userId);
    syncSessionUserXp($pdo, $userId);
    $csrf   = csrfToken();
    $prog   = courseProgress($pdo, $userId, $courseId);

    if ($view === 'final') {
        if (!$prog['all_lessons_done']) {
            flash('quiz_err', __('flash.lessons_first'));
            redirect('course.php?id=' . $courseId);
        }
        $pageTitle = e_t('course.final_test') . ' — ' . e($payload['title'] ?? courseLocalizedTitle($course));
        $extraCss  = ['css/pages/course-v4.css', 'css/pages/test-guard.css'];
        $extraJs   = ['js/test-guard.js'];
        $bodyClass = 'app-body page-course-v4 page-course-v4-final';
        require __DIR__ . '/includes/header.php';
        require __DIR__ . '/includes/partials/course_v4_final.php';
        require __DIR__ . '/includes/footer.php';
        exit;
    }

    if ($prog['test_passed']) {
        flash('quiz_ok', __f('course.test_done', (string)$prog['test_score']));
    }

    $pageTitle = e($payload['title'] ?? courseLocalizedTitle($course)) . ' — ' . __('site.name');
    $extraCss  = ['css/pages/course-v4.css'];
    $extraJs   = ['js/course-v4.js'];
    $bodyClass = 'app-body page-course-v4';
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/partials/course_v4.php';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$flashOk  = flash('quiz_ok');
$flashErr = flash('quiz_err');

$currentLesson = null;
if ($lessonId > 0) {
    foreach ($lessons as $l) {
        if ((int)$l['id'] === $lessonId) {
            $currentLesson = $l;
            break;
        }
    }
}

$prog = courseProgress($pdo, $userId, $courseId);

$showQuiz = ($view === 'final' && !$prog['test_passed']) || (
    $currentLesson && count(getLessonQuestions($pdo, (int)$currentLesson['id'])) > 0
    && !isLessonQuizPassed($pdo, $userId, (int)$currentLesson['id'])
);

$pageTitle = e(courseLocalizedTitle($course)) . ' — ' . __('site.name');
$extraCss  = ['css/pages/course.css', 'css/pages/test-guard.css'];
$extraJs   = $showQuiz ? ['js/test-guard.js'] : [];
$bodyClass = $showQuiz ? 'app-body page-test-guard' : 'app-body';
require __DIR__ . '/includes/header.php';
?>

<div class="container course-page">
  <div class="course-header app-card">
    <span class="course-header__icon"><?= e($course['icon']) ?></span>
    <?php $courseSlugAttr = courseSlug($course); $platformI18n = hasV4Course($course); ?>
    <h1<?= $platformI18n ? ' data-course-slug="' . e($courseSlugAttr) . '" data-course-field="title"' : '' ?>><?= e(courseLocalizedTitle($course)) ?></h1>
    <p<?= $platformI18n ? ' data-course-slug="' . e($courseSlugAttr) . '" data-course-field="desc"' : '' ?>><?= e(courseLocalizedDescription($course)) ?></p>
    <p class="progress-text"<?= i18n_attrs('course.progress', [$prog['completed_lessons'], $prog['total_lessons']]) ?>><?= e(__f('course.progress', $prog['completed_lessons'], $prog['total_lessons'])) ?></p>
  </div>

  <?php if ($flashOk): ?><p class="flash flash-ok"><?= e($flashOk) ?></p><?php endif; ?>
  <?php if ($flashErr): ?><p class="flash flash-err"><?= e($flashErr) ?></p><?php endif; ?>

  <div class="course-layout">
    <aside class="lesson-sidebar app-card">
      <h2 data-i18n="course.lessons"><?= e_t('course.lessons') ?></h2>
      <ul class="lesson-list">
        <?php foreach ($lessons as $l):
          $done = isLessonDone($pdo, $userId, (int)$l['id']);
          $active = $currentLesson && (int)$l['id'] === (int)$currentLesson['id'];
        ?>
        <li class="<?= $active ? 'active' : '' ?> <?= $done ? 'done' : '' ?>">
          <a href="<?= e(asset('course.php?id=' . $courseId . '&lesson=' . (int)$l['id'])) ?>">
            <?= $done ? '✅' : '📖' ?> <?= e($l['title']) ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php if ($prog['all_lessons_done']): ?>
      <a href="<?= e(asset('course.php?id=' . $courseId . '&view=final')) ?>" class="btn-secondary"><span data-i18n-prefix="🏆 " data-i18n="course.final_test"><?= e_t('course.final_test') ?></span></a>
      <?php endif; ?>
    </aside>

    <div class="lesson-content">
      <?php if ($view === 'final'): ?>
        <div class="app-card">
          <h2 data-i18n="course.final_test"><?= e_t('course.final_test') ?></h2>
          <?php if ($prog['test_passed']): ?>
            <p class="badge-success"<?= i18n_attrs('profile.test_passed', [(string)$prog['test_score']]) ?>><?= e(__f('profile.test_passed', (string)$prog['test_score'])) ?></p>
          <?php else:
            $finalQs = getFinalQuestions($pdo, $courseId);
            $formAction = asset('course.php?id=' . $courseId . '&view=final');
            $submitLabel = __('course.submit_final');
            $submitLabelKey = 'course.submit_final';
            $quizType = 'final';
            require __DIR__ . '/includes/partials/quiz_form.php';
          endif; ?>
        </div>
      <?php elseif ($currentLesson): ?>
        <div class="app-card">
          <h2><?= e($currentLesson['title']) ?></h2>
          <div class="lesson-body"><?= $currentLesson['content_html'] ?? nl2br(e($currentLesson['content'] ?? '')) ?></div>
        </div>
        <?php
        $lessonQs = getLessonQuestions($pdo, (int)$currentLesson['id']);
        if (count($lessonQs) > 0):
          if (!isLessonQuizPassed($pdo, $userId, (int)$currentLesson['id'])):
        ?>
        <div class="app-card quiz-block">
          <h3 data-i18n="course.mini_test"><?= e_t('course.mini_test') ?></h3>
          <?php
            $formAction = asset('course.php?id=' . $courseId . '&lesson=' . (int)$currentLesson['id']);
            $submitLabel = __('course.check_answers');
            $submitLabelKey = 'course.check_answers';
            $quizType = 'lesson';
            $questions = $lessonQs;
            require __DIR__ . '/includes/partials/quiz_form.php';
          ?>
        </div>
        <?php else: ?>
        <p class="badge-success"><span data-i18n="course.mini_passed" data-i18n-suffix=" ✅"><?= e_t('course.mini_passed') ?></span></p>
        <?php endif; endif; ?>
      <?php else: ?>
        <div class="app-card">
          <p data-i18n="course.select_lesson"><?= e_t('course.select_lesson') ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
