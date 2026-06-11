<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/teacher_courses.php';
require_once __DIR__ . '/includes/v4_courses.php';
requireTeacher();

$user   = currentUser();
$status = $user['teacher_status'] ?? 'approved';

$students = $pdo->query(
    "SELECT u.id, u.name, u.email, u.age,
     (SELECT COUNT(*) FROM lesson_completions lc WHERE lc.user_id = u.id) AS lessons_done
     FROM users u WHERE u.role = 'student' ORDER BY u.name LIMIT 100"
)->fetchAll();

$courses      = [];
$statsCourse  = null;
$statsRows    = [];
$statsCourseId = (int)($_GET['stats'] ?? 0);

if ($status === 'approved') {
    $courses = listCoursesForTeacher($pdo, $user);
    if ($statsCourseId > 0) {
        $statsCourse = fetchCourseRow($pdo, $statsCourseId);
        if ($statsCourse && canTeacherViewCourseStats($statsCourse, $user)) {
            $statsRows = getCourseStudentProgressList($pdo, $statsCourseId);
        } else {
            $statsCourse = null;
        }
    }
}

$pageTitle = __('nav.teacher') . ' — ' . __('site.name');
$extraCss  = ['css/pages/panels.css', 'css/pages/teacher-panel.css'];
require __DIR__ . '/includes/header.php';

$tchOk  = flash('tch_ok');
$tchErr = flash('tch_err');
?>

