<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/teacher_courses.php';
requireTeacher();

$user = currentUser();
$isAdminEditor = hasRole('admin');
$adminCoursesPath = 'admin.php?tab=courses';
$adminCoursesUrl = asset($adminCoursesPath);

if (!$isAdminEditor && ($user['role'] ?? '') === 'teacher' && ($user['teacher_status'] ?? 'none') !== 'approved') {
  redirect('teacher.php');
}

$courseId = (int) ($_GET['id'] ?? 0);
$isNew = $courseId < 1;
$course = $isNew ? null : fetchCourseRow($pdo, $courseId);
$canDelete = $course && canDeleteCourse($pdo, $user, $course);

if (!$isNew && (!$course || !canTeacherEditCourse($course, $user))) {
  flash($isAdminEditor ? 'admin_err' : 'tch_err', __('tch.course_no_access'));
  redirect($isAdminEditor ? $adminCoursesPath : 'teacher.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $action = $_POST['action'] ?? 'save';

  if ($action === 'delete' && $courseId > 0) {
    if (deleteTeacherCourse($pdo, $user, $courseId)) {
      flash($isAdminEditor ? 'admin_ok' : 'tch_ok', __('tch.course_deleted'));
    } else {
      flash($isAdminEditor ? 'admin_err' : 'tch_err', __('tch.course_no_access'));
    }
    redirect($isAdminEditor ? $adminCoursesPath : 'teacher.php');
  }

  $lessons = [];
  $titles = $_POST['lesson_title'] ?? [];
  $contents = $_POST['lesson_content'] ?? [];
  $ids = $_POST['lesson_id'] ?? [];
  $illusKeep = $_POST['lesson_illustration_keep'] ?? [];
  $quizEnabled = $_POST['lesson_quiz_enabled'] ?? [];
  $quizQuestions = $_POST['lesson_quiz_question'] ?? [];
  $quizA = $_POST['lesson_quiz_a'] ?? [];
  $quizB = $_POST['lesson_quiz_b'] ?? [];
  $quizC = $_POST['lesson_quiz_c'] ?? [];
  $quizCorrect = $_POST['lesson_quiz_correct'] ?? [];
  if (is_array($titles)) {
    foreach ($titles as $i => $t) {
      $enabled = is_array($quizEnabled) && (string) ($quizEnabled[$i] ?? '0') === '1';
      $lessons[] = [
        'id' => (int) ($ids[$i] ?? 0),
        'title' => $t,
        'content' => $contents[$i] ?? '',
        'illustration_keep' => is_array($illusKeep) ? (string) ($illusKeep[$i] ?? '') : '',
        'quiz' => [
          'enabled' => $enabled,
          'question' => is_array($quizQuestions) ? (string) ($quizQuestions[$i] ?? '') : '',
          'options' => [
            'a' => is_array($quizA) ? (string) ($quizA[$i] ?? '') : '',
            'b' => is_array($quizB) ? (string) ($quizB[$i] ?? '') : '',
            'c' => is_array($quizC) ? (string) ($quizC[$i] ?? '') : '',
          ],
          'correct' => is_array($quizCorrect) ? (string) ($quizCorrect[$i] ?? 'a') : 'a',
        ],
      ];
    }
  }

  $imageUploads = normalizeLessonImageUploads($_FILES['lesson_image'] ?? null);

  $result = saveTeacherCourse($pdo, $user, [
    'id' => $courseId,
    'title' => $_POST['title'] ?? '',
    'description' => $_POST['description'] ?? '',
    'icon' => $_POST['icon'] ?? '📚',
    'is_free' => $_POST['is_free'] ?? '',
    'xp_reward' => $_POST['xp_reward'] ?? 30,
    'badge' => $_POST['badge'] ?? '',
    'sort_order' => $_POST['sort_order'] ?? 0,
  ], $lessons, $imageUploads);

   if ($result['ok']) {
    flash($isAdminEditor ? 'admin_ok' : 'tch_ok', $isNew ? __('tch.course_created') : __('tch.course_saved'));
    redirect($isAdminEditor ? $adminCoursesPath : 'teacher.php?stats=' . (int) $result['id']);
  }

  $errKey = 'tch.err_save';
  switch ($result['error'] ?? '') {
    case 'title':
      $errKey = 'tch.err_title';
      break;
    case 'lessons':
      $errKey = 'tch.err_lessons';
      break;
    case 'access':
      $errKey = 'tch.course_no_access';
      break;
    case 'upload':
      $errKey = 'tch.err_upload';
      break;
    case 'upload_size':
      $errKey = 'tch.err_upload_size';
      break;
    case 'upload_type':
      $errKey = 'tch.err_upload_type';
      break;
  }
  flash($isAdminEditor ? 'admin_err' : 'tch_err', __($errKey));
  redirect($isNew ? 'teacher_course.php' : 'teacher_course.php?id=' . $courseId);
}  

$defaultLesson = [
    'id' => 0,
    'title' => '',
    'content' => '',
    'illustration' => '',
    'quiz' => lessonQuizEditorDefaults(),
];
$lessons = $isNew ? [$defaultLesson] : fetchCourseLessonsForEditor($pdo, $courseId);
if ($lessons === []) {
  $lessons = [$defaultLesson];
}

$pageTitle = ($isNew
    ? ($isAdminEditor ? __('admin.course_new') : __('tch.course_new'))
    : ($isAdminEditor ? __('admin.course_edit') : __('tch.course_edit'))) . ' — ' . __('site.name');
$extraCss = ['css/pages/panels.css', 'css/pages/teacher-panel.css'];
$extraJs = ['js/teacher-course.js'];
require __DIR__ . '/includes/header.php';

$tchOk = flash($isAdminEditor ? 'admin_ok' : 'tch_ok');
$tchErr = flash($isAdminEditor ? 'admin_err' : 'tch_err');
$backUrl = $isAdminEditor ? $adminCoursesUrl : asset('teacher.php');
$backLabel = $isAdminEditor ? __('admin.back_courses') : __('tch.back');
?>

<div class="container panel-page teacher-page<?= $isAdminEditor ? ' admin-course-editor' : '' ?>">
  <p class="teacher-back"><a href="<?= e($backUrl) ?>">← <?= e($backLabel) ?></a></p>
  <h1 class="page-title"><?= $isNew
    ? e($isAdminEditor ? __('admin.course_new') : __('tch.course_new'))
    : e($isAdminEditor ? __('admin.course_edit') : __('tch.course_edit')) ?></h1>
  <?php if (!$isNew && $course && isPlatformCourse($course)): ?>
    <p class="hint" data-i18n="admin.course_platform_hint"><?= e_t('admin.course_platform_hint') ?></p>
  <?php endif; ?>

  <?php if ($tchOk): ?>
    <p class="flash flash-ok"><?= e($tchOk) ?></p><?php endif; ?>
  <?php if ($tchErr): ?>
    <p class="flash flash-err"><?= e($tchErr) ?></p><?php endif; ?>

  <form class="app-card teacher-form" method="post" id="teacher-course-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="save">

    <div class="form-row-2">
      <div class="fg">
        <label class="fl"><?= e_t('tch.field_title') ?> *</label>
        <input class="fi" type="text" name="title" required maxlength="200" value="<?= e($course['title'] ?? '') ?>">
      </div>
      <div class="fg">
        <label class="fl"><?= e_t('tch.field_icon') ?></label>
        <input class="fi" type="text" name="icon" maxlength="16" placeholder="📚"
          value="<?= e($course['icon'] ?? '📚') ?>">
      </div>
    </div>

    <div class="fg">
      <label class="fl"><?= e_t('tch.field_description') ?></label>
      <textarea class="fi" name="description" rows="3"
        maxlength="2000"><?= e($course['description'] ?? '') ?></textarea>
    </div>

    <div class="form-row-2">
      <div class="fg">
        <label class="fl"><?= e_t('tch.field_xp') ?></label>
        <input class="fi" type="number" name="xp_reward" min="0" max="999"
          value="<?= (int) ($course['xp_reward'] ?? 30) ?>">
      </div>
      <div class="fg">
        <label class="fl"><?= e_t('tch.field_sort') ?></label>
        <input class="fi" type="number" name="sort_order" value="<?= (int) ($course['sort_order'] ?? 0) ?>">
      </div>
    </div>

    <div class="fg">
      <label class="fl"><?= e_t('tch.field_badge') ?></label>
      <input class="fi" type="text" name="badge" maxlength="120" value="<?= e($course['badge'] ?? '') ?>">
    </div>

    <label class="teacher-check">
      <input type="checkbox" name="is_free" value="1" <?= !empty($course['is_free']) ? 'checked' : '' ?>>
      <?= e_t('tch.field_free') ?>
    </label>

    <hr class="teacher-hr">

    <h2 class="teacher-subtitle"><?= e_t('tch.lessons_block') ?></h2>
    <p class="hint"><?= e_t('tch.lessons_hint') ?></p>

    <div id="lessons-list" class="lessons-editor">
      <?php foreach ($lessons as $idx => $lesson): ?>
        <?php
        $hideRemove = count($lessons) <= 1;
        require __DIR__ . '/includes/partials/lesson_editor_row.php';
        ?>
      <?php endforeach; ?>
    </div>

    <template id="lesson-row-template"><?php
      $lesson = $defaultLesson;
      $idx = 0;
      $hideRemove = false;
      ob_start();
      require __DIR__ . '/includes/partials/lesson_editor_row.php';
      echo ob_get_clean();
    ?></template>

    <button type="button" class="btn-secondary" id="add-lesson-btn">+ <?= e_t('tch.lesson_add') ?></button>

    <div class="teacher-form-actions">
      <button type="submit" class="btn-primary"><?= e_t('tch.save_course') ?></button>
    </div>
  </form>

  <?php if (!$isNew && $canDelete): ?>
    <form class="teacher-delete-form" method="post"
      onsubmit="return confirm('<?= e(addslashes(__('tch.confirm_delete'))) ?>')">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
      <input type="hidden" name="action" value="delete">
      <button type="submit" class="btn-secondary btn-danger"><?= e_t('tch.delete_course') ?></button>
    </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>