<div class="container panel-page teacher-page">
  <h1 class="page-title">👩‍🏫 <?= e_t('nav.teacher') ?>: <?= e($user['name']) ?></h1>

  <?php if ($tchOk): ?><p class="flash flash-ok"><?= e($tchOk) ?></p><?php endif; ?>
  <?php if ($tchErr): ?><p class="flash flash-err"><?= e($tchErr) ?></p><?php endif; ?>

  <div class="pending-banner" id="tch-pending" style="<?= $status === 'pending' ? '' : 'display:none' ?>">
    <div class="pb-ico">⏳</div>
    <h3 data-i18n="tch.pending_title"><?= e_t('tch.pending_title') ?></h3>
    <p data-i18n="tch.pending_desc"><?= e_t('tch.pending_desc') ?></p>
  </div>

  <div class="approved-banner<?= $status === 'approved' ? ' show' : '' ?>" id="tch-approved">
    <div class="ab-ico">✅</div>
    <h3 data-i18n="tch.approved_title"><?= e_t('tch.approved_title') ?></h3>
    <p data-i18n="tch.approved_desc"><?= e_t('tch.approved_desc') ?></p>
  </div>

  <?php if ($status === 'approved'): ?>

  <section class="panel-section">
    <div class="teacher-section-head">
      <h2><?= e_t('tch.my_courses') ?></h2>
      <a href="<?= e(asset('teacher_course.php')) ?>" class="btn-primary">+ <?= e_t('tch.add_course') ?></a>
    </div>

    <div class="teacher-course-grid">
      <?php foreach ($courses as $c):
        $slug = courseSlug($c);
        $platformI18n = hasV4Course($c);
        $cardTitle = courseLocalizedTitle($c);
        $cardDesc = courseLocalizedDescription($c);
      ?>
      <article class="app-card teacher-course-card">
        <div class="teacher-course-card__head">
          <span class="teacher-course-card__icon"><?= e($c['icon']) ?></span>
          <div>
            <h3<?= $platformI18n ? ' data-course-slug="' . e($slug) . '" data-course-field="title"' : '' ?>><?= e($cardTitle) ?></h3>
            <?php if (empty($c['created_by'])): ?>
            <span class="teacher-tag" data-i18n="tch.platform_course"><?= e_t('tch.platform_course') ?></span>
            <?php endif; ?>
          </div>
        </div>
        <p class="teacher-course-card__desc"<?= $platformI18n ? ' data-course-slug="' . e($slug) . '" data-course-field="desc"' : '' ?>><?= e(mb_strimwidth($cardDesc, 0, 120, '…')) ?></p>

        <div class="teacher-stats-mini">
          <div class="teacher-stat-pill">
            <span class="teacher-stat-pill__val"><?= (int)$c['started'] ?></span>
            <span class="teacher-stat-pill__lbl" data-i18n="tch.stat_started"><?= e_t('tch.stat_started') ?></span>
          </div>
          <div class="teacher-stat-pill teacher-stat-pill--green">
            <span class="teacher-stat-pill__val"><?= (int)$c['completed'] ?></span>
            <span class="teacher-stat-pill__lbl" data-i18n="tch.stat_completed"><?= e_t('tch.stat_completed') ?></span>
          </div>
          <div class="teacher-stat-pill teacher-stat-pill--blue">
            <span class="teacher-stat-pill__val"><?= e($c['completion_rate']) ?>%</span>
            <span class="teacher-stat-pill__lbl" data-i18n="tch.stat_completion_rate"><?= e_t('tch.stat_completion_rate') ?></span>
          </div>
          <div class="teacher-stat-pill">
            <span class="teacher-stat-pill__val"><?= e($c['avg_progress']) ?>%</span>
            <span class="teacher-stat-pill__lbl" data-i18n="tch.stat_avg_progress"><?= e_t('tch.stat_avg_progress') ?></span>
          </div>
        </div>

        <p class="hint teacher-lessons-count">
          <span data-i18n="course.lessons"><?= e_t('course.lessons') ?></span>: <?= (int)$c['total_lessons'] ?>
        </p>

        <div class="teacher-course-card__actions">
          <a href="<?= e(asset('teacher.php?stats=' . (int)$c['id'])) ?>" class="btn-secondary btn-sm" data-i18n="tch.view_progress">
            <?= e_t('tch.view_progress') ?>
          </a>
          <?php if (!empty($c['can_edit'])): ?>
          <a href="<?= e(asset('teacher_course.php?id=' . (int)$c['id'])) ?>" class="btn-primary btn-sm" data-i18n="tch.edit_course">
            <?= e_t('tch.edit_course') ?>
          </a>
          <?php endif; ?>
          <a href="<?= e(asset('course.php?id=' . (int)$c['id'])) ?>" class="btn-secondary btn-sm" target="_blank" rel="noopener" data-i18n="course.open">
            <?= e_t('course.open') ?>
          </a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if ($statsCourse): ?>
  <?php $agg = getCourseAggregateStats($pdo, (int)$statsCourse['id']); ?>
  <section class="panel-section" id="course-stats-detail">
    <h2><?= e_t('tch.progress_title') ?>: <?= e(courseLocalizedTitle($statsCourse)) ?></h2>
    <div class="teacher-stats-bar app-card">
      <div class="teacher-stats-bar__item">
        <strong><?= (int)$agg['started'] ?></strong>
        <span><?= e_t('tch.stat_started') ?></span>
      </div>
      <div class="teacher-stats-bar__item">
        <strong><?= (int)$agg['completed'] ?></strong>
        <span><?= e_t('tch.stat_completed') ?></span>
      </div>
      <div class="teacher-stats-bar__item">
        <strong><?= e($agg['completion_rate']) ?>%</strong>
        <span><?= e_t('tch.stat_completion_rate') ?></span>
      </div>
      <div class="teacher-stats-bar__item">
        <strong><?= e($agg['avg_progress']) ?>%</strong>
        <span><?= e_t('tch.stat_avg_progress') ?></span>
      </div>
    </div>

    <?php if ($statsRows === []): ?>
    <p class="hint"><?= e_t('tch.no_students_yet') ?></p>
    <?php else: ?>
    <div class="app-card">
      <div class="table-wrap">
      <table class="data-table data-table--teacher-stats">
        <thead>
          <tr>
            <th><?= e_t('auth.name') ?></th>
            <th data-i18n="auth.email"><?= e_t('auth.email') ?></th>
            <th><?= e_t('tch.col_progress') ?></th>
            <th><?= e_t('tch.col_lessons') ?></th>
            <th><?= e_t('tch.col_final') ?></th>
            <th><?= e_t('tch.col_status') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($statsRows as $row): ?>
          <tr>
            <td><?= e($row['name']) ?></td>
            <td class="cell-email"><?= e($row['email']) ?></td>
            <td>
              <div class="teacher-progress-cell">
                <div class="teacher-progress-bar"><span style="width:<?= min(100, (float)$row['progress_percent']) ?>%"></span></div>
                <span><?= e($row['progress_percent']) ?>%</span>
              </div>
            </td>
            <td><?= (int)$row['completed_lessons'] ?> / <?= (int)$row['total_lessons'] ?></td>
            <td>
              <?php if ($row['test_passed']): ?>
                <?= e_t('tch.final_passed') ?> (<?= (int)$row['test_score'] ?>%)
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
            <td>
              <?php if ($row['is_completed']): ?>
                <span class="teacher-status teacher-status--ok"><?= e_t('tch.status_done') ?></span>
              <?php else: ?>
                <span class="teacher-status"><?= e_t('tch.status_progress') ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <section class="panel-section">
    <h2><?= e_t('tch.students_list') ?></h2>
    <div class="app-card">
      <div class="table-wrap">
      <table class="data-table data-table--teacher-students">
        <thead><tr><th><?= e_t('auth.name') ?></th><th data-i18n="auth.email"><?= e_t('auth.email') ?></th><th><?= e_t('auth.age') ?></th><th><?= e_t('course.lessons') ?></th></tr></thead>
        <tbody>
          <?php foreach ($students as $s): ?>
          <tr>
            <td><?= e($s['name']) ?></td>
            <td class="cell-email"><?= e($s['email']) ?></td>
            <td><?= (int)$s['age'] ?></td>
            <td><?= (int)$s['lessons_done'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </section>

  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